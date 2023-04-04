<?php defined('WPINC') || exit; ?>

<div class="wrap">
    <h2 style="display:none"></h2>

    <form style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.55rem">
        <h1><?php _e('Zorgportal &lsaquo; Logs', 'zorgportal'); ?></h1>

        <?php foreach ( $_GET as $arg => $value ) : ?>
            <input type="hidden" name="<?php echo esc_attr($arg); ?>" value="<?php echo esc_attr($value); ?>" />
        <?php endforeach; ?>

        <input type="text" name="search" value="<?php echo esc_attr($_GET['search'] ?? null); ?>" placeholder="<?php esc_attr_e('Search...', 'zorgportal'); ?>"
         style="margin-left:auto;display:table" />
    </form>

    <form method="post" action="/" data-action="<?php echo remove_query_arg('bulk'); ?>" id="euproj-items" data-confirm="<?php esc_attr_e('Are you sure?', 'zorgportal'); ?>">
        <table class="wp-list-table widefat striped posts xfixed">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1"><?php esc_attr_e('Select All'); ?></label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>

                    <th scope="col" class="manage-column column-title column-primary">
                        <?php esc_attr_e('Request URL', 'zorgportal'); ?>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'request_body' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('request_body')); ?>">
                        <a href="<?php echo add_query_arg('sort', "request_body,{$getNextSort('request_body')}"); ?>">
                            <span><?php esc_attr_e('Request Body', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'request_headers' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('request_headers')); ?>">
                        <a href="<?php echo add_query_arg('sort', "request_headers,{$getNextSort('request_headers')}"); ?>">
                            <span><?php esc_attr_e('Headers', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'response' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('response')); ?>">
                        <a href="<?php echo add_query_arg('sort', "response,{$getNextSort('response')}"); ?>">
                            <span><?php esc_attr_e('Response', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'response_headers' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('response_headers')); ?>">
                        <a href="<?php echo add_query_arg('sort', "response_headers,{$getNextSort('response_headers')}"); ?>">
                            <span><?php esc_attr_e('Headers', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'status' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('status')); ?>">
                        <a href="<?php echo add_query_arg('sort', "status,{$getNextSort('status')}"); ?>">
                            <span><?php esc_attr_e('Status', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'object_id' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('object_id')); ?>">
                        <a href="<?php echo add_query_arg('sort', "object_id,{$getNextSort('object_id')}"); ?>">
                            <span><?php esc_attr_e('Invoice ID', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'date' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('date')); ?>">
                        <a href="<?php echo add_query_arg('sort', "date,{$getNextSort('date')}"); ?>">
                            <span><?php esc_attr_e('Date', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th></th>
                </tr>
            </thead>

            <tbody id="the-list">
                <?php if ( $list ) : ?>
                    <?php foreach ( $list as $log ) : ?>
                        <tr id="post-<?php echo $log['id']; ?>" class="iedit author-self level-0 post-<?php echo $log['id']; ?> type-post status-publish format-standard hentry category-uncategorized entry">
                            <th scope="row" class="check-column">
                                <input id="cb-select-<?php echo $log['id']; ?>" type="checkbox" name="logs[]" value="<?php echo $log['id']; ?>">
                            </th>

                            <td class="author column-author" style="max-width:125px"><?php echo esc_attr($log['request_url']); ?></td>

                            <td class="author column-author">
                                <a href="javascript:" class="button euproj-view-data"><?php _e('View Data'); ?></a>
                                <pre style="display:none"><?php echo esc_attr($log['request_body']); ?></pre>
                            </td>

                            <td class="author column-author">
                                <a href="javascript:" class="button euproj-view-data"><?php _e('View Data'); ?></a>
                                <pre style="display:none"><?php echo esc_attr($log['request_headers']); ?></pre>
                            </td>

                            <td class="author column-author">
                                <a href="javascript:" class="button euproj-view-data"><?php _e('View Data'); ?></a>
                                <pre style="display:none"><?php echo esc_attr($log['response']); ?></pre>
                            </td>

                            <td class="author column-author">
                                <a href="javascript:" class="button euproj-view-data"><?php _e('View Data'); ?></a>
                                <pre style="display:none"><?php echo esc_attr($log['response_headers']); ?></pre>
                            </td>

                            <td class="author column-author" style="color:<?php echo ($ok=\Zorgportal\EoLogs::STATUS_OK) === $log['status'] ? 'green' : 'red'; ?>"><?php echo ($ok === $log['status']) ? __('Success', 'zorgportal') : __('Error', 'zorgportal'); ?></td>

                            <td class="author column-author"><a href="admin.php?page=zorgportal-edit-invoice&id=<?php echo (int) $log['object_id']; ?>"><?php echo (int) $log['object_id']; ?></a></td>

                            <td class="author column-author"><?php echo date( 'Y-m-d H:i:s', $log['date'] ); ?></td>

                            <td class="author column-author">
                                <a href="javascript:" class="button-link-delete euproj-inline-delete"><?php _e('Delete', 'zorgportal'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr id="post-0" class="iedit author-self level-0 post-0 type-post status-publish format-standard hentry category-uncategorized entry">
                        <td class="author column-author" colspan="10" style="text-align:center;padding:1rem">
                            <em><?php count($_GET) > 1 ? _e('Nothing found for your current filters.', 'zorgportal') : _e('Nothing to show yet. Please check back later.', 'zorgportal'); ?></em>
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
                    <option value="delete"><?php esc_attr_e('Delete logs', 'zorgportal'); ?></option>
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