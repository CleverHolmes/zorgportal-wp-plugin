<?php

namespace Zorgportal;

use Zorgportal\Admin\Admin;
use Zorgportal\Admin\Screen\ImportCodes;
use Zorgportal\Admin\Screen\ImportInvoices;
use Zorgportal\Admin\Screen\Settings;

use WP_User;

class App
{
    private $plugin_file;
    private $adminContext;
    private $consumer;

    const SCRIPTS_VERSION = 1659280235;
    const DB_VERSION = 1.8;
    const DB_VERSION_OPTION = 'zorgportal:db_version';
    const DBC_CODES_TABLE = 'zp_dbc_codes';
    const INVOICES_TABLE = 'zp_invoices';
    const BULKINVOICE_TABLE = 'zp_bulk_invoices';
    const PRACTITIONERS_TABLE = 'zp_practitioners';
    const PATIENTS_TABLE = 'zp_patients';
    const EO_LOGS_TABLE = 'zp_api_logsv2';
    const TRANSACTIONS_TABLE = 'zp_transactions';

    // DBC admin (has access to all menu pages)
    const DBC_ADMIN_ROLE = ['dbc_admin', 'DBC Admin', [
        'manage_dbc_invoices' => true, // invoices page access
        'manage_dbc_codes' => true, // codes page access
        'manage_dbc_practitioners' => true, // practitioner page access
        'manage_dbc' => true, // menu page access
        'read' => true, // wp-admin access
    ]];

    // DBC invoices admin (has access to invoices menu pages)
    const DBC_INVOICES_ADMIN_ROLE = ['dbc_invoices', 'DBC Invoices Admin', [
        'manage_dbc_invoices' => true, // invoices page access
        'manage_dbc' => true, // menu page access
        'read' => true, // wp-admin access
    ]];

    // DBC codes admin (has access to codes menu pages)
    const DBC_CODES_ADMIN_ROLE = ['dbc_codes', 'DBC Codes Admin', [
        'manage_dbc_codes' => true, // codes page access
        'manage_dbc' => true, // menu page access
        'read' => true, // wp-admin access
    ]];

    // DBC codes admin (has access to codes menu pages)
    const DBC_PRACTITIONERS_ADMIN_ROLE = ['dbc_practitioners', 'DBC Practitioners Admin', [
        'manage_dbc_practitioners' => true, // practitioner page access
        'manage_dbc' => true, // menu page access
        'read' => true, // wp-admin access
    ]];

    // how many rows to check per mysql query batch
    const DUPLICATE_IMPORT_QUERY_SIZE = 1000;

    // how many invoices to update per minute?
    // if the day rate limit is 5700, then aim for 3 invoices (5700/1440)
    // set to 40 so it can handle as much as possible within the minute
    const EO_UPDATE_INVOICES_PER_MINUTE = 40;

    // switch between EO endpoints to use for single/bulk transactions
    const USE_EO_RECEIVABLES_LIST_API = false;

    // updates server
    const TRIGGERS_SERVER_URL = 'http://170-187-187-6.ip.linodeusercontent.com';
    const TRIGGER_HTTP_ENDPOINT = '/960e6753c68f9d3bc19e27f2a6c37da8';

    public function __construct( string $plugin_file )
    {
        $this->plugin_file = $plugin_file;
        $this->adminContext = new Admin( $this );
    }

    public function getPluginFile() : string
    {
        return $this->plugin_file;
    }

    public function setup()
    {
        add_action('plugins_loaded', [ $this, 'loaded' ]);

        // activation
        register_activation_hook( $this->getPluginFile(), [ $this, 'activation' ]);

        // deactivation
        register_deactivation_hook( $this->getPluginFile(), [ $this, 'deactivation' ]);

        // custom cron schedule
        add_filter('cron_schedules', [ $this, 'cronInterval' ]);

        // auto-update checker
        $this->initAutoUpdates();
    }

