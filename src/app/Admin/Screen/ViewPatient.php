<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\Patients as Core;
use Zorgportal\Invoices;

class ViewPatient extends Screen
{
    public function init()
    {
        $id = (int) ( $_GET['id'] ?? null );

        if ( $id <= 0 )
            exit( wp_safe_redirect('admin.php?page=zorgportal-patients') );

        if ( ! $this->patient = Core::queryOne(['id' => $id]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal-patients') );
    }

    public function render()
    {
        return $this->renderTemplate('view-patient.php', [
            'patient' => $this->patient,
            'nonce' => wp_create_nonce('zorgportal'),
            'invoices' => $invoices=Invoices::query([
                'DeclaratieDebiteurnummer' => $this->patient['id'],
                'nopaged' => 1,
            ])['list'],
            'max_status' => ($list=wp_list_pluck($invoices, 'EoStatus')) ? max($list) : null,
        ]);
    }
}