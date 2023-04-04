<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\DbcCodes as Codes;
use Zorgportal\App;
use WP_REST_Response;

class ImportCodes extends Screen
{
    public function render()
    {
        return $this->renderTemplate('import-codes.php', [
            'nonce' => wp_create_nonce('zorgportal'),
            'baseUrl' => trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() )),
        ]);
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_script( 'zportal-codes', "{$base}src/assets/import.js", ['jquery'], $this->appContext::SCRIPTS_VERSION, 1 );
        wp_localize_script('zportal-codes', 'ZORGPORTAL_I18N', [
            'ajaxUrl' => rest_url('zorgportal/v1/%s?_wpnonce=' . wp_create_nonce('wp_rest')),
            'error' => __('Error occurred, please try again.', 'zorgportal'),
            'colPlaceholder' => __('(column %s)', 'zorgportal'),
            'err_no_rows' => __('No rows loaded. Please upload a file and wait for it to be loaded.', 'zorgportal'),
            'success_message_inserted' => __('%d DBC codes imported correctly', 'zorgportal'),
            'success_message_overwritten' => __('%d DBC codes overwritten', 'zorgportal'),
            'success_message_errored' => __('%d DBC codes not imported because no correct value', 'zorgportal'),
            'pre_select_headers' => [
                'Declaratiecode',
                'Omschrijving',
                '',
                'Passantentarief',
            ],
            'pre_select_ranges' => [
                'insurer_packages' => ['Passantentarief', 'Omschrijving'],
            ],
        ]);
    }

    public function restExtractFile() : WP_REST_Response
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wp_rest' ) )
            return new WP_REST_Response(null, 401);

        $file = current($_FILES);

        if ( ! $file || ( $file['error'] ?? '' ) || ! $file['size'] )
            return new WP_REST_Response(null, 400);
    
        return new WP_REST_Response([ 'rows' => $this->getRows($file['tmp_name']) ]);
    }

    public function restImport() : WP_REST_Response
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wp_rest' ) )
            return new WP_REST_Response(null, 401);

        $overwrite = $_POST['overwrite'] ?? null;
        $clear_all = $_POST['clear_all'] ?? null;

        $rows = json_decode(wp_unslash($_POST['rows_json'] ?? '[]'), 1);

        $date_from = ($_POST['date_criterea'] ?? '') == 'year'
            ? ($_POST['year'] ?? '') . '-01-01'
            : (($t=strtotime($_POST['date_from'] ?? '')) ? date('Y-m-d', $t) : '');

        $date_to = ($_POST['date_criterea'] ?? '') == 'year'
            ? ($_POST['year'] ?? '') . '-12-31'
            : (($t=strtotime($_POST['date_to'] ?? '')) ? date('Y-m-d', $t) : '');

        $errors = [];

        if ( ! is_array($rows) || count($rows) < 2 || ! is_array( $rows[0] ) ) {
            $errors []= __('Empty dataset supplied.', 'zorgportal');
        } else {
            $map = [
                'dbc_code' => null,
                'dbc_description' => null,
                'insurer_packages' => [],
                'dbc_total_amount' => null,
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

            if ( count($map) != count(array_filter($map)) )
                $errors []= __('One or more fields not mapped to their value location.', 'zorgportal');
        }

        if ( ! preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date_from) || ! preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date_to) )
            $errors []= __('Invalid date range.', 'zorgportal');

        $codes = array_filter(array_map(function($row) use ($map)
        {
            return $row[ $map['dbc_code'] ] ?? null;
        }, $rows));

        if ( count($codes) != count(array_unique($codes)) )
            $errors []= sprintf(
                __('%d DBC codes duplicate detected: <code style="display:table;margin-top:5px">%s</code>', 'zorgportal'),
                count($dups=array_unique(array_diff_assoc($codes, array_unique($codes)))),
                join(', ', $dups)
            );

        if ( $errors )
            return new WP_REST_Response(['success' => false, 'errors' => $errors]);

        $date_added = time();
        $extract_num = [App::class, 'extractNum'];
        $is_not_null = function(...$args) { return ! is_null(...$args); };

        $inserted = 0;
        $errored = 0;
        $overwritten = 0;

        if ( $clear_all ) // clear data if checkbox on
            Codes::deleteAll();

        $inserted_codes = [];
        $update_ids = [];

        if ( ! $overwrite && ! $clear_all ) {
            $dup_queries = [];

            foreach ( array_slice($rows, 1) as $row ) {
                if ( ! $dbc_code = $row[ $map['dbc_code'] ] ?? null )
                    continue;

                // already added
                if ( in_array($dbc_code, wp_list_pluck($dup_queries, 'dbc_code')) )
                    continue;

                $dup_queries []= compact('dbc_code', 'date_from', 'date_to');
            }

            if ( $dup_queries ) {
                global $wpdb;

                foreach ( array_chunk($dup_queries, App::DUPLICATE_IMPORT_QUERY_SIZE) as $batch ) {
                    $queries = array_map(function($q) use ($wpdb)
                    {
                        $table = $wpdb->prefix . App::DBC_CODES_TABLE;
                        return $wpdb->prepare("select id, dbc_code, insurer_packages from {$table} where (dbc_code = %s and active_start_date <= %s and active_end_date >= %s)", $q['dbc_code'], $q['date_from'], $q['date_to']);
                    }, $batch);

                    $dups = $wpdb->get_results(join(' union ', array_unique($queries)), ARRAY_A);

                    foreach ( $dups as $duplicate ) {
                        $update_ids[ $duplicate['dbc_code'] ] = $duplicate;
                    }
                }
            }
        }

        $insert_ids = [];

        foreach ( $rows as $i => $row ) {
            if ( ! $i ) continue; // headers

            $entry = [
                'dbc_code' => $row[ $map['dbc_code'] ] ?? null,
                'dbc_description' => $row[ $map['dbc_description'] ] ?? null,
                'active_start_date' => $date_from,
                'active_end_date' => $date_to, 
                'insurer_packages' => null,
                'dbc_total_amount' => $extract_num($row[ $map['dbc_total_amount'] ] ?? null),
                'date_added' => $date_added,
            ];

            $update = $update_ids[ $entry['dbc_code'] ] ?? null;

            if ( ($update['id'] ?? null) && ($update['insurer_packages'] ?? null) ) {
                $entry['insurer_packages'] = json_decode($update['insurer_packages'], true) ?: null;
            }

            foreach ( (array) $map['insurer_packages'] as $col ) {
                if ( null != ($amt = $extract_num($row[$col] ?? null)) ) {
                    $entry['insurer_packages'] = $entry['insurer_packages'] ?? [];
                    $entry['insurer_packages'][ trim($rows[0][$col]) ] = $amt;
                }
            }

            $entry = array_filter($entry, $is_not_null);

            if ( $update['id'] ?? null ) { // merge existing
                if ( isset( $entry['insurer_packages'] ) ) {
                    Codes::update($update['id'], [ 'insurer_packages' => $entry['insurer_packages'] ]);
                    $inserted++;
                    $insert_ids []= $update['id']; // don't delete this entry for duplicates
                }
            } else { // insert new
                if ( $insert_id = Codes::insert($entry) ) {
                    $inserted_codes []= $entry['dbc_code'];
                    $inserted++;
                    $insert_ids []= $insert_id;
                } else {
                    $errored++;
                }
            }
        }

        $inserted_codes = array_unique($inserted_codes);

        // clear duplicates if checkbox on
        if ( $overwrite && count($inserted_codes) > 0 ) {
            $delete_dups = Codes::query([
                'dbc_code_in' => $inserted_codes,
                'id_not_in' => $insert_ids,
                'active_start_date_gte' => $date_from,
                'active_end_date_lte' => $date_to,
            ])['list'];

            $overwritten = $delete_dups ? Codes::delete(wp_list_pluck($delete_dups, 'id')) : 0; 
        }

        return new WP_REST_Response(array_merge(['success' => true], compact('errored', 'inserted', 'overwritten')));
    }

    private function getRows( string $file ) : array
    {
        // convert to csv
        // if ( false === strpos(mime_content_type($file), 'text/') ) { // convert to csv
        //     $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        //     $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        //     $writer->save($file="{$file}.csv");
        // }
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $rows = array_values(array_filter($spreadsheet->getActiveSheet()->toArray(null, true, true, true), 'array_filter'));
            return $row;
        } catch ( \Exception $e ) {
            return [];
        }
    }

}