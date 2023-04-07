<?php

namespace Zorgportal;
use Zorgportal\App;
use DateTime;
use DateInterval;

class Invoices
{
    const COLUMNS = [
        'id' => null,
        '_CreatedDate' => null,
        'DeclaratieNummer' => null,
        'DeclaratieDatum' => null,
        'DeclaratieregelOmschrijving' => null,
        'DeclaratieBedrag' => null,
        'DossierNUmmer' => null,
        'DossierBehandellocatie' => null,
        'DossierNaam' => null,
        'SubtrajectNummer' => null,
        'SubtrajectHoofdbehandelaar' => null,
        'SubtrajectStartdatum' => null,
        'SubtrajectEinddatum' => null,
        'SubtrajectDeclaratiecode' => null,
        'SubtrajectDeclaratiecodeOmschrijving' => null,
        'SubtrajectDiagnosecode' => null,
        'SubtrajectDeclaratiebedrag' => null,
        'DeclaratieDebiteurnummer' => null,
        'DeclaratieDebiteurNaam' => null,
        'DebiteurTelefoon' => null,
        'DebiteurMailadres' => null,
        'DebiteurAdres' => null,
        'ZorgverzekeraarNaam' => null,
        'ZorgverzekeraarUZOVI' => null,
        'ZorgverzekeraarPakket' => null,
        'ReimburseAmount' => null,
        'EoLastFetched' => null,
        'EoStatus' => null,
        'Reminder1Sent' => null,
        'Reminder2Sent' => null,
        'BulkInvoiceNumber' => null,
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

        $table = $wpdb->prefix . App::INVOICES_TABLE;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta("CREATE TABLE IF NOT EXISTS {$table} (
          `id` bigint(20) unsigned not null auto_increment,
          `_CreatedDate` datetime,
          `DeclaratieNummer` bigint unsigned,
          `DeclaratieDatum` datetime,
          `DeclaratieregelOmschrijving` text,
          `DeclaratieBedrag` decimal(10,2),
          `DossierNUmmer` int unsigned,
          `DossierBehandellocatie` text,
          `DossierNaam` text,
          `SubtrajectNummer` bigint unsigned,
          `SubtrajectHoofdbehandelaar` text,
          `SubtrajectStartdatum` datetime,
          `SubtrajectEinddatum` datetime,
          `SubtrajectDeclaratiecode` text,
          `SubtrajectDeclaratiecodeOmschrijving` text,
          `SubtrajectDiagnosecode` int,
          `SubtrajectDeclaratiebedrag` decimal(10,2),
          `DeclaratieDebiteurnummer` bigint unsigned,
          `DeclaratieDebiteurNaam` text,
          `DebiteurTelefoon` text,
          `DebiteurMailadres` text,
          `DebiteurAdres` text,
          `ZorgverzekeraarNaam` text,
          `ZorgverzekeraarUZOVI` text,
          `ZorgverzekeraarPakket` text,
          `ReimburseAmount` decimal(10,2),
          `EoLastFetched` bigint(20) unsigned,
          `EoStatus` int unsigned,
          `Reminder1Sent` bigint(20) unsigned,
          `Reminder2Sent` bigint(20) unsigned,
          `BulkInvoiceNumber` bigint(20) unsigned,
          primary key(`id`)
        ) {$wpdb->get_charset_collate()};");

        if ( $db_version < 0.6 ) {
            $wpdb->query("alter table {$table} modify column `DeclaratieBedrag` decimal(10,2) not null");
            $wpdb->query("alter table {$table} modify column `SubtrajectDeclaratiebedrag` decimal(10,2) not null");
        }

        if ( $db_version < 0.8 ) {
            $wpdb->query("alter table {$table} add column `ReimburseAmount` decimal(10,2)");
        }

        if ( $db_version < 0.9 ) {
            $wpdb->query("alter table {$table} modify column `_CreatedDate` datetime");
            $wpdb->query("alter table {$table} modify column `DeclaratieNummer` bigint unsigned");
            $wpdb->query("alter table {$table} modify column `DeclaratieDatum` datetime");
            $wpdb->query("alter table {$table} modify column `DeclaratieBedrag` decimal(10,2)");
            $wpdb->query("alter table {$table} modify column `SubtrajectDeclaratiebedrag` decimal(10,2)");
        }

        if ( $db_version < 1.2 ) {
            $wpdb->query("alter table {$table} add column `EoLastFetched` bigint(20) unsigned");
            $wpdb->query("alter table {$table} add column `EoStatus` int unsigned");
            $wpdb->query("alter table {$table} add column `Reminder1Sent` bigint(20) unsigned");
            $wpdb->query("alter table {$table} add column `Reminder2Sent` bigint(20) unsigned");
        }
    }

