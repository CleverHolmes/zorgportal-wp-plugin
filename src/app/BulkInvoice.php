<?php

namespace Zorgportal;
use Zorgportal\App;

class BulkInvoice
{
    const COLUMNS = [
        'id' => null,
        '_CreatedDate' => null,
        'NumberInvoices' => null,
        'Date' => null,
        'DueDate' => null,
        'ReimburseTotal' => null,
        'Location' => null,
        'Insurer' => null,
        'Policy' => null,
        'Status' => null
    ];

    const PAYMENT_STATUS_PAID = 1;
    const PAYMENT_STATUS_DUE = 2;
    const PAYMENT_STATUS_OVERDUE = 3;

    const PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_PAID,
        self::PAYMENT_STATUS_DUE,
        self::PAYMENT_STATUS_OVERDUE,
    ];

    public static function setupDb( float $db_version=0 )
    {
        global $wpdb;

        $table = $wpdb->prefix . App::BULKINVOICES_TABLE;
        $invoiceTable = $wpdb->prefix . App::INVOICES_TABLE;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta("CREATE TABLE IF NOT EXISTS {$table} (
          `id` bigint(20) unsigned not null auto_increment,
          `_CreatedDate` datetime,
          `NumberInvoices` int unsigned,
          `Date` datetime,
          `DueDate` datetime,
          `AmountTotal` decimal(10,2),
          `ReimburseTotal` decimal(10,2),
          `Practitioner ` text,
          `Location ` text,
          `Insurer ` text,
          `Policy ` text,
          `Status` int unsigned,
          primary key(`id`),
          FOREIGN KEY (id) REFERENCES ".$invoiceTable."(BulkInvoiceNumber)
        ) {$wpdb->get_charset_collate()};");
    }

    public static function intialData(array $args=[]) : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::BULKINVOICE_TABLE;
        $invoicetable = $wpdb->prefix . App::INVOICES_TABLE;

        $sql = "SELECT  {$table}.id AS parent_id,{$invoicetable}.id AS child_id, {$table}.*, {$invoicetable}.* FROM {$table} LEFT JOIN {$invoicetable} ON {$table}.id = {$invoicetable}.BulkInvoiceNumber WHERE 1=1 ";

        $orderby = sanitize_text_field($args['orderby'] ?? '');
        $orderby = in_array($orderby, array_merge(array_keys(self::COLUMNS), ['rand()'])) ? $table.".".$orderby : $table.'.id';

        $sql .= " order by {$orderby} ";
        $sql .= in_array(strtolower($args['order'] ?? ''), ['asc', 'desc']) ? strtolower($args['order'] ?? '') : 'desc';
     
        $list = (array) $wpdb->get_results($sql, ARRAY_A);

        $newList = array(); $temp = 0; $i = 0;

        if(!empty($list)) {
            foreach ($list as $key => $value) {
                if($temp != $value['parent_id']) {
                    $i++; $j=0;
                    $newList[$i] = $value;
                    $newList[$i]['child'][$j] = $value;
                } else {
                    $j++; $newList[$i]['child'][$j] = $value;
                }
                $temp = $value['parent_id'];
            }
        }
    
        return compact("newList");
    }

    public static function queryOne(array $args=[]) : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::BULKINVOICE_TABLE;

        $sql = "SELECT * FROM {$table} WHERE id='".$args['id']."' ";

        $bulkinvoice = (array) $wpdb->get_results($sql, ARRAY_A);
    
        return $bulkinvoice[0];
    }

    public static function update( int $id, array $args, bool $extract_nums=true ) : bool
    {
        global $wpdb;
        $data = self::prepareData( $args, $extract_nums );
        unset($data['id']);
        // print_r($data);die;
        return !! $wpdb->update($wpdb->prefix . App::BULKINVOICE_TABLE, $data, compact('id'));
    }

    public static function delete( array $ids ) : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::BULKINVOICE_TABLE;
        $invoicetable = $wpdb->prefix . App::INVOICES_TABLE;

        $uQuery = "update {$invoicetable} set BulkInvoiceNumber = NULL  where BulkInvoiceNumber = '".$ids[0]."'";
        $update = $wpdb->query($uQuery);

        return $wpdb->query("delete from {$table} where `id` in (" . join(',', array_map('intval', $ids)) . ")");
    }

    public static function prepareData( array $args, bool $extract_nums=true ) : array
    {
        $data = [];
        foreach ( ['Practitioner','Location','DossierNaam','Insurer','Policy'] as $char )
            array_key_exists($char, $args) && ($data[$char] = trim($args[$char]));

        foreach ( ['AmountTotal','ReimburseTotal'] as $float ) {
            array_key_exists($float, $args) && ($data[$float] = $extract_nums ? App::extractNum($args[$float]) : $args[$float]);
        }

        foreach ( ['NumberInvoices'] as $int )
            array_key_exists($int, $args) && ($data[$int] = (int) $args[$int]);

        foreach ( ['_CreatedDate','Date','DueDate'] as $date )
            array_key_exists($date, $args) && ($data[$date] = trim($args[$date]));

        return $data;
    }

    public static function queryChild(array $args=[]) : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;

        $sql = "SELECT * FROM {$table} WHERE BulkInvoiceNumber='".$args['id']."' ";

        $invoice = (array) $wpdb->get_results($sql, ARRAY_A);
    
        return $invoice;
    }
    

}