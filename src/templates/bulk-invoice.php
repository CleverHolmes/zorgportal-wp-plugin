<?php defined('WPINC') || exit; ?>

<div class="wrap">
    <h2 style="display:none"></h2>

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:.55rem">
        <h1 style="padding:9px 9px 9px 0"><?php _e('Zorgportal &lsaquo; Bulk invoices', 'zorgportal'); ?></h1>
    </div>

    <form method="post" action="/" data-action="<?php echo remove_query_arg('bulk'); ?>" id="zportal-items" data-confirm="<?php esc_attr_e('Are you sure?', 'zorgportal'); ?>">
        <table class="wp-list-table widefat striped posts xfixed">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1"><?php esc_attr_e('Select All'); ?></label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'id' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('id')); ?>">
                        <a href="<?php echo add_query_arg('sort', "id,{$getNextSort('id')}"); ?>">
                            <span><?php esc_attr_e('Bulk Invoice ID', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'Date' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('Date')); ?>">
                        <a href="<?php echo add_query_arg('sort', "Date,{$getNextSort('Date')}"); ?>">
                            <span><?php esc_attr_e('Invoice date', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'Location' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('Location')); ?>">
                        <a href="<?php echo add_query_arg('sort', "Location,{$getNextSort('Location')}"); ?>">
                            <span><?php esc_attr_e('Location', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'Practitioner' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('Practitioner')); ?>">
                        <a href="<?php echo add_query_arg('sort', "Practitioner,{$getNextSort('Practitioner')}"); ?>">
                            <span><?php esc_attr_e('Practitioner', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'Insurer' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('Insurer')); ?>">
                        <a href="<?php echo add_query_arg('sort', "Insurer,{$getNextSort('Insurer')}"); ?>">
                            <span><?php esc_attr_e('Insurer', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'NumberInvoices' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('NumberInvoices')); ?>">
                        <a href="<?php echo add_query_arg('sort', "NumberInvoices,{$getNextSort('NumberInvoices')}"); ?>">
                            <span><?php esc_attr_e('Invoices', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'AmountTotal' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('AmountTotal')); ?>">
                        <a href="<?php echo add_query_arg('sort', "AmountTotal,{$getNextSort('AmountTotal')}"); ?>">
                            <span><?php esc_attr_e('Amount', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'ReimburseTotal' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('ReimburseTotal')); ?>">
                        <a href="<?php echo add_query_arg('sort', "ReimburseTotal,{$getNextSort('ReimburseTotal')}"); ?>">
                            <span><?php esc_attr_e('Reimbursement', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'Status' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('Status')); ?>">
                        <a href="<?php echo add_query_arg('sort', "Status,{$getNextSort('Status')}"); ?>">
                            <span><?php esc_attr_e('Actual Paid', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'Status' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('Status')); ?>">
                        <a href="<?php echo add_query_arg('sort', "Status,{$getNextSort('Status')}"); ?>">
                            <span><?php esc_attr_e('Status', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column column-title column-primary">
                    </th>
                    <th scope="col" class="manage-column column-title column-primary">
                    </th>
                </tr>
            </thead>

            <tbody id="the-list">
                <?php if ( $newList ) : ?>
                    <?php foreach ( $newList as $entry ) : ?>
                        <tr id="parent-<?php echo $entry['parent_id']; ?>" class="iedit author-self level-0 parent-<?php echo $entry['parent_id']; ?> type-post status-publish format-standard hentry category-uncategorized entry">
                            <td scope="row" class="check-column" style="text-align:center;vertical-align: middle;">
                                <input id="cb-select-<?php echo $entry['parent_id']; ?>" type="checkbox" name="items[]" value="<?php echo $entry['parent_id']; ?>" style="margin-top:20px; margin-left:10px">
                            </td>
                            <td class="author column-author" style="vertical-align: middle;"><?php echo esc_attr($entry['parent_id']) ?: '-'; ?></td>
                            <td class="author column-author " style="vertical-align: middle;"><?php echo esc_attr(preg_replace('/\s\d+\:.+$/', '', $entry['Date'])) ?: '-'; ?></td>
                            <td class="author column-author" style="vertical-align: middle;"><?php echo esc_attr($entry['Location']) ?: '-'; ?></td>
                            <td class="author column-author" style="vertical-align: middle;"><?php echo esc_attr($entry['Practitioner']) ?: '-'; ?></td>
                            <td class="author column-author" style="vertical-align: middle;"><?php echo esc_attr($entry['Insurer']) ?: '-'; ?></td>
                            <td class="author column-author" style="vertical-align: middle;"><?php echo esc_attr($entry['NumberInvoices']) ?: '-'; ?></td>
                            <td class="author column-author" style="white-space:nowrap; vertical-align: middle;"><?php echo '€ ', esc_attr(number_format($entry['AmountTotal'], 2)) ?: '-'; ?></td>
                            <td class="author column-author" style="white-space:nowrap; vertical-align: middle;"><?php echo '€ ', esc_attr(number_format($entry['ReimburseTotal'], 2)) ?: '-'; ?></td>
                            <td class="author column-author" style="white-space:nowrap; vertical-align: middle;">    
                                <?php echo $entry['actual'] > 0 ? '€' . esc_attr(number_format($entry['actual'], 2)) : 'N/A';?>                    
                                <?php echo esc_attr($entry['tranId']); ?>
                            </td>
                            <td style="vertical-align: middle;"><?php \Zorgportal\BulkInvoice::bulkStatus($entry); ?></td>

                            <td class="author column-author" style="vertical-align: middle;">
                                <a href="admin.php?page=zorgportal-view-bulkinvoice&id=<?php echo $entry['parent_id']; ?>"><?php _e('View', 'zorgportal'); ?></a>
                                <br/>
                                <a href="admin.php?page=zorgportal-edit-bulkinvoice&id=<?php echo $entry['parent_id']; ?>"><?php _e('Edit', 'zorgportal'); ?></a>
                                <br/>
                                <a href="javascript:" class="button-link-delete zportal-inline-delete"><?php _e('Delete', 'zorgportal'); ?></a>
                            </td>
                            <td style="vertical-align: middle;">
                               <span class="dashicons dashicons-arrow-down-alt2 collapse_icn" style="color: '#f44336'; cursor:pointer; marginLeft: 4;" data-show="0" data-id="<?php echo $entry['parent_id']; ?>"></span>
                            </td>
                        </tr>

                        <tr id="child-<?php echo $entry['parent_id']; ?>"  class="iedit author-self level-0 child-<?php echo $entry['child_id']; ?> type-post status-publish format-standard hentry category-uncategorized entry" style="display:none;">
                            <td colspan="12" style="text-align:center">
                            <table class="wp-list-table widefat striped posts xfixed" style="width: 300px;   overflow-x: scroll;">
                                <thead>
                                    <tr>
                                        <td id="cb" class="manage-column column-cb check-column">
                                            <label class="screen-reader-text" for="cb-select-all-1"><?php esc_attr_e('Select All'); ?></label>
                                            <input id="cb-select-all-1" type="checkbox">
                                        </td>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'DeclaratieNummer' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('DeclaratieNummer')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "DeclaratieNummer,{$getNextSort('DeclaratieNummer')}"); ?>">
                                                <span><?php esc_attr_e('Invoice id', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'DeclaratieDatum' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('DeclaratieDatum')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "DeclaratieDatum,{$getNextSort('DeclaratieDatum')}"); ?>">
                                                <span><?php esc_attr_e('Invoice date', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary">
                                            <span><?php esc_attr_e('Location', 'zorgportal'); ?></span>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary">
                                            <span><?php esc_attr_e('Practitioner', 'zorgportal'); ?></span>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'SubtrajectDeclaratiecode' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('SubtrajectDeclaratiecode')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "SubtrajectDeclaratiecode,{$getNextSort('SubtrajectDeclaratiecode')}"); ?>">
                                                <span><?php esc_attr_e('DBC Code', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'SubtrajectDeclaratiecodeOmschrijving' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('SubtrajectDeclaratiecodeOmschrijving')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "SubtrajectDeclaratiecodeOmschrijving,{$getNextSort('SubtrajectDeclaratiecodeOmschrijving')}"); ?>">
                                                <span><?php esc_attr_e('SubtrajectDeclaratiecodeOmschrijving', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'SubtrajectStartdatum' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('SubtrajectStartdatum')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "SubtrajectStartdatum,{$getNextSort('SubtrajectStartdatum')}"); ?>">
                                                <span><?php esc_attr_e('SubtrajectStartdatum', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'SubtrajectEinddatum' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('SubtrajectEinddatum')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "SubtrajectEinddatum,{$getNextSort('SubtrajectEinddatum')}"); ?>">
                                                <span><?php esc_attr_e('SubtrajectEinddatum', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'ZorgverzekeraarNaam' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('ZorgverzekeraarNaam')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "ZorgverzekeraarNaam,{$getNextSort('ZorgverzekeraarNaam')}"); ?>">
                                                <span><?php esc_attr_e('Insurer', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'ZorgverzekeraarPakket' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('ZorgverzekeraarPakket')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "ZorgverzekeraarPakket,{$getNextSort('ZorgverzekeraarPakket')}"); ?>">
                                                <span><?php esc_attr_e('Policy', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'DeclaratieBedrag' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('DeclaratieBedrag')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "DeclaratieBedrag,{$getNextSort('DeclaratieBedrag')}"); ?>">
                                                <span><?php esc_attr_e('Amount', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'ReimburseAmount' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('ReimburseAmount')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "ReimburseAmount,{$getNextSort('ReimburseAmount')}"); ?>">
                                                <span><?php esc_attr_e('Reimbursement', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>

                                        <th scope="col" class="manage-column column-title column-primary <?php echo 'EoStatus' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('EoStatus')); ?>">
                                            <a href="<?php echo add_query_arg('sort', "EoStatus,{$getNextSort('EoStatus')}"); ?>">
                                                <span><?php esc_attr_e('Status', 'zorgportal'); ?></span>
                                                <span class="sorting-indicator"></span>
                                            </a>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>

                                <tbody id="the-list">
                                    <?php if ( $entry['child'] ) : ?>
                                        <?php foreach ( $entry['child'] as $child_entry ) : ?>
                                            <tr id="post-<?php echo $entry['child_id']; ?>" class="iedit author-self level-0 post-<?php echo $child_entry['child_id']; ?> type-post status-publish format-standard hentry category-uncategorized entry">
                                                <th scope="row" class="check-column">
                                                    <input id="cb-select-<?php echo $child_entry['child_id']; ?>" type="checkbox" name="items[]" value="<?php echo $child_entry['child_id']; ?>">
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
                                                    <a href="admin.php?page=zorgportal-view-invoice&id=<?php echo $child_entry['child_id']; ?>"><?php _e('View', 'zorgportal'); ?></a>
                                                    <br/>
                                                    <a href="admin.php?page=zorgportal-edit-invoice&id=<?php echo $child_entry['child_id']; ?>"><?php _e('Edit', 'zorgportal'); ?></a>
                                                    <br/>
                                                    <a href="<?php echo add_query_arg([
                                                        'update_id' => $child_entry['child_id'],
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

        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
    </form>

    <!-- <?php if ( $has_prev ) : ?>
        <a href="<?php echo add_query_arg('p', $current_page -1); ?>" class="button" style="margin-top:1rem"><?php _e('&larr; Previous Page', 'zorgportal'); ?></a>
    <?php endif; ?>

    <?php if ( $has_next ) : ?>
        <a href="<?php echo add_query_arg('p', $current_page +1); ?>" class="button" style="margin-top:1rem"><?php _e('Next Page &rarr;', 'zorgportal'); ?></a>
    <?php endif; ?> -->

</div>