    public static function prepareData( array $args, bool $extract_nums=true ) : array
    {
        $data = [];

        foreach ( ['DeclaratieregelOmschrijving','DossierBehandellocatie','DossierNaam','SubtrajectHoofdbehandelaar','SubtrajectDeclaratiecode','SubtrajectDeclaratiecodeOmschrijving','DeclaratieDebiteurNaam','DebiteurTelefoon','DebiteurMailadres','DebiteurAdres','ZorgverzekeraarNaam','ZorgverzekeraarUZOVI','ZorgverzekeraarPakket'] as $char )
            array_key_exists($char, $args) && ($data[$char] = trim($args[$char]));

        foreach ( ['DeclaratieBedrag','SubtrajectDeclaratiebedrag','ReimburseAmount'] as $float ) {
            array_key_exists($float, $args) && ($data[$float] = $extract_nums ? App::extractNum($args[$float]) : $args[$float]);
        }

        foreach ( ['DeclaratieNummer','DossierNUmmer','SubtrajectNummer','SubtrajectDiagnosecode','DeclaratieDebiteurnummer','EoLastFetched','EoStatus','Reminder1Sent','Reminder2Sent','BulkInvoiceNumber'] as $int )
            array_key_exists($int, $args) && ($data[$int] = (int) $args[$int]);

        foreach ( ['_CreatedDate','DeclaratieDatum','SubtrajectStartdatum','SubtrajectEinddatum'] as $date )
            array_key_exists($date, $args) && ($data[$date] = trim($args[$date]));

        return $data;
    }

    public static function insert( array $args, bool $extract_nums=true ) : int
    {
        global $wpdb;

        $data = self::prepareData( $args, $extract_nums );

        $wpdb->insert($wpdb->prefix . App::INVOICES_TABLE, $data);

        return $wpdb->insert_id;
    }

    public static function update( int $id, array $args, bool $extract_nums=true ) : bool
    {
        global $wpdb;
        $data = self::prepareData( $args, $extract_nums );
        unset($data['id']);
        return !! $wpdb->update($wpdb->prefix . App::INVOICES_TABLE, $data, compact('id'));
    }

