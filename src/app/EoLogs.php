<?php

namespace Zorgportal;

class EoLogs
{
    const COLUMNS = [
        'id' => null,
        'type' => null,
        'request_url' => 1024,
        'request_body' => null,
        'request_headers' => null,
        'response' => null,
        'response_headers' => null,
        'http_status' => null,
        'status' => null,
        'object_id' => null,
        'object_type' => null,
        'date' => null,
    ];

    const STATUS_OK = 1;
    const STATUS_ERROR = 2;
    const STATUS_INFO = 3;

    public static function setupDb( float $db_version=0 )
    {
        global $wpdb;

        $table = $wpdb->prefix . App::EO_LOGS_TABLE;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta("CREATE TABLE IF NOT EXISTS {$table} (
          `id` bigint(20) unsigned not null auto_increment,
          `type` varchar(30) not null default 'api',
          `request_url` varchar(1024),
          `request_body` longtext,
          `request_headers` text,
          `response` longtext,
          `response_headers` text,
          `http_status` int unsigned,
          `status` int unsigned default 1,
          `object_id` bigint(20) unsigned default 0,
          `object_type` varchar(30) default 'invoice',
          `date` bigint(20) unsigned not null,
          primary key(`id`)
        ) {$wpdb->get_charset_collate()};");
    }

    public static function prepareData( array $args ) : array
    {
        $data = [];

        foreach ( ['type','request_url','request_body','request_headers','response','response_headers','object_type'] as $char )
            array_key_exists($char, $args) && ($data[$char] = trim($args[$char]));

        foreach ( ['id','date','http_status','status','object_id'] as $int )
            array_key_exists($int, $args) && ($data[$int] = (int) $args[$int]);

        return $data;
    }

    public static function insert( array $args ) : int
    {
        global $wpdb;

        $data = self::prepareData( $args );

        $wpdb->insert($wpdb->prefix . App::EO_LOGS_TABLE, $data);

        return $wpdb->insert_id;
    }

    public static function push( string $message ) : int
    {
        return 0; // @deprecated

        // return self::insert([
        //     'message' => $message,
        //     'date' => time()
        // ]);
    }

    public static function update( int $id, array $args ) : bool
    {
        global $wpdb;
        $data = self::prepareData( $args );
        unset($data['id']);
        return !! $wpdb->update($wpdb->prefix . App::EO_LOGS_TABLE, $data, compact('id'));
    }

    public static function delete( array $ids ) : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::EO_LOGS_TABLE;
        return $wpdb->query("delete from {$table} where `id` in (" . join(',', array_map('intval', $ids)) . ")");
    }

    public static function deleteAll() : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::EO_LOGS_TABLE;
        return $wpdb->query("delete from {$table}");
    }

    public static function deleteExpired( int $lt_epoch ) : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::EO_LOGS_TABLE;
        return $wpdb->query($wpdb->prepare("delete from {$table} where `date` < %d", $lt_epoch));
    }

    public static function query( array $args=[] ) : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::EO_LOGS_TABLE;
        $sql = "select * from {$table} where 1=1";
        $exec = [];

        $int_fields = ['id','date','http_status','status','object_id'];
        $float_fields = [];

        foreach ( ['id','date','http_status','status','object_id','type','object_type'] as $prop ) {
            $format = in_array($prop, $int_fields) ? '%d' : ( in_array($prop, $float_fields) ? '%f' : '%s' );

            if ( array_key_exists($prop, $args) ) {
                $sql .= $wpdb->prepare(" and {$prop} = {$format}", $args[$prop]);
            }

            if ( $args["{$prop}_in"] ?? '' ) {
                $sql .= $wpdb->prepare(
                    " and {$prop} in (" . join( ',', array_fill(0, count((array) $args["{$prop}_in"]), $format) ) . ')',
                    ...((array) $args["{$prop}_in"])
                );
            }

            if ( $args["{$prop}_not_in"] ?? '' ) {
                $sql .= $wpdb->prepare(
                    " and {$prop} not in (" . join( ',', array_fill(0, count((array) $args["{$prop}_not_in"]), $format) ) . ')',
                    ...((array) $args["{$prop}_not_in"])
                );
            }

            if ( $args["{$prop}_ne_or_null"] ?? '' ) {
                $sql .= $wpdb->prepare(" and ({$prop} is null or {$prop} != {$format})", $args["{$prop}_ne_or_null"]);
            }
        }

        if ( $args['search'] ?? '' ) {
            $sql .= ' and (
                `request_url` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `request_body` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `request_headers` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `response` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `response_headers` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `object_id` like \'%' . $wpdb->esc_like($args['search']) . '%\'
            )';
        }

        $orderby = sanitize_text_field($args['orderby'] ?? '');
        $orderby = in_array($orderby, array_merge(array_keys(self::COLUMNS), ['rand()'])) ? $orderby : 'id';
        $sql .= " order by {$orderby} ";
        $sql .= in_array(strtolower($args['order'] ?? ''), ['asc', 'desc']) ? strtolower($args['order'] ?? '') : 'desc';

        if ( is_numeric($args['limit'] ?? '') ) {
            $sql .= ' limit ' . intval($args['limit']);
        }

        if ( intval($args['per_page'] ?? 0) <= 0 ) {
            $args['per_page'] = get_option('zorgportal:eo-logs-per-page', 100);
        } else {
            $args['per_page'] = (int) $args['per_page'];
        }

        if ( intval( $args['current_page'] ?? 0 ) < 1 ) {
            $args['current_page'] = 1;
        } else {
            $args['current_page'] = (int) $args['current_page'];
        }

        $start = 0;
        for ( $i=2; $i<= $args['current_page']; $i++ ) {
            $start += $args['per_page'];
        }

        $args['per_page']++;

        if ( ! ( $args['nopaged'] ?? null ) ) {
            $sql .= " limit {$start}, {$args['per_page']}";
        }

        $list = [];
        $has_prev = $has_next = null;
        $list = array_map([self::class, 'parseDbItems'], $wpdb->get_results( $sql ));
        $has_prev = ($current_page=$args['current_page']) > 1;
        $has_next = count( $list ) > --$args['per_page'];
        
        if ( ! ( $args['nopaged'] ?? null ) ) {
            $list = array_slice($list, 0, $args['per_page']);
        }

        return compact('has_prev', 'has_next', 'list', 'current_page');
    }

    public static function queryOne( array $args=[] ) : ?array
    {
        return self::query( array_merge($args, [ 'per_page' => 1, 'current_page' => 1 ]) )['list'][0] ?? null;
    }

    public static function parseDbItems( $data ) : array
    {
        if ( ! is_array($data) )
            $data = (array) $data;

        foreach ( ['id','date','http_status','status','object_id'] as $p )
            $data[$p] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;
        
        foreach ( ['type','request_url','request_body','request_headers','response','response_headers','object_type'] as $p )
            $data[$p] = isset($data[$p]) ? trim($data[$p]) : null;

        return $data;
    }
}