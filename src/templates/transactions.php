<?php defined('WPINC') || exit; ?>

<div class="wrap">
    <h2 style="display:none"></h2>

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:.55rem">
        <form style="display:flex;align-items:center;justify-content:space-between;flex:1">
            <h1 style="padding:9px 9px 9px 0"><?php _e('Zorgportal &lsaquo; Transactions', 'zorgportal'); ?></h1>

            <?php foreach ( $_GET as $arg => $value ) : ?>
                <input type="hidden" name="<?php echo esc_attr($arg); ?>" value="<?php echo esc_attr($value); ?>" />
            <?php endforeach; ?>

            <div style="flex:1"></div>

            <select name="year" style="margin-left:4px;max-width:150px">
                <option value=""><?php esc_attr_e('&mdash; Year &mdash;', 'zorgportal'); ?></option>
                <?php foreach ( range(($y=intval(date('Y')))-3, $y+10) as $year ) : ?>
                    <option value="<?php echo esc_attr($year); ?>" <?php selected(($_GET['year'] ?? '') == $year); ?>>
                        <?php echo esc_attr($year); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="invoice_status" style="margin-left:2px">
                <option value=""><?php esc_attr_e('&mdash; Invoice Status &mdash;', 'zorgportal'); ?></option>
                <?php foreach ( [
                    \Zorgportal\Invoices::PAYMENT_STATUS_PAID => __('Paid', 'zorgportal'),
                    \Zorgportal\Invoices::PAYMENT_STATUS_DUE => __('Open', 'zorgportal'),
                    \Zorgportal\Invoices::PAYMENT_STATUS_OVERDUE => __('Over-due', 'zorgportal'),
                ] as $status => $display ) : ?>
                    <option value="<?php echo esc_attr($status); ?>" <?php selected($status == ($_GET['status'] ?? '')); ?>><?php echo esc_attr($display); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status" style="margin-left:4px;max-width:150px">
                <option value=""><?php esc_attr_e('&mdash; Transaction Status &mdash;', 'zorgportal'); ?></option>
                <?php foreach ( [] as $status => $display ) : ?>
                    <option value="<?php echo esc_attr($status); ?>" <?php selected($status == ($_GET['status'] ?? '')); ?>><?php echo esc_attr($display); ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="search" value="<?php echo esc_attr($_GET['search'] ?? null); ?>" placeholder="<?php esc_attr_e('Search...', 'zorgportal'); ?>" style="margin-left:2px;margin-right:3px" />
            <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'zorgportal'); ?>" />
        </form>

        &nbsp;&nbsp;&nbsp;

        <form method="post" action="admin.php?page=zorgportal-transactions" onsubmit="return confirm('<?php esc_attr_e('Are you sure?', 'zorgportal'); ?>')" style="margin-left:4px">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
            <input type="hidden" name="delete_all" value="1" />
            <input class="button button-link-delete" type="submit" value="<?php esc_attr_e('Delete All', 'zorgportal'); ?>" />
        </form>
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
                            <span><?php esc_attr_e('ID', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary">
                        <?php esc_attr_e('Status', 'zorgportal'); ?>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'name' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('name')); ?>">
                        <a href="<?php echo add_query_arg('sort', "name,{$getNextSort('name')}"); ?>">
                            <span><?php esc_attr_e('Name', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'email' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('email')); ?>">
                        <a href="<?php echo add_query_arg('sort', "email,{$getNextSort('email')}"); ?>">
                            <span><?php esc_attr_e('Email', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'phone' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('phone')); ?>">
                        <a href="<?php echo add_query_arg('sort', "phone,{$getNextSort('phone')}"); ?>">
                            <span><?php esc_attr_e('Phone', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'address' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('address')); ?>">
                        <a href="<?php echo add_query_arg('sort', "address,{$getNextSort('address')}"); ?>">
                            <span><?php esc_attr_e('Address', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'insurer' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('insurer')); ?>">
                        <a href="<?php echo add_query_arg('sort', "insurer,{$getNextSort('insurer')}"); ?>">
                            <span><?php esc_attr_e('Insurer', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'policy' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('policy')); ?>">
                        <a href="<?php echo add_query_arg('sort', "policy,{$getNextSort('policy')}"); ?>">
                            <span><?php esc_attr_e('Policy', 'zorgportal'); ?></span>
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

                            <td class="author column-author"><?php echo esc_attr($entry['id']) ?: '-'; ?></td>
                            <td><?php \Zorgportal\Invoices::printStatus(['EoStatus' => $getMaxInvoiceStatus($entry['id'], $maxInvoiceStatuses)]); ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['name']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['email']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['phone']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['address']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['insurer']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['policy']) ?: '-'; ?></td>

                            <td class="author column-author">
                                <a href="admin.php?page=zorgportal-view-patient&id=<?php echo $entry['id']; ?>"><?php _e('View', 'zorgportal'); ?></a>
                                &nbsp;
                                <a href="admin.php?page=zorgportal-edit-patient&id=<?php echo $entry['id']; ?>"><?php _e('Edit', 'zorgportal'); ?></a>
                                &nbsp;
                                <a href="javascript:" class="button-link-delete zportal-inline-delete"><?php _e('Delete', 'zorgportal'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr id="post-0" class="iedit author-self level-0 post-0 type-post status-publish format-standard hentry category-uncategorized entry">
                        <td class="author column-author" colspan="10" style="text-align:center;padding:1rem">
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