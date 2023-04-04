<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\Invoices as Core;
use Zorgportal\Patients;

class SendInvoiceReminder extends Screen
{
    protected $invoice;
    protected $reminder;
    protected $contents;

    public function init()
    {
        $id = (int) ( $_GET['id'] ?? null );

        if ( $id <= 0 )
            exit( wp_safe_redirect('admin.php?page=zorgportal-invoices') );

        if ( ! $this->invoice = Core::queryOne(['id' => $id]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal-invoices') );

        if ( ! in_array($this->reminder=intval($_GET['reminder'] ?? -1), [1,2]) )
            exit( wp_safe_redirect('admin.php?page=zorgportal-invoices') );

        $this->contents = call_user_func([Core::class, 1 == $this->reminder ? 'sendFirstReminder' : 'sendSecondReminder'], [
            'invoice' => $this->invoice,
            'patient' => Patients::queryOne(['id' => $this->invoice['DeclaratieDebiteurnummer']]),
        ], $this->appContext, true);

        $this->contents['priority'] = 1;
        $this->contents['to'] = $this->contents['notify_email'];
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url( $this->appContext->getPluginFile() ));
        wp_enqueue_script( 'zp-settings', "{$base}src/assets/js/admin-reminders.js", ['jquery'], $this->appContext::SCRIPTS_VERSION );

        wp_localize_script('zp-settings', 'ZP_SETTINGS', [
            'uploader_title' => __('Select a File', 'zorgportal'),
            'uploader_button' => __('Select File', 'zorgportal'),
            'invalid_file' => __('Invalid file selected.', 'zorgportal'),
        ]);
    }

    public function render()
    {
        if ( 'POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') ) {
            $_POST = $this->contents;

            $default_att = [
                'id' => $att_id=intval(get_option('zorgportal:instructions_att_id')),
                'file' => $att_id ? get_attached_file($att_id) : null,
            ];

            if ( $default_att['file'] )
                $_POST['atts'] = [ $default_att ];
        }

        return $this->renderTemplate('invoice-reminder.php', [
            'invoice' => $this->invoice,
            'contents' => $this->contents,
            'reminder' => $this->reminder,
            'nonce' => wp_create_nonce('zorgportal'),
        ]);
    }

    public function update()
    {
        $atts = array_filter(array_map(function($id)
        {
            return ($file=get_attached_file($id)) ? compact('id', 'file') : null;
        }, array_filter(array_unique(array_map('intval', $_POST['attachments'] ?? [])))));

        $_POST['atts'] = $atts;

        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'zorgportal' ) )
            return $this->error( __('Invalid request, authorization check failed. Please try again.', 'zorgportal') );

        if ( ! $subject = trim($_POST['subject']) )
            return $this->error( __('Please enter a subject.', 'zorgportal') );

        if ( ! in_array($priority = intval($_POST['priority'] ?? ''), [1,3,5]) )
            return $this->error( __('Please select a priority.', 'zorgportal') );

        if ( ! $to = sanitize_text_field($_POST['to'] ?? '') )
            return $this->error( __('Please enter a recipient.', 'zorgportal') );

        $cc = sanitize_text_field($_POST['cc'] ?? '');
        $bcc = sanitize_text_field($_POST['bcc'] ?? '');

        if ( ! $body = trim($_POST['body']) )
            return $this->error( __('Please specify the email body.', 'zorgportal') );

        $sent = wp_mail($to, wp_unslash($subject), wp_unslash($body), array_filter([
            'content-type: text/html; charset=utf-8',
            "X-Priority: {$priority}",
            'X-MSMail-Priority: ' . ($prios=[ 1 => 'High', 3 => 'Normal', 5 => 'Low' ])[$priority],
            'Importance: ' . $prios[$priority],
            $cc ? "cc: {$cc}" : '',
            $bcc ? "Bcc: {$bcc}" : '',
        ]), wp_list_pluck($atts, 'file'));

        if ( $sent ) {
            unset($_POST['atts']);
            return $this->success( __('Email reminder sent successfully!', 'zorgportal') );
        } else {
            return $this->error( __('Email reminder could not be sent.', 'zorgportal') );
        }
    }
}