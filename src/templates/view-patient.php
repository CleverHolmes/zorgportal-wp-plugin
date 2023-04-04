<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php _e('Zorgportal &lsaquo; Patient Details', 'zorgportal'); ?></h2>

    <p></p>
    <h3><?php _e('Patient', 'zorgportal'); ?></h3>

    <table class="table widefat striped">
        <tbody>
            <tr>
                <td><strong><?php _e('Status', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['status'] ?? ''); ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Nummer', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['id'] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Naam', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['name'] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Telefoon', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['phone'] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Email', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['email'] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Adres', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['address'] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Verzekeraar', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['insurer'] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('UZOVI', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['UZOVI'] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Pakket', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['policy'] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Locatie', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['location'] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Behandelaar', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['practitioner'] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Laast bijgewerkt', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($patient['last_edited'] ?? '') ?: '-'; ?></td>
            </tr>
        </tbody>
    </table>

    <h3><?php _e('Facturen', 'zorgportal'); ?></h3>

    <table class="table widefat striped">
        <thead>
            <tr>
                <th><?php _e('Nummer', 'zorgportal'); ?></th>
                <th><?php _e('Datum', 'zorgportal'); ?></th>
                <th><?php _e('Bedrag', 'zorgportal'); ?></th>
                <th><?php _e('Vergoeding', 'zorgportal'); ?></th>
                <th><?php _e('Uiterste betaaldatum', 'zorgportal'); ?></th>
                <th><?php _e('Status', 'zorgportal'); ?></th>
                <th><?php _e('Acties', 'zorgportal'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( count($invoices) > 0 ) : ?>
                <?php foreach ( $invoices as $invoice ) : ?>
                    <tr>
                        <td><?php echo esc_attr( $invoice['DeclaratieNummer'] ) ?: '-'; ?></td>
                        <td><?php echo esc_attr( $invoice['DeclaratieDatum'] ) ?: '-'; ?></td>
                        <td><?php echo '&euro; ', esc_attr( $invoice['DeclaratieBedrag'] ) ?: '-'; ?></td>
                        <td><?php echo '&euro; ', esc_attr( $invoice['ReimburseAmount'] ) ?: '-'; ?></td>
                        <td><?php echo date('Y-m-d', strtotime($invoice['DeclaratieDatum']) + 28 * DAY_IN_SECONDS) ?></td>
                        <td><?php \Zorgportal\Invoices::printStatus($invoice); ?></td>
                        <td>
                            <a href="admin.php?page=zorgportal-view-invoice&id=<?php echo $invoice['id']; ?>"><?php _e('View', 'zorgportal'); ?></a>
                            &nbsp;
                            <a href="mailto:<?php echo esc_attr($patient['email']); ?>"><?php _e('Contact opnemen', 'zorgportal'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7"><?php _e('No invoices found.', 'zorgportal'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p>
        <a href="admin.php?page=zorgportal-patients" class="button"><?php _e('&laquo; Back to Patients', 'zorgportal'); ?></a>
    </p>
</div>

