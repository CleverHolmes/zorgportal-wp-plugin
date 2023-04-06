<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php _e('Zorgportal &lsaquo; Bulk Invoice Details', 'zorgportal'); ?></h2>

    <h3 style="margin-right:2rem"><?php _e('Bulk Invoice', 'zorgportal'); ?></h3>

    <table class="table widefat striped">
        <tbody>
            <tr>
                <td><strong><?php _e('Invoice ID', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['id']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Date', 'zorgportal'); ?></strong></td>
                <td><?php echo date('d-m-Y', strtotime($invoice['Date'])) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Number of Invoices', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['NumberInvoices']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Practitioner', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['Practitioner']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Insurer', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['Insurer']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Location', 'zorgportal'); ?></strong></td>
                <td><?php echo esc_attr($invoice['Location']) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Amount', 'zorgportal'); ?></strong></td>
                <td><?php echo '€ ', esc_attr(number_format($invoice['AmountTotal'], 2)) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Reimbursement', 'zorgportal'); ?></strong></td>
                <td><?php echo '€ ', esc_attr(number_format($invoice['ReimburseTotal'], 2)) ?: '-'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('ActualPaid', 'zorgportal'); ?></strong></td>
                <td>
                    <?php echo '€ ', esc_attr(number_format($invoice['actual'], 2)) ?: 'N/A'; ?>
                    <?php echo esc_attr($invoice['tranId']); ?>
                </td>
            </tr>
            <tr>
                <td><strong><?php _e('Status', 'zorgportal'); ?></strong></td>
                <td><?php \Zorgportal\Invoices::printStatus($invoice); ?></td>
            </tr>     
        </tbody>
    </table>
    
    &nbsp;
    <h3 style="margin-right:2rem"><?php _e('Invoices', 'zorgportal'); ?></h3>
    <table class="wp-list-table widefat striped posts xfixed" >
        <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column">
                    <label class="screen-reader-text" for="cb-select-all-1"><?php esc_attr_e('Select All'); ?></label>
                    <input id="cb-select-all-1" type="checkbox">
                </td>

                <th scope="col" class="manage-column column-title column-primary">
                    <span><?php esc_attr_e('Invoice id', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <span><?php esc_attr_e('Invoice date', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <span><?php esc_attr_e('Location', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <span><?php esc_attr_e('Practitioner', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary ">
                    <span><?php esc_attr_e('DBC Code', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary ">
                    <span><?php esc_attr_e('SubtrajectDeclaratiecodeOmschrijving', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <span><?php esc_attr_e('SubtrajectStartdatum', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <span><?php esc_attr_e('SubtrajectEinddatum', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary ">
                    <span><?php esc_attr_e('Insurer', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <span><?php esc_attr_e('Policy', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <span><?php esc_attr_e('Amount', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <span><?php esc_attr_e('Reimbursement', 'zorgportal'); ?></span>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <span><?php esc_attr_e('Status', 'zorgportal'); ?></span>
                </th>
                <th></th>
            </tr>
        </thead>

        <tbody id="the-list">
            <?php if ( $childs) : ?>
                <?php foreach ( $childs as $child_entry ) : ?>
                    <tr id="post-<?php echo $entry['id']; ?>" class="iedit author-self level-0 post-<?php echo $child_entry['id']; ?> type-post status-publish format-standard hentry category-uncategorized entry">
                        <th scope="row" class="check-column">
                            <input id="cb-select-<?php echo $child_entry['id']; ?>" type="checkbox" name="items[]" value="<?php echo $child_entry['id']; ?>">
                        </th>
                        <td class="author column-author"><?php echo esc_attr($child_entry['DeclaratieNummer']) ?: '-'; ?></td>
                        <td class="author column-author"><?php echo esc_attr(preg_replace('/\s\d+\:.+$/', '', $child_entry['DeclaratieDatum'])) ?: '-'; ?></td>
                        <td class="author column-author"><?php echo esc_attr(explode(' - ', $child_entry['SubtrajectHoofdbehandelaar'])[1] ?? '') ?: '-'; ?></td>
                        <td class="author column-author"><?php echo esc_attr(explode(' - ', $child_entry['SubtrajectHoofdbehandelaar'])[0] ?? '') ?: '-'; ?></td>
                        <td class="author column-author"><?php echo esc_attr($child_entry['SubtrajectDeclaratiecode']) ?: '-'; ?></td>
                        <td class="author column-author"><?php echo esc_attr($child_entry['SubtrajectDeclaratiecodeOmschrijving']) ?: '-'; ?></td>
                        <td class="author column-author"><?php echo esc_attr(preg_replace('/\s\d+\:.+$/', '', $child_entry['SubtrajectStartdatum'])) ?: '-'; ?></td>
                        <td class="author column-author"><?php echo esc_attr(preg_replace('/\s\d+\:.+$/', '', $child_entry['SubtrajectEinddatum'])) ?: '-'; ?></td>
                        <td class="author column-author"><?php echo esc_attr($child_entry['ZorgverzekeraarNaam']) ?: '-'; ?></td>
                        <td class="author column-author"><?php echo esc_attr($child_entry['ZorgverzekeraarPakket']) ?: '-'; ?></td>
                        <td class="author column-author" style="white-space:nowrap"><?php echo '€ ', esc_attr(number_format($child_entry['DeclaratieBedrag'], 2)) ?: '-'; ?></td>
                        <td class="author column-author" style="white-space:nowrap"><?php echo '€ ', esc_attr(number_format($child_entry['ReimburseAmount'], 2)) ?: '-'; ?></td>
                        <td><?php \Zorgportal\Invoices::printStatus($child_entry); ?></td>
                        <td class="author column-author">
                            <a href="admin.php?page=zorgportal-view-invoice&id=<?php echo $child_entry['id']; ?>"><?php _e('View', 'zorgportal'); ?></a>
                            <br/>
                            <a href="admin.php?page=zorgportal-edit-invoice&id=<?php echo $child_entry['id']; ?>"><?php _e('Edit', 'zorgportal'); ?></a>
                            <br/>
                            <a href="<?php echo add_query_arg([
                                'update_id' => $child_entry['id'],
                                '_wpnonce' => $nonce,
                            ]); ?>"><?php _e('Update status', 'zorgportal'); ?></a>
                            <br/>
                            <a href="javascript:" class="button-link-delete zportal-inline-delete"><?php _e('Delete', 'zorgportal'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr id="post-0" class="iedit author-self level-0 post-0 type-post status-publish format-standard hentry category-uncategorized entry">
                    <td class="author column-author" colspan="15" style="text-align:center;padding:1rem">
                        <em><?php count($_GET) > 1 ? _e('Nothing found for your current filters.', 'zorgportal') : _e('Nothing to show yet.', 'zorgportal'); ?></em>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    &nbsp;
    <h3 style="margin-right:2rem"><?php _e('Transactions', 'zorgportal'); ?></h3>
    <table class="wp-list-table widefat striped posts xfixed" style="margin-top:14px">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('No', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Date', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Description', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Notes', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Amount', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Open', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Last Modified', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Import Date', 'zorgportal'); ?></strong>
                </th>

                <th scope="col" class="manage-column column-title column-primary">
                    <strong><?php esc_attr_e('Status', 'zorgportal'); ?></strong>
                </th>
            </tr>
        </thead>

        <tbody id="the-list">
            
            <?php $i = 1; if ( count($txns) > 0 ) : ?>
                <?php foreach ( $txns as $txn ) : ?>
                    <tr id="post-<?php echo $txn['id']; ?>" class="iedit author-self level-0 post-<?php echo $txn['id']; ?> type-post status-publish format-standard hentry category-uncategorized entry">
                        <td class="author column-author"><?php echo esc_attr($i++); ?></td>
                        <td class="author column-author"><?php echo esc_attr(preg_replace('/\s\d+\:.+$/', '', $txn['Created'])); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['Description']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['Notes']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['AmountDC']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['AmountFC']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['Modified']); ?></td>
                        <td class="author column-author"><?php echo esc_attr($txn['Date']); ?></td>
                        <td class="author column-author"><?php \Zorgportal\Invoices::printStatus($txn); ?></td>
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
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />

    <p>
        <a href="admin.php?page=zorgportal-bulkinvoice" class="button"><?php _e('&laquo; Back to Bulk Invoices', 'zorgportal'); ?></a>
    </p>
</div>

