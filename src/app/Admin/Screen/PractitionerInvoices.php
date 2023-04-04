<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\{Practitioners,App,Invoices};
use WP_REST_Response;

class PractitionerInvoices extends Screen
{
    const QUARTERS = [
        1 => ['Y-01-01', 'Y-03-31'],
        2 => ['Y-04-01', 'Y-06-30'],
        3 => ['Y-07-01', 'Y-09-30'],
        4 => ['Y-10-01', 'Y-12-31'],
    ];

    protected $invoice_locations = [];

    public function init()
    {
        $id = (int) ( $_GET['id'] ?? null );

        if ( $id <= 0 )
            exit( wp_safe_redirect('admin.php?page=zorgportal-practitioners') );

        if ( ! $this->practitioner = Practitioners::queryOne(['id' => $id]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal-practitioners') );

        $this->invoice_locations = Invoices::getMatchingLocations($this->practitioner['name'], $this->practitioner['specialty']);

        $filter_location = $this->practitioner['location'];

        if ( $_POST['location'] ?? '' ) {
            $filter_location = sanitize_text_field($_POST['location']);
        }

        $search = join(' - ', array_filter(array_map('trim', [$this->practitioner['name'], $filter_location, $this->practitioner['specialty']])));

        $args = [
            'SubtrajectHoofdbehandelaar' => $search,
            'per_page' => 999999999,
            'current_page' => 1,
        ];

        if ( 'quarter' == ( $_POST['date_criterea'] ?? '' ) ) {
            list($year, $quarter) = explode('-', $_POST['quarter'] ?? '');

            if ( ! $year || ! preg_match('/^\d{4}$/', $year)  ) {
                $this->error(__('Invalid quarter selected.', 'zorgportal'));
            } else if ( ! in_array($quarter = intval($quarter), array_keys($this::QUARTERS)) ) {
                $this->error(__('Invalid quarter selected.', 'zorgportal'));
            } else {
                $args['end_date_gte'] = date(str_replace('Y-', "{$year}-", $this::QUARTERS[ $quarter ][0]));
                $args['end_date_lte'] = date(str_replace('Y-', "{$year}-", $this::QUARTERS[ $quarter ][1]));
            }
        } else if ( 'range' == ( $_POST['date_criterea'] ?? '' ) ) {
            $date_from = $_POST['date_from'] ?? '';
            $date_to = $_POST['date_to'] ?? '';

            if ( ! preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date_from) )
                $this->error(__('Invalid start date.', 'zorgportal'));

            if ( ! preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date_to) )
                $this->error(__('Invalid end date.', 'zorgportal'));

            if ( 0 == count($this->getErrors()) ) {
                $args['end_date_gte'] = $date_from;
                $args['end_date_lte'] = $date_to;
            }
        }

        if ( ! isset( $args['end_date_gte'], $args['end_date_lte'] ) )
            return;

        $this->invoices = Invoices::query($args)['list'];

        if ( isset( $_POST['download'] ) )
            return $this->download($args, $filter_location);
    }

    public function render()
    {
        return $this->renderTemplate('practitioner-invoices.php', [
            'nonce' => wp_create_nonce('zorgportal'),
            'practitioner' => $this->practitioner,
            'invoices' => $this->invoices ?? null,
            'dateFmt' => function(...$args) { return $this->dateFmt(...$args); },
            'invoice_locations' => $this->invoice_locations,
        ]);
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_script( 'zportal-invoices', "{$base}src/assets/js/practitioner-invoices.js", ['jquery'], $this->appContext::SCRIPTS_VERSION, 1 );
    }

