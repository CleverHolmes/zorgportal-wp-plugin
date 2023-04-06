<?php

namespace Zorgportal;
use Zorgportal\App;
use DateTime;
use DateInterval;

class BulkInvoice
{
    const COLUMNS = [
        'id' => null,
        '_CreatedDate' => null,
        'NumberInvoices' => null,
        'Date' => null,
        'DueDate' => null,
        'AmountTotal' => null,
        'ReimburseTotal' => null,
        'Location' => null,
        'Insurer' => null,
        'Policy' => null,
        'Status' => null,
        'SingleInvoices' => null    
    ];

    const IMPORTED = 0;
    const PAID = 0;
    const DUE = 1;
    const CREDITED = 0;
    const COLLECTION = 0;

    const PAYMENT_STATUSES = [
        self::IMPORTED,
        self::PAID,
        self::DUE,
        self::CREDITED,
        self::COLLECTION,
    ];

    const INVOICE = 1;
    const BULKINVOICE = 0;

    const INVOCE_TYPE = [
        self::INVOICE,
        self::BULKINVOICE,
    ];   

    public static function setupDb( float $db_version=0 )
    {
        global $wpdb;

        $table = $wpdb->prefix . App::BULKINVOICE_TABLE;
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
          `SingleInvoices` text,
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
        $sql .= in_array(strtolower($args['order'] ?? ''), ['asc', 'desc']) ? strtolower($args['order'] ?? '') : 'asc';
     
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

    public static function getMaxId()
    {
        global $wpdb;
        $table = $wpdb->prefix . App::BULKINVOICE_TABLE;

        $maxBulk = (array) $wpdb->get_results("select max(id)as maxId from {$table} where 1=1");
        $max = $maxBulk[0]->maxId == 0 ? "43000000" : $maxBulk[0]->maxId + 1; 

        return $max;
    }
    
    public static function insert( array $list ) 
    {
        global $wpdb;
        $BulkTbl = $wpdb->prefix . App:: BULKINVOICE_TABLE ;

        $now = new DateTime();
        $interval = new DateInterval('P28D'); // P28D means "28 Days Interval"
        $iData ['id'] = $list['id']; 
        $iData ['_CreatedDate'] = date("Y/m/d H:i:s"); 
        $iData ['NumberInvoices'] = $list['NumberInvoices']; 
        $iData ['Date'] =  date("Y/m/d"); 
        $iData ['DueDate'] = $now->add($interval)->format('Y/m/d');
        $iData ['ReimburseTotal'] = $list['ReimburseTotal'];  
        $iData ['AmountTotal'] = $list['AmountTotal'];  
        $iData ['Practitioner'] = "VGZ - Bulkfactuur"; 
        $iData ['Location'] = "Excellent Klinieken Zoetermeer"; 
        $iData ['Insurer'] = "VGZ Zorgverzekeraar N.V"; 
        $iData ['Policy'] = "-"; 
        $iData ['Status'] = 0; 
        $iData ['SingleInvoices'] = join(',', array_map('intval', $list['invoiceList'])); 

        $wpdb->insert($BulkTbl, $iData);
        return $wpdb->insert_id;
    }

    public static function update( int $id, array $args, bool $extract_nums=true ) : bool
    {
        global $wpdb;
        $data = self::prepareData( $args, $extract_nums );
        unset($data['id']);
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

    public static function eoBulkRetrieveInvoices( string $from, string $to, App $appContext )
    {
        if ( ! $division_code = $appContext->getCurrentDivisionCode() )
            return;

        $results = [];
       
        set_time_limit(0);
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
        ], [ $filename, $plugin_dir . 'src/ assets/Begeleidende brief indienen zorgverzekeraar.pdf' ]);
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
    

}