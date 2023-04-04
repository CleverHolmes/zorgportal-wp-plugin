<?php defined('WPINC') || exit; ?>

<div class="wrap">
    <h2 style="display:none"></h2>

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:.55rem">
        <form style="display:flex;align-items:center;justify-content:space-between;flex:1">
            <h1 style="padding:9px 9px 9px 0"><?php _e('Zorgportal &lsaquo; DBC Codes', 'zorgportal'); ?></h1>
            <a href="admin.php?page=zorgportal-import-codes" class="page-title-action" style="position:static"><?php _e('Import Codes', 'zorgportal'); ?></a>
            <a href="admin.php?page=zorgportal-new-code" class="page-title-action" style="position:static"><?php _e('New Code', 'zorgportal'); ?></a>

            <?php foreach ( $_GET as $arg => $value ) : ?>
                <input type="hidden" name="<?php echo esc_attr($arg); ?>" value="<?php echo esc_attr($value); ?>" />
            <?php endforeach; ?>

            <div style="flex:1"></div>

            <div style="<?php echo ($_GET['date_criteria'] ?? '') != 'range' ? '' : 'display:none'; ?>">
                <div style="display:flex;align-items:center">
                    <select name="year">
                        <option value=""><?php esc_attr_e('Year', 'zorgportal'); ?></option>
                        <?php foreach ( range(($y=intval(date('Y')))-3, $y+10) as $year ) : ?>
                            <option value="<?php echo esc_attr($year); ?>" <?php selected( ($_GET['year'] ?? '') == $year ); ?>>
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

            <select name="insurer" style="margin-left:2px;max-width:150px">
                <option value=""><?php esc_attr_e('Insurer', 'zorgportal'); ?></option>
                <?php foreach ( $insurers as $insurer ) : ?>
                    <option <?php selected(esc_attr($insurer) == ($_GET['insurer'] ?? '')); ?>><?php echo esc_attr($insurer); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="policy" style="margin-left:2px;max-width:150px">
                <option value=""><?php esc_attr_e('Policy', 'zorgportal'); ?></option>
                <?php foreach ( $policies as $policy ) : ?>
                    <option <?php selected(esc_attr($policy) == ($_GET['policy'] ?? '')); ?>><?php echo esc_attr($policy); ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="search" value="<?php echo esc_attr($_GET['search'] ?? null); ?>" placeholder="<?php esc_attr_e('Search...', 'zorgportal'); ?>" style="margin-left:2px;display:table" />
            <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'zorgportal'); ?>" />
        </form>

        <form method="post" action="admin.php?page=zorgportal" onsubmit="return confirm('<?php esc_attr_e('Are you sure?', 'zorgportal'); ?>')" style="margin-left:4px">
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

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'dbc_code' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('dbc_code')); ?>">
                        <a href="<?php echo add_query_arg('sort', "dbc_code,{$getNextSort('dbc_code')}"); ?>">
                            <span><?php esc_attr_e('Code', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'dbc_description' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('dbc_description')); ?>">
                        <a href="<?php echo add_query_arg('sort', "dbc_description,{$getNextSort('dbc_description')}"); ?>">
                            <span><?php esc_attr_e('Description', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'active_start_date' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('active_start_date')); ?>">
                        <a href="<?php echo add_query_arg('sort', "active_start_date,{$getNextSort('active_start_date')}"); ?>">
                            <span><?php esc_attr_e('Active from', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'active_end_date' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('active_end_date')); ?>">
                        <a href="<?php echo add_query_arg('sort', "active_end_date,{$getNextSort('active_end_date')}"); ?>">
                            <span><?php esc_attr_e('Active to', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'insurer_packages' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('insurer_packages')); ?>">
                        <a href="<?php echo add_query_arg('sort', "insurer_packages,{$getNextSort('insurer_packages')}"); ?>">
                            <span><?php esc_attr_e('Insurer Package', 'zorgportal'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>

                    <th scope="col" class="manage-column column-title column-primary <?php echo 'dbc_total_amount' == ($getActiveSort()['prop'] ?? '') ? "sorted {$getActiveSort()['order']}" : 'sortable ' . str_replace(['asc','desc'],['desc','asc'],$getNextSort('dbc_total_amount')); ?>">
                        <a href="<?php echo add_query_arg('sort', "dbc_total_amount,{$getNextSort('dbc_total_amount')}"); ?>">
                            <span><?php esc_attr_e('DBC Total Amount', 'zorgportal'); ?></span>
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
                                <input id="cb-select-<?php echo $entry['id']; ?>" type="checkbox" name="codes[]" value="<?php echo $entry['id']; ?>">
                            </th>

                            <td class="author column-author"><?php echo esc_attr($entry['dbc_code']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['dbc_description']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['active_start_date']) ?: '-'; ?></td>
                            <td class="author column-author"><?php echo esc_attr($entry['active_end_date']) ?: '-'; ?></td>
                            <td class="author column-author">
                                <?php foreach ( (array) ( $entry['insurer_packages'] ?: null ) as $policy => $amt ) : ?>
                                    <?php echo esc_attr("{$policy}: $amt"), '<br/>'; ?>
                                <?php endforeach; ?>
                            </td>
                            <td class="author column-author"><?php echo '€ ', esc_attr($entry['dbc_total_amount']) ?: '-'; ?></td>

                            <td class="author column-author">
                                <a href="admin.php?page=zorgportal-edit-code&id=<?php echo $entry['id']; ?>"><?php _e('Edit', 'zorgportal'); ?></a>
                                &nbsp;
                                <a href="admin.php?page=zorgportal-new-code&clone_id=<?php echo $entry['id']; ?>"><?php _e('Clone', 'zorgportal'); ?></a>
                                &nbsp;
                                <a href="javascript:" class="button-link-delete zportal-inline-delete"><?php _e('Delete', 'zorgportal'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr id="post-0" class="iedit author-self level-0 post-0 type-post status-publish format-standard hentry category-uncategorized entry">
                        <td class="author column-author" colspan="8" style="text-align:center;padding:1rem">
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

    <form method="post">
        <p style="margin-bottom:5px"><strong><?php esc_attr_e('Export DBC Codes', 'zorgportal'); ?></strong></p>

        <select name="year">
            <?php foreach ( range(($y=intval(date('Y')))-3, $y+10) as $year ) : ?>
                <option value="<?php echo esc_attr($year); ?>" <?php selected(date('Y') == $year); ?>>
                    <?php echo esc_attr($year); ?></option>
            <?php endforeach; ?>
        </select>

        <input type="hidden" name="export_codes" />
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
        <input type="submit" class="button" value="<?php esc_attr_e('Submit', 'zorgportal'); ?>" />
    </form>

    <?php if ( $has_prev ) : ?>
        <a href="<?php echo add_query_arg('p', $current_page -1); ?>" class="button" style="margin-top:1rem"><?php _e('&larr; Previous Page', 'zorgportal'); ?></a>
    <?php endif; ?>

    <?php if ( $has_next ) : ?>
        <a href="<?php echo add_query_arg('p', $current_page +1); ?>" class="button" style="margin-top:1rem"><?php _e('Next Page &rarr;', 'zorgportal'); ?></a>
    <?php endif; ?>

</div>