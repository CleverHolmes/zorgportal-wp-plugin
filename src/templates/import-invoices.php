<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php echo __('Zorgportal &lsaquo; Import Invoices', 'zorgportal'); ?></h2>

    <form method="post" id="zp-import">
        <p>
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
                <?php foreach (['Invoice ID','Treatment Date','Invoice Date','Total Amount','Dbc Code','Location','Practitioner','Omschrijving','Patient name','Patient ID','Patient email','Patient phone','Patient address','Patient Insurer','Patient Policy'] as $i => $field) : ?>
                    <tr>
                        <td>
                            <label for="zp-field-<?php echo $i; ?>"><?php echo esc_attr(ucwords(str_replace('_', ' ', $field))); ?></label>
                        </td>
                        <td><select name="fields_map[<?php echo sanitize_text_field($field); ?>]" id="zp-field-<?php echo $i; ?>" class="widefat"></select></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <p id="submit-button-early" style="display:none">
            <input type="submit" class="button button-primary" disabled="disabled" data-loading="<?php esc_attr_e('Generating invoice...', 'zorgportal'); ?>" data-download="<?php esc_attr_e('Download file', 'zorgportal'); ?>" data-value="<?php esc_attr_e('Next', 'zorgportal'); ?>" value="<?php esc_attr_e('Next', 'zorgportal'); ?>">
        </p>

        <div id="zp-notices"></div>

        <p>
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="submit" class="button button-primary" disabled="disabled" data-loading="<?php esc_attr_e('Generating invoice...', 'zorgportal'); ?>" data-download="<?php esc_attr_e('Download file', 'zorgportal'); ?>" data-value="<?php esc_attr_e('Next', 'zorgportal'); ?>" value="<?php esc_attr_e('Next', 'zorgportal'); ?>">
        </p>
    </form>
</div>