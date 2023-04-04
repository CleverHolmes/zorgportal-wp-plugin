<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php _e('Zorgportal &lsaquo; Edit Bulk invoice', 'zorgportal'); ?></h2>

    <form method="post" style="max-width:600px">
        <?php foreach ( $invoice as $prop => $value ) : if ( in_array($prop, ['id']) ) continue; ?>
            <?php if($name($prop) !== "Status") { ?>
                <p>
                    <label>
                        <strong style="display:table;margin-bottom:5px">
                            <?php echo esc_attr( $name($prop) ); ?>
                        </strong>
                        
                        <input type="text" class="widefat" name="<?php echo esc_attr($prop); ?>" value="<?php echo esc_attr($_POST[$prop] ?? ''); ?>" />
                    </label>
                </p>
            <?php }?>
        <?php endforeach; ?>

        <p>
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'zorgportal'); ?>">
            &nbsp;<a href="admin.php?page=zorgportal-bulkinvoice"><?php _e('Cancel', 'zorgportal'); ?></a>
        </p>
    </form>
</div>