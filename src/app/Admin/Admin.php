<?php

namespace Zorgportal\Admin;

use Zorgportal\App;

use Zorgportal\Admin\Screen\DbcCodes;
use Zorgportal\Admin\Screen\EditDbcCode;
use Zorgportal\Admin\Screen\AddDbcCode;
use Zorgportal\Admin\Screen\ImportCodes;
use Zorgportal\Admin\Screen\ImportInvoices;
use Zorgportal\Admin\Screen\Practitioners;
use Zorgportal\Admin\Screen\EditPractitioner;
use Zorgportal\Admin\Screen\AddPractitioner;
use Zorgportal\Admin\Screen\PractitionerInvoices;
use Zorgportal\Admin\Screen\Invoices;
use Zorgportal\Admin\Screen\BulkInvoice;
use Zorgportal\Admin\Screen\EditInvoice;
use Zorgportal\Admin\Screen\ViewInvoice;
use Zorgportal\Admin\Screen\EditBulkInvoice;
use Zorgportal\Admin\Screen\ViewBulkInvoice;
use Zorgportal\Admin\Screen\Settings;
use Zorgportal\Admin\Screen\SettingsV0 as SettingsOld;
use Zorgportal\Admin\Screen\Patients;
use Zorgportal\Admin\Screen\EditPatient;
use Zorgportal\Admin\Screen\AddPatient;
use Zorgportal\Admin\Screen\ViewPatient;
use Zorgportal\Admin\Screen\SendInvoiceReminder;
use Zorgportal\Admin\Screen\Transactions;
use Zorgportal\Admin\Screen\ViewTransaction;
use Zorgportal\Admin\Screen\Logs;

class Admin
{
    private $appContext;

    public function __construct( App $appContext )
    {
        $this->appContext = $appContext;

        if ( is_admin() && ( ! defined('DOING_AJAX') || ! DOING_AJAX ) ) {
            // menu
            add_action('admin_menu', [$this, 'pages']);

            // headers
            add_action('admin_menu', [$this, 'init']);

            // update settings
            $_POST && add_action('admin_menu', [$this, 'maybeUpdate']);

            // scripts
            add_action('admin_enqueue_scripts', [$this, 'scripts']);

            // css compat
            add_action('admin_head', function()
            {
                echo '<style type="text/css">#adminmenu a[href="admin.php?page=zorgportal-import-codes"],#adminmenu a[href^="admin.php?page=zorgportal-edit-code"],#adminmenu a[href="admin.php?page=zorgportal-new-code"],#adminmenu a[href^="admin.php?page=zorgportal-edit-practitioner"],#adminmenu a[href="admin.php?page=zorgportal-new-practitioner"],#adminmenu a[href="admin.php?page=zorgportal-practitioner-invoices"],#adminmenu a[href="admin.php?page=zorgportal-edit-invoice"],#adminmenu a[href="admin.php?page=zorgportal-view-invoice"],#adminmenu a[href="admin.php?page=zorgportal-view-bulkinvoice"],#adminmenu a[href="admin.php?page=zorgportal-edit-bulkinvoice"],#adminmenu a[href^="admin.php?page=zorgportal-edit-patient"],#adminmenu a[href="admin.php?page=zorgportal-new-patient"],#adminmenu a[href="admin.php?page=zorgportal-view-patient"],#adminmenu a[href="admin.php?page=zorgportal-send-invoice-reminder"],#adminmenu a[href="admin.php?page=zorgportal-view-transaction"],#adminmenu a[href="admin.php?page=zorgportal-import-invoices"],#adminmenu a[href="admin.php?page=zorgportal-settings-old"]{display:none}</style>', PHP_EOL;
            });

            // notices
            add_action('admin_notices', function()
            {
                if ( (float) get_site_option($this->appContext::DB_VERSION_OPTION) !== $this->appContext::DB_VERSION ) {
                    echo '<div class="notice error"><p>'
                       , __('Please upgrade your database for <a href="plugins.php?s=zorgportal">Zorgportal</a> plugin by deativating then activating the plugin.', 'zorgportal')
                       , '</p></div>', PHP_EOL;
                }
            });
        }

        if ( is_admin() ) {
            // plugins meta link shortcut
            $plugin_base = plugin_basename( $this->appContext->getPluginFile() );
            add_filter("plugin_action_links_{$plugin_base}", [ $this, 'connectionsLinkShortcut' ]);
        }

        return $this;
    }

