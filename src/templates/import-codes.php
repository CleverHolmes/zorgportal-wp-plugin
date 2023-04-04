<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php echo __('Zorgportal &lsaquo; Upload DBC Codes', 'zorgportal'); ?></h2>

    <form method="post" id="zp-import">
        <p>
            <strong style="display:table;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em"><?php _e('Settings', 'zorgportal'); ?></strong>

            <label style="display:table;margin-bottom:5px">
                <input type="checkbox" name="overwrite" />
                <span><?php _e('Overwrite DBC Codes if there are duplicates', 'zorgportal'); ?></span>
            </label>

            <label style="display:table;margin-bottom:5px;color:red;">
                <input type="checkbox" name="clear_all" />
                <span><?php _e('Clear all data and fill in new', 'zorgportal'); ?></span>
            </label>
        </p>

        <p style="margin-top:1.5rem">
            <label>
                <strong style="display:flex;align-items:center;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">
                    <?php _e('Select file to upload', 'zorgportal'); ?>
                    <img src="<?php echo $baseUrl, '/src/assets/ajax-loader.gif'; ?>" alt="loading" width="16" height="16" style="margin-left:4px;display:none" class="zp-ajax-loader" />
                </strong>
                <input type="file" name="file" />
            </label><br/>
        </p>

        <div style="margin-top:1.5rem;display:none" id="zp-fields-map">
            <strong style="display:table;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">
                <?php _e('Fields Map', 'zorgportal'); ?>
            </strong>

            <table>
                <?php foreach (['dbc_code','dbc_description','insurer_packages','dbc_total_amount'] as $i => $field) : ?>
                    <tr>
                        <td>
                            <label for="zp-field-<?php echo $i; ?>"><?php echo esc_attr(ucwords(str_replace('_', ' ', $field))); ?></label>
                        </td>
                        <td><select name="fields_map[<?php echo sanitize_text_field($field); ?>]<?php echo 'insurer_packages' == $field ? '[]' : ''; ?>" id="zp-field-<?php echo $i; ?>" class="widefat" <?php echo 'insurer_packages' == $field ? 'multiple="multiple"' : ''; ?>></select></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <p style="margin-top:1.5rem">
            <label>
                <strong style="display:table;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">
                    <?php _e('Applied Date', 'zorgportal'); ?>
                </strong>

                <input type="radio" name="date_criterea" value="year" checked="checked" />
                <span><?php _e('Select Year to apply', 'zorgportal'); ?></span>
            </label>

            <br/>

            <label>
                <input type="radio" name="date_criterea" value="range" />
                <span><?php _e('Or Select From - To Date', 'zorgportal'); ?></span>
            </label>
        </p>

        <p style="margin-top:1rem" id="zp-date-year">
            <select name="year">
                <?php foreach ( range(($y=intval(date('Y')))-3, $y+10) as $year ) : ?>
                    <option value="<?php echo esc_attr($year); ?>" <?php selected(date('Y') == $year); ?>>
                        <?php echo esc_attr($year); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p style="margin-top:1rem;display:none" id="zp-date-range">
            <label>
                <span><?php _e('From', 'zorgportal'); ?></span>
                <input type="date" name="date_from" />
            </label>

            <label style="margin-left:8px">
                <span><?php _e('To', 'zorgportal'); ?></span>
                <input type="date" name="date_to" />
            </label>
        </p>

        <p>&nbsp;</p>

        <div id="zp-notices"></div>

        <p>
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="submit" class="button button-primary" disabled="disabled" data-loading="<?php esc_attr_e('Importing...', 'zorgportal'); ?>" data-value="<?php esc_attr_e('Upload', 'zorgportal'); ?>" value="<?php esc_attr_e('Upload', 'zorgportal'); ?>">
        </p>
    </form>
</div>