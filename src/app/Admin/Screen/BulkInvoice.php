<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\Invoices as Invoices;
use Zorgportal\BulkInvoice as Core;
use Zorgportal\App;

class BulkInvoice extends Screen
{
    public function init()
    {
        if ( $update_id = intval($_GET['update_id'] ?? '') ) {
            check_admin_referer();

            $status = $this->appContext->updateInvoicesEoStatus(function() use ($update_id)
            {
                return array_filter([Invoices::queryOne(['id' => $update_id])]);
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
            'orderby' => $this->getActiveSort()['prop'] ?? '',
            'order' => $this->getActiveSort()['order'] ?? '',
        ]; 
        
        return $this->renderTemplate('bulk-invoice.php', array_merge(Core::intialData($query), [
            'getActiveSort' => [ $this, 'getActiveSort' ],
            'getNextSort' => [ $this, 'getNextSort' ],
            'nonce' => wp_create_nonce('zorgportal')
        ]));
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_style( 'zportal-codes', "{$base}src/assets/codes.css", [], $this->appContext::SCRIPTS_VERSION );
        wp_enqueue_script( 'zportal-invoices', "{$base}src/assets/js/bulk-invoice.js", ['jquery'], $this->appContext::SCRIPTS_VERSION, 1 );
    }

    public function update()
    {      
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        $items = array_filter(array_unique( array_map('intval', (array) ($_POST['items'] ?? '')) ));

        if ( ! $items )
            return;

        if ( !empty($items[0]) ) {
            $del = Core::delete($items);
            return $this->success( sprintf(
                _n( '%d Bulk invoice deleted.', '%d Bulk invoice deleted.', $del, 'zorgportal' ), $del
            ) );
        }
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