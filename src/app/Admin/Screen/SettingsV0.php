<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\App;
use Zorgportal\EoLogs;

class SettingsV0 extends Screen
{
    public function init()
    {
        if ( $code = $_GET['code'] ?? null ) {
            // list($res, $raw) = self::getAuthTokens($code);

            add_action('admin_footer', function()
            {
                $admin_url = admin_url('admin.php?page=zorgportal-settings');
                echo "<script>history.replaceState({}, {}, '{$admin_url}')</script>\n";
            });

            // if ( ($res['access_token'] ?? '') && ($res['refresh_token'] ?? '') ) {
            //     $res['_expires'] = time() + intval($res['expires_in'] ?? 0);
            //     update_option('zp_exactonline_auth_tokens', $res);
            //     // EoLogs::push(__('Authenticated', 'zorgportal'));
            //     return $this->success( sprintf(__('ExactOnline connected successfully!', 'zorgportal'), $raw) );
            // } else {
            //     // EoLogs::push(__('Authentication failed.', 'zorgportal'));
            //     return $this->error( sprintf(__('Invalid authorization response from exactonline: %s', 'zorgportal'), $raw) );
            // }

            $this->auth();
        }

        add_action('admin_head', function()
        {
            echo '<script>(function()
            {
                setInterval(function()
                {
                    var timer = document.getElementById("timer-countdown")
                    timer && ( timer.textContent = Number(timer.textContent) -1 )
                }, 1000)
            })()</script>', PHP_EOL;
        });
    }

    function auth()
    {
        $tokens = get_option('zp_exactonline_auth_tokens') ?: [];

        // If authorization code is returned from Exact, save this to use for token request
        if (isset($_GET['code']) && is_null($tokens['authorizationcode'] ?? null)) {
            $tokens['authorizationcode'] = $_GET['code'];
            update_option('zp_exactonline_auth_tokens', $tokens);
        }

        // If we do not have a authorization code, authorize first to setup tokens
        if (($tokens['authorizationcode'] ?? null) === null) {
            $this->authorize();
        }

        // Create the Exact client
        return ($connection = $this->connect());
    }

    public function render()
    {
        if ( ! isset( $_POST['oauth_redirect'] ) ) {
            $_POST['client_id'] = get_option('zorgportal_exact_client_id') ?: '';
            $_POST['client_secret'] = get_option('zorgportal_exact_client_secret') ?: '';
            $_POST['webhook_secret'] = get_option('zorgportal_exact_webhook_secret') ?: '';
        }

        return $this->renderTemplate('settings.php', [
            'nonce' => wp_create_nonce('zorgportal'),
            'tokens' => $tokens=get_option('zp_exactonline_auth_tokens'),
            'division' => get_option('zp_exactonline_current_division'),
            'divisions' => get_option('zp_exactonline_divisions'),
            'connected' => call_user_func(function() use ($tokens)
            {
                if ( ! ($tokens['access_token'] ?? '') )
                    return false;

                if ( ! ($tokens['_expires'] ?? '') )
                    return false;

                return $tokens['_expires'] > time();
            }),
            'api_usage_minute' => App::getCounter(sprintf('%s/eo-usage/%s', $client_id=get_option('zorgportal_exact_client_id'), date('H-i'))),
            'api_usage_day' => App::getCounter(sprintf('%s/eo-usage/%s', $client_id, date('Y-m-d'))),
            'token_usage_day' => App::getCounter(sprintf('%s/eo-usage/tokens/%s', $client_id, date('Y-m-d'))),
            'api_errors_hour' => App::getCounter(sprintf('%s/eo-errors/%s', $client_id, date('H-00'))),
        ]);
    }

    public function update()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        if ( isset($_POST['oauth_redirect']) )
            return $this->oauthRedirect();

        if ( isset($_POST['oauth_refresh']) )
            return $this->oauthRefresh();

        if ( isset($_POST['oauth_disconnect']) )
            return $this->oauthDisconnect();

        if ( isset($_POST['ex_get_devision']) )
            return $this->getCurrentDivision();

        if ( isset($_POST['ex_get_devisions']) )
            return $this->getAllDivisions();

        if ( isset($_POST['set_current_division']) )
            return $this->setCurrentDivision();
        
        return $this->success( __('Changes saved successfully.', 'zorgportal') );
    }

    private function oauthRedirect()
    {
        /*if ( ! $client_id = sanitize_text_field($_POST['client_id'] ?? '') )
            return $this->error( __('Please enter a client id.', 'zorgportal') );

        if ( ! $client_secret = sanitize_text_field($_POST['client_secret'] ?? '') )
            return $this->error( __('Please enter a client secret.', 'zorgportal') );

        $webhook_secret = sanitize_text_field($_POST['webhook_secret'] ?? '');

        update_option('zorgportal_exact_client_id', $client_id);
        update_option('zorgportal_exact_client_secret', $client_secret);
        update_option('zorgportal_exact_webhook_secret', $webhook_secret);

        wp_redirect(add_query_arg([
            'client_id' => $client_id,
            'redirect_uri' => urlencode( admin_url('admin.php?page=zorgportal-settings') ),
            'response_type' => 'code',
            'force_login' => '0 ',
        ], 'https://start.exactonline.nl/api/oauth2/auth'));
        exit;*/

        try {
            $this->auth();
            return $this->success( __('Access token refreshed successfully.', 'zorgportal') );
        } catch ( \Exception $e ) {
            return $this->error( __('Error occurred, access token could not be refreshed.', 'zorgportal') );
        }
    }

    public static function getAuthTokens( string $exchange, string $exchange_type='code', string $grant_type='authorization_code' ) : ?array
    {
        if ( ! $client_id = get_option('zorgportal_exact_client_id') )
            return null;

        if ( ! $client_secret = get_option('zorgportal_exact_client_secret') )
            return null;

        $res = wp_remote_post($url='https://start.exactonline.nl/api/oauth2/token', $params=[
            'method' => 'POST',
            'headers' => [
                'content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => sprintf('%s=%s&redirect_uri=%s&grant_type=%s&client_id=%s&client_secret=%s',
                $exchange_type, $exchange,
                urlencode( admin_url('admin.php?page=zorgportal-settings') ),
                $grant_type,
                $client_id,
                $client_secret
            ),
            'timeout' => 20,
        ]);

        App::incrCounter(sprintf('%s/eo-usage/%s', $client_id, date('H-i')), MINUTE_IN_SECONDS);
        App::incrCounter(sprintf('%s/eo-usage/tokens/%s', $client_id, date('Y-m-d')), DAY_IN_SECONDS);
        App::incrCounter(sprintf('%s/eo-usage/%s', $client_id, date('Y-m-d')), DAY_IN_SECONDS);

        if ( ! is_wp_error($res) && false === strpos(strval($res['response']['code'] ?? ''), '2') ) {
            $is_error_res = true;
            App::incrCounter(sprintf('%s/eo-errors/%s', $client_id, date('H-00')), HOUR_IN_SECONDS);
        }

        // log response status/headers
        EoLogs::insert([
            'request_url' => $url,
            'request_body' => $params['body'],
            'request_headers' => App::getResponseHeadersStr( null, $params['headers']),
            'response' => ($res['body'] ?? null) ?: '',
            'response_headers' => App::getResponseHeadersStr( $res ),
            'http_status' => intval($res['response']['code'] ?? ''),
            'status' => isset($is_error_res) ? EoLogs::STATUS_ERROR : EoLogs::STATUS_OK,
            'date' => time(),
        ]);

        return [json_decode($raw=$res['body'] ?? '', 1), $raw];
    }

    public static function refreshTokensCron() : bool
    {
        /*if ( ! $tokens = get_option('zp_exactonline_auth_tokens') )
            return false;

        if ( isset($tokens['_expires']) && ($tokens['_expires'] - time()) > 30 )
            return false; // not expired

        list($res, $raw) = self::getAuthTokens($tokens['refresh_token'], 'refresh_token', 'refresh_token');

        if ( ($res['access_token'] ?? '') && ($res['refresh_token'] ?? '') ) {
            // EoLogs::push(__('Got new access token', 'zorgportal'));
            $res['_expires'] = time() + intval($res['expires_in'] ?? 0);
            update_option('zp_exactonline_auth_tokens', $res);
            return true;
        } else {
            // EoLogs::push(sprintf(__('Refresh token task failed: %s', 'zorgportal'), $raw));
            delete_option('zp_exactonline_auth_tokens');
            return false;
        }*/

        $this->auth();
    }

    private function oauthRefresh()
    {
        /*if ( self::refreshTokensCron() )
            return $this->success( __('Access token refreshed successfully.', 'zorgportal') );

        return $this->error( __('Error occurred, access token could not be refreshed.', 'zorgportal') );*/

        try {
            $this->auth();
            return $this->success( __('Access token refreshed successfully.', 'zorgportal') );
        } catch ( \Exception $e ) {
            return $this->error( __('Error occurred, access token could not be refreshed.', 'zorgportal') );
        }
    }

    private function getCurrentDivision()
    {
        $tokens = get_option('zp_exactonline_auth_tokens');

        if ( ! ($tokens['access_token'] ?? '') )
            return $this->error( __('Error occurred: bad request.', 'zorgportal') );

        list( $res, $error, $res_obj ) = App::callEoApi('https://start.exactonline.nl/api/v1/current/Me?$select=CurrentDivision', [
            'method' => 'GET',
            'headers' => [
                'Authorization' => "bearer {$tokens['access_token']}",
            ],
            'timeout' => 20,
        ]);

        if ( $error )
            return $this->error( $error );

        if ( false === strpos(strval($res_obj['response']['code'] ?? ''), '2') )
            return $this->error( __('Error occurred: server responded with a non-2xx status.', 'zorgportal') );

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $doc->loadHTML($res ?: '<xml></xml>');
        $xpath = new \DOMXPath($doc);

        $division = trim($xpath->query('//feed/entry/content')[0]->textContent ?? '');

        if ( ! $division || ! is_numeric($division) )
            return $this->error( __('Error occurred: division id could not be extracted.', 'zorgportal') );

        update_option('zp_exactonline_current_division', $division);

        return $this->success( __('Current division updated successfully.', 'zorgportal') );
    }

    private function getAllDivisions()
    {
        if ( ! $division = get_option('zp_exactonline_current_division') )
            return $this->error( __('Error occurred: bad request.', 'zorgportal') );

        $tokens = get_option('zp_exactonline_auth_tokens');

        if ( ! ($tokens['access_token'] ?? '') )
            return $this->error( __('Error occurred: bad request.', 'zorgportal') );

        list( $res, $error, $res_obj ) = App::callEoApi("https://start.exactonline.nl/api/v1/{$division}/system/AllDivisions", [
            'method' => 'GET',
            'headers' => [
                'Authorization' => "bearer {$tokens['access_token']}",
            ],
            'timeout' => 20,
        ]);

        if ( $error )
            return $this->error( $error );

        if ( false === strpos(strval($res_obj['response']['code'] ?? ''), '2') )
            return $this->error( __('Error occurred: server responded with a non-2xx status.', 'zorgportal') );

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $doc->loadHTML($res ?: '<xml></xml>');
        $xpath = new \DOMXPath($doc);

        $divisions = [];

        foreach ( iterator_to_array($xpath->query('//feed/entry/content')) as $content ) {
            $props = $xpath->query('./properties', $content);

            if ( $props && $props->length ) {
                $description = trim($xpath->query('./description', $props[0])[0]->textContent ?? '');
                $code = trim($xpath->query('./code', $props[0])[0]->textContent ?? '');
                $customer = trim($xpath->query('./customername', $props[0])[0]->textContent ?? '');

                if ( $code && is_numeric($code) && $description ) {
                    $divisions []= compact('code', 'description', 'customer');
                }
            }
        }

        if ( ! $divisions )
            return $this->error( __('Error: no divisions found.', 'zorgportal') );

        update_option('zp_exactonline_divisions', $divisions);

        return $this->success( __('Project divisions updated successfully.', 'zorgportal') );
    }

    private function setCurrentDivision()
    {
        if ( ! $divisions = get_option('zp_exactonline_divisions') )
            return $this->error( __('Error occurred, no divisions loaded.', 'zorgportal') );

        foreach ( $divisions as $i => $div ) {
            unset($divisions[$i]['current']);
        }

        foreach ( $divisions as $i => $div ) {
            if ( $div['code'] == ($_POST['current_division'] ?? '') ) {
                $divisions[$i]['current'] = true;
                break;
            }
        }

        update_option('zp_exactonline_divisions', $divisions);

        return $this->success( __('Current division updated successfully.', 'zorgportal') );
    }

    private function oauthDisconnect()
    {
        if ( delete_option('zp_exactonline_auth_tokens') ) {
            // EoLogs::push(__('Disconnected', 'zorgportal'));
        }

        return $this->success( __('OAuth disconnected successfully.', 'zorgportal') );
    }

    /**
     * Function to authorize with Exact, this redirects to Exact login promt and retrieves authorization code
     * to set up requests for oAuth tokens.
     */
    function authorize()
    {
        $connection = new \Picqer\Financials\Exact\Connection();
        $connection->setRedirectUrl(admin_url('admin.php?page=zorgportal-settings'));
        $connection->setExactClientId(get_option('zorgportal_exact_client_id'));
        $connection->setExactClientSecret(get_option('zorgportal_exact_client_secret'));
        $connection->redirectForAuthorization();
    }

    /**
     * Callback function that sets values that expire and are refreshed by Connection.
     *
     * @param \Picqer\Financials\Exact\Connection $connection
     */
    function tokenUpdateCallback(\Picqer\Financials\Exact\Connection $connection)
    {
        update_option('zp_exactonline_auth_tokens', [
            'access_token' => $connection->getAccessToken(),
            'refresh_token' => $connection->getRefreshToken(),
            'expires_in' => $connection->getTokenExpires(),
            '_expires' => time() + ($connection->getTokenExpires() ?: 0),
        ]);
    }

    /**
     * Function to connect to Exact, this creates the client and automatically retrieves oAuth tokens if needed.
     *
     * @throws Exception
     *
     * @return \Picqer\Financials\Exact\Connection
     */
    function connect()
    {
        $connection = new \Picqer\Financials\Exact\Connection();
        $connection->setRedirectUrl(admin_url('admin.php?page=zorgportal-settings'));
        $connection->setExactClientId(get_option('zorgportal_exact_client_id'));
        $connection->setExactClientSecret(get_option('zorgportal_exact_client_secret'));

        $tokens = get_option('zp_exactonline_auth_tokens') ?: [];

        // Retrieves authorizationcode from database
        if ($value=($tokens['authorizationcode'] ?? '')) {
            $connection->setAuthorizationCode($value);
        }

        // Retrieves accesstoken from database
        if ($value=($tokens['access_token'] ?? '')) {
            $connection->setAccessToken($value);
        }

        // Retrieves refreshtoken from database
        if ($value=($tokens['refresh_token'] ?? '')) {
            $connection->setRefreshToken($value);
        }

        // Retrieves expires timestamp from database
        if ($value=($tokens['expires_in'] ?? '')) {
            $connection->setTokenExpires($value);
        }

        // Set callback to save newly generated tokens
        $connection->setTokenUpdateCallback([$this, 'tokenUpdateCallback']);

        // Make the client connect and exchange tokens
        try {
            $connection->connect();
        } catch (\Exception $e) {
            throw new \Exception('Could not connect to Exact: ' . $e->getMessage());
        }

        return $connection;
    }
}