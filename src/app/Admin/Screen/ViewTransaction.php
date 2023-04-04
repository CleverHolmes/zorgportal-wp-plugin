<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\Transactions as Core;

class ViewTransaction extends Screen
{
    public function init()
    {
        $id = (int) ( $_GET['id'] ?? null );

        if ( $id <= 0 )
            exit( wp_safe_redirect('admin.php?page=zorgportal-transactions') );

        if ( ! $this->transaction = Core::queryOne(['id' => $id]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal-transactions') );
    }

    public function render()
    {
        return $this->renderTemplate('view-transaction.php', [
            'transaction' => $this->transaction,
            'nonce' => wp_create_nonce('zorgportal'),
        ]);
    }
}