<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\DbcCodes as Codes;
use Zorgportal\App;

class DbcCodes extends Screen
{
    public function render()
    {
        $args = [
            'current_page' => (int) ($_GET['p'] ?? ''),
            'search' => $_GET['search'] ?? '',
            'orderby' => $this->getActiveSort()['prop'] ?? '',
            'order' => $this->getActiveSort()['order'] ?? '',
        ];

        switch ($_GET['date_criteria'] ?? '') {
            case 'year':
                // don't get current Year, just show all years
                if ( ($year=($_GET['year'] ?? '')) && preg_match('/^\d{4}$/', $year) ) {
                    $args['active_start_date_gte'] = "{$year}-01-01";
                    $args['active_end_date_lte'] = "{$year}-12-31";
                }
                break;

            case 'range':
                if ( ($date_from=($_GET['date_from'] ?? '')) && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date_from) ) {
                    $args['active_start_date_gte'] = $date_from;
                }

                if ( ($date_to=($_GET['date_to'] ?? '')) && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date_to) ) {
                    $args['active_end_date_lte'] = $date_to;
                }
                break;
        }

        if ( $insurer = ($_GET['insurer'] ?? '') ) {
            $args['insurer'] = $insurer;
        }

        if ( $policy = ($_GET['policy'] ?? '') ) {
            $args['policy'] = $policy;
        }

        return $this->renderTemplate('codes.php', array_merge(Codes::query($args), [
            'getActiveSort' => [ $this, 'getActiveSort' ],
            'getNextSort' => [ $this, 'getNextSort' ],
            'nonce' => wp_create_nonce('zorgportal'),
            'insurers' => Codes::getAllInsurers(),
            'policies' => Codes::getAllPolicies(),
        ]));
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_style( 'zportal-codes', "{$base}src/assets/codes.css", [], $this->appContext::SCRIPTS_VERSION );
        wp_enqueue_script( 'zportal-codes', "{$base}src/assets/codes.js", ['jquery'], $this->appContext::SCRIPTS_VERSION, 1 );
    }

    public function update()
    {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        if ( isset( $_POST['delete_all'] ) ) {
            $del = Codes::deleteAll();
            return $this->success( sprintf(
                _n( '%d item deleted.', '%d codes deleted.', $del, 'zorgportal' ), $del
            ) );
        }

        if ( isset( $_POST['export_codes'] ) )
            return $this->export();

        $codes = array_filter(array_unique( array_map('intval', (array) ($_POST['codes'] ?? '')) ));

        if ( ! $codes )
            return;

        if ( 'delete' == ( $_POST['action2'] ?? '' ) ) {
            $del = Codes::delete($codes);
            return $this->success( sprintf(
                _n( '%d item deleted.', '%d codes deleted.', $del, 'zorgportal' ), $del
            ) );
        }
    }

    private function export()
    {
        if ( ! $year = intval($_POST['year'] ?? null) )
            return $this->error( __('Please select a valid year for your export.', 'zorgportal') );

        $codes = Codes::query($args=[
            'active_start_date_gte' => "{$year}-01-01",
            'active_end_date_lte' => "{$year}-12-31",
            'nopaged' => 1,
        ])['list'];

        if ( 0 == count($codes) )
            return $this->error( __('No DBC Codes found for your selected year.', 'zorgportal') );

        $entries = [[
            'Declaratiecode' => 'Declaratiecode',
            'Passantentarief' => 'Passantentarief',
        ]];

        foreach ( $codes as $i => $entry ) {
            $row = [
                'Declaratiecode' => $entry['dbc_code'],
                'Passantentarief' => $entry['dbc_total_amount'],
            ];

            foreach ( $entry['insurer_packages'] as $pkg => $amt ) {
                if ( ! array_key_exists($pkg, $entries[0]) ) {
                    $entries[0][$pkg] = $pkg;
                }

                $row[$pkg] = $amt;
            }

            $row['Omschrijving'] = $entry['dbc_description'];
            $entries []= $row;
        }

        $entries[0]['Omschrijving'] = 'Omschrijving';

        $cells = [array_keys($entries[0])];

        foreach ( array_slice($entries, 1) as $entry ) {
            $cell = [];

            foreach ( $cells[0] as $prop ) {
                $cell []= $entry[$prop] ?? '';
            }

            $cells []= $cell;
        }

        $doc = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $doc->setActiveSheetIndex(0);
        $doc->getActiveSheet()->fromArray($cells, null, 'A1');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($doc, 'Xls');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header(sprintf('Content-Disposition: attachment;filename="%s - ZP %s - %s to %s - %s.xls"',
            __('DBC Codes Export', 'zorgportal'),
            parse_url(home_url(), PHP_URL_HOST),
            $args['active_start_date_gte'],
            $args['active_end_date_lte'],
            date('c')));
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function getActiveSort() : array
    {
        $sort = explode(',', (string) ( $_GET['sort'] ?? '' ));
        $prop = strtolower($sort[0] ?? '');
        $order = strtolower($sort[1] ?? '');

        if ( $prop && ! array_key_exists($prop, Codes::COLUMNS) ) {
            $prop = '';
            $order = '';
        }

        $order = in_array($order, ['asc','desc']) ? $order : 'desc';
        $order = $prop ? $order : '';

        return compact('order', 'prop');
    }

    public function getNextSort( string $prop ) : string
    {
        $current = $this->getActiveSort();

        if ( $prop == $current['prop'] ) {
            return 'asc' !== $current['order'] ? 'asc' : 'desc';
        }

        return 'desc';
    }
}