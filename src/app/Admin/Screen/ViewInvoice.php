<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\Invoices as Core;
use Zorgportal\Transactions;
use Zorgportal\Patients;

class ViewInvoice extends Screen
{
    protected $transactions;

    public function init()
    {
        $id = (int) ( $_GET['id'] ?? null );

        if ( $id <= 0 )
            exit( wp_safe_redirect('admin.php?page=zorgportal-invoices') );

        if ( ! $this->invoice = Core::queryOne(['id' => $id]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal-invoices') );

        // refresh status
        if ( $_GET['update_invoice'] ?? '' ) {
            check_admin_referer();

            $status = $this->appContext->updateInvoicesEoStatus(function()
            {
                return array_filter([Core::queryOne(['id' => $this->invoice['id']])]);
            }, true);

            exit(wp_redirect(add_query_arg('updated', "invoice-{$status}", remove_query_arg(['update_invoice', 'send_reminder', '_wpnonce']))));
        }

        if ( $reminder = intval($_GET['send_reminder'] ?? '') ) {
            check_admin_referer();

            if ( in_array($reminder, [1,2]) ) {
                $sent = call_user_func([Core::class, 1 == $reminder ? 'sendFirstReminder' : 'sendSecondReminder'], [
                    'invoice' => $this->invoice,
                    'patient' => Patients::queryOne(['id' => $this->invoice['DeclaratieDebiteurnummer']]),
                ], $this->appContext);
            } else {
                $sent = false;
            }

            exit(wp_redirect(add_query_arg($sent ? 'updated' : 'error', "reminder-{$reminder}", remove_query_arg(['update_invoice', 'send_reminder', '_wpnonce']))));
        }

        if ( 0 === strpos($val=($_GET['updated'] ?? ''), 'invoice') )
            $this->info( sprintf(__('Invoice update status: %s', 'zorgportal'), substr($val, strlen('invoice-')) ?: __('Unknown response.', 'zorgportal')) );
        elseif ( ($_GET['updated'] ?? '') == 'reminder-1' )
            $this->success( __('Reminder 1 sent successfully.', 'zorgportal') );
        elseif ( ($_GET['updated'] ?? '') == 'reminder-2' )
            $this->success( __('Reminder 2 sent successfully.', 'zorgportal') );
        elseif ( ($_GET['error'] ?? '') == 'reminder-1' )
            $this->error( __('Reminder 1 could not be sent.', 'zorgportal') );
        elseif ( ($_GET['error'] ?? '') == 'reminder-2' )
            $this->error( __('Reminder 2 could not be sent.', 'zorgportal') );

        $this->transactions = Transactions::query([
            'YourRef' => $this->invoice['id'],
            'nopaged' => 1,
        ])['list'];
    }

    public function render()
    {
        return $this->renderTemplate('view-invoice.php', [
            'invoice' => $this->invoice,
            'txns' => $this->transactions,
            'nonce' => wp_create_nonce('zorgportal'),
        ]);
    }
}