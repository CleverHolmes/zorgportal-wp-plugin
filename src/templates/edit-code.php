<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php echo $code
        ? __('Zorgportal &lsaquo; Edit DBC Code', 'zorgportal')
        : __('Zorgportal &lsaquo; Add DBC Code', 'zorgportal'); ?></h2>

    <form method="post" style="max-width:600px">
        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('DBC Code', 'europa-project'); ?>
                </strong>
                
                <input type="text" class="widefat" name="dbc_code" value="<?php echo esc_attr($_POST['dbc_code'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('Description', 'europa-project'); ?>
                </strong>
                
                <input type="text" class="widefat" name="dbc_description" value="<?php echo esc_attr($_POST['dbc_description'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('Applied Date', 'europa-project'); ?>
                </strong>
                
                <span style="display:flex;align-items:center">
                    <small style="margin-right:4px"><?php _e('From', 'zorgportal'); ?></small>
                    <input type="date" class="widefat" name="active_start_date" value="<?php echo esc_attr($_POST['active_start_date'] ?? ''); ?>" />

                    <small style="margin-left:8px;margin-right:4px"><?php _e('To', 'zorgportal'); ?></small>
                    <input type="date" class="widefat" name="active_end_date" value="<?php echo esc_attr($_POST['active_end_date'] ?? ''); ?>" />
                </span>
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('Total Amount', 'europa-project'); ?>
                </strong>
                
                <input type="text" class="widefat" name="dbc_total_amount" value="<?php echo esc_attr($_POST['dbc_total_amount'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <strong style="display:table;margin-bottom:5px">
                <?php _e('Insurer Packages', 'europa-project'); ?>
            </strong>
            
            <span id="zp-packages">
                <?php $i=-1; foreach ( (((array) ( $_POST['insurer_packages'] ?? null )) ?: ['' => '']) as $insurer => $amount ) : ?>
                    <span style="margin-top:3px;display:flex;align-items:center">
                        <input type="text" style="flex:1;margin-right:3px" name="packages[<?php echo ++$i; ?>][name]" value="<?php echo esc_attr($insurer); ?>" placeholder="<?php _e('Insurer', 'zorgportal'); ?>" />
                        <input type="text" name="packages[<?php echo $i; ?>][amount]" value="<?php echo esc_attr($amount); ?>" placeholder="<?php _e('Amount', 'zorgportal'); ?>" />
                        <span class="dashicons dashicons-trash zp-delete-inline" style="cursor:pointer;margin-left:3px"></span>
                    </span>
                <?php endforeach; ?>
            </span>

            <small class="button" id="zp-add-package" style="display:inline-flex;align-items:center;margin-top:5px;font-size:smaller">
                <span class="dashicons dashicons-plus-alt2" style="margin-right:2px"></span>
                <?php _e('Add Package', 'zorgportal'); ?>
            </small>
        </p>

        <p>
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'zorgportal'); ?>">
        </p>
    </form>
</div>