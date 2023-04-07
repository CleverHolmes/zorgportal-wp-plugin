<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\App;
use Zorgportal\EoLogs;
use Zorgportal\Util\Exact\Client as ExactClient;

// we port the functions 
use Zorgportal\Utils;

class Settings extends Screen
{
    public function init()
    {
        // we initialize, so we just load, maybe there is no response $GET with a ['code']
        // we first check if user onboarded (authd), else it's onboarding time
        // then just refresh the counters (not via .js) and values
        // only when we trigger auth, it's relevant to getCode
        // if user onboarded, then we should first check our own db, for timings, maybe refresh, reduce calls
        // the if getCode is only relevant when user "submit" "oauth_redirect" 

        if ( $code = $_GET['code'] ?? null ) {
            add_action('admin_footer', function()
            {
                $admin_url = admin_url('admin.php?page=zorgportal-settings');
                echo "<script>history.replaceState({}, {}, '{$admin_url}')</script>\n";
            });

            self::auth();
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

    public static function auth()
    {      
        $tokens = get_option('zp_exactonline_auth_tokens') ?: [];
        

        // If authorization code is returned from Exact, save this to use for token request
        if (isset($_GET['code']) && is_null($tokens['authorizationcode'] ?? null)) {
            $tokens['authorizationcode'] = $_GET['code'];
            update_option('zp_exactonline_auth_tokens', $tokens);
        }

        // If we do not have an authorization code, authorize first to setup tokens
        if (($tokens['authorizationcode'] ?? null) === null) {
            ExactClient::authorize();
        }

        // Create the Exact client
        return ($connection = ExactClient::connect());
    }

    public function render()
    {
        if ( ! isset( $_POST['oauth_redirect'] ) ) {
            $_POST['client_id'] = get_option('zorgportal_exact_client_id') ?: '';
            $_POST['client_secret'] = get_option('zorgportal_exact_client_secret') ?: '';
            $_POST['webhook_secret'] = get_option('zorgportal_exact_webhook_secret') ?: '';
        }

        return $this->renderTemplate('settings-v2.php', [
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
            'rate_limits' => ExactClient::getRateLimitState(),
        ]);
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_style( 'zportal-settings', "{$base}src/assets/css/settings.css", [], $this->appContext::SCRIPTS_VERSION );
    }

    public function update()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        if ( isset($_POST['oauth_disconnect']) )
            return $this->oauthDisconnect();

        if ( isset($_POST['oauth_redirect']) )
            return $this->oauthRedirect();

        if ( isset($_POST['oauth_refresh']) )
            return $this->oauthRefresh();

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
        // if ( ! $client_id = sanitize_text_field($_POST['client_id'] ?? '') )
        //     return $this->error( __('Please enter a client id.', 'zorgportal') );

        // if ( ! $client_secret = sanitize_text_field($_POST['client_secret'] ?? '') )
        //     return $this->error( __('Please enter a client secret.', 'zorgportal') );

        // $webhook_secret = sanitize_text_field($_POST['webhook_secret'] ?? '');

        // if ( $client_id != get_option('zorgportal_exact_client_id')
        //     || $client_secret != get_option('zorgportal_exact_client_secret') ) {
        //     $this->oauthDisconnect();
        // }

        // update_option('zorgportal_exact_client_id', $client_id);
        // update_option('zorgportal_exact_client_secret', $client_secret);
        // update_option('zorgportal_exact_webhook_secret', $webhook_secret);

        try {
            self::auth();
            return $this->success( __('Access token refreshed successfully.', 'zorgportal') );
        } catch ( \Exception $e ) {
            return $this->error( __('Error occurred, access token could not be refreshed.', 'zorgportal') );
        }
    }

    public static function refreshTokensCron() : void
    {
        self::auth();
    }

    private function oauthRefresh()
    {
        try {
            self::auth();
            return $this->info( __('Access token may have been refreshed.', 'zorgportal') );
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
        update_option('zp_exactonline_divisions', ExactClient::getSystemDivisions());
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
}