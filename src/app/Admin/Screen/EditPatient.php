<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\Patients;

class EditPatient extends Screen
{
    public function init()
    {
        $id = (int) ( $_GET['id'] ?? null );

        if ( $id <= 0 )
            exit( wp_safe_redirect('admin.php?page=zorgportal-patients') );

        if ( ! $this->patient = Patients::queryOne(['id' => $id]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal-patients') );
    }

    public function render()
    {
        if ( 'POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') )
            $_POST = $this->patient;

        return $this->renderTemplate('edit-patient.php', [
            'patient' => $this->patient,
            'nonce' => wp_create_nonce('zorgportal'),
        ]);
    }

    public function update()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        $patient = Patients::prepareData([
            'id' => sanitize_text_field( $_POST['id'] ?? '' ),
            'name' => sanitize_text_field( $_POST['name'] ?? '' ),
            'email' => sanitize_text_field( $_POST['email'] ?? '' ),
            'phone' => sanitize_text_field( $_POST['phone'] ?? '' ),
            'address' => sanitize_text_field( $_POST['address'] ?? '' ),
            'insurer' => sanitize_text_field( $_POST['insurer'] ?? '' ),
            'policy' => sanitize_text_field( $_POST['policy'] ?? '' ),
            'UZOVI' => sanitize_text_field( $_POST['UZOVI'] ?? '' ),
            'location' => sanitize_text_field( $_POST['location'] ?? '' ),
            'practitioner' => sanitize_text_field( $_POST['practitioner'] ?? '' ),
            'status' => sanitize_text_field( $_POST['status'] ?? '' ),
            'last_edited' => date('Y-m-d H:i:s'),
        ]);

        if ( ! $this->patient ) {
            if ( $patient['id'] <= 0 )
                return $this->error( __('Patient ID cannot be empty.', 'zorgportal') );

            if ( Patients::queryOne(['id' => $patient['id']]) )
                return $this->error( __('A patient already exists with this ID.', 'zorgportal') );
        }

        if ( ! $patient['name'] )
            return $this->error( __('Patient name cannot be empty.', 'zorgportal') );

        if ( $this->patient['id'] ?? null ) {
            $_POST['id'] = $this->patient['id'];
            Patients::update($this->patient['id'], $patient);
            return $this->success( __('Patient updated successfully.', 'zorgportal') );
        } else {
            if ( ! Patients::insert($patient) )
                return $this->error( __('Error occurred, please try again.', 'zorgportal') );

            exit( wp_safe_redirect('admin.php?page=zorgportal-patients') );
        }
    }
}