    private function download($args, string $location='')
    {
        if ( 0 == count($this->invoices) )
            return $this->error(__('No invoices available for download.', 'zorgportal'));

        $entries = [[
            'DossierNUmmer',
            'Subtrajectnr',
            'DossierNaam',
            'Location',
            'SubtrajectHoofdbehandelaar',
            'DeclaratieDebiteurnummer',
            'DeclaratieDebiteurNaam',
            'DeclaratieNummer',
            'DeclaratieDatum',
            'SubtrajectDiagnosecode',
            'DeclaratieBedrag',
            'SubtrajectDeclaratiebedrag',
            'Reimburse Amount',
            'Honorarium',
            'Vergoeding',
            'Status',
            'Type',
            'Imported',
            'Paid Out Date',
            'DBC Code',
            'DeclaratieregelOmschrijving',
            'SubtrajectStartdatum',
            'SubtrajectEinddatum',
            'ZorgverzekeraarUZOVI',
            'ZorgverzekeraarNaam',
            'ZorgverzekeraarPakket',
        ]];

        foreach ( $this->invoices as $invoice ) {
            $entries []= [
                $invoice['DossierNUmmer'] ?? '',
                $invoice['SubtrajectNummer'] ?? '',
                $invoice['DossierNaam'] ?? '',
                explode(' - ', $invoice['SubtrajectHoofdbehandelaar'])[1] ?? '',
                $invoice['SubtrajectHoofdbehandelaar'] ?? '',
                $invoice['DeclaratieDebiteurnummer'] ?? '',
                $invoice['DeclaratieDebiteurNaam'] ?? '',
                $invoice['DeclaratieNummer'] ?? '',
                $invoice['DeclaratieDatum'] ?? '',
                $invoice['SubtrajectDiagnosecode'] ?? '',
                $invoice['DeclaratieBedrag'] ?? '',
                $invoice['SubtrajectDeclaratiebedrag'] ?? '',
                $invoice['ReimburseAmount'] ?? '',
                strval($this->practitioner['fee']) . '%',
                strval(round(floatval($invoice['ReimburseAmount']) * $this->practitioner['fee']/100, 2)),
                '', // status
                '', // invoice type
                '', // imported
                '', // Paid Out Date
                $invoice['SubtrajectDeclaratiecode'] ?? '',
                'DeclaratieregelOmschrijving' ?? '',
                $this->dateFmt($invoice['SubtrajectStartdatum'] ?? ''),
                $this->dateFmt($invoice['SubtrajectEinddatum'] ?? ''),
                'ZorgverzekeraarUZOVI' ?? '',
                'ZorgverzekeraarNaam' ?? '',
                'ZorgverzekeraarPakket' ?? '',
            ];
        }

        $total_value_rows = count($entries) - 1;

        if ( $total_value_rows > 0 ) {
            $entries []= array_merge(array_fill(0, 13, ''), ['Totaal honorarium', '0']);
            $entries []= array_merge(array_fill(0, 13, ''), ['Voorschot honorarium', '0']);
            $entries []= array_merge(array_fill(0, 13, ''), ['Tegoed', '0']);
            $entries []= ['Ontvangen voorschot'];
            $entries []= [ '', '0'];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ '', '0' ];
            $entries []= [ 'Total', '0' ];
        }

        $doc = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $doc->setActiveSheetIndex(0);
        $sheet = $doc->getActiveSheet();
        $sheet->fromArray($entries, null, 'A1');

        if ( $total_value_rows > 0 ) {
            $sheet->setCellValue('O' . ($total_value_rows+2), '=SUM(O2:O' . ($total_value_rows+1) . ')');
            $sheet->setCellValue('O' . ($total_value_rows+3), '=B' . ($total_value_rows+1+18));
            $sheet->setCellValue('O' . ($total_value_rows+4), '=O' . ($total_value_rows+2) . '-O' . ($total_value_rows+3));
            $sheet->setCellValue('B' . ($last=($total_value_rows+1+18)), '=SUM(B' . ($total_value_rows+1+5) . ':B' . ($last-1) . ')');
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($doc, 'Xls');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header(sprintf('Content-Disposition: attachment;filename="%s - %s - %s to %s.xls"', $this->practitioner['name'], $location ?: $this->practitioner['location'], $args['end_date_gte'], $args['end_date_lte']));
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    private function dateFmt(string $raw) : string
    {
        if ( $raw && strtotime($raw) )
            return date('Y-m-d', strtotime($raw)) ?: $raw;

        return $raw;
    }
}