    public function pages()
    {
        add_menu_page(
            __('Zorgportal', 'zorgportal'),
            __('Zorgportal', 'zorgportal'),
            'manage_dbc_codes',
            'zorgportal',
            [$this->getScreenObject(DbcCodes::class), 'render'],
            'dashicons-cart'
        );
        

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; DbcCodes', 'zorgportal'),
            __('DbcCodes', 'zorgportal'),
            'manage_dbc_codes',
            'zorgportal'
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Add New Code', 'zorgportal'),
            null,
            'manage_dbc_codes',
            'zorgportal-new-code',
            [$this->getScreenObject( AddDbcCode::class ), 'render']
        );
        
        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Edit Code', 'zorgportal'),
            null,
            'manage_dbc_codes',
            'zorgportal-edit-code',
            [$this->getScreenObject( EditDbcCode::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Import Codes', 'zorgportal'),
            null,
            'manage_dbc_codes',
            'zorgportal-import-codes',
            [$this->getScreenObject( ImportCodes::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Import Invoices', 'zorgportal'),
            null,
            'manage_dbc_invoices',
            'zorgportal-import-invoices',
            [$this->getScreenObject( ImportInvoices::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Practitioners', 'zorgportal'),
            __('Practitioners', 'zorgportal'),
            'manage_dbc_practitioners',
            'zorgportal-practitioners',
            [$this->getScreenObject( Practitioners::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Edit Practitioner', 'zorgportal'),
            null,
            'manage_dbc_practitioners',
            'zorgportal-edit-practitioner',
            [$this->getScreenObject( EditPractitioner::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Add Practitioner', 'zorgportal'),
            null,
            'manage_dbc_practitioners',
            'zorgportal-new-practitioner',
            [$this->getScreenObject( AddPractitioner::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Practitioner Invoices', 'zorgportal'),
            null,
            'manage_dbc_practitioners',
            'zorgportal-practitioner-invoices',
            [$this->getScreenObject( PractitionerInvoices::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Invoices', 'zorgportal'),
            __('Invoices', 'zorgportal'),
            'manage_dbc_invoices',
            'zorgportal-invoices',
            [$this->getScreenObject( Invoices::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
             __('Zorgportal &mdash; Bulk Invoice', 'zorgportal'),
             null,
            'manage_dbc',
            'zorgportal-bulkinvoice',
            [$this->getScreenObject( BulkInvoice::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Edit Invoice', 'zorgportal'),
            null,
            'manage_dbc_invoices',
            'zorgportal-edit-invoice',
            [$this->getScreenObject( EditInvoice::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; View Invoice', 'zorgportal'),
            null,
            'manage_dbc_invoices',
            'zorgportal-view-invoice',
            [$this->getScreenObject( ViewInvoice::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Edit BulkInvoice', 'zorgportal'),
            null,
            'manage_dbc_invoices',
            'zorgportal-edit-bulkinvoice',
            [$this->getScreenObject( EditBulkInvoice::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; View BulkInvoice', 'zorgportal'),
            null,
            'manage_dbc_invoices',
            'zorgportal-view-bulkinvoice',
            [$this->getScreenObject( ViewBulkInvoice::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Send Invoice Reminders', 'zorgportal'),
            null,
            'manage_dbc_invoices',
            'zorgportal-send-invoice-reminder',
            [$this->getScreenObject( SendInvoiceReminder::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Transactions', 'zorgportal'),
            __('Transactions', 'zorgportal'),
            'manage_dbc_invoices',
            'zorgportal-transactions',
            [$this->getScreenObject( Transactions::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; View Transaction', 'zorgportal'),
            null,
            'manage_dbc_invoices',
            'zorgportal-view-transaction',
            [$this->getScreenObject( ViewTransaction::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Settings', 'zorgportal'),
            null,
            'manage_dbc',
            'zorgportal-settings-old',
            [$this->getScreenObject( SettingsOld::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Settings', 'zorgportal'),
            __('Settings', 'zorgportal'),
            'manage_dbc',
            'zorgportal-settings',
            [$this->getScreenObject( Settings::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Patients', 'zorgportal'),
            __('Patients', 'zorgportal'),
            'manage_dbc', // @feature may need custom role
            'zorgportal-patients',
            [$this->getScreenObject( Patients::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Edit Patient', 'zorgportal'),
            null,
            'manage_dbc', // @feature may need custom role
            'zorgportal-edit-patient',
            [$this->getScreenObject( EditPatient::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Add Patient', 'zorgportal'),
            null,
            'manage_dbc', // @feature may need custom role
            'zorgportal-new-patient',
            [$this->getScreenObject( AddPatient::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; View Patient', 'zorgportal'),
            null,
            'manage_dbc', // @feature may need custom role
            'zorgportal-view-patient',
            [$this->getScreenObject( ViewPatient::class ), 'render']
        );

        add_submenu_page(
            'zorgportal',
            __('Zorgportal &mdash; Logs', 'zorgportal'),
            __('Logs', 'zorgportal'),
            'manage_dbc',
            'zorgportal-logs',
            [$this->getScreenObject( Logs::class ), 'render']
        );
    }

    
    private function callPageScreenMethod(string $method)
    {
        switch ( $_REQUEST['page'] ?? null ) {
            case 'zorgportal':
                return call_user_func([$this->getScreenObject(DbcCodes::class), $method]);

            case 'zorgportal-import-codes':
                return call_user_func([$this->getScreenObject(ImportCodes::class), $method]);

            case 'zorgportal-import-invoices':
                return call_user_func([$this->getScreenObject(ImportInvoices::class), $method]);

            case 'zorgportal-edit-code':
                return call_user_func([$this->getScreenObject(EditDbcCode::class), $method]);

            case 'zorgportal-new-code':
                return call_user_func([$this->getScreenObject(AddDbcCode::class), $method]);

            case 'zorgportal-practitioners':
                return call_user_func([$this->getScreenObject(Practitioners::class), $method]);

            case 'zorgportal-edit-practitioner':
                return call_user_func([$this->getScreenObject(EditPractitioner::class), $method]);

            case 'zorgportal-new-practitioner':
                return call_user_func([$this->getScreenObject(AddPractitioner::class), $method]);

            case 'zorgportal-practitioner-invoices':
                return call_user_func([$this->getScreenObject(PractitionerInvoices::class), $method]);

            case 'zorgportal-invoices':
                return call_user_func([$this->getScreenObject(Invoices::class), $method]);

            case 'zorgportal-edit-invoice':
                return call_user_func([$this->getScreenObject(EditInvoice::class), $method]);

            case 'zorgportal-view-invoice':
                return call_user_func([$this->getScreenObject(ViewInvoice::class), $method]);

            case 'zorgportal-edit-bulkinvoice':
                return call_user_func([$this->getScreenObject(EditBulkInvoice::class), $method]);

            case 'zorgportal-view-bulkinvoice':
                return call_user_func([$this->getScreenObject(ViewBulkInvoice::class), $method]);

            case 'zorgportal-send-invoice-reminder':
                return call_user_func([$this->getScreenObject(SendInvoiceReminder::class), $method]);

            case 'zorgportal-settings':
                return call_user_func([$this->getScreenObject(Settings::class), $method]);

            case 'zorgportal-patients':
                return call_user_func([$this->getScreenObject(Patients::class), $method]);

            case 'zorgportal-new-patient':
                return call_user_func([$this->getScreenObject(AddPatient::class), $method]);

            case 'zorgportal-edit-patient':
                return call_user_func([$this->getScreenObject(EditPatient::class), $method]);

            case 'zorgportal-view-patient':
                return call_user_func([$this->getScreenObject(ViewPatient::class), $method]);

            case 'zorgportal-logs':
                return call_user_func([$this->getScreenObject(Logs::class), $method]);
                
            case 'zorgportal-bulkinvoice':
                return call_user_func([$this->getScreenObject(BulkInvoice::class), $method]);
        }
    }

    public function init()
    {
        return $this->callPageScreenMethod('init');
    }

    public function maybeUpdate()
    {
        return $this->callPageScreenMethod('update');
    }

    public function scripts()
    {
        return $this->callPageScreenMethod('scripts');
    }

    public function getScreenObject( string $class )
    {
        return $this->screenContext[$class] ?? ( $this->screenContext[$class] = new $class( $this->appContext ) );
    }

    public function connectionsLinkShortcut( $links )
    {
        return array_merge([
            'settings' => '<a href="admin.php?page=zorgportal">' . __('Manage', 'zorgportal') . '</a>'
        ], $links);
    }
}