<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\Invoices as Core;
use Zorgportal\App;

class Invoices extends Screen
{
    public function init()
    {
        if ( $update_id = intval($_GET['update_id'] ?? '') ) {
            check_admin_referer();

            $status = $this->appContext->updateInvoicesEoStatus(function() use ($update_id)
            {
                return array_filter([Core::queryOne(['id' => $update_id])]);
            }, true);

            exit(wp_redirect(add_query_arg('updated', "invoice-{$status}", remove_query_arg(['update_id', '_wpnonce']))));
        }

        if ( 0 === strpos($val=($_GET['updated'] ?? ''), 'invoice') )
            return $this->info( sprintf(__('Invoice update status: %s', 'zorgportal'), substr($val, strlen('invoice-')) ?: __('Unknown response.', 'zorgportal')) );
    }

    public function render()
    {
        $query = [
            'current_page' => (int) ($_GET['p'] ?? ''),
            'search' => $_GET['search'] ?? '',
            'orderby' => $this->getActiveSort()['prop'] ?? '',
            'order' => $this->getActiveSort()['order'] ?? '',
        ];

        switch ($_GET['date_criteria'] ?? '') {
            case 'year':
                if ( ($year=($_GET['year'] ?? '')) && preg_match('/^\d{4}$/', $year) ) {
                    $query['end_date_gte'] = "{$year}-01-01";
                    $query['end_date_lte'] = "{$year}-12-31";
                }
                break;

            case 'range':
                if ( ($date_from=($_GET['date_from'] ?? '')) && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date_from) ) {
                    $query['end_date_gte'] = $date_from;
                }

                if ( ($date_to=($_GET['date_to'] ?? '')) && preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $date_to) ) {
                    $query['end_date_lte'] = $date_to;
                }
                break;
        }

        if ( $location = ($_GET['location'] ?? '') ) {
            $query['location'] = $location;
        }

        if ( $practitioner = ($_GET['practitioner'] ?? '') ) {
            $query['practitioner'] = $practitioner;
        }

        if ( $specialty = ($_GET['specialty'] ?? '') ) {
            $query['specialty'] = $specialty;
        }

        if ( $dbc_code = ($_GET['dbc_code'] ?? '') ) {
            $query['SubtrajectDeclaratiecode'] = $dbc_code;
        }

        if ( is_numeric($status = ($_GET['status'] ?? '')) && in_array((int) $status, Core::PAYMENT_STATUSES) ) {
            if ( (int) $status == Core::PAYMENT_STATUS_DUE ) {
                $query['EoStatus_eq_or_null'] = (int) $status;
            } else {
                $query['EoStatus'] = (int) $status;
            }
        }
        
        return $this->renderTemplate('invoices.php', array_merge(Core::query($query), [
            'getActiveSort' => [ $this, 'getActiveSort' ],
            'getNextSort' => [ $this, 'getNextSort' ],
            'nonce' => wp_create_nonce('zorgportal'),
            'practitioners' => Core::getAllPractitioners(),
            'specialties' => Core::getAllSpecialties(),
            'locations' => Core::getAllLocations(),
            'dbc_codes' => Core::getAllDbcCodes(),
        ]));
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_style( 'zportal-codes', "{$base}src/assets/codes.css", [], $this->appContext::SCRIPTS_VERSION );
        wp_enqueue_style( 'zportal-pum', "{$base}src/assets/css/pum-site-styles.css", [], $this->appContext::SCRIPTS_VERSION );
        wp_enqueue_script( 'zportal-codes', "{$base}src/assets/codes.js", ['jquery'], $this->appContext::SCRIPTS_VERSION, 1 );
        wp_enqueue_script( 'zportal-pum', "{$base}src/assets/js/pum-site-scripts.js", ['jquery'], $this->appContext::SCRIPTS_VERSION, 1 );
    }

    public function update()
    {       
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        if ( isset( $_POST['delete_all'] ) ) {
            $del = Core::deleteAll();
            return $this->success( sprintf(
                _n( '%d invoice deleted.', '%d invoices deleted.', $del, 'zorgportal' ), $del
            ) );
        }

        if ( isset( $_POST['update_all'] ) )
            return $this->updateAll();

        $items = array_filter(array_unique( array_map('intval', (array) ($_POST['items'] ?? '')) ));

        if ( isset( $_POST['export_bulk']) ) {

            $h_items = array_filter(array_unique( array_map('intval', (array) ($_POST['h_items'] ?? '')) ));

            $bulk = Core::bulkInvoice($h_items, $this->appContext);
            if($bulk == 0) return $this->error('Invoice was bulked already.', ' bulk_error.', $bulk, 'zorgportal' );
            else exit( wp_safe_redirect('admin.php?page=zorgportal-bulkinvoice') );
        }

        if ( ! $items )
            return;

        if ( 'delete' == ( $_POST['action2'] ?? '' ) ) {
            $del = Core::delete($items);
            return $this->success( sprintf(
                _n( '%d invoice deleted.', '%d invoices deleted.', $del, 'zorgportal' ), $del
            ) );
        }

        if ( 'export' == ( $_POST['action2'] ?? '' ) ) {

            $bulk = Core::bulkInvoice($items, $this->appContext);
            if($bulk == 0) return $this->error('Invoice was bulked already.', ' bulk_error.', $bulk, 'zorgportal' );
            else exit( wp_safe_redirect('admin.php?page=zorgportal-bulkinvoice') );
        }
        
    }

    private function updateAll()
    {
        $qargs = [
            'EoStatus_not_in_or_null' => [ Core::PAYMENT_STATUS_PAID ], // [ 2, 3, null ]
            'orderby' => 'DeclaratieDatum',
            'order' => 'asc',
        ];

        if ( ! $from = (Core::queryOne($qargs)['DeclaratieDatum'] ?? null) )
            return $this->error(__('Insufficient data.', 'zorgportal'));

        if ( ! $to = (Core::queryOne(array_merge($qargs, ['order' => 'desc']))['DeclaratieDatum'] ?? null) )
            return $this->error(__('Insufficient data.', 'zorgportal'));

        Core::eoBulkCheckUnpaidInvoices($from, $to, $this->appContext);

        return $this->info(__('Update completed.', 'zorgportal'));
    }

    public function getActiveSort() : array
    {
        $sort = explode(',', (string) ( $_GET['sort'] ?? '' ));
        $prop = ($sort[0] ?? '');
        $order = ($sort[1] ?? '');

        if ( $prop && ! array_key_exists($prop, Core::COLUMNS) ) {
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