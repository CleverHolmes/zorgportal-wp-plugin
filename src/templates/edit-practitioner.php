<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php echo $practitioner
        ? __('Zorgportal &lsaquo; Edit Practitioner', 'zorgportal')
        : __('Zorgportal &lsaquo; Add Practitioner', 'zorgportal'); ?></h2>

    <form method="post" style="max-width:600px">
        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('Practitioner Name', 'europa-project'); ?>
                </strong>
                
                <input type="text" class="widefat" name="name" value="<?php echo esc_attr($_POST['name'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('Location', 'europa-project'); ?>
                </strong>
                
                <input type="text" class="widefat" name="location" value="<?php echo esc_attr($_POST['location'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('Specialty', 'europa-project'); ?>
                </strong>
                
                <input type="text" class="widefat" name="specialty" value="<?php echo esc_attr($_POST['specialty'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('Fee (%)', 'europa-project'); ?>
                </strong>
                
                <input type="text" class="widefat" name="fee" value="<?php echo esc_attr($_POST['fee'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'zorgportal'); ?>">
        </p>
    </form>
</div>