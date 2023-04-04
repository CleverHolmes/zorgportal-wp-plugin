<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php echo sprintf(__('Zorgportal &lsaquo; Send reminder %d email', 'zorgportal'), $reminder); ?></h2>

    <form method="post" style="max-width:600px">
        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('Priority', 'zorgportal'); ?>
                </strong>

                <select name="priority">
                    <option value=""><?php _e('&mdash; select &mdash;', 'zorgportal'); ?></option>
                    <option value="1" <?php selected(1 == ($_POST['priority'] ?? '')); ?>><?php _e('High !!', 'zorgportal'); ?></option>
                    <option value="3" <?php selected(3 == ($_POST['priority'] ?? '')); ?>><?php _e('Medium !', 'zorgportal'); ?></option>
                    <option value="5" <?php selected(5 == ($_POST['priority'] ?? '')); ?>><?php _e('Low', 'zorgportal'); ?></option>
                </select>
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('To', 'zorgportal'); ?>
                </strong>

                <input type="text" class="widefat" name="to" value="<?php echo esc_attr($_POST['to'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('CC', 'zorgportal'); ?>
                </strong>

                <input type="text" class="widefat" name="cc" value="<?php echo esc_attr($_POST['cc'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('BCC', 'zorgportal'); ?>
                </strong>

                <input type="text" class="widefat" name="bcc" value="<?php echo esc_attr($_POST['bcc'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('Subject', 'zorgportal'); ?>
                </strong>

                <input type="text" class="widefat" name="subject" value="<?php echo esc_attr($_POST['subject'] ?? ''); ?>" />
            </label>
        </p>

        <p>
            <label>
                <strong style="display:table;margin-bottom:5px">
                    <?php _e('Body', 'zorgportal'); ?>
                </strong>
            </label>
        </p>

        <?php wp_editor( wp_unslash($_POST['body'] ?? ''), 'body', ['wpautop' => false]); ?>

        <div id="zp-files">
            <?php if ( $_POST['atts'] ?? null ) : ?>
                <?php foreach ( (array) $_POST['atts'] as $att ) : ?>
                    <p>
                        <span><?php echo esc_attr( basename($att['file']) ); ?></span>
                        <span class="dashicons dashicons-trash" style="color:#f44336;cursor:pointer;margin-left:4px" onclick="jQuery(this).cloest('p').remove()"></span>
                        <input type=hidden name="attachments[]" value="<?php echo esc_attr( $att['id'] ); ?>" />
                    </p>
                <?php endforeach; ?>
            <?php endif; ?>

            <p>
                <span class="button upload-attachment" style="display:inline-flex;align-items:center">
                    <span class="dashicons dashicons-plus-alt" style="margin-right:4px"></span>
                    <span><?php _e('Add attachment', 'zorgportal'); ?></span>
                </span>
            </p>
        </div>

        <p style="margin-top:5px">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Send email', 'zorgportal'); ?>">
            <a href="admin.php?page=zorgportal-view-invoice&id=<?php echo esc_attr($invoice['id']); ?>" style="margin-left:7px"><?php _e('cancel (back to invoice details)', 'zorgportal'); ?></a>
        </p>
    </form>
</div>
