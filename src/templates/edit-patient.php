<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php echo $patient
        ? __('Zorgportal &lsaquo; Edit Patient', 'zorgportal')
        : __('Zorgportal &lsaquo; Add Patient', 'zorgportal'); ?></h2>

    <form method="post" style="max-width:600px">
        <?php foreach ( [
            'id' => __('Patient ID', 'zorgportal'),
            'name' => __('Patient name', 'zorgportal'),
            'email' => __('Patient email', 'zorgportal'),
            'phone' => __('Patient phone', 'zorgportal'),
            'address' => __('Patient address', 'zorgportal'),
            'insurer' => __('Patient Insurer', 'zorgportal'),
            'policy' => __('Patient Policy', 'zorgportal'),
            'UZOVI' => __('UZOVI', 'zorgportal'),
            'location' => __('Location', 'zorgportal'),
            'practitioner' => __('Practitioner', 'zorgportal'),
            'status' => __('Status', 'zorgportal'),
        ] as $prop => $name ) : ?>
            <p>
                <label>
                    <strong style="display:table;margin-bottom:5px"><?php echo esc_attr($name); ?></strong>
                    
                    <input type="text" class="widefat" name="<?php echo esc_attr($prop); ?>" value="<?php echo esc_attr($_POST[$prop] ?? ''); ?>" <?php echo $patient && 'id' == $prop ? 'disabled="disabled"' : ''; ?> />
                </label>
            </p>
        <?php endforeach; ?>

        <p>
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'zorgportal'); ?>">
        </p>
    </form>
</div>