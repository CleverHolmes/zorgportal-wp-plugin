<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php printf(__('Zorgportal &lsaquo; %s &lsaquo; Invoices', 'zorgportal'), $practitioner['name']); ?></h2>

    <form method="post" id="zp-import">
        <p style="margin-top:1.5rem">
            <label>
                <strong style="display:table;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">
                    <?php _e('Date Period', 'zorgportal'); ?>
                </strong>

                <input type="radio" name="date_criterea" value="quarter" checked="checked" <?php checked(($_POST['date_criterea'] ?? '') == 'quarter'); ?> />
                <span><?php _e('Select Date Quarter', 'zorgportal'); ?></span>
            </label>

            <br/>

            <label>
                <input type="radio" name="date_criterea" value="range" <?php checked(($_POST['date_criterea'] ?? '') == 'range'); ?> />
                <span><?php _e('Or Select From - To Date', 'zorgportal'); ?></span>
            </label>
        </p>

        <p style="display:flex;align-items:flex-start;margin-top:1rem">
            <span style="display:<?php echo ($_POST['date_criterea'] ?? '') != 'range' ? 'flex' : 'none'; ?>;align-items:center" id="zp-date-quarter">
                <select name="quarter">
                    <option value=""><?php esc_attr_e('&mdash; select &mdash;', 'zorgportal'); ?></option>
                    <?php foreach ( range(0,4) as $year ) : ?>
                        <?php foreach ( range(1,4) as $quarter ) : $time = strtotime("-{$year} years") ?>
                            <option value="<?php echo esc_attr(date('Y', $time) . "-{$quarter}"); ?>" <?php selected(($_POST['quarter'] ?? '') == date('Y', $time) . "-{$quarter}"); ?>>
                                <?php echo esc_attr(sprintf(__('Q%d %d', 'zorgportal'), $quarter, date('Y', $time))); ?></option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>&nbsp;
            </span>

            <span style="display:<?php echo ($_POST['date_criterea'] ?? '') == 'range' ? 'flex' : 'none'; ?>;align-items:center" id="zp-date-range">
                <label>
                    <span><?php _e('From', 'zorgportal'); ?></span>
                    <input type="date" name="date_from" value="<?php echo esc_attr($_POST['date_from'] ?? ''); ?>" />
                </label>

                <label style="margin-left:8px">
                    <span><?php _e('To', 'zorgportal'); ?></span>
                    <input type="date" name="date_to" value="<?php echo esc_attr($_POST['date_to'] ?? ''); ?>" />
                </label>&nbsp;
            </span>

            <span style="align-items:center">
                <select name="location">
                    <option value=""><?php esc_attr_e('&mdash; location &mdash;', 'zorgportal'); ?></option>
                    <?php foreach ( $invoice_locations as $location ) : ?>
                        <option <?php selected(($_POST['location'] ?? '') == $location); ?>><?php echo esc_attr($location); ?></option>
                    <?php endforeach; ?>
                </select>&nbsp;
            </span>

            <button type="submit" class="button" name="filter"><?php esc_attr_e('Filter', 'zorgportal'); ?></button>
        </p>

        <?php if ( ! is_null($invoices) ) : ?>
            <table>
                <tbody>
                    <tr>
                        <td><?php _e('Total Revenue', 'zorgportal'); ?></td>
                        <td style="padding-left:1.5rem"><?php echo '€ ', array_sum(wp_list_pluck($invoices, 'ReimburseAmount')); ?></td>
                    </tr>

                    <tr>
                        <td><?php _e('Honorarium', 'zorgportal'); ?></td>
                        <td style="padding-left:1.5rem"><?php echo esc_attr($practitioner['fee']), ' %'; ?></td>
                    </tr>

                    <tr>
                        <td><?php _e('Total vergoeding', 'zorgportal'); ?></td>
                        <td style="padding-left:1.5rem"><?php echo '€ ', array_sum(wp_list_pluck($invoices, 'ReimburseAmount'))* $practitioner['fee']/100; ?></td>
                    </tr>
                </tbody>
            </table>

            <p>
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <input type="submit" class="button button-primary" name="download" value="<?php esc_attr_e('Download', 'zorgportal'); ?>">
            </p>

            <p style="margin-top:.5rem">
                <label>
                    <strong style="display:table;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">
                        <?php _e('All invoices from that period', 'zorgportal'); ?>
                    </strong>
                </label>
            </p>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><strong><?php _e('Invoice id', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('Invoice Date', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('Location', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('DossierNUmmer', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('SubtrajectStartdatum', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('SubtrajectEinddatum', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('SubtrajectDeclaratiecode', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('SubtrajectDeclaratiecodeOmschrijving', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('SubtrajectDeclaratiebedrag', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('Reimburse Amount', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('Honorarium', 'zorgportal'); ?></strong></th>
                        <th><strong><?php _e('Vergoeding', 'zorgportal'); ?></strong></th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ( 0 == count($invoices) ) : ?>
                        <tr>
                            <td colspan="11">
                                <em style="display:table;margin:0 auto"><?php _e('No invoices available. Try resetting your filters.', 'zorgportal'); ?></em>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $invoices as $invoice ) : ?>
                             <tr>
                                <td><?php echo esc_attr($invoice['DeclaratieNummer'] ?? ''); ?></td>
                                <td><?php echo esc_attr($dateFmt($invoice['DeclaratieDatum'] ?? '')); ?></td>
                                <td><?php echo esc_attr(explode(' - ', $invoice['SubtrajectHoofdbehandelaar'])[1] ?? '') ?: '-'; ?></td>
                                <td><?php echo esc_attr($invoice['DossierNUmmer'] ?? ''); ?></td>
                                <td><?php echo esc_attr($dateFmt($invoice['SubtrajectStartdatum'] ?? '')); ?></td>
                                <td><?php echo esc_attr($dateFmt($invoice['SubtrajectEinddatum'] ?? '')); ?></td>
                                <td><?php echo esc_attr($invoice['SubtrajectDeclaratiecode'] ?? ''); ?></td>
                                <td><?php echo esc_attr($invoice['SubtrajectDeclaratiecodeOmschrijving'] ?? ''); ?></td>
                                <td><?php echo '€ ', esc_attr(number_format($invoice['SubtrajectDeclaratiebedrag'], 2)); ?></td>
                                <td><?php echo '€ ', esc_attr(number_format($invoice['ReimburseAmount'] ?? '', 2)); ?></td>
                                <td><?php echo esc_attr($practitioner['fee']), '%'; ?></td>
                                <td><?php echo '€ ', esc_attr(number_format( round(floatval($invoice['ReimburseAmount']) * $practitioner['fee']/100, 2), 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <input type="submit" class="button button-primary" name="download" value="<?php esc_attr_e('Download', 'zorgportal'); ?>">
            </p>
        <?php endif; ?>
    </form>
</div>