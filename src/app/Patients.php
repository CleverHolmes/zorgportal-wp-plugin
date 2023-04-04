<?php

namespace Zorgportal;

class Patients
{
    const COLUMNS = [
        'id' => null, //; DeclaratieDebiteurnummer 
        'name' => 250, //; DeclaratieDebiteurNaam
        'email' => 250, //; DebiteurMailadres
        'phone' => 250, //; DebiteurTelefoon
        'address' => 500, //; DebiteurAdres 
        'insurer' => 500, //; ZorgverzekeraarNaam 
        'policy' => 500, //; ZorgverzekeraarPakket 
        'UZOVI' => 500, //; ZorgverzekeraarUZOVI
        'location' => 200,
        'practitioner' => 200,
        'last_edited' => null,
        'status' => null,
    ];

    public static function setupDb( float $db_version=0 )
    {
        global $wpdb;

        $table = $wpdb->prefix . App::PATIENTS_TABLE;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta("CREATE TABLE IF NOT EXISTS {$table} (
          `id` bigint(20) unsigned not null,
          `name` varchar(250),
          `email` varchar(250),
          `phone` varchar(250),
          `address` varchar(500),
          `insurer` varchar(500),
          `policy` varchar(500),
          `UZOVI` varchar(500),
          `location` varchar(200),
          `practitioner` varchar(200),
          `last_edited` datetime,
          `status` int unsigned,
          primary key(`id`)
        ) {$wpdb->get_charset_collate()};");

        if ( $db_version < 1.6 ) {
            $wpdb->query("alter table {$table} add column `UZOVI` varchar(500)");
            $wpdb->query("alter table {$table} add column `location` varchar(200)");
            $wpdb->query("alter table {$table} add column `practitioner` varchar(200)");
            $wpdb->query("alter table {$table} add column `last_edited` datetime");
            $wpdb->query("alter table {$table} add column `status` int unsigned");
        }
    }

    public static function prepareData( array $args ) : array
    {
        $data = [];

        foreach ( ['id','status'] as $int )
            array_key_exists($int, $args) && ($data[$int] = (int) $args[$int]);

        foreach ( ['name','email','phone','address','insurer','policy','UZOVI','location','practitioner'] as $char )
            array_key_exists($char, $args) && ($data[$char] = substr(esc_attr($args[$char]), 0, self::COLUMNS[$char]));

        foreach ( ['last_edited'] as $char )
            array_key_exists($char, $args) && ($data[$char] = trim($args[$char]));

        return $data;
    }

    public static function insert( array $args ) : bool
    {
        global $wpdb;

        $data = self::prepareData( $args );

        return !! $wpdb->insert($wpdb->prefix . App::PATIENTS_TABLE, $data);
    }

    public static function update( int $id, array $args ) : bool
    {
        global $wpdb;
        $data = self::prepareData( $args );
        unset($data['id']);
        return !! $wpdb->update($wpdb->prefix . App::PATIENTS_TABLE, $data, compact('id'));
    }

    public static function delete( array $ids ) : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::PATIENTS_TABLE;
        return $wpdb->query("delete from {$table} where `id` in (" . join(',', array_map('intval', $ids)) . ")");
    }

    public static function deleteAll() : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::PATIENTS_TABLE;
        return $wpdb->query("delete from {$table}");
    }

    public static function query( array $args=[] ) : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::PATIENTS_TABLE;
        $sql = "select * from {$table} where 1=1";
        $exec = [];

        foreach ( ['id','name','email','phone','address','insurer','policy','UZOVI','location','practitioner','last_edited','status'] as $prop ) {
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
                `id` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `name` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `email` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `phone` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `address` like \'%' . $wpdb->esc_like($args['search']) . '%\'
            )';
        }

        $sql = apply_filters('Zorgportal\Patients::query@sql', $sql, $args);

        $orderby = sanitize_text_field($args['orderby'] ?? '');
        $orderby = in_array($orderby, array_merge(array_keys(self::COLUMNS), ['rand()'])) ? $orderby : 'id';
        $sql .= " order by {$orderby} ";
        $sql .= in_array(strtolower($args['order'] ?? ''), ['asc', 'desc']) ? strtolower($args['order'] ?? '') : 'desc';

        if ( is_numeric($args['limit'] ?? '') ) {
            $sql .= ' limit ' . intval($args['limit']);
        }

        if ( intval($args['per_page'] ?? 0) <= 0 ) {
            $args['per_page'] = get_option('zorgportal:patients-per-page', 30);
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
        $sql .= " limit {$start}, {$args['per_page']}";

        $list = [];
        $has_prev = $has_next = null;
        $list = array_map([self::class, 'parseDbItems'], $wpdb->get_results( $sql ));
        $has_prev = ($current_page=$args['current_page']) > 1;
        $has_next = count( $list ) > --$args['per_page'];
        $list = array_slice($list, 0, $args['per_page']);

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
        $data['name'] = trim( esc_attr( $data['name'] ?: '' ) );
        $data['email'] = trim( esc_attr( $data['email'] ?: '' ) );
        $data['phone'] = trim( esc_attr( $data['phone'] ?: '' ) );
        $data['address'] = trim( esc_attr( $data['address'] ?: '' ) );
        $data['insurer'] = trim( esc_attr( $data['insurer'] ?: '' ) );
        $data['policy'] = trim( esc_attr( $data['policy'] ?: '' ) );
        $data['UZOVI'] = trim( esc_attr( $data['UZOVI'] ?: '' ) );
        $data['location'] = trim( esc_attr( $data['location'] ?: '' ) );
        $data['practitioner'] = trim( esc_attr( $data['practitioner'] ?: '' ) );
        $data['last_edited'] = trim( esc_attr( $data['last_edited'] ?: '' ) );
        $data['status'] = (int) $data['status'];

        return $data;
    }

    public static function getAllAddresses() : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::PATIENTS_TABLE;
        $results = (array) $wpdb->get_results("select max(address) as address from {$table} q where address != 'NULL' and address > '' group by address", ARRAY_A);
        return array_filter(wp_list_pluck($results, 'address'));
    }

    public static function getAllInsurers() : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::PATIENTS_TABLE;
        $results = (array) $wpdb->get_results("select max(insurer) as insurer from {$table} q where insurer != 'NULL' and insurer > '' group by insurer", ARRAY_A);
        return array_filter(wp_list_pluck($results, 'insurer'));
    }

    public static function getAllPolicies() : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::PATIENTS_TABLE;
        $results = (array) $wpdb->get_results("select max(policy) as policy from {$table} q where policy != 'NULL' and policy > '' group by policy", ARRAY_A);
        return array_filter(wp_list_pluck($results, 'policy'));
    }
}