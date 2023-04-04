<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php _e('Zorgportal &lsaquo; Invoice Details', 'zorgportal'); ?></h2>

    <p></p>
    <div style="display:flex;align-items:center">
        <h3 style="margin-right:2rem"><?php _e('Factuur', 'zorgportal'); ?></h3>
        
        <a class="button button-primary" href="<?php echo add_query_arg([
            'update_invoice' => 1,
            '_wpnonce' => $nonce,
        ]); ?>"><?php _e('Refresh status', 'zorgportal'); ?></a>

        <a class="button button-primary" style="margin-left:10px" href="admin.php?page=zorgportal-send-invoice-reminder&id=<?php echo esc_attr($invoice['id']); ?>&reminder=1"><?php _e('Send first reminder', 'zorgportal'); ?></a>

        <a class="button button-primary" style="margin-left:10px" href="admin.php?page=zorgportal-send-invoice-reminder&id=<?php echo esc_attr($invoice['id']); ?>&reminder=2"><?php _e('Send second reminder', 'zorgportal'); ?></a>
    </div>

    <table class="table widefat striped">
        <tbody>
            <tr>
                <td><strong><?php _e('Nummer', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['DeclaratieNummer']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Datum', 'zorgportal'); ?></strong></td>
                <td><?php echo date('d-m-Y', strtotime($invoice['DeclaratieDatum'])) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Omschrijving', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['SubtrajectDeclaratiecodeOmschrijving']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Bedrag', 'zorgportal'); ?></strong></td>
                <td><?php echo '€ ', esc_attr(number_format($invoice['DeclaratieBedrag'], 2)) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Vergoeding', 'zorgportal'); ?></strong></td>
                <td><?php echo '€ ', esc_attr(number_format($invoice['ReimburseAmount'], 2)) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Verzekeraar', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['ZorgverzekeraarNaam']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Polis', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['ZorgverzekeraarPakket']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Uiterste betaaldatum', 'zorgportal'); ?></strong></td>
                <td><?php echo date('d-m-Y', strtotime($invoice['DeclaratieDatum']) + 28 * DAY_IN_SECONDS) ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Status', 'zorgportal'); ?></strong></td>
                <td><?php \Zorgportal\Invoices::printStatus($invoice); ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Dossiernummer', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['DossierNUmmer']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Dossieromschrijving', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['SubtrajectDeclaratiecodeOmschrijving']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Locatie', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr(explode(' - ', $invoice['SubtrajectHoofdbehandelaar'])[1] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Behandelaar', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr(explode(' - ', $invoice['SubtrajectHoofdbehandelaar'])[0] ?? '') ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Diagnosecode', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['SubtrajectDeclaratiecode']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Behandelingnummer', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['DeclaratieDebiteurnummer']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Startdatum', 'zorgportal'); ?></strong></td>
                <td><?php echo date('d-m-Y', strtotime($invoice['SubtrajectStartdatum'])) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Einddatum', 'zorgportal'); ?></strong></td>
                <td><?php echo date('d-m-Y', strtotime($invoice['SubtrajectEinddatum'])) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Behandeling omschrijving', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['DeclaratieregelOmschrijving']) ?: '-'; ?></td>
            </tr>

        </tbody>
    </table>

    &nbsp;
    <h3 style="margin-right:2rem"><?php _e('Betaling', 'zorgportal'); ?></h3>

    <table class="wp-list-table widefat striped posts xfixed" style="margin-top:14px">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Aanmaak Datum', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Datum', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('AccountName', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Description', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('AmountDC', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('GLAccountCode', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('GLAccountDescription', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Notes', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Type', 'zorgportal'); ?></strong>
                </th>
            </tr>
        </thead>

        <tbody id="the-list">
            
            // template randomly gets txns ; should be only for this invoice.

            <?php if ( count($txns) > 0 ) : ?>
                <?php foreach ( $txns as $txn ) : ?>
                    <tr id="post-<?php echo $txn['id']; ?>" class="iedit author-self level-0 post-<?php echo $txn['id']; ?> type-post status-publish format-standard hentry category-uncategorized entry">
                        <td class="author column-author"><?php echo esc_attr(preg_replace('/\s\d+\:.+$/', '', $txn['Created'])); ?></td>
                        <td class="author column-author"><?php echo esc_attr(preg_replace('/\s\d+\:.+$/', '', $txn['Date'])); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['AccountName']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['Description']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['AmountDC']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['GLAccountCode']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['GLAccountDescription']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['Notes']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['Type']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr id="post-0" class="iedit author-self level-0 post-0 type-post status-publish format-standard hentry category-uncategorized entry">
                    <td class="author column-author" colspan="9" style="text-align:center;padding:1rem">
                        <em><?php _e('No transactions found.', 'zorgportal'); ?></em>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p>
        <a href="admin.php?page=zorgportal-invoices" class="button"><?php _e('&laquo; Back to Invoices', 'zorgportal'); ?></a>
    </p>
</div>

