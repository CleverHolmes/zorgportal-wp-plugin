<?php

namespace Zorgportal;

class Practitioners
{
    const COLUMNS = [
        'id' => null,
        'name' => 250,
        'location' => 250,
        'specialty' => 250,
        'fee' => null,
    ];

    public static function setupDb( float $db_version=0 )
    {
        global $wpdb;

        $table = $wpdb->prefix . App::PRACTITIONERS_TABLE;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta("CREATE TABLE IF NOT EXISTS {$table} (
          `id` bigint(20) unsigned not null auto_increment,
          `name` varchar(250) not null,
          `location` varchar(250) not null,
          `specialty` varchar(250) not null,
          `fee` decimal(8,2) unsigned not null default 0,
          primary key(`id`)
        ) {$wpdb->get_charset_collate()};");
    }

    public static function prepareData( array $args ) : array
    {
        $data = [];

        foreach ( ['name','location','specialty'] as $char )
            array_key_exists($char, $args) && ($data[$char] = substr(esc_attr($args[$char]), 0, self::COLUMNS[$char]));

        foreach ( ['fee'] as $dec )
            array_key_exists($dec, $args) && ($data[$dec] = (float) $args[$dec]);

        return $data;
    }

    public static function insert( array $args ) : int
    {
        global $wpdb;

        $data = self::prepareData( $args );

        $wpdb->insert($wpdb->prefix . App::PRACTITIONERS_TABLE, $data);

        return $wpdb->insert_id;
    }

    public static function update( int $id, array $args ) : bool
    {
        global $wpdb;
        $data = self::prepareData( $args );
        unset($data['id']);
        return !! $wpdb->update($wpdb->prefix . App::PRACTITIONERS_TABLE, $data, compact('id'));
    }

    public static function delete( array $ids ) : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::PRACTITIONERS_TABLE;
        return $wpdb->query("delete from {$table} where `id` in (" . join(',', array_map('intval', $ids)) . ")");
    }

    public static function deleteAll() : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::PRACTITIONERS_TABLE;
        return $wpdb->query("delete from {$table}");
    }

    public static function query( array $args=[] ) : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::PRACTITIONERS_TABLE;
        $sql = "select * from {$table} where 1=1";
        $exec = [];

        foreach ( ['id','name','location','specialty','fee'] as $prop ) {
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
                `name` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `location` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `specialty` like \'%' . $wpdb->esc_like($args['search']) . '%\'
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
            $args['per_page'] = get_option('zorgportal:practitioners-per-page', 30);
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
        $data['location'] = trim( esc_attr( $data['location'] ?: '' ) );
        $data['specialty'] = trim( esc_attr( $data['specialty'] ?: '' ) );
        $data['fee'] = ! is_null($data['fee'] ?? null) ? floatval($data['fee']) : null;

        return $data;
    }

    public static function fromString( string $raw ) : array
    {
        $parts = array_filter(array_map('sanitize_text_field', explode('-', $raw)));

        $name = $parts[0] ?? null;
        $location = $parts[1] ?? null;
        $specialty = $parts[2] ?? null;
        $fee = 0;

        if ( $name && $location && $specialty )
            return compact('name', 'location', 'specialty', 'fee');

        return [];
    }
}