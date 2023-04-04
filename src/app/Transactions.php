<?php

namespace Zorgportal;

class Transactions
{
    const COLUMNS = [
        'id' => null,
        'GUID' => 150,
        'AccountName' => 500,
        'AmountDC' => null,
        'AmountFC' => null,
        'Created' => null,
        'Date' => null,
        'Modified' => null,
        'Description' => null,
        'DocumentSubject' => 500,
        'EntryNumber' => null,
        'GLAccountCode' => null,
        'GLAccountDescription' => 500,
        'InvoiceNumber' => null,
        'JournalCode' => null,
        'JournalDescription' => 500,
        'Notes' => null,
        'FinancialPeriod' => null,
        'FinancialYear' => null,
        'PaymentReference' => 500,
        'Status' => null,
        'Type' => null,
        'YourRef' => null,
    ];

    public static function setupDb( float $db_version=0 )
    {
        global $wpdb;

        $table = $wpdb->prefix . App::TRANSACTIONS_TABLE;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta("CREATE TABLE IF NOT EXISTS {$table} (
           `id` bigint(20) unsigned not null auto_increment,
           `GUID` varchar(150) not null unique,
           `AccountName` varchar(500),
           `AmountDC` decimal(10,2),
           `AmountFC` decimal(10,2),
           `Created` datetime,
           `Date` datetime,
           `Modified` datetime,
           `Description` text,
           `DocumentSubject` varchar(500),
           `EntryNumber` bigint,
           `GLAccountCode` int,
           `GLAccountDescription` varchar(500),
           `InvoiceNumber` bigint,
           `JournalCode` int,
           `JournalDescription` varchar(500),
           `Notes` text,
           `FinancialPeriod` int,
           `FinancialYear` int,
           `PaymentReference` varchar(500),
           `Status` int,
           `Type` int,
           `YourRef` int,
          primary key(`id`)
        ) {$wpdb->get_charset_collate()};");

        if ( $db_version < 1.5 ) {
            $wpdb->query("alter table {$table} add column `GUID` varchar(150) not null unique");
        }
    }

    public static function prepareData( array $args ) : array
    {
        $data = [];

        foreach ( ['AccountName','DocumentSubject','GLAccountDescription','JournalDescription','PaymentReference','GUID'] as $char )
            array_key_exists($char, $args) && ($data[$char] = substr(trim($args[$char]), 0, self::COLUMNS[$char]));

        foreach ( ['Created','Date','Modified','Description','Notes'] as $char )
            array_key_exists($char, $args) && ($data[$char] = trim($args[$char]));

        foreach ( ['id','EntryNumber','GLAccountCode','InvoiceNumber','JournalCode','FinancialPeriod','FinancialYear','Status','Type','YourRef'] as $int )
            array_key_exists($int, $args) && ($data[$int] = (int) $args[$int]);

        foreach ( ['AmountDC','AmountFC'] as $float )
            array_key_exists($float, $args) && ($data[$float] = (float) $args[$float]);

        return $data;
    }

    public static function insert( array $args ) : int
    {
        global $wpdb;

        $data = self::prepareData( $args );

        $wpdb->insert($wpdb->prefix . App::TRANSACTIONS_TABLE, $data);

        return $wpdb->insert_id;
    }

    public static function insertBulk( array $items ) : int
    {
        global $wpdb;

        $items = array_map([self::class, 'prepareData'], $items);

        $sql = 'insert into ' . $wpdb->prefix . App::TRANSACTIONS_TABLE . '(' . join(',',
            array_keys($items[0])) . ') values ';

        foreach ( $items as $item ) {
            $sql .= $wpdb->prepare(
                "(" . join( ',', array_fill(0, count(array_values($item)), '%s') ) . '),',
                ...(array_values($item))
            );
        }

        return $wpdb->query(substr($sql, 0, strlen($sql) - 1));
    }

    public static function push( string $message ) : int
    {
        return self::insert([
            'message' => $message,
            'date' => time()
        ]);
    }

    public static function update( int $id, array $args ) : bool
    {
        global $wpdb;
        $data = self::prepareData( $args );
        unset($data['id']);
        return !! $wpdb->update($wpdb->prefix . App::TRANSACTIONS_TABLE, $data, compact('id'));
    }

    public static function delete( array $ids ) : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::TRANSACTIONS_TABLE;
        return $wpdb->query("delete from {$table} where `id` in (" . join(',', array_map('intval', $ids)) . ")");
    }

    public static function deleteAll() : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::TRANSACTIONS_TABLE;
        return $wpdb->query("delete from {$table}");
    }

    public static function deleteOrphaned() : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::TRANSACTIONS_TABLE;
        $table2 = $wpdb->prefix . App::INVOICES_TABLE;
        return $wpdb->query("delete from {$table} where YourRef not in (select DeclaratieNummer from {$table2})");
    }

    public static function query( array $args=[] ) : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::TRANSACTIONS_TABLE;
        $sql = "select * from {$table} where 1=1";
        $exec = [];

        $int_fields = ['id','EntryNumber','GLAccountCode','InvoiceNumber','JournalCode','FinancialPeriod','FinancialYear','Status','Type','YourRef'];
        $float_fields = ['AmountDC','AmountFC'];

        foreach ( ['id', 'AccountName'] as $prop ) {
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
                `message` like \'%' . $wpdb->esc_like($args['search']) . '%\'
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
            $args['per_page'] = get_option('zorgportal:transactions-per-page', 100);
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

        foreach ( ['AccountName','DocumentSubject','GLAccountDescription','JournalDescription','PaymentReference','GUID'] as $char )
            $data[$char] = is_null($data[$char] ?? null) ? null : trim($data[$char]);

        foreach ( ['Created','Date','Modified','Description','Notes'] as $char )
            $data[$char] = is_null($data[$char] ?? null) ? null : trim($data[$char]);

        foreach ( ['id','EntryNumber','GLAccountCode','InvoiceNumber','JournalCode','FinancialPeriod','FinancialYear','Status','Type','YourRef'] as $int )
            $data[$int] = is_numeric($data[$int] ?? null) ? intval($data[$int]) : null;

        foreach ( ['AmountDC','AmountFC'] as $float )
            $data[$float] = is_numeric($data[$float] ?? null) ? floatval($data[$float]) : null;

        return $data;
    }

    public static function parseApiItem( array $ref ) : array
    {
        $alias = ['ID' => 'GUID'];

        foreach ( array_keys($ref) as $prop ) {
            if ( ! in_array($prop, ['AccountName','AmountDC','AmountFC','Created','Date','Modified','Description','DocumentSubject','EntryNumber','GLAccountCode','GLAccountDescription','InvoiceNumber','JournalCode','JournalDescription','Notes','FinancialPeriod','FinancialYear','PaymentReference','Status','Type','YourRef','ID']) )
                unset($ref[$prop]);

            if ( in_array($prop, ['Created','Date','Modified']) && $ref[$prop] ) {
                preg_match('/\((\d{10,})\)/', $ref[$prop], $m);
                if ( $m[1] ?? null ) {
                    $ref[$prop] = date('Y-m-d H:i:s', ceil(intval($m[1]) / 1000));
                } else {
                    $ref[$prop] = null;
                }
            }

            if ( $alias[$prop] ?? null ) {
                $ref[$alias[$prop]] = $ref[$prop];
                unset($ref[$prop]);
            }
        }

        return $ref;
    }
}