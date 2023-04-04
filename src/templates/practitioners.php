<?php defined('WPINC') || exit; ?>

<div class="wrap">
    <h2 style="display:none"></h2>

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:.55rem">
        <form style="display:flex;align-items:center;justify-content:space-between;flex:1">
            <h1 style="padding:9px 9px 9px 0"><?php _e('Zorgportal &lsaquo; Practitioners', 'zorgportal'); ?></h1>
            <a href="admin.php?page=zorgportal-new-practitioner" class="page-title-action" style="position:static"><?php _e('Add New', 'zorgportal'); ?></a>

            <?php foreach ( $_GET as $arg => $value ) : ?>
                <input type="hidden" name="<?php echo esc_attr($arg); ?>" value="<?php echo esc_attr($value); ?>" />
            <?php endforeach; ?>

            <input type="text" name="search" value="<?php echo esc_attr($_GET['search'] ?? null); ?>" placeholder="<?php esc_attr_e('Search...', 'zorgportal'); ?>"
             style="margin-left:auto;display:table" />
        </form>

        <form method="post" action="admin.php?page=zorgportal-practitioners" onsubmit="return confirm('<?php esc_attr_e('Are you sure?', 'zorgportal'); ?>')" style="margin-left:4px">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
            <input type="hidden" name="delete_all" value="1" />
            <input class="button" type="submit" value="<?php esc_attr_e('Delete All', 'zorgportal'); ?>" />
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

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'name' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('name')); ?>">
                        <a href="<?php echo add_query_arg('sort', "name,{$getNextSort('name')}"); ?>">
                            <span><?php esc_attr_e('Name', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'location' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('location')); ?>">
                        <a href="<?php echo add_query_arg('sort', "location,{$getNextSort('location')}"); ?>">
                            <span><?php esc_attr_e('Location', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'specialty' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('specialty')); ?>">
                        <a href="<?php echo add_query_arg('sort', "specialty,{$getNextSort('specialty')}"); ?>">
                            <span><?php esc_attr_e('Specialty', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'fee' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('fee')); ?>">
                        <a href="<?php echo add_query_arg('sort', "fee,{$getNextSort('fee')}"); ?>">
                            <span><?php esc_attr_e('Fee', 'zorgportal'); ?></span>
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

                            <td class="author column-author"><?php echo esc_attr($entry['name']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['location']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['specialty']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['fee']), '%'; ?></td>

                            <td class="author column-author">
                                <a href="admin.php?page=zorgportal-practitioner-invoices&id=<?php echo $entry['id']; ?>"><?php _e('Invoices', 'zorgportal'); ?></a>
                                &nbsp;
                                <a href="admin.php?page=zorgportal-edit-practitioner&id=<?php echo $entry['id']; ?>"><?php _e('Edit', 'zorgportal'); ?></a>
                                &nbsp;
                                <a href="javascript:" class="button-link-delete zportal-inline-delete"><?php _e('Delete', 'zorgportal'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr id="post-0" class="iedit author-self level-0 post-0 type-post status-publish format-standard hentry category-uncategorized entry">
                        <td class="author column-author" colspan="6" style="text-align:center;padding:1rem">
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