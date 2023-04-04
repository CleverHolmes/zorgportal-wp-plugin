<?php

namespace Zorgportal;

class DbcCodes
{
    const COLUMNS = [
        'id' => null,
        'dbc_code' => 20,
        'dbc_description' => 1000,
        'active_start_date' => null,
        'active_end_date' => null, 
        'insurer_packages' => 65535,
        'dbc_total_amount' => null,
        'date_added' => null,
        'meta' => null,
    ];

    public static function setupDb( float $db_version=0 )
    {
        global $wpdb;

        $table = $wpdb->prefix . App::DBC_CODES_TABLE;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta("CREATE TABLE IF NOT EXISTS {$table} (
          `id` bigint(20) unsigned not null auto_increment,
          `dbc_code` varchar(20) not null,
          `dbc_description` varchar(1000),
          `active_start_date` date not null,
          `active_end_date` date not null,
          `insurer_packages` text not null,
          `dbc_total_amount` decimal(10,2) unsigned not null,
          `date_added` bigint(20) unsigned not null,
          `meta` longtext,
          unique(`id`)
        ) {$wpdb->get_charset_collate()};");

        if ( $db_version < 0.2 ) {
            $wpdb->query("alter table {$table} add column `date_added` bigint(20) unsigned not null");
        }

        if ( $db_version < 0.3 ) {
            $wpdb->query("alter table {$table} drop column `amount`");
            $wpdb->query("alter table {$table} drop column `insurer_package`");
            $wpdb->query("alter table {$table} add column `insurer_packages` text not null");
        }

        if ( $db_version < 0.4 ) {
            $wpdb->query("alter table {$table} modify column `dbc_description` varchar(1000)");
        }

        if ( $db_version < 1.7 ) {
            $wpdb->query("alter table {$table} add column `meta` longtext");
        }
    }

    public static function prepareData( array $args ) : array
    {
        $data = [];

        foreach ( ['dbc_code','dbc_description'] as $char )
            array_key_exists($char, $args) && ($data[$char] = substr(esc_attr($args[$char]), 0, self::COLUMNS[$char]));

        foreach ( ['dbc_total_amount'] as $dec )
            array_key_exists($dec, $args) && ($data[$dec] = (float) $args[$dec]);

        foreach ( ['active_start_date','active_end_date'] as $prop )
            array_key_exists($prop, $args) && ($data[$prop] = esc_attr($args[$prop]));

        foreach ( ['date_added'] as $int )
            array_key_exists($int, $args) && ($data[$int] = (int) $args[$int]);

        foreach ( ['insurer_packages', 'meta'] as $json ) {
            if ( array_key_exists($json, $args) ) {
                is_array($args[$json]) && ( $args[$json] = json_encode($args[$json]) );

                if ( isset( self::COLUMNS[$json] ) ) {
                    $data[$json] = substr($args[$json], 0, self::COLUMNS[$json]);
                } else {
                    $data[$json] = $args[$json];
                }
            }
        }

        return $data;
    }

    public static function insert( array $args ) : int
    {
        global $wpdb;

        $data = self::prepareData( $args );

        $wpdb->insert($wpdb->prefix . App::DBC_CODES_TABLE, $data);

        return $wpdb->insert_id;
    }

    public static function update( int $id, array $args ) : bool
    {
        global $wpdb;
        $data = self::prepareData( $args );
        unset($data['id']);
        return !! $wpdb->update($wpdb->prefix . App::DBC_CODES_TABLE, $data, compact('id'));
    }

