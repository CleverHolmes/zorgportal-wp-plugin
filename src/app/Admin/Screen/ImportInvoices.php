<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\DbcCodes as Codes;
use Zorgportal\App;
use Zorgportal\Practitioners;
use Zorgportal\Invoices;
use Zorgportal\BulkInvoice;
use Zorgportal\Patients;
use WP_REST_Response;
use WP_REST_Request;
use DateTime;
use DateInterval;

class ImportInvoices extends Screen
{
    public function render()
    {
        return $this->renderTemplate('import-invoices.php', [
            'nonce' => wp_create_nonce('zorgportal'),
            'baseUrl' => trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() )),
        ]);
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_script( 'zportal-invoices', "{$base}src/assets/js/import-invoices.js", ['jquery'], $this->appContext::SCRIPTS_VERSION, 1 );
        wp_localize_script('zportal-invoices', 'ZORGPORTAL_I18N', [
            'ajaxUrl' => rest_url('zorgportal/v1/%s?_wpnonce=' . wp_create_nonce('wp_rest')),
            'error' => __('Error occurred, please try again.', 'zorgportal'),
            'colPlaceholder' => __('(column %s)', 'zorgportal'),
            'err_no_rows' => __('No rows loaded. Please upload a file and wait for it to be loaded.', 'zorgportal'),
            'err_in_rows' => __('Invoices already exists. Please check file and try again.', 'zorgportal'),
            'err_file_structure' => __('File Structure is incorrect. Please check file and try again.', 'zorgportal'),
            'pre_select_headers' => [
                'DeclaratieNummer',
                'SubtrajectStartdatum',
                'DeclaratieDatum',
                'DeclaratieBedrag',
                'SubtrajectDeclaratiecode',
                'DossierBehandellocatie',
                'SubtrajectHoofdbehandelaar',
                'SubtrajectDeclaratiecodeOmschrijving',
                'DeclaratieDebiteurNaam',
                'DeclaratieDebiteurnummer',
                'DebiteurMailadres',
                'DebiteurTelefoon',
                'DebiteurAdres',
                'ZorgverzekeraarNaam',
                'ZorgverzekeraarPakket',
            ],
            'status_ok_header' => __('%d invoices adjusted accordingly', 'zorgportal'),
            'codes_404_pre' => __('%d unrecognized DBC codes:', 'zorgportal'),
            'policy_404_pre' => __('%d unrecognized invoices:', 'zorgportal'),
            'policy_save' => __('Save', 'zorgportal'),
            'invoices_code_column_name' => 'DBC Code',
            'invoices_insurance_company_column_name' => 'Insurance company',
            'invoices_insurance_policy_column_name' => 'Insurance policy',
            'invoices_date_column_name' => 'Treatment Date',
            'invoices_omschrijving_column_name' => 'SubtrajectDeclaratiecodeOmschrijving',
            'invoices_total_amount_column_name' => 'Amount',
            'applied_date_html' => call_user_func(function()
            {
                ob_start();
                $this->renderTemplate('applied-date.php');
                return ob_get_clean();
            }),
            'error_date_invalid' => __('Please select a valid year or date range.', 'zorgportal'),
            'baseUrl' => trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() )),
        ]);
    }

    public function restImport() : WP_REST_Response
    {   
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wp_rest' ) )
            return new WP_REST_Response(null, 401);

        set_time_limit(0);
        $rows = json_decode(wp_unslash($_POST['rows_json'] ?? '[]'), 1);

        $errors = [];

        if ( ! is_array($rows) || count($rows) < 2 || ! is_array( $rows[0] ) ) {
            $errors []= __('Empty dataset supplied.', 'zorgportal');
        } else {
            $map = [
                'Invoice ID' => null,
                'Treatment Date' => null,
                'Invoice Date' => null,
                'Total Amount' => null,
                'Dbc Code' => null,
                // 'Patient Policy' => null,
                'Location' => null,
                // 'Insurer' => null,
                'Practitioner' => null,
                'Omschrijving' => null,
                'Patient name' => null,
                'Patient ID' => null,
                'Patient email' => null,
                'Patient phone' => null,
                'Patient address' => null,
                'Patient Insurer' => null,
                'Patient Policy' => null,
            ];

            foreach ( $rows[0] as $col => $val ) {
                foreach ( array_keys($map) as $field ) {
                    if ( is_array($_POST['fields_map'][$field] ?? null) ) {
                        foreach ( $_POST['fields_map'][$field] as $subcol ) {
                            if ( in_array($subcol, [$val, "__col::{$col}"]) )
                                array_push($map[$field], $col);
                        }
                    } else if ( in_array(($_POST['fields_map'][$field] ?? ''), [$val, "__col::{$col}"]) ) {
                        $map[$field] = $col;
                    }
                }

            }

            // if ( count($map) != count(array_filter($map)) )
            //     $errors []= __('One or more fields not mapped to their value location.', 'zorgportal');
        }

        if ( $errors )
            return new WP_REST_Response(['success' => false, 'errors' => $errors]);

        $map['SubtrajectDeclaratiecodeOmschrijving'] = $map['Omschrijving'] ?? null;

        $entries = [];
        $codes_404 = $policy_404 = [];
        $success_decr = 0;
        $headers = [];

        $extract_num = [App::class, 'extractNum'];

        $getAllIds = function($rows, $headers) use ($extract_num)
        {
            return array_map(function($row) use ($extract_num, $headers)
            {
                return $extract_num($row[$headers['DeclaratieNummer'] ?? ''] ?? null);
            }, $rows);
        };

        $invoice_dates = $invoice_numbers = [];
        $ignore_duplicates = [];
        $bulkInvoice = [];
        $bulkInvoice['id'] = BulkInvoice::getMaxId();
        $bulkInvoice['NumberInvoices'] = 0;
        $bulkInvoice['AmountTotal'] = 0;
        $bulkInvoice['ReimburseTotal'] = 0;
        $bulkInvoice['invoiceList'] = [];
    
        foreach ( $rows as $i => $row ) {
            if ( ! $i ) {
                foreach ( $row as $loc => $header ) {
                    $headers[$header] = $loc;
                }

                continue; // headers
            }

            // drop duplicates
            $invoice_number = $extract_num($row[$headers['DeclaratieNummer'] ?? ''] ?? null);
            if ( in_array($invoice_number, array_diff_key($ids=$getAllIds($rows, $headers), array_unique($ids) )) ) {
                if ( ! in_array($invoice_number, $ignore_duplicates) ) { // keep the first
                    $ignore_duplicates []= $invoice_number;
                } else { // ignore the rest of invoices
                    continue;
                }
            }

            /*// drop unmatching amounts
            $amount1 = trim($row[$headers['DeclaratieBedrag'] ?? ''] ?? null);
            $amount2 = trim($row[$headers['SubtrajectDeclaratiebedrag'] ?? ''] ?? null);

            if ( $amount1 && $amount2 && $amount1 != $amount2 ) {
                $ids = $getAllIds($rows, $headers);

                if ( ! in_array($invoice_number, $ignore_duplicates) ) { // keep the first
                    $ignore_duplicates []= $invoice_number;
                } else { // ignore the rest of invoices
                    print_r( 'ignore-2 ' . print_r($row, 1) . PHP_EOL );
                    continue;
                }
            }*/

            if ( $practitioner = Practitioners::fromString($row[$headers['SubtrajectHoofdbehandelaar'] ?? ''] ?? '') ) {
                unset($practitioner['fee']);
                // insert new practitioner if not exists
                Practitioners::queryOne($practitioner) || Practitioners::insert($practitioner);
            }

            // patients
            $patient = Patients::prepareData([
                'id' => sanitize_text_field($row[ $headers['DeclaratieDebiteurnummer'] ?? '' ] ?? ''),
                'name' => sanitize_text_field($row[ $headers['DeclaratieDebiteurNaam'] ?? '' ] ?? ''),
                'email' => sanitize_text_field($row[ $headers['DebiteurMailadres'] ?? '' ] ?? ''),
                'phone' => sanitize_text_field($row[ $headers['DebiteurTelefoon'] ?? '' ] ?? ''),
                'address' => sanitize_text_field($row[ $headers['DebiteurAdres'] ?? '' ] ?? ''),
                'insurer' => sanitize_text_field($row[ $headers['ZorgverzekeraarNaam'] ?? '' ] ?? ''),
                'policy' => sanitize_text_field($row[ $headers['ZorgverzekeraarPakket'] ?? '' ] ?? ''),
                'UZOVI' => sanitize_text_field( $row[ $headers['ZorgverzekeraarUZOVI'] ?? '' ] ?? '' ),
                'location' => sanitize_text_field( $row[ $map['Location'] ] ?? null ),
                'practitioner' => sanitize_text_field( $practitioner['name'] ?? '' ),
                'last_edited' => date('Y-m-d H:i:s'),
                'status' => 0,
            ]);

            if ( $patient['id'] ) {
                // save patient to the database if a new entry
                Patients::queryOne(['id' => $patient['id']]) || Patients::insert($patient);
            }

            // parse dbc code, subtrajectnr, credit invoice from invoice description
            $desc_parts = array_filter(array_map('trim', explode(' - ', sanitize_text_field($row[$headers['DeclaratieregelOmschrijving'] ?? ''] ?? null))));

            if ( preg_match('/^[a-zA-Z0-9]{5,}$/', $desc_parts[0]) ) {
                // print_r(['parsed dbc code' => $desc_parts[0]]); // @feature parse data
            }

            preg_match('/\(Subtrajectnr\.\s{1,}(\d+)\s{0,}\)/', $desc_parts[1] ?? '', $subtrajectnr_m);

            if ( ($subtrajectnr_m = intval($subtrajectnr_m[1] ?? '')) > 0 ) {
                // print_r(['parsed Subtrajectnr' => $subtrajectnr_m]); // @feature parse data
            }

            preg_match('/creditering\s{1,}factuur\s{1,}(\d+)/', $desc_parts[2] ?? '', $credit_invoice_m);

            if ( ($credit_invoice_m = intval($credit_invoice_m[1] ?? '')) > 0 ) {
                // print_r(['parsed credit invoice' => $credit_invoice_m]); // @feature parse data
                // @feature parse description as well
            }

            $is_successful = true;
            $code_error = false;
            $dbc_code = sanitize_text_field($row[ $map['Dbc Code'] ] ?? null);
            $policy = trim($row[ $map['Patient Policy'] ] ?? null);

            $entry = [
                $row[ $map['Invoice ID'] ] ?? null,
                '70',
                $row[ $map['Location'] ] ?? null,
                $this->formatDateStr($row[ $map['Invoice Date'] ] ?? null),
                '',
                '21',
                $row[ $map['Patient ID'] ] ?? null,
                $row[ $map['Patient name'] ] ?? null,
                '8000',
                '0',
                '0,00',
                null,
                $row[ $map['Practitioner'] ] ?? null,
            ];

            $args = [
                'dbc_code' => $dbc_code,
                'most_recent_date' => true,
                // any date that's active (not a past of future date)
                'active_start_date_lte' => date('Y-m-d'), // starts today or earlier,
                'active_end_date_gte' => date('Y-m-d'), // AND ends today or in the future
            ];

            if ( $invoice_ts = strtotime($row[ $map['Treatment Date'] ] ?? null) ) {
                $args['active_start_date_lte'] = date('Y-m-d', $invoice_ts);
                $args['active_end_date_gte'] = date('Y-m-d', $invoice_ts);
            }

            $origin_amount = $row[ $map['Total Amount'] ] ?? null;
            // $parsed_amount = (float) preg_replace('/[^\d+\.\,\-]/si', '', str_replace(',', '.', $origin_amount));
            $parsed_amount = $extract_num($origin_amount);

            if ( $dbc_code && ($code_info = Codes::queryOne($args)) ) {
                $company = trim($row[ $map['Patient Insurer'] ] ?? '');
                $policy_aliased = trim(join('_', [$company, $policy]));

                foreach ( [$policy_aliased, $policy] as $key ) {
                    foreach ( $code_info['insurer_packages'] as $search => $value ) {
                        if ( strtolower($search) == strtolower($key) ) {
                            $entry[11] = $value;
                            break;
                        }
                    }
                }
            } else { // DBC entry not found
                $code_error = true;

                // https://dev.azure.com/turcod/europa-project/_workitems/edit/707
                if ( strtolower($dbc_code) != 'null' ) {
                    $codes_404 []= [
                        'Invoice' => $entry[0],
                        'DBC Code' => $dbc_code,
                        'Total Amount' => $row[ $map['Total Amount'] ] ?? '',
                        'Insurance Policy' => $row[ $map['Patient Policy'] ] ?? '',
                    ];

                    $is_successful = false;
                }
            }

            // if ( null == $entry[11] && ! $code_error ) {
            if ( null == $entry[11] && strtolower($dbc_code) != 'null' ) {
                if ( $row[ $map['Patient Policy'] ] ?? '' ) {
                    $policy_404 []= [
                        'Invoice' => $row[ $map['Invoice ID'] ] ?? '',
                        'Invoice Date' => $this->formatDateStr($row[ $map['Invoice Date'] ?? '' ] ?? null),
                        'Treatment Date' => $this->formatDateStr($row[ $map['Treatment Date'] ?? '' ] ?? null),
                        'DBC Code' => $dbc_code,
                        'SubtrajectDeclaratiecodeOmschrijving' => $row[ $map['SubtrajectDeclaratiecodeOmschrijving'] ?? '' ] ?? '',
                        'Amount' => $row[ $map['Total Amount'] ?? '' ] ?? '',
                        'Insurance company' => $row[ $map['Patient Insurer'] ?? '' ] ?? '',
                        'Insurance policy' => $row[ $map['Patient Policy'] ?? '' ] ?? '',
                        'Reimburse amount' => '',
                        'code_id' => $code_info['id'] ?? null,
                    ];
                    $is_successful = false;
                }
            }

            if ( $parsed_amount < 0 ) { // negative amount
                $entry[11] = null == $entry[11] ? $parsed_amount : floatval($entry[11]) * -1;
            } else {
                $entry[11] = null == $entry[11] ? $parsed_amount : $entry[11];
            }

            $entries []= $entry;    
            $is_successful || $success_decr++;

            $searchargs = [
                $p='_CreatedDate' => date('Y-m-d H:i:s', $_created_date_time=strtotime($row[$headers[$p] ?? ''] ?? null)),
                $p='DeclaratieNummer' => $extract_num($row[$headers[$p] ?? ''] ?? null),
                $p='DeclaratieBedrag' => $extract_num($row[$headers['SubtrajectDeclaratiebedrag'] ?? ''] ?? null, 2),
                $p='SubtrajectDeclaratiebedrag' => $extract_num($row[$headers[$p] ?? ''] ?? null, 2),
            ];

            if ( ! $_created_date_time )
                unset($searchargs['_CreatedDate']);

            // save invoice to the database
            $imported = Invoices::queryOne($searchargs);

            // SubtrajectDeclaratiecode
            $dbc_code = sanitize_text_field($row[$headers['SubtrajectDeclaratiecode'] ?? ''] ?? null);

            // DeclaratieregelOmschrijving
            if ( ! $dbc_code || 'INF' == strtoupper($dbc_code) ) {
                preg_match('/^[0-9A-Z]{4,}\s/', sanitize_text_field($row[$headers['DeclaratieregelOmschrijving'] ?? ''] ?? null), $m);

                if ( trim($m[0] ?? '') ) {
                    $dbc_code = trim($m[0] ?? '');
                }
            }

            $bulkId = $row[$headers['DeclaratieDebiteurNaam']] === "VGZ Zorgverzekeraar N.V." ? $bulkInvoice['id'] : null;

            $save_invoice = [
                $p='_CreatedDate' =>  date("Y/m/d H:i:s"),
                $p='DeclaratieNummer' => $row[$headers[$p] ?? ''] ?? null,
                $p='DeclaratieDatum' => $this->formatDateStr($row[$headers[$p] ?? ''] ?? null, 'Y-m-d'),
                $p='DeclaratieregelOmschrijving' => $row[$headers[$p] ?? ''] ?? null,
                $p='DeclaratieBedrag' => $extract_num($row[$headers['SubtrajectDeclaratiebedrag'] ?? ''] ?? null),
                $p='DossierNUmmer' => $row[$headers[$p] ?? ''] ?? null,
                $p='DossierBehandellocatie' => $row[$headers[$p] ?? ''] ?? null,
                $p='DossierNaam' => $row[$headers[$p] ?? ''] ?? null,
                $p='SubtrajectNummer' => $row[$headers[$p] ?? ''] ?? null,
                $p='SubtrajectHoofdbehandelaar' => $row[$headers[$p] ?? ''] ?? null,
                $p='SubtrajectStartdatum' => $this->formatDateStr($row[$headers[$p] ?? ''] ?? null, 'Y-m-d'),
                $p='SubtrajectEinddatum' => $this->formatDateStr($row[$headers[$p] ?? ''] ?? null, 'Y-m-d'),
                $p='SubtrajectDeclaratiecode' => $dbc_code,
                $p='SubtrajectDeclaratiecodeOmschrijving' => $row[$headers[$p] ?? ''] ?? null,
                $p='SubtrajectDiagnosecode' => $row[$headers[$p] ?? ''] ?? null,
                $p='SubtrajectDeclaratiebedrag' => $extract_num($row[$headers[$p] ?? ''] ?? null),
                $p='DeclaratieDebiteurnummer' => $row[$headers[$p] ?? ''] ?? null,
                $p='DeclaratieDebiteurNaam' => $row[$headers[$p] ?? ''] ?? null,
                $p='DebiteurTelefoon' => $row[$headers[$p] ?? ''] ?? null,
                $p='DebiteurMailadres' => $row[$headers[$p] ?? ''] ?? null,
                $p='DebiteurAdres' => $row[$headers[$p] ?? ''] ?? null,
                $p='ZorgverzekeraarNaam' => $row[$headers[$p] ?? ''] ?? null,
                $p='ZorgverzekeraarUZOVI' => $row[$headers[$p] ?? ''] ?? null,
                $p='ZorgverzekeraarPakket' => $row[$headers[$p] ?? ''] ?? null,
                // re have to parse the reimburse amount, it's not being parsed correctly right now
                $p='ReimburseAmount' => is_float($entry[11]) || is_int($entry[11]) ? $entry[11] : App::extractNum($entry[11]),
                $p='BulkInvoiceNumber' => intval($bulkId)
            ];

            
            if($bulkId !== null) {
                $bulkInvoice['AmountTotal'] += $save_invoice['ReimburseAmount'];
                $bulkInvoice['ReimburseTotal'] += $save_invoice['SubtrajectDeclaratiebedrag'];
                $bulkInvoice['NumberInvoices'] ++;
                array_push($bulkInvoice['invoiceList'], $save_invoice['DeclaratieNummer'] );
            }

            if ( $imported ) {
                Invoices::update($imported['id'], $save_invoice, false);
            } else {
                Invoices::insert($save_invoice, false);
            }
            
            $invoice_dates []= strtotime($save_invoice['DeclaratieDatum']);
            $invoice_numbers []= (int) $save_invoice['DeclaratieNummer'];
        }

        // Generate BulkInvoice from single Invoices
        if($bulkInvoice['NumberInvoices'] > 0) BulkInvoice::insert($bulkInvoice);


        if ( $invoice_dates ) {

            // import transactions
            Invoices::eoBulkRetrieveInvoices(date('Y-m-d', min($invoice_dates)), date('Y-m-d'), $this->appContext);

            // query receivables and mark invoices as paid/due/overdue
            Invoices::eoBulkRetrieveReceivables(
                date('Y-m-d', min($invoice_dates)),
                date('Y-m-d'),
                $invoice_numbers,
                $this->appContext
            );
        }

        // remove codes found in policy errors table from unrecognized dbc codes
        $codes_404 = array_filter($codes_404, function($code) use ($policy_404)
        {
            return ! in_array($code['DBC Code'], wp_list_pluck($policy_404, 'DBC Code'));
        });

        $code_ids = wp_list_pluck($policy_404, 'code_id');

        if ( count($code_ids) != count(array_filter($code_ids)) ) { // contains fields with date required
            foreach ( $policy_404 as $i => $val )
                $policy_404[$i] = array_merge(
                    array_slice($policy_404[$i], 0, count($policy_404[$i])-1),
                    ['Applied date' => ''],
                    array_slice($policy_404[$i], count($policy_404[$i])-1)
                );
        }

        $tmpfile = tempnam(sys_get_temp_dir(), uniqid('zorgportal-xls-'));
        is_dir($tmpfile) && rmdir($tmpfile);
        is_file($tmpfile) && unlink($tmpfile);

        $doc = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $doc->setActiveSheetIndex(0);
        $doc->getActiveSheet()->fromArray($entries, null, 'A1');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($doc, 'Xls');
        $writer->save($tmpfile);

        return new WP_REST_Response([
            'success' => true,
            'download_url' => rest_url(sprintf('zorgportal/v1/invoices/download/%s?_wpnonce=%s', basename($tmpfile), wp_create_nonce('wp_rest'))),
            'policy_404' => $policy_404,
            'codes_404' => $codes_404,
            'total_ok' => max(0, count($entries) - $success_decr),
        ]);
    }
    
    public function restExtractFile() : WP_REST_Response
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wp_rest' ) )
            return new WP_REST_Response(null, 401);

        $file = current($_FILES);

        if ( ! $file || ( $file['error'] ?? '' ) || ! $file['size'] )
            return new WP_REST_Response(null, 400);

        $res = $this->checkFirstRow($file['tmp_name']); 
        if($res == "err_file_structure" || $res == "err_in_rows") {
            $errors = $res;
            return new WP_REST_Response(['errors' => $errors]);
        }

        // $rowCheck = $this->eachRowCheck($file['tmp_name']);
        // if($rowCheck !==  "success") { 
        //     $errors = $rowCheck;
        //     return new WP_REST_Response(['non_exist' => $errors]);
        // }

        return new WP_REST_Response([ 'rows' => $this->checkFirstRow($file['tmp_name']) ]);
    }

    private function formatDateStr(?string $raw, string $format='d/m/Y') : ?string
    {
        // zero-pad numbers
        $raw = preg_replace_callback('/\d+/', function($m)
        {
            return (strlen($m[0]) == 1 ? '0' : '') . $m[0];
        }, $raw);

        foreach ( ['dd/m/Y'] as $parse_format ) {
            $dt = DateTime::createFromFormat($parse_format, $raw_clean=sanitize_text_field($raw));
    
            if ( $dt )
                return $dt->format($format);

            if ( $raw_clean && strtotime($raw_clean) )
                return date($format, strtotime($raw_clean)) ?: $raw;
        }

        return $raw;
    }

    public function restDownload( WP_REST_Request $request ) : ?WP_REST_Response
    {
        $filename = $request->get_param('filename');

        if ( ! preg_match('/^zorgportal\-xls\-[a-zA-Z0-9]{10,}$/', $filename) )
            return new WP_REST_Response(null, 400);
        
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        if ( ! file_exists($file) || ! is_readable($file) )
            return new WP_REST_Response(null, 500);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header(sprintf('Content-Disposition: attachment;filename="%s %s.xls"', esc_attr('import ready'), date('Y-m-d H:i:s')));
        header('Cache-Control: max-age=0');
        readfile($file);
        exit;
    }

    public function updateInsuranceInfo( WP_REST_Request $request ) : WP_REST_Response
    {
        $code_id = (int) $request->get_param('code_id');
        $data = $request->get_json_params();

        if ( ! ( $code_id > 0 ) )
            return new WP_REST_Response(null, 400);

        $amount = $data['amount'] ?? '';
        $insurer = sanitize_text_field($data['insurer'] ?? '');
        $policy = sanitize_text_field($data['policy'] ?? '');

        if ( ! is_numeric($amount) )
            return new WP_REST_Response(null, 400);

        if ( ! $policy )
            return new WP_REST_Response(null, 400);

        $amount = floatval($amount);

        if ( ! $code = Codes::queryOne([ 'id' => $code_id ]) )
            return new WP_REST_Response(null, 404);

        $code['insurer_packages'][trim(join('_', array_filter([$insurer, $policy])))] = $amount;

        Codes::update($code['id'], [
            'insurer_packages' => $code['insurer_packages'],
            'dbc_description' => sanitize_text_field($data['description'] ?? ''),
            'dbc_total_amount' => App::extractNum($data['total_amount'] ?? ''),
        ]);

        return new WP_REST_Response(true);
    }

    public function insertDbcCodes( WP_REST_Request $request ) : WP_REST_Response
    {
        $data = $request->get_json_params();

        // validate data
        if ( ! $dbc_code = sanitize_text_field($data['dbc_code'] ?? '') )
            return new WP_REST_Response(null, 400);

        $date_from = ($data['date_criterea'] ?? '') == 'year'
            ? ($data['date_year'] ?? '') . '-01-01'
            : (($t=strtotime($data['date_from'] ?? '')) ? date('Y-m-d', $t) : '');

        $date_to = ($data['date_criterea'] ?? '') == 'year'
            ? ($data['date_year'] ?? '') . '-12-31'
            : (($t=strtotime($data['date_to'] ?? '')) ? date('Y-m-d', $t) : '');

        if ( ! preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date_from) || ! preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date_to) )
            return new WP_REST_Response(null, 400);

        $amount = $data['amount'] ?? '';
        $insurer = sanitize_text_field($data['insurer'] ?? '');
        $policy = sanitize_text_field($data['policy'] ?? '');

        if ( ! is_numeric($amount) )
            return new WP_REST_Response(null, 400);

        if ( ! $policy )
            return new WP_REST_Response(null, 400);

        $amount = floatval($amount);

        $current = Codes::queryOne([
            'dbc_code' => $dbc_code,
            'most_recent_date' => true,
            // any date that's active (not a past of future date)
            'active_start_date_lte' => $date_from, // starts at client date or earlier,
            'active_end_date_gte' => $date_to, // AND ends in client date or in the future
        ]);

        if ( $current ) { // update existing: add insurer package and amount
            $current['insurer_packages'][trim(join('_', array_filter([$insurer, $policy])))] = $amount;

            Codes::update($current['id'], [
                'insurer_packages' => $current['insurer_packages'],
                'dbc_description' => sanitize_text_field($data['description'] ?? ''),
                'dbc_total_amount' => App::extractNum($data['total_amount'] ?? ''),
            ]);

            return new WP_REST_Response($current['id']);
        } else { // insert a new DBC code
            $id = Codes::insert([
                'dbc_code' => $dbc_code,
                'active_start_date' => $date_from,
                'active_end_date' => $date_to,
                'insurer_packages' => [
                    trim(join('_', array_filter([$insurer, $policy]))) => $amount,
                ],
                'dbc_total_amount' => 0,
                'date_added' => time(),
                'dbc_description' => sanitize_text_field($data['description'] ?? ''),
                'dbc_total_amount' => App::extractNum($data['total_amount'] ?? ''),
            ]);

            return new WP_REST_Response($id, $id ? 200 : 500);
        }
    }

    public function checkFirstRow( string $file )
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $row = array_values(array_filter($spreadsheet->getActiveSheet()->toArray(null, true, true, true), 'array_filter'));
            $exists =  Invoices::getMatchingInvoices(array_slice($row, 0, 2));

            if(!empty($exists)) {
                $res = $exists[0]->chk == "true" ? "err_in_rows" : $row;
            } else $res = "err_file_structure";

            return $res;

        } catch ( \Exception $e ) {
            return [];
        }
    }

    public function eachRowCheck( string $file )
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $row = array_values(array_filter($spreadsheet->getActiveSheet()->toArray(null, true, true, true), 'array_filter'));

            foreach ($row as $key_p => $p) {
                foreach ($p as $key_c => $c) {
                    if(empty($c)) {
                         $err = $row[0][$key_c]."field's value non exist in ".($key_p+1)."rows";
                         return $err; 
                    }
                }
            }
            return "success";
        } catch ( \Exception $e ) {
            return [];
        }
    }

}
