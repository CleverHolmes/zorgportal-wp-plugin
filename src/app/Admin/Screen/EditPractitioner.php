<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\Practitioners;

class EditPractitioner extends Screen
{
    public function init()
    {
        $id = (int) ( $_GET['id'] ?? null );

        if ( $id <= 0 )
            exit( wp_safe_redirect('admin.php?page=zorgportal-practitioners') );

        if ( ! $this->practitioner = Practitioners::queryOne(['id' => $id]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal-practitioners') );
    }

    public function render()
    {
        if ( 'POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') )
            $_POST = $this->practitioner;

        return $this->renderTemplate('edit-practitioner.php', [
            'practitioner' => $this->practitioner,
            'nonce' => wp_create_nonce('zorgportal'),
        ]);
    }

    public function update()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        if ( ! $name = sanitize_text_field($_POST['name'] ?? '') )
            return $this->error( __('Practitioner name cannot be empty.', 'zorgportal') );

        if ( ! $location = sanitize_text_field($_POST['location'] ?? '') )
            return $this->error( __('Practitioner location cannot be empty.', 'zorgportal') );

        if ( ! $specialty = sanitize_text_field($_POST['specialty'] ?? '') )
            return $this->error( __('Practitioner specialty cannot be empty.', 'zorgportal') );

        if ( ! isset($_POST['fee']) )
            return $this->error( __('Practitioner fee cannot be empty.', 'zorgportal') );

        $fee = floatval($_POST['fee'] ?? '');

        if ( $fee > 100 || $fee < 0 )
            return $this->error( __('Invalid practitioner fee.', 'zorgportal') );

        $data = compact('name', 'location', 'specialty', 'fee');

        if ( $this->practitioner['id'] ?? null ) {
            Practitioners::update($this->practitioner['id'], $data);
            return $this->success( __('Practitioner updated successfully.', 'zorgportal') );
        } else {
            if ( ! Practitioners::insert($data) )
                return $this->error( __('Error occurred, please try again.', 'zorgportal') );

            exit( wp_safe_redirect('admin.php?page=zorgportal-practitioners') );
        }
    }
}