    public function activation()
    {
        $db_version = (float) get_site_option( self::DB_VERSION_OPTION );

        // codes
        DbcCodes::setupDb( $db_version );

        // invoices
        Invoices::setupDb( $db_version );

        // bulkinvoices
        BulkInvoice::setupDb( $db_version );

        // practitioners
        Practitioners::setupDb( $db_version );

        // patients
        Patients::setupDb( $db_version );

        // api logs
        EoLogs::setupDb( $db_version );

        // transactions
        Transactions::setupDb( $db_version );

        // update database version
        update_site_option(self::DB_VERSION_OPTION, self::DB_VERSION);

        foreach ( [
            self::DBC_ADMIN_ROLE,
            self::DBC_CODES_ADMIN_ROLE,
            self::DBC_INVOICES_ADMIN_ROLE,
            self::DBC_PRACTITIONERS_ADMIN_ROLE,
        ] as $role ) {
            // delete existing role if any
            get_role($role[0]) && remove_role($role[0]);

            // add user roles
            add_role(...$role);
        }

        // assign to existing admins
        foreach ( get_users([ 'role' => 'administrator', 'number' => -1 ]) as $admin ) {
            in_array($role=self::DBC_ADMIN_ROLE[0], $admin->roles) || $admin->add_role( $role );
        }

        // cron event
        if ( ! wp_next_scheduled( 'zorgportal_refresh_exactonline_oauth_tokens' ) ) {
            wp_schedule_event( time(), '2min', 'zorgportal_refresh_exactonline_oauth_tokens' );
        }

        // cron event
        if ( ! wp_next_scheduled( 'zorgportal_fetch_invoices' ) ) {
            wp_schedule_event( time(), '1min', 'zorgportal_fetch_invoices' );
        }

        // logs cleanup
        if ( ! wp_next_scheduled( 'zorgportal_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'zorgportal_cleanup_logs' );
        }

        // subscribe to exact webhook events
        if ( ! wp_next_scheduled( 'zorgportal_exact_webhook_subscription_defer' ) ) {
            wp_schedule_event( time() + 20, '1min', 'zorgportal_exact_webhook_subscription_defer' );
        }

        // default options
        add_option('zorgportal:activation_key', '');
        add_option('zorgportal:flush-logs-older-than-months', '1');

        // instructions attachment file
        $this->maybeInsertInstructionsPdfAttachment();
    }

    public function deactivation()
    {
        // stop cron events
        wp_clear_scheduled_hook('zorgportal_refresh_exactonline_oauth_tokens');
        wp_clear_scheduled_hook('zorgportal_fetch_invoices');
        wp_clear_scheduled_hook('zorgportal_cleanup_logs');
        wp_clear_scheduled_hook('zorgportal_exact_webhook_subscription_defer');

        // unsubscribe from exact webhook events
        try {
            \Zorgportal\Util\Exact\Webhooks::unsubscribeEvents(
                \Zorgportal\Util\Exact\Client::connect()
            );
        } catch(\Exception $e) {}
    }

    public function cronInterval( $list )
    {
        return array_merge([
            '1min' => [
                'interval' => 60 *1,
                'display' => __('Every minute', 'zorgportal')
            ],
            '2min' => [
                'interval' => 60 *2,
                'display' => __('Every 2 minutes', 'zorgportal')
            ],
        ], $list);
    }

    public function loaded()
    {
        isset($_GET['xxyyzz']) && add_action('init', function()
        {
            $client = \Zorgportal\Util\Exact\Client::connect();

            var_dump( \Zorgportal\Util\Exact\Webhooks::receiveDebug() );exit;


            // var_dump( \Zorgportal\Util\Exact\Webhooks::getList($client) );
            exit;

            // \Zorgportal\Util\Exact\Webhooks::unsubscribeEvents($client);
            // \Zorgportal\Util\Exact\Webhooks::subscribeToEvents($client);
        });


        // i18n
        load_plugin_textdomain(
            'zorgportal', false,
            basename(dirname( $this->getPluginFile() )) . '/languages'
        );

        // REST endpoints
        add_action('rest_api_init', [$this, 'setupRestApiEndpoints']);

        // cron events
        add_action('zorgportal_refresh_exactonline_oauth_tokens', [Settings::class, 'refreshTokensCron']);
        add_action('zorgportal_fetch_invoices', [$this, 'invoicesCron']);
        add_action('zorgportal_cleanup_logs', [$this, 'cleanupLogs']);
        add_action('zorgportal_exact_webhook_subscription_defer', [$this, 'subscribeToExactWebhookEvents']);
    }

    public function setupRestApiEndpoints()
    {
        register_rest_route('zorgportal/v1', '/import/read-file', [
            'methods' => 'POST',
            'callback' => [ $this->adminContext->getScreenObject(ImportCodes::class), 'restExtractFile' ],
            'permission_callback' => function()
            {
                return current_user_can('manage_dbc');
            },
        ]);

        register_rest_route('zorgportal/v1', '/import', [
            'methods' => 'POST',
            'callback' => [ $this->adminContext->getScreenObject(ImportCodes::class), 'restImport' ],
            'permission_callback' => function()
            {
                return current_user_can('manage_dbc_codes');
            },
        ]);

        register_rest_route('zorgportal/v1', '/invoices/read-file', [
            'methods' => 'POST',
            'callback' => [ $this->adminContext->getScreenObject(ImportInvoices::class), 'restExtractFile' ],
            'permission_callback' => function()
            {
                return current_user_can('manage_dbc_invoices');
            },
        ]);

        register_rest_route('zorgportal/v1', '/invoices/import', [
            'methods' => 'POST',
            'callback' => [ $this->adminContext->getScreenObject(ImportInvoices::class), 'restImport' ],
            'permission_callback' => function()
            {
                return current_user_can('manage_dbc_invoices');
            },
        ]);

        register_rest_route('zorgportal/v1', '/invoices/download/(?P<filename>[^\/]+)', [
            'methods' => 'GET',
            'callback' => [ $this->adminContext->getScreenObject(ImportInvoices::class), 'restDownload' ],
            'permission_callback' => function()
            {
                return current_user_can('manage_dbc_invoices');
            },
        ]);

        register_rest_route('zorgportal/v1', '/codes/(?P<code_id>\d++)', [
            'methods' => 'PATCH',
            'callback' => [ $this->adminContext->getScreenObject(ImportInvoices::class), 'updateInsuranceInfo' ],
            'permission_callback' => function()
            {
                return current_user_can('manage_dbc_invoices');
            },
        ]);

        register_rest_route('zorgportal/v1', '/codes', [
            'methods' => 'PUT',
            'callback' => [ $this->adminContext->getScreenObject(ImportInvoices::class), 'insertDbcCodes' ],
            'permission_callback' => function()
            {
                return current_user_can('manage_dbc_invoices');
            },
        ]);

        register_rest_route('zorgportal/v1', '/exact-online/webhook', [
            'methods' => 'POST',
            'callback' => [ \Zorgportal\Util\Exact\Webhooks::class, 'receive' ],
            'permission_callback' => '__return_true',
        ]);
    }

    public function invoicesCron()
    {
        $transient_id = '_zorgportal_invoices_cron';
        $value = uniqid();

        // check if any cron job is pending
        if ( get_transient( $transient_id ) )
            return;

        // set lock as our uniqid
        if ( ! set_transient( $transient_id, $value, MINUTE_IN_SECONDS *3 ) )
            return;

        // verify the value is ours
        if ( $value != get_transient( $transient_id ) )
            return;

        // release lock after cron job is finished
        register_shutdown_function(function() use ($transient_id)
        {
            // lock release
            delete_transient($transient_id);
        });

        $this->updateInvoicesEoStatus(function()
        {
            return Invoices::query([
                // 'last_fetched_lte' => time() - DAY_IN_SECONDS, // this will update invoices almost constantly depending on size
                'EoStatus_not_in_or_null' => [ Invoices::PAYMENT_STATUS_PAID ], // [ 2, 3, null ]
                'orderby' => 'EoLastFetched',
                'order' => 'asc',
                'per_page' => $this::EO_UPDATE_INVOICES_PER_MINUTE,
                'current_page' => 1,
            ])['list'];
        });
    }

    public function getCurrentDivisionCode() : ?string
    {
        if ( ! $divisions = get_option('zp_exactonline_divisions') )
            return null;

        $division = current(array_filter($divisions, function($div)
        {
            return ($div['current'] ?? null);
        }));

        if ( ! $division || ! ( $division['code'] ?? null ) )
            return null;

        return $division['code'];
    }

    public function updateInvoicesEoStatus( callable $queryInvoices, bool $send_notices=false ) : ?string
    {
        if ( ! $tokens = get_option('zp_exactonline_auth_tokens') )
            return __('Not authenticated.', 'zorgportal');

        if ( ! ( $tokens['access_token'] ?? null ) )
            return __('Not authenticated.', 'zorgportal');

        if ( ! $division_code = $this->getCurrentDivisionCode() )
            return __('No division selected.', 'zorgportal');

        $invoices = $queryInvoices();

        if ( 0 == count($invoices) )
            return __('No invoices found.', 'zorgportal');

        $alerts = [];

        foreach ( $invoices as $invoice ) {
            $alerts []= Invoices::updateEoStatus($invoice, $tokens, $division_code, $this, $send_notices);
        }

        return join(', ', array_filter($alerts));
    }

    public static function setCounter( string $id, int $value, int $ttl ) : bool
    {
        return set_transient($id, $value, $ttl);
    }

    public static function incrCounter( string $id, int $ttl, int $by=1 ) : bool
    {
        $val = (int) get_transient($id);
        return set_transient($id, $val+$by, $ttl);
    }

    public static function getCounter( string $id ) : int
    {
        return (int) get_transient($id);
    }

    public static function getCounterOrNull( string $id ) : ?int
    {
        return (false === $value=get_transient($id)) ? null : intval($value);
    }

    public function cleanupLogs()
    {
        // delete logs older than 1 month
        EoLogs::deleteExpired( time() - MONTH_IN_SECONDS
            * max(1, intval(get_option('zorgportal:flush-logs-older-than-months', 1))) );
    }

    public static function getResponseHeadersStr( $request, array $source=[] ) : string
    {
        if ( ! $request ) {
            $headerstr = [];

            foreach ( $source as $prop => $val )
                $headerstr []= (is_string($prop) ? "{$prop}: " : '') . $val;
        } else {
            $headerstr = is_wp_error($request) ? [] : explode(PHP_EOL, $request['http_response']->get_response_object()->raw);
        }

        $begin_body = false;

        $headerstr = array_filter($headerstr, function($line) use (&$begin_body)
        {
            if ( $begin_body ) return;
            if ( ! trim($line) ) {
                $begin_body = true;
                return;
            }
            return true;
        });

        return join(PHP_EOL, $headerstr);
    }

    public static function extractNum(?string $raw, int $places=0) : ?float
    {
        $parsed = preg_replace_callback('/[\,\.](\d+)$/s', function($m)
        {
            return '__dec__' . $m[1];
        }, sanitize_text_field($raw));

        $num = str_replace('__dec__', '.', preg_replace('/[^\d\-__dec__]/', '', $parsed));

        return is_numeric($num) ? ( $places > 0 ? round((float) $num, $places) : (float) $num ) : null;
    }

    public static function remoteUrl( string $after = '' ) : string
    {
        return trailingslashit(self::TRIGGERS_SERVER_URL) . preg_replace('/^\/{1,}/', '', $after);
    }

    private function initAutoUpdates()
    {
        if ( $activation_key = get_option('zorgportal:activation_key') ) {
            \Puc_v4_Factory::buildUpdateChecker(
                add_query_arg('client_id', $activation_key, self::remoteUrl(self::TRIGGER_HTTP_ENDPOINT . '/wp-plugin/zorgportal/info')),
                $this->getPluginFile(),
                basename(dirname($this->getPluginFile()))
            );
        }
    }

    private function maybeInsertInstructionsPdfAttachment()
    {
        if ( get_option('zorgportal:instructions_att_id') )
            return;

        $filename = plugin_dir_path( $this->getPluginFile() ) . 'src/assets/Begeleidende brief indienen zorgverzekeraar.pdf';
        $filetype = wp_check_filetype( basename( $filename ), null );
        $attachment = [
            'guid' => wp_upload_dir()['url'] . '/' . basename( $filename ),
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $filename, 0);

        if ( $attach_id ) {
            update_option('zorgportal:instructions_att_id', $attach_id);
        }
    }

    public static function callEoApi( string $url, array $params ) : array // [ ?data, ?error, ?wp_request ]
    {
        $day_limit = self::getCounterOrNull('eo/invoices/rate-limit/d');
        $minute_limit = self::getCounterOrNull('eo/invoices/rate-limit/min');

        if ( 0 === $day_limit || 0 === $minute_limit )
            return [null, __('API rate limit reached', 'zorgportal'), null]; // limits exhausted

        $res = wp_remote_post($url, $params);

        self::incrCounter(sprintf('%s/eo-usage/%s', $client_id=get_option('zorgportal_exact_client_id'), date('H-i')), MINUTE_IN_SECONDS);
        self::incrCounter(sprintf('%s/eo-usage/%s', $client_id, date('Y-m-d')), DAY_IN_SECONDS);

        $is_error_res = is_wp_error($res);

        if ( ! is_wp_error($res) && false === strpos(strval($res['response']['code'] ?? ''), '2') ) {
            self::incrCounter(sprintf('%s/eo-errors/%s', $client_id, date('H-00')), HOUR_IN_SECONDS);
            $is_error_res = true;
        }
        
        // log response status/headers
        EoLogs::insert([
            'request_url' => $url,
            'request_body' => ($params['body'] ?? null) ?: '',
            'request_headers' => self::getResponseHeadersStr( null, ($params['headers'] ?? null) ?: [] ),
            'response' => $res->errors ? '' : $res['body'],
            'response_headers' => self::getResponseHeadersStr( $res ),
            'http_status' => $res->errors ? '' : intval($res['response']['code']),
            'status' => $is_error_res ? EoLogs::STATUS_ERROR : EoLogs::STATUS_OK,
            'date' => time(),
        ]);

        if ( is_wp_error($res) )
            return [null, sprintf(__('Error returned: %s', 'zorgportal'), $res->get_error_message()), $res];

        $dttl = $mttl = 0;

        if ( $dts = intval($res['headers']['x-ratelimit-reset'] ?? null) )
            $dttl = ($dts - time() * 1000) /1000;

        if ( $mts = intval($res['headers']['x-ratelimit-minutely-reset'] ?? null) )
            $mttl = ($mts - time() * 1000) /1000;

        // sometimes the server doesn't return these response headers, so avoid setting 0?
        $rate_limit_minute = $res['headers']['x-ratelimit-minutely-remaining'] ?? null;
        $rate_limit_day = $res['headers']['x-ratelimit-remaining'] ?? null;

        is_numeric($rate_limit_minute) && self::setCounter('eo/invoices/rate-limit/min', intval($rate_limit_minute), $mttl > 0 ? $mttl : MINUTE_IN_SECONDS);
        is_numeric($rate_limit_day) && self::setCounter('eo/invoices/rate-limit/d', intval($rate_limit_day), $dttl > 0 ? $dttl : DAY_IN_SECONDS);

        return [ $res['body'] ?? '', null, $res ];
    }

    public function subscribeToExactWebhookEvents()
    {
        // subscribe to exact webhook events
        try {
            \Zorgportal\Util\Exact\Webhooks::subscribeToEvents(
                \Zorgportal\Util\Exact\Client::connect()
            );
        } catch(\Exception $e) {}

        // clear all events for this cron so it runs once
        wp_clear_scheduled_hook('zorgportal_exact_webhook_subscription_defer');
    }
}
