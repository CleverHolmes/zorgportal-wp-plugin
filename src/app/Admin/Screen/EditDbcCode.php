<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\DbcCodes as Codes;

class EditDbcCode extends Screen
{
    public function init()
    {
        $id = (int) ( $_GET['id'] ?? null );

        if ( $id <= 0 )
            exit( wp_safe_redirect('admin.php?page=zorgportal') );

        if ( ! $this->code = Codes::queryOne(['id' => $id]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal') );
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_script( 'zportal-edit-code', "{$base}src/assets/js/edit-code.js", ['jquery'], $this->appContext::SCRIPTS_VERSION );
    }

    public function render()
    {
        if ( 'POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') ) {
            $_POST = $this->code ?: ($this->clone ?? []);
        }

        return $this->renderTemplate('edit-code.php', [
            'code' => $this->code,
            'nonce' => wp_create_nonce('zorgportal'),
        ]);
    }

    public function update()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        $insurer_packages = [];

        foreach ( (array) ($_POST['packages'] ?? null) as $package ) {
            if ( ! $name = trim($package['name'] ?? '') )
                continue;

            $amount = floatval($package['amount'] ?? '');

            $insurer_packages[$name] = $amount;
        }

        $_POST['insurer_packages'] = $insurer_packages;

        if ( ! $dbc_code = sanitize_text_field($_POST['dbc_code'] ?? '') )
            return $this->error( __('DBC code cannot be empty.', 'zorgportal') );

        $dbc_description = sanitize_text_field($_POST['dbc_description'] ?? '');

        if ( ! $from = strtotime($_POST['active_start_date'] ?? '') )
            return $this->error( __('Invalid or missing applied start date.', 'zorgportal') );

        if ( ! $to = strtotime($_POST['active_end_date'] ?? '') )
            return $this->error( __('Invalid or missing applied end date.', 'zorgportal') );

        $active_start_date = date('Y-m-d', $from);
        $active_end_date = date('Y-m-d', $to);
        $dbc_total_amount = max(0, floatval($_POST['dbc_total_amount'] ?? ''));

        $_POST = array_merge($_POST, $update = compact(
            'dbc_code',
            'dbc_description',
            'active_start_date',
            'active_end_date',
            'dbc_total_amount',
            'insurer_packages'
        ));

        if ( $this->code['id'] ?? null ) {
            Codes::update($this->code['id'], $update);
            return $this->success( __('Code updated successfully.', 'zorgportal') );
        } else {
            /*$current = Codes::queryOne([
                'dbc_code' => $dbc_code,
                'most_recent_date' => true,
                // any date that's active (not a past of future date)
                'active_start_date_lte' => date('Y-m-d', $from), // starts at client date or earlier,
                'active_end_date_gte' => date('Y-m-d', $from), // AND ends in client date or in the future
            ]) ?: Codes::queryOne([
                'dbc_code' => $dbc_code,
                'most_recent_date' => true,
                // any date that's active (not a past of future date)
                'active_start_date_lte' => date('Y-m-d', $to), // starts at client date or earlier,
                'active_end_date_gte' => date('Y-m-d', $to), // AND ends in client date or in the future
            ]);*/

            // save new codes without checking for duplicates/overlaping dates
            $current = false;

            if ( $current )
                return $this->error(sprintf(
                    __('Duplicate code detected. Did you mean to edit DBC code<a target="_blank" href="%s">%s</a>?', 'zorgportal'),
                    "admin.php?page=zorgportal-edit-code&id={$current['id']}",
                    $dbc_code
                ));

            if ( ! Codes::insert(array_merge($update, ['date_added' => time()])) )
                return $this->error( __('Error occurred, please try again.', 'zorgportal') );

            exit( wp_safe_redirect('admin.php?page=zorgportal') );
        }
    }
}