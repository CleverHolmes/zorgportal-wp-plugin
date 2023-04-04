<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\Invoices;

class EditInvoice extends Screen
{
    public function init()
    {
        $id = (int) ( $_GET['id'] ?? null );

        if ( $id <= 0 )
            exit( wp_safe_redirect('admin.php?page=zorgportal-invoices') );

        if ( ! $this->invoice = Invoices::queryOne(['id' => $id]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal-invoices') );
    }

    public function render()
    {
        if ( 'POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') )
            $_POST = $this->invoice;

        return $this->renderTemplate('edit-invoice.php', [
            'invoice' => $this->invoice,
            'nonce' => wp_create_nonce('zorgportal'),
            'name' => function( string $id ) : string
            {
                switch ( $id ) {
                    case '_CreatedDate': $id = 'CreatedDate';
                    case 'DossierNUmmer': $id = 'DossierNummer';
                }

                return trim(preg_replace_callback('/[A-Z]/', function($m)
                {
                    return " {$m[0]}";
                }, $id));
            },
        ]);
    }

    public function update()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        Invoices::update($this->invoice['id'], $_POST);
        
        return $this->success( __('Invoice updated successfully.', 'zorgportal') );
    }
}