    public static function delete( array $ids ) : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::DBC_CODES_TABLE;
        return $wpdb->query("delete from {$table} where `id` in (" . join(',', array_map('intval', $ids)) . ")");
    }

    public static function deleteAll() : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::DBC_CODES_TABLE;
        return $wpdb->query("delete from {$table}");
    }

    public static function deleteDuplicates(array $codes, int $date_added) : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::DBC_CODES_TABLE;
        return $wpdb->query($wpdb->prepare("delete from {$table} where `date_added` != %d and `dbc_code` in (" . join(',', array_map(function(string $code){
            return '"' . esc_sql($code) . ' "';
        }, $codes)) . ")", $date_added));
    }

    public static function query( array $args=[] ) : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::DBC_CODES_TABLE;
        $sql = "select * from {$table} where 1=1";
        $exec = [];

        foreach ( ['id','dbc_code','active_start_date','active_end_date','insurer_packages','date_added'] as $prop ) {
            if ( array_key_exists($prop, $args) ) {
                $sql .= $wpdb->prepare(" and {$prop} = %s", $args[$prop]);
            }

            if ( $args["{$prop}_in"] ?? '' ) {
                $sql .= $wpdb->prepare(
                    " and {$prop} in (" . join( ',', array_fill(0, count((array) $args["{$prop}_in"]), '%s') ) . ')',
                    ...((array) $args["{$prop}_in"])
                );
            }

            if ( $args["{$prop}_not_in"] ?? '' ) {
                $sql .= $wpdb->prepare(
                    " and {$prop} not in (" . join( ',', array_fill(0, count((array) $args["{$prop}_not_in"]), '%s') ) . ')',
                    ...((array) $args["{$prop}_not_in"])
                );
            }
        }

        if ( $args['search'] ?? '' ) {
            $sql .= ' and (
                `dbc_code` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `dbc_description` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `insurer_packages` like \'%' . $wpdb->esc_like($args['search']) . '%\'
            )';
        }

        foreach ( ['active_start_date', 'active_end_date'] as $prop ) {
            if ( $args["{$prop}_lte"] ?? null ) {
                $sql .= $wpdb->prepare(" and {$prop} <= %s", $args["{$prop}_lte"]);
            }

            if ( $args["{$prop}_gte"] ?? null ) {
                $sql .= $wpdb->prepare(" and {$prop} >= %s", $args["{$prop}_gte"]);
            }
        }

        if ( $args['insurer'] ?? null ) {
            /*
            $sql .= $wpdb->prepare(" and (
                (
                    substring(trim(substring_index(substring_index(insurer_packages, '{\"', 2), '_', 1)), 3) = %s
                    and insurer_packages like '%{\"%'
                ) or (
                    trim(substring_index(substring_index(substring_index(insurer_packages, ',\"', 2), ',\"', -1), '_', 1)) = %s
                    and insurer_packages like '%,\"%'
                ))", $args['insurer'], $args['insurer']);
            */
            $sql .= sprintf(' and (insurer_packages like \'%%{"%1$s_%%\' or insurer_packages like \'%%,"%1$s_%%\')', $wpdb->esc_like($args['insurer']));
        }

        if ( $args['policy'] ?? null ) {
            $sql .= " and insurer_packages like '%_" . $wpdb->esc_like($args['policy']) . "\":%'";
        }

        if ( $args['most_recent_date'] ?? '' ) {
            $sql .= ' order by datediff(active_end_date, active_start_date) asc';
        } else {
            $orderby = sanitize_text_field($args['orderby'] ?? '');
            $orderby = in_array($orderby, array_merge(array_keys(self::COLUMNS), ['rand()'])) ? $orderby : 'id';
            $sql .= " order by {$orderby} ";
            $sql .= in_array(strtolower($args['order'] ?? ''), ['asc', 'desc']) ? strtolower($args['order'] ?? '') : 'desc';
        }

        if ( is_numeric($args['limit'] ?? '') ) {
            $sql .= ' limit ' . intval($args['limit']);
        }

        if ( intval($args['per_page'] ?? 0) <= 0 ) {
            $args['per_page'] = get_option('zorgportal:codes-per-page', 30);
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

        $data['id'] = (int) $data['id'];
        $data['dbc_code'] = trim( esc_attr( $data['dbc_code'] ?: '' ) );
        $data['dbc_description'] = trim( esc_attr( $data['dbc_description'] ?: '' ) );
        $data['active_start_date'] = ! is_null($data['active_start_date'] ?? null) ? trim($data['active_start_date']) : null;
        $data['active_end_date'] = ! is_null($data['active_end_date'] ?? null) ? trim($data['active_end_date']) : null;
        $data['insurer_packages'] = json_decode($data['insurer_packages'] ?: '', 1) ?: [];
        $data['dbc_total_amount'] = ! is_null($data['dbc_total_amount'] ?? null) ? floatval($data['dbc_total_amount']) : null;
        $data['date_added'] = ! is_null($data['date_added'] ?? null) ? intval($data['date_added']) : null;
        $data['meta'] = json_decode($data['meta'] ?: '', 1) ?: [];

        return $data;
    }

    public static function getAllInsurers() : array
    {
        $items = wp_list_pluck(self::query(['nopaged' => 1])['list'], 'insurer_packages');
        $insurers = [];

        foreach ( $items as $policies ) {
            foreach ( $policies as $name => $policy ) {
                $insurer = trim(explode('_', $name)[0] ?? '');

                if ( $insurer && ! in_array($insurer, $insurers) )
                    $insurers []= $insurer;
            }
        }

        return $insurers;
    }

    public static function getAllPolicies() : array
    {
        $items = wp_list_pluck(self::query(['nopaged' => 1])['list'], 'insurer_packages');
        $matches = [];

        foreach ( $items as $policies ) {
            foreach ( $policies as $name => $policy ) {
                $parts = explode('_', $name);
                $match = join('_', array_slice($parts, 1));

                if ( $match && ! in_array($match, $matches) )
                    $matches []= $match;
            }
        }

        return $matches;
    }
}