<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\BulkInvoice;

class EditBulkInvoice extends Screen
{
    public function init()
    {
        $id = (int) ( $_GET['id'] ?? null );

        if ( $id <= 0 )
            exit( wp_safe_redirect('admin.php?page=zorgportal-bulkinvoice') );

        if ( ! $this->bulkinvoice = BulkInvoice::queryOne(['id' => $id]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal-bulkinvoice') );
    }

    public function render()
    {
        if ( 'POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') )
            $_POST = $this->bulkinvoice;
        return $this->renderTemplate('edit-bulkinvoice.php', [
            'invoice' => $this->bulkinvoice,
            'nonce' => wp_create_nonce('zorgportal'),
            'name' => function( string $id ) : string
            {
                switch ( $id ) {
                    case '_CreatedDate': $id = 'CreatedDate';
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
            
        BulkInvoice::update($this->bulkinvoice['id'], $_POST);
        
        return $this->success( __('BulkInvoice updated successfully.', 'zorgportal') );
    }
}