    public static function delete( array $ids ) : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;
        return $wpdb->query("delete from {$table} where `id` in (" . join(',', array_map('intval', $ids)) . ")");
    }

    public static function deleteAll() : int
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;
        return $wpdb->query("delete from {$table}");
    }

    public static function query( array $args=[] ) : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;
        $sql = "select * from {$table} where 1=1";
        $exec = [];

        $int_fields = ['DeclaratieNummer','EoStatus'];
        $float_fields = ['DeclaratieBedrag','SubtrajectDeclaratiebedrag'];

        foreach ( ['id','_CreatedDate','DeclaratieNummer','DeclaratieBedrag','SubtrajectDeclaratiebedrag','SubtrajectHoofdbehandelaar','SubtrajectDeclaratiecode','DeclaratieDebiteurnummer','EoStatus'] as $prop ) {
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

            if ( $args["{$prop}_not_in_or_null"] ?? '' ) {
                $sql .= $wpdb->prepare(
                    " and ({$prop} not in (" . join( ',', array_fill(0, count((array) $args["{$prop}_not_in_or_null"]), $format) ) . ") or {$prop} is null)",
                    ...((array) $args["{$prop}_not_in_or_null"])
                );
            }

            if ( $args["{$prop}_ne_or_null"] ?? '' ) {
                $sql .= $wpdb->prepare(" and ({$prop} is null or {$prop} != {$format})", $args["{$prop}_ne_or_null"]);
            }

            if ( $args["{$prop}_eq_or_null"] ?? '' ) {
                $sql .= $wpdb->prepare(" and ({$prop} is null or {$prop} = {$format})", $args["{$prop}_eq_or_null"]);
            }
        }

        if ( $args['end_date_gte'] ?? '' ) {
            $sql .= $wpdb->prepare(' and `SubtrajectEinddatum` >= %s', $args['end_date_gte']);
        }

        if ( $args['end_date_lte'] ?? '' ) {
            $sql .= $wpdb->prepare(' and `SubtrajectEinddatum` <= %s', $args['end_date_lte']);
        }

        if ( $args['last_fetched_gte'] ?? '' ) {
            $sql .= $wpdb->prepare(' and `EoLastFetched` >= %s', $args['last_fetched_gte']);
        }

        if ( $args['last_fetched_lte'] ?? '' ) {
            $sql .= $wpdb->prepare(' and (`EoLastFetched` <= %s or `EoLastFetched` is null)', $args['last_fetched_lte']);
        }

        if ( $practitioner = ($args['practitioner'] ?? '') ) {
            $sql .= $wpdb->prepare(" and trim(substring_index(SubtrajectHoofdbehandelaar, ' - ', 1)) = %s", $practitioner);
        }

        if ( $location = ($args['location'] ?? '') ) {
            $sql .= $wpdb->prepare(" and trim(substring_index(substring_index(SubtrajectHoofdbehandelaar, ' - ', 2), ' - ', -1)) = %s", $location);
        }

        if ( $specialty = ($args['specialty'] ?? '') ) {
            $sql .= $wpdb->prepare(" and trim(substring_index(SubtrajectHoofdbehandelaar, ' - ', -1)) = %s", $specialty);
        }

        if ( $args['search'] ?? '' ) {
            $sql .= ' and (
                `DeclaratieregelOmschrijving` like \'%' . $wpdb->esc_like($args['search']) . '%\'
                or `DeclaratieNummer` like \'%' . $wpdb->esc_like($args['search']) . '%\'
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
            $args['per_page'] = get_option('zorgportal:invoices-per-page', 100);
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

        $data[$p='id'] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;
        $data[$p='_CreatedDate'] = is_null($data[$p] ?? null) ? null : trim($data[$p]);
        $data[$p='DeclaratieNummer'] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;
        $data[$p='DeclaratieDatum'] = is_null($data[$p] ?? null) ? null : trim($data[$p]);
        $data[$p='DeclaratieregelOmschrijving'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='DeclaratieBedrag'] = isset($data[$p]) ? floatval($data[$p]) : null;
        $data[$p='DossierNUmmer'] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;
        $data[$p='DossierBehandellocatie'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='DossierNaam'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='SubtrajectNummer'] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;
        $data[$p='SubtrajectHoofdbehandelaar'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='SubtrajectStartdatum'] = is_null($data[$p] ?? null) ? null : trim($data[$p]);
        $data[$p='SubtrajectEinddatum'] = is_null($data[$p] ?? null) ? null : trim($data[$p]);
        $data[$p='SubtrajectDeclaratiecode'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='SubtrajectDeclaratiecodeOmschrijving'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='SubtrajectDiagnosecode'] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;
        $data[$p='SubtrajectDeclaratiebedrag'] = isset($data[$p]) ? floatval($data[$p]) : null;
        $data[$p='DeclaratieDebiteurnummer'] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;
        $data[$p='DeclaratieDebiteurNaam'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='DebiteurTelefoon'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='DebiteurMailadres'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='DebiteurAdres'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='ZorgverzekeraarNaam'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='ZorgverzekeraarUZOVI'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='ZorgverzekeraarPakket'] = isset($data[$p]) ? trim($data[$p]) : null;
        $data[$p='ReimburseAmount'] = isset($data[$p]) ? floatval($data[$p]) : null;
        $data[$p='EoLastFetched'] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;
        $data[$p='EoStatus'] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;
        $data[$p='Reminder1Sent'] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;
        $data[$p='Reminder2Sent'] = is_numeric($data[$p] ?? null) ? intval($data[$p]) : null;

        return $data;
    }

    public static function getAllPractitioners() : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;
        $results = (array) $wpdb->get_results("select max(prac) as practitioner from (
            select trim(substring_index(SubtrajectHoofdbehandelaar, ' - ', 1)) as prac from {$table}
        ) q where q.prac != 'NULL' group by q.prac", ARRAY_A);
        return array_filter(wp_list_pluck($results, 'practitioner'));
    }

    public static function getAllLocations() : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;
        $results = (array) $wpdb->get_results("select max(specialty) as specialty from (
            select trim(substring_index(substring_index(SubtrajectHoofdbehandelaar, ' - ', 2), ' - ', -1)) as specialty from {$table}
        ) q where q.specialty != 'NULL' group by q.specialty", ARRAY_A);
        return array_filter(wp_list_pluck($results, 'specialty'));
    }

    public static function getAllSpecialties() : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;
        $results = (array) $wpdb->get_results("select max(location) as location from (
            select trim(substring_index(SubtrajectHoofdbehandelaar, ' - ', -1)) as location from {$table}
        ) q where q.location != 'NULL' group by q.location", ARRAY_A);
        return array_filter(wp_list_pluck($results, 'location'));
    }

    public static function getAllDbcCodes() : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;
        $results = (array) $wpdb->get_results("select max(SubtrajectDeclaratiecode) as dbc_code from {$table} q where SubtrajectDeclaratiecode != 'NULL' group by SubtrajectDeclaratiecode", ARRAY_A);
        return array_filter(wp_list_pluck($results, 'dbc_code'));
    }

    public static function getMatchingLocations( string $practioner_name, string $specialty ) : array
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;
        $results = (array) $wpdb->get_results("select max(location) as location from (
            select trim(substring_index(substring_index(SubtrajectHoofdbehandelaar, ' - ', 2), ' - ', -1)) as location, SubtrajectHoofdbehandelaar from {$table}
        ) q where
            q.location != 'NULL' and q.location > ''
            and q.SubtrajectHoofdbehandelaar like '" . $wpdb->esc_like($practioner_name) . " - %'
            and q.SubtrajectHoofdbehandelaar like '% - " . $wpdb->esc_like($specialty) . "'
        group by q.location", ARRAY_A);
        return array_filter(wp_list_pluck($results, 'location'));
    }

    public static function printStatus( array $invoice )
    {
        switch ( $invoice['EoStatus'] ?? null ) {
            case self::PAYMENT_STATUS_PAID:
                echo __('Paid', 'zorgportal'); break;

            case self::PAYMENT_STATUS_DUE:
                echo __('Open', 'zorgportal'); break;

            case self::PAYMENT_STATUS_OVERDUE:
                echo __('Over-due', 'zorgportal'); break;

            default:
                echo __('Open', 'zorgportal'); break;
        }
    }

    public static function updateEoStatus(array $invoice, array $tokens, int $division, App $appContext, bool $send_notices=false) : string
    {
        if ( $appContext::USE_EO_RECEIVABLES_LIST_API ) {
            $apiUrl = sprintf('https://start.exactonline.nl/api/v1/%1$s/read/financial/ReceivablesList/?$filter=YourRef eq \'%2$s\'&$select=*', $division, $invoice['DeclaratieNummer']);
        } else {
            $apiUrl = sprintf('https://start.exactonline.nl/api/v1/%1$s/financialtransaction/TransactionLines/?$filter=(substringof(\'%2$s\',Notes) or YourRef eq \'%2$s\' or substringof(\'%2$s\',Description)) and Type eq 40 and GLAccountCode eq \'1100\'&$select=*', $division, $invoice['DeclaratieNummer']);
        }

        list( $res, $error, $res_obj ) = App::callEoApi($apiUrl, [
            'method' => 'GET',
            'headers' => [
                'Authorization' => "bearer {$tokens['access_token']}",
                'Accept' => 'application/json',
            ],
            'timeout' => 20,
        ]);

        if ( ! $res )
            return $error ?: __('Bad response from API server.', 'zorgportal');

        $data = json_decode($res, true);

        $result = $data['d']['results'][0] ?? null;
        $days_past = (time() - ($dec_time=strtotime($invoice['DeclaratieDatum']))) /DAY_IN_SECONDS;

        if ( ! isset($data['d']['results']) ) // bad response from api server
            return __('Bad response from API server.', 'zorgportal');

        $is_paid = $appContext::USE_EO_RECEIVABLES_LIST_API ? ! $result : $result;

        if ( ! $is_paid ) { // invoice not found/paid
            self::update($invoice['id'], [
                'EoLastFetched' => time(),
                'EoStatus' => $days_past < 28 ? self::PAYMENT_STATUS_DUE : self::PAYMENT_STATUS_OVERDUE,
            ]);

            if ( $send_notices ) {
                $patient = Patients::queryOne(['id' => $invoice['DeclaratieDebiteurnummer']]);

                if ( $patient && is_email($patient['email']) ) {
                    if ( $dec_time && $days_past >= (28 + 14) && ! $invoice['Reminder1Sent'] ) {
                        self::sendFirstReminder( compact('invoice', 'patient'), $appContext ) && ($update['Reminder1Sent'] = time());
                    }

                    if ( $dec_time && $days_past >= (28 + 14 + 7) && ! $invoice['Reminder2Sent'] ) {
                        self::sendSecondReminder( compact('invoice', 'patient'), $appContext ) && ($update['Reminder2Sent'] = time());
                    }
                }
            }

            return __('Invoice marked as due/overdue', 'zorgportal');
        }

        // invoice is paid
        $status = self::PAYMENT_STATUS_PAID;

        // save item
        $txn = Transactions::parseApiItem( $result ?: [] );

        if ( $existing = Transactions::queryOne(['GUID' => $txn['GUID']]) ) {
            Transactions::update($existing['id'], $txn);
        } else {
            Transactions::insert($txn);
        }

        self::update($invoice['id'], [
            'EoStatus' => $status,
            'EoLastFetched' => time(),
        ]);

        return __('Invoice marked as paid', 'zorgportal');
    }

    private static function extractPossibleInvoiceNumbers(string $search) : array
    {
        $search = preg_replace('/[^\d]/', ' ', $search);
        return array_filter(array_unique(array_map('intval', explode(' ', $search))));
    }

    /*
    *  Implement getBankTransaction On Import Invoices
    *
    * @param string $from, First invoice date
    * @param string $to, Today
    * @param App $appContext
    * @return boolean
    */
    public static function eoBulkRetrieveInvoices( string $from, string $to, App $appContext )
    {
        if ( ! $division_code = $appContext->getCurrentDivisionCode() )
            return;

        $results = [];
       
        set_time_limit(0);

        // Implement Sync Calls

        self::_eoBulkRetrieveInvoices($results, sprintf('https://start.exactonline.nl/api/v1/%s/financialtransaction/TransactionLines/?$filter=Type eq 40 and GLAccountCode eq \'1100\' and (Date gt datetime\'%s\' and Date le datetime\'%s\')&$select=ID,AccountName,AmountDC,AmountFC,Created,Date,Modified,Description,DocumentSubject,EntryNumber,GLAccountCode,GLAccountDescription,InvoiceNumber,JournalCode,JournalDescription,Notes,FinancialPeriod,FinancialYear,PaymentReference,Status,Type,YourRef', $division_code, $from, $to), $appContext);

        $search = join(' ', array_map(function($payment)
        {
            return join(' ', [$payment['Notes'] ?? '', $payment['YourRef'] ?? '', $payment['Description'] ?? '']);
        }, $results));

        $ids = array_filter(self::extractPossibleInvoiceNumbers($search), function($num)
        {
            return 8 == strlen((string) $num);
        });

        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;

        // disabled
        // foreach ( array_chunk($ids, 1000) as $bulk ) {
        //     // set invoices as paid
        //     $wpdb->query($wpdb->prepare(
        //         "update {$table} set EoStatus = %d where DeclaratieNummer in (" . join( ',', array_fill(0, count($bulk), '%d') ) . ')',
        //         self::PAYMENT_STATUS_PAID,
        //         ...$bulk
        //     ));
        // }

        // save transaction
        $txns = array_values(array_filter(array_map(function($id) use ($results)
        {
            foreach ( $results as $payment ) {
                if ( ($payment['YourRef'] ?? '') == $id )
                    return Transactions::parseApiItem($payment);
            }

            foreach ( $results as $payment ) {
                $search = join(' ', [$payment['Notes'] ?? '', $payment['YourRef'] ?? '', $payment['Description'] ?? '']);

                if ( in_array($id, self::extractPossibleInvoiceNumbers($search)) ) {
                    $payment['YourRef'] = $id; // not filled by employee, found in notes
                    return Transactions::parseApiItem($payment);
                }
            }
        }, $ids)));

        foreach ( array_chunk($txns, 100) as $bulk ) {
            Transactions::insertBulk( $bulk );
        }

        // delete orphaned transactions
        Transactions::deleteOrphaned();
    }

    public static function eoSyncRetrieveInvoices( string $from, string $to, App $appContext )
    {
        if ( ! $division_code = $appContext->getCurrentDivisionCode() )
            return;

        $results = [];
       
        set_time_limit(0);

        // Sync call
        self::_eoBulkRetrieveInvoices($results, sprintf("https://start.exactonline.nl/api/v1/%s/read/sync/Sync/SyncTimestamp?modified=datetime'%s'&endPoint='TransactionLines'", $division_code, date('Y-m-d')), $appContext);

        self::_eoBulkRetrieveInvoices($results, sprintf('https://start.exactonline.nl/api/v1/%s/sync/Financial/TransactionLines?$filter=Timestamp gt \'%s\' and Type eq 40 and GLAccountCode eq \'1100\' and (Date gt datetime\'%s\' and Date le datetime\'%s\')&$select=ID,AccountName,AmountDC,AmountFC,Created,Date,Modified,Description,DocumentSubject,EntryNumber,GLAccountCode,GLAccountDescription,InvoiceNumber,JournalCode,JournalDescription,Notes,FinancialPeriod,FinancialYear,PaymentReference,Status,Type,YourRef', $division_code, $results['TimeStampAsBigInt'], $from, $to), $appContext);

        $search = join(' ', array_map(function($payment)
        {
            return join(' ', [$payment['Notes'] ?? '', $payment['YourRef'] ?? '', $payment['Description'] ?? '']);
        }, $results));

        $ids = array_filter(self::extractPossibleInvoiceNumbers($search), function($num)
        {
            return 8 == strlen((string) $num);
        });


        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;

        // save transaction
        $txns = array_values(array_filter(array_map(function($id) use ($results)
        {
            foreach ( $results as $payment ) {
                if ( ($payment['YourRef'] ?? '') == $id )
                    return Transactions::parseApiItem($payment);
            }

            foreach ( $results as $payment ) {
                $search = join(' ', [$payment['Notes'] ?? '', $payment['YourRef'] ?? '', $payment['Description'] ?? '']);

                if ( in_array($id, self::extractPossibleInvoiceNumbers($search)) ) {
                    $payment['YourRef'] = $id; // not filled by employee, found in notes
                    return Transactions::parseApiItem($payment);
                }
            }
        }, $ids)));


        foreach ( array_chunk($txns, 100) as $bulk ) {
            Transactions::insertBulk( $bulk );
        }

        // delete orphaned transactions
        Transactions::deleteOrphaned();
    }

    public static function eoBulkRetrieveReceivables( string $from, string $to, array $invoice_numbers, App $appContext )
    { 
        if ( ! $division_code = $appContext->getCurrentDivisionCode() )
            return;

        $results = [];

        set_time_limit(0);
        self::_eoBulkRetrieveInvoices($results, sprintf('https://start.exactonline.nl/api/v1/%s/read/financial/ReceivablesList/?$filter=InvoiceDate gt datetime\'%s\' and InvoiceDate le datetime\'%s\'&$select=*', $division_code, $from, $to), $appContext);

        $search = join(' ', array_map(function($payment)
        {
            return join(' ', [$payment['Notes'] ?? '', $payment['YourRef'] ?? '', $payment['Description'] ?? '']);
        }, $results));

        $ids = array_filter(self::extractPossibleInvoiceNumbers($search), function($num)
        {
            return 8 == strlen((string) $num);
        });

        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;

        foreach ( array_chunk($ids, 1000) as $bulk ) {
            // set invoices as due
            $wpdb->query($wpdb->prepare(
                "update {$table} set EoStatus = %d where cast(datediff(now(), `DeclaratieDatum`) as unsigned) < 28 and `DeclaratieNummer` in (" . join( ',', array_fill(0, count($bulk), '%d') ) . ')',
                self::PAYMENT_STATUS_DUE,
                ...$bulk
            ));

            // set invoices as overdue
            $wpdb->query($wpdb->prepare(
                "update {$table} set EoStatus = %d where cast(datediff(now(), `DeclaratieDatum`) as unsigned) >= 28 and `DeclaratieNummer` in (" . join( ',', array_fill(0, count($bulk), '%d') ) . ')',
                self::PAYMENT_STATUS_OVERDUE,
                ...$bulk
            ));
        }

        // ids not returned in receivables list
        $paid_ids = array_diff($invoice_numbers, $ids);

        foreach ( array_chunk($paid_ids, 1000) as $bulk ) {
            // set invoices as paid
            $wpdb->query($wpdb->prepare(
                "update {$table} set EoStatus = %d where `DeclaratieNummer` in (" . join( ',', array_fill(0, count($bulk), '%d') ) . ')',
                self::PAYMENT_STATUS_PAID,
                ...$bulk
            ));
        }
    }

    public static function eoBulkCheckUnpaidInvoices( string $from, string $to, App $appContext )
    {
        if ( ! $division_code = $appContext->getCurrentDivisionCode() )
            return;

        $results = [];

        set_time_limit(0);
    
        self::_eoBulkRetrieveInvoices($results, sprintf('https://start.exactonline.nl/api/v1/%s/bulk/Cashflow/Receivables/?$filter=InvoiceDate gt datetime\'%s\' and InvoiceDate le datetime\'%s\'&$select=AccountCode,AccountName,AmountDC,BankAccountNumber,Created,CreatorFullName,Description,Division,DueDate,EndDate,EndYear,EndPeriod,EntryDate,EntryNumber,GLAccountCode,GLAccountDescription,InvoiceDate,InvoiceNumber,IsFullyPaid,Journal,JournalDescription,LastPaymentDate,Modified,ModifierFullName,Source,Status,TransactionAmountDC,TransactionReportingPeriod,PaymentCondition,PaymentConditionDescription,PaymentDays,PaymentMethod,PaymentReference,YourRef', $division_code, $from, $to), $appContext);

        $ids = array_filter(array_unique(array_map(function($payment)
        {
            return intval(($payment['YourRef'] ?? '') ?: ($payment['PaymentReference'] ?? ''));
        }, $results)));

        $ids = array_filter($ids, function($num)
        {
            return 8 == strlen((string) $num);
        });

        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;

        foreach ( array_chunk($ids, 1000) as $bulk ) {
            // set invoices as paid
            $wpdb->query($wpdb->prepare(
                "update {$table} set EoStatus = %d where DeclaratieNummer in (" . join( ',', array_fill(0, count($bulk), '%d') ) . ')',
                self::PAYMENT_STATUS_PAID,
                ...$bulk
            ));
        }
    }

    private static function _eoBulkRetrieveInvoices( array &$ref, string $apiUrl, App $appContext )
    {
        if ( ! $tokens = get_option('zp_exactonline_auth_tokens') )
            return;
        
        if ( ! ( $tokens['access_token'] ?? null ) )
            return;

        list( $res, $error, $res_obj ) = App::callEoApi($apiUrl, [
            'method' => 'GET',
            'headers' => [
                'Authorization' => "bearer {$tokens['access_token']}",
                'Accept' => 'application/json',
            ],
            'timeout' => 20,
        ]);

        if ( $error ) {
            error_log('Invoices cron update api error: ' . $error . PHP_EOL);
            return;
        }

        if ( ! $res )
            return;

        $data = json_decode($res, true);

        $ref = array_merge($ref, $data['d']['results'] ?? []);

        if ( $data['d']['__next'] ?? null )
            return self::_eoBulkRetrieveInvoices( $ref, $data['d']['__next'], $appContext );
    }

    private static function _eoSyncRetrieveInvoices( array &$ref, string $apiUrl, App $appContext )
    {
        if ( ! $tokens = get_option('zp_exactonline_auth_tokens') )
            return;

        if ( ! ( $tokens['access_token'] ?? null ) )
            return;
        
        list( $res, $error, $res_obj ) = App::callEoApi($apiUrl, [
            'method' => 'GET',
            'headers' => [
                'Authorization' => "bearer {$tokens['access_token']}",
                'Accept' => 'application/json',
            ],
            'timeout' => 20,
        ]);

        if ( $error ) {
            error_log('Invoices cron update api error: ' . $error . PHP_EOL);
            return;
        }

        if ( ! $res )
            return;

        $data = json_decode($res, true);

        $ref = array_merge($ref, $data['d']['results'] ?? []);

        if ( $data['d']['__next'] ?? null )
            return self::_eoSyncRetrieveInvoices( $ref, $data['d']['__next'], $appContext );
    }

    public static function sendFirstReminder( array $vars, App $appContext, bool $get_email_contents=false )
    {
        extract($vars);

        // @todo enable once we start emailing patients
        // if ( ! is_email( $patient['email'] ?? '' ) )
        //     return false;

        $subject = 'ATTENTIE OPENSTAANDE NOTA REACTIEDATUM ' . ($due_date_formatted=date('d M Y', strtotime($invoice['DeclaratieDatum']) + (28 + 14) * DAY_IN_SECONDS));

        $decimalcomma = function( ?float $num ) : string
        {
            return str_replace('.', ',', strval( number_format($num, 2) ));
        };

        $plugin_dir_url = plugin_dir_url( $appContext->getPluginFile() );

        ob_start();
        include(plugin_dir_path( $appContext->getPluginFile() ) . '/src/templates/invoice-reminder-1.php');
        $body = wpautop(trim(ob_get_clean()));

        $notify_email = 'ibo.10@live.nl, elhardoum3@gmail.com'; // @todo use $patient['email'] once tested

        if ( $get_email_contents )
            return compact('subject', 'body', 'notify_email');

        return wp_mail($notify_email, $subject, $body, [
            'content-type: text/html; charset=utf-8'
        ]);
    }

    public static function sendSecondReminder( array $vars, App $appContext, bool $get_email_contents=false )
    {
        extract($vars);

        // @todo enable once we start emailing patients
        // if ( ! is_email( $patient['email'] ?? '' ) )
        //     return false;

        $subject = 'ATTENTIE OPENSTAANDE NOTA REACTIEDATUM ' . ($due_date_formatted=date('d M Y', strtotime($invoice['DeclaratieDatum']) + (28 + 14 + 7) * DAY_IN_SECONDS));

        extract(self::getCollectionsValues( $invoice['ReimburseAmount'] ));

        $decimalcomma = function( ?float $num ) : string
        {
            return str_replace('.', ',', strval( number_format($num, 2) ));
        };

        $plugin_dir_url = plugin_dir_url( $appContext->getPluginFile() );
        
        ob_start();
        include(($plugin_dir=plugin_dir_path( $appContext->getPluginFile() )) . '/src/templates/invoice-reminder-2.php');
        $body = wpautop(trim(ob_get_clean()));

        $notify_email = 'ibo.10@live.nl, elhardoum3@gmail.com'; // @todo use $patient['email'] once tested

        if ( $get_email_contents )
            return compact('subject', 'body', 'notify_email');

        // zend-lib is abandoned and will need upgrades in the future
        $pdf = \ZendPdf\PdfDocument::load($plugin_dir . 'src/assets/reminder-2-template.pdf');
        $page = $pdf->pages[0];
        // not compatible
        // $font = \ZendPdf\Font::fontWithPath( $plugin_dir . 'src/assets/MyriadPro-Regular.ttf' );
        $font = \ZendPdf\Font::fontWithName(\ZendPdf\Font::FONT_HELVETICA);
        $page->setFont($font, 9);

        $page->drawText($invoice['DeclaratieDebiteurNaam'], 90, 618, 'UTF-8');
        $page->drawText($invoice['DebiteurAdres'], 90, 618 - 9*1.2*1, 'UTF-8');

        $page->drawText('UZOVI: ' . ($invoice['ZorgverzekeraarUZOVI'] ?? ''), 90, 618 - 9*1.2*6, 'UTF-8');
        $page->drawText($invoice['ZorgverzekeraarNaam'], 90, 618 - 9*1.2*7, 'UTF-8');

        $page->setFont($font, 8);
        $page->drawText(preg_replace('/\s\d+\:.+$/', '', $invoice['DeclaratieDatum']), 388, 623, 'UTF-8');
        $page->drawText($invoice['DeclaratieNummer'], 424, 605, 'UTF-8');

        foreach ( str_split($invoice['SubtrajectDeclaratiecodeOmschrijving'], 35) as $i => $line ) {
            if ( $i > 3 ) break;
            $page->drawText(trim($line), 90, 492 - 8*1.2*$i, 'UTF-8');
        }

        $page->drawText(preg_replace('/\s\d+\:.+$/', '', $invoice['SubtrajectStartdatum']), 230, 492, 'UTF-8');
        $page->drawText(preg_replace('/\s\d+\:.+$/', '', $invoice['SubtrajectEinddatum']), 293, 492, 'UTF-8');

        foreach ( str_split($invoice['DeclaratieregelOmschrijving'], 35) as $i => $line ) {
            if ( $i > 3 ) break;
            $page->drawText(trim($line), 355, 492 - 8*1.2*$i, 'UTF-8');
        }

        $page->drawText($invoice['DeclaratieBedrag'] . ' €', 490, 492, 'UTF-8');

        $page->drawText($invoice['SubtrajectDeclaratiecode'], 150, 337.5, 'UTF-8');
        $page->drawText("Boer, R.D.H. de (Remco) {$invoice['SubtrajectHoofdbehandelaar']}", 165, 254, 'UTF-8');
        
        $price = $invoice['DeclaratieBedrag'] . ' €';
        $page->drawText($price, 575 - strlen($price) * 2.75, 208, 'UTF-8');

        $tmpdir = tempnam(sys_get_temp_dir(), 'zp-pdfs');
        @unlink($tmpdir);
        mkdir($tmpdir);
        $pdf->save($filename = $tmpdir . '/' . "Factuur {$invoice['DeclaratieNummer']} {$invoice['DeclaratieDebiteurNaam']}.pdf");

        return wp_mail($notify_email, $subject, $body, [
            'content-type: text/html; charset=utf-8',
        ], [ $filename, $plugin_dir . 'src/assets/Begeleidende brief indienen zorgverzekeraar.pdf' ]);
    }

    // go to https://www.flanderijn.nl/opdrachtgevers/diensten/minnelijke-incasso/incassokosten/calculator/ (view-source)
    // find js bundle file
    // deobfuscate using unminify.com
    // find the formula and convert variables to php
    public static function getCollectionsValues( float $value )
    {
        $exclBtw = 0;
        if ($value <= 2500) {
            $exclBtw = $value * 0.15;
        } else if ($value <= 5000) {
            $first = 2500;
            $second = $value - $first;
            $exclBtw = $first * 0.15 + $second * 0.1;
        } else if ($value <= 10000) {
            $first = 2500;
            $second = 2500;
            $third = $value - ($first + $second);
            $exclBtw = $first * 0.15 + $second * 0.1 + $third * 0.05;
        } else if ($value <= 200000) {
            $first = 2500;
            $second = 2500;
            $third = 5000;
            $fourth = $value - ($first + $second + $third);
            $exclBtw = $first * 0.15 + $second * 0.1 + $third * 0.05 + $fourth * 0.01;
        } else {
            $first = 2500;
            $second = 2500;
            $third = 5000;
            $fourth = 190000;
            $fifth = $value - ($first + $second + $third + $fourth);
            $exclBtw = $first * 0.15 + $second * 0.1 + $third * 0.05 + $fourth * 0.01 + $fifth * 0.005;
        }
        if ($exclBtw < 40) {
            $exclBtw = 40;
        } else if ($exclBtw > 6775) {
            $exclBtw = 6775;
        }
        $exclBtw = round($exclBtw, 2);
        $btw = $exclBtw * 0.21;
        $btw = round($btw, 2);
        $inclBtw = $btw * 1 + $exclBtw * 1;
        $inclBtw = round($inclBtw, 2);

        return compact('btw', 'inclBtw', 'exclBtw');
    }

    public static function getMatchingInvoices( array $chkArr )
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;
        $addWhere = "where 1=1";
        $extract_num = [App::class, 'extractNum'];

        if(!empty($chkArr)) {
            foreach ($chkArr[0] as $key => $value) {
                $temp = $chkArr[1][$key];
                if($value == "DeclaratieBedrag" || $value == "SubtrajectDeclaratiebedrag")  $temp = $extract_num($temp);
                if($value == "DeclaratieDatum" || $value == "SubtrajectStartdatum" || $value == "SubtrajectEinddatum" ) { 
                      $temp = date_format(date_create($temp),"Y-m-d H:i:s");
                } 
                $addWhere .= " and ".$value."='".$temp."'";
            }

        }
        // If invoice record already exist, then return true else false
        $res =  (array) $wpdb->get_results("select IF(count(id) > 0,'true','false')as chk from {$table} ".$addWhere);
        
        return $res;
    }

    public static function bulkInvoice( array $ids, App $appContext )
    {
        global $wpdb;
        $table = $wpdb->prefix . App::INVOICES_TABLE;
        $BulkTbl = $wpdb->prefix . App:: BULKINVOICE_TABLE ;

        $maxBulk = (array) $wpdb->get_results("select max(id)as maxId from {$BulkTbl} where 1=1");
        $max = $maxBulk[0]->maxId == 0 ? "43000000" : $maxBulk[0]->maxId + 1; 

            $sQuery = "select sum(SubtrajectDeclaratiebedrag)as Atotal, sum(ReimburseAmount)as Rtotal, max(DeclaratieDatum)as BillDate,count(id)as idCnt from {$table} where `id` in (" . join(',', array_map('intval', $ids)) . ") and (BulkInvoiceNumber IS NULL OR BulkInvoiceNumber = 0)";

            $uQuery = "update {$table} set BulkInvoiceNumber = '".$max."' where `id` in (" . join(',', array_map('intval', $ids)) . ') and (BulkInvoiceNumber IS NULL OR BulkInvoiceNumber = 0)';

            $idQuery = "select GROUP_CONCAT(DeclaratieNummer SEPARATOR ',')as idGroup, max(DeclaratieDatum)as endDate, min(DeclaratieDatum)as startDate from {$table} where `id` in (" . join(',', array_map('intval', $ids)) . ") and (BulkInvoiceNumber IS NULL OR BulkInvoiceNumber = 0)";

        $mount = (array) $wpdb->get_results($sQuery);
        $idGroup = (array) $wpdb->get_results($idQuery, ARRAY_A);
        $update = $wpdb->query($uQuery);

        if($mount[0]->idCnt > 0 ) {

            $now = new DateTime();
            $interval = new DateInterval('P28D'); // P28D means "28 Days Interval"
            $iData ['id'] = $max; 
            $iData ['_CreatedDate'] = date("Y/m/d H:i:s"); 
            $iData ['NumberInvoices'] = $mount[0]->idCnt; 
            $iData ['Date'] =  date("Y/m/d"); 
            $iData ['DueDate'] = $now->add($interval)->format('Y/m/d');
            $iData ['ReimburseTotal'] = $mount[0]->Rtotal;  
            $iData ['AmountTotal'] = $mount[0]->Atotal;  
            $iData ['Practitioner'] = "VGZ - Bulkfactuur"; 
            $iData ['Location'] = "Excellent Klinieken Zoetermeer"; 
            $iData ['Insurer'] = "VGZ Zorgverzekeraar N.V"; 
            $iData ['Policy'] = "-"; 
            $iData ['Status'] = 0; 
            $iData ['SingleInvoices'] = $idGroup[0]['idGroup']; 
            $wpdb->insert($BulkTbl, $iData);

            //Call BankTransaction API
            $startDate = new DateTime($idGroup[0]['startDate']);

            BulkInvoice::eoSyncRetrieveInvoices(strval($startDate->format('Y-m-d')), strval(date('Y-m-d')), $appContext);

            return $wpdb->insert_id;
        }
        return $mount[0]->idCnt; 
    }
}