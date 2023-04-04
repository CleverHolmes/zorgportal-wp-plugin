<?php

namespace Zorgportal\Util\Exact;

use \Picqer\Financials\Exact\Connection;

class Client
{
    const RATE_LIMIT_METADATA_TRANSIENT = 'Zp.ExactOnlineRateLimitInfo';

    public static function connect() : Connection
    {
        $connection = new Connection();
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
        $connection->setTokenUpdateCallback([self::class, 'tokenUpdateCallback']);

        // Make the client connect and exchange tokens
        try {
            $connection->connect();
        } catch (\Exception $e) {
            throw new \Exception('Could not connect to Exact: ' . $e->getMessage());
        }

        // set current division if any
        if ( $divisions = get_option('zp_exactonline_divisions') ) {
            if ( $divcode = current(array_filter($divisions, fn($d) => $d['current'] ?? null))['code'] ?? null ) {
                $connection->setDivision((string) $divcode);
            }
        }

        // wait for rate limit reset if limit reached
        $connection->setWaitOnMinutelyRateLimitHit(true);

        return $connection;
    }

    /**
     * Callback function that sets values that expire and are refreshed by Connection.
     *
     * @param Connection $connection
     */
    function tokenUpdateCallback(Connection $connection) : void
    {
        update_option('zp_exactonline_auth_tokens', [
            'access_token' => $connection->getAccessToken(),
            'refresh_token' => $connection->getRefreshToken(),
            'expires_in' => $connection->getTokenExpires(),
            'authorizationcode' => get_option('zp_exactonline_auth_tokens')['authorizationcode'] ?? null,
            '_expires' => $connection->getTokenExpires(),
        ]);
    }

    /**
     * Function to authorize with Exact, this redirects to Exact login promt and retrieves authorization code
     * to set up requests for oAuth tokens.
     */
    public static function authorize() : void
    {
        $connection = new Connection();
        $connection->setRedirectUrl(admin_url('admin.php?page=zorgportal-settings'));
        $connection->setExactClientId(get_option('zorgportal_exact_client_id'));
        $connection->setExactClientSecret(get_option('zorgportal_exact_client_secret'));
        $connection->redirectForAuthorization();
    }

    /**
      * Store rate limit metadata in transients
      */
    private static function updateRateLimitState( Connection $connection ) : void
    {
        set_transient(self::RATE_LIMIT_METADATA_TRANSIENT, [
            'dailyLimit' => $connection->getDailyLimit(),
            'dailyLimitRemaining' => $connection->getDailyLimitRemaining(),
            'dailyLimitReset' => $connection->getDailyLimitReset(),
            'minutelyLimit' => $connection->getMinutelyLimit(),
            'minutelyLimitRemaining' => $connection->getMinutelyLimitRemaining(),
            'minutelyLimitReset' => $connection->getMinutelyLimitReset(),
        ], DAY_IN_SECONDS);
    }

    /**
      * get rate limit metadata
      */
    public static function getRateLimitState() : array
    {
        return get_transient(self::RATE_LIMIT_METADATA_TRANSIENT) ?: [];
    }

    public static function getSystemDivisions() : array
    {
        $divs = new \Picqer\Financials\Exact\SystemDivision( self::connect() );
        
        $set = $divs->getResultSet();
        $divs = [];
        
        while ( $set->hasMore() ) {
            $divs = array_merge($divs, array_filter($set->next()));
        }

        if ( count($divs) > 0 ) {
            self::updateRateLimitState(end($divs)->connection());
        }

        return array_map(fn($div) => [
            'description' => $div->attributes()['Description'],
            'code' => $div->attributes()['Code'],
            'customer' => $div->attributes()['Customer'],
        ], $divs);
    }
}