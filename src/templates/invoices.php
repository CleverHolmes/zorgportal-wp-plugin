<?php defined('WPINC') || exit; ?>

<div class="wrap">
    <h2 style="display:none"></h2>
    
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:.55rem">

        <form method="post" action="admin.php?page=zorgportal-invoices" style="margin-left:4px;position:absolute;margin-left: 294px;margin-top: -60px;" onsubmit="return confirm('<?php esc_attr_e('Are you sure?', 'zorgportal'); ?>')">
            <?php if ( $list ) : ?>
                <?php foreach ( $list as $entry ) : ?>
                <input type="hidden" name="h_items[]" value="<?php echo $entry['id']; ?>" >
                <?php endforeach; ?>
            <?php endif; ?>
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
            <input type="hidden" name="export_bulk" value="1" />
            <input type="submit" class="page-title-action"  style="position:static;margin-right:7px" value="<?php _e('Export', 'zorgportal'); ?>" />
        </form>
        <form style="display:flex;flex-wrap:wrap;align-items:center;">
            <h1 style="padding:9px 9px 9px 0"><?php _e('Zorgportal &lsaquo; Invoices', 'zorgportal'); ?></h1>
            <a href="admin.php?page=zorgportal-import-invoices" class="page-title-action" style="position:static;margin-right:7px"><?php _e('Import', 'zorgportal'); ?></a>
        
            <?php foreach ( $_GET as $arg => $value ) : ?>
                <input type="hidden" name="<?php echo esc_attr($arg); ?>" value="<?php echo esc_attr($value); ?>" />
            <?php endforeach; ?>

            <div style="<?php echo ($_GET['date_criteria'] ?? '') != 'range' ? '' : 'display:none'; ?>">
                <div style="display:flex;align-items:center;margin-left:80px">
                    <select name="year">
                        <option value=""><?php esc_attr_e('Year', 'zorgportal'); ?></option>
                        <?php foreach ( range(($y=intval(date('Y')))-3, $y+10) as $year ) : ?>
                            <option value="<?php echo esc_attr($year); ?>" <?php selected(($_GET['year'] ?? '') == $year); ?>>
                                <?php echo esc_attr($year); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label onclick="jQuery(this).parent().parent().hide().next().show()" style="margin-left:3px;color:#03a9f4;cursor:pointer;text-decoration:underline">
                        <input type="radio" name="date_criteria" value="range" style="visibility:hidden;position:absolute;left:-9999999px" class="zp-criteria-range" <?php checked( ($_GET['date_criteria'] ?? '') == 'range' ); ?> />
                        <?php _e('select period', 'zorgportal'); ?>
                    </label>
                </div>
            </div>

            <div style="<?php echo ($_GET['date_criteria'] ?? '') == 'range' ? '' : 'display:none'; ?>">
                <div style="display:flex;align-items:center">
                    <label style="display:table">
                        <span><?php _e('From', 'zorgportal'); ?></span>
                        <input type="date" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>" />
                    </label>

                    <label style="display:table">
                        <span><?php _e('To', 'zorgportal'); ?></span>
                        <input type="date" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>" />
                    </label>

                    <label onclick="jQuery(this).parent().parent().hide().prev().show()" style="margin-left:3px;color:#03a9f4;cursor:pointer;text-decoration:underline">
                        <input type="radio" name="date_criteria" value="year" style="visibility:hidden;position:absolute;left:-9999999px" class="zp-criteria-year" <?php checked( ($_GET['date_criteria'] ?? '') != 'range' ); ?> />
                        <?php _e('select year', 'zorgportal'); ?>
                    </label>
                </div>
            </div>

            <select name="location" style="margin-left:4px">
                <option value=""><?php esc_attr_e('Location', 'zorgportal'); ?></option>
                <?php foreach ( $locations as $location ) : ?>
                    <option <?php selected($location == ($_GET['location'] ?? '')); ?>><?php echo esc_attr($location); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="practitioner" style="margin-left:2px">
                <option value=""><?php esc_attr_e('Practitioner', 'zorgportal'); ?></option>
                <?php foreach ( $practitioners as $practitioner ) : ?>
                    <option <?php selected($practitioner == ($_GET['practitioner'] ?? '')); ?>><?php echo esc_attr($practitioner); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="specialty" style="margin-left:2px">
                <option value=""><?php esc_attr_e('Specialty', 'zorgportal'); ?></option>
                <?php foreach ( $specialties as $specialty ) : ?>
                    <option <?php selected($specialty == ($_GET['specialty'] ?? '')); ?>><?php echo esc_attr($specialty); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="dbc_code" style="margin-left:2px">
                <option value=""><?php esc_attr_e('DBC Code', 'zorgportal'); ?></option>
                <?php foreach ( $dbc_codes as $dbc_code ) : ?>
                    <option <?php selected($dbc_code == ($_GET['dbc_code'] ?? '')); ?>><?php echo esc_attr($dbc_code); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status" style="margin-left:2px">
                <option value=""><?php esc_attr_e('Status', 'zorgportal'); ?></option>
                <?php foreach ( [
                    \Zorgportal\Invoices::PAYMENT_STATUS_PAID => __('Paid', 'zorgportal'),
                    \Zorgportal\Invoices::PAYMENT_STATUS_DUE => __('Open', 'zorgportal'),
                    \Zorgportal\Invoices::PAYMENT_STATUS_OVERDUE => __('Over-due', 'zorgportal'),
                ] as $status => $display ) : ?>
                    <option value="<?php echo esc_attr($status); ?>" <?php selected($status == ($_GET['status'] ?? '')); ?>><?php echo esc_attr($display); ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="search" value="<?php echo esc_attr($_GET['search'] ?? null); ?>" placeholder="<?php esc_attr_e('Search...', 'zorgportal'); ?>" style="margin-left:2px;margin-right:3px" />

            <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'zorgportal'); ?>" />
        </form>

        <form method="post" action="admin.php?page=zorgportal-invoices" onsubmit="return confirm('<?php esc_attr_e('Are you sure?', 'zorgportal'); ?>')" style="margin-left:4px">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
            <input type="hidden" name="delete_all" value="1" />
            <input class="button button-link-delete" type="submit" value="<?php esc_attr_e('Delete All', 'zorgportal'); ?>" />
        </form>

        <form method="post" action="admin.php?page=zorgportal-invoices" onsubmit="return confirm('<?php esc_attr_e('Are you sure?', 'zorgportal'); ?>')" style="margin-left:4px">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
            <input type="hidden" name="update_all" value="1" />
            <input class="button" type="submit" value="<?php esc_attr_e('Update All', 'zorgportal'); ?>" />
        </form>
    </div>
    <form method="post" action="/" data-action="<?php echo remove_query_arg('bulk'); ?>" id="zportal-items" data-id="popmake-195" data-confirm="<?php esc_attr_e('Are you ffff?', 'zorgportal'); ?>">
        <table class="wp-list-table widefat striped posts xfixed">
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
                <?php if ( $list ) : ?>
                    <?php foreach ( $list as $entry ) : ?>
                        <tr id="post-<?php echo $entry['id']; ?>" class="iedit author-self level-0 post-<?php echo $entry['id']; ?> type-post status-publish format-standard hentry category-uncategorized entry">
                            <th scope="row" class="check-column">
                                <input id="cb-select-<?php echo $entry['id']; ?>" type="checkbox" name="items[]" value="<?php echo $entry['id']; ?>">
                            </th>

                            <td class="author column-author"><?php echo esc_attr($entry['DeclaratieNummer']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr(preg_replace('/\s\d+\:.+$/', '', $entry['DeclaratieDatum'])) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr(explode(' - ', $entry['SubtrajectHoofdbehandelaar'])[1] ?? '') ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr(explode(' - ', $entry['SubtrajectHoofdbehandelaar'])[0] ?? '') ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['SubtrajectDeclaratiecode']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['SubtrajectDeclaratiecodeOmschrijving']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr(preg_replace('/\s\d+\:.+$/', '', $entry['SubtrajectStartdatum'])) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr(preg_replace('/\s\d+\:.+$/', '', $entry['SubtrajectEinddatum'])) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['ZorgverzekeraarNaam']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['ZorgverzekeraarPakket']) ?: '-'; ?></td>
                            <td class="author column-author" style="white-space:nowrap"><?php echo '€ ', esc_attr(number_format($entry['DeclaratieBedrag'], 2)) ?: '-'; ?></td>
                            <td class="author column-author" style="white-space:nowrap"><?php echo '€ ', esc_attr(number_format($entry['ReimburseAmount'], 2)) ?: '-'; ?></td>
                            <td><?php \Zorgportal\Invoices::printStatus($entry); ?></td>

                            <td class="author column-author">
                                <a href="admin.php?page=zorgportal-view-invoice&id=<?php echo $entry['id']; ?>"><?php _e('View', 'zorgportal'); ?></a>
                                <br/>
                                <a href="admin.php?page=zorgportal-edit-invoice&id=<?php echo $entry['id']; ?>"><?php _e('Edit', 'zorgportal'); ?></a>
                                <br/>
                                <a href="<?php echo add_query_arg([
                                    'update_id' => $entry['id'],
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

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_attr_e('Select bulk action'); ?></label>
                <select name="action2" id="bulk-action-selector-bottom">
                    <option value="-1"><?php esc_attr_e('Bulk Actions'); ?></option>
                    <option value="export"><?php esc_attr_e('Export Items'); ?></option>
                    <option value="delete"><?php esc_attr_e('Delete items', 'zorgportal'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply'); ?>" />
            </div>
        </div>

        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
    </form>

    <?php if ( $has_prev ) : ?>
        <a href="<?php echo add_query_arg('p', $current_page -1); ?>" class="button" style="margin-top:1rem"><?php _e('&larr; Previous Page', 'zorgportal'); ?></a>
    <?php endif; ?>

    <?php if ( $has_next ) : ?>
        <a href="<?php echo add_query_arg('p', $current_page +1); ?>" class="button" style="margin-top:1rem"><?php _e('Next Page &rarr;', 'zorgportal'); ?></a>
    <?php endif; ?>

</div>