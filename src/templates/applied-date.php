<?php defined('WPINC') || exit; ?>

<div data-row-id="__row_id__">
    <select name="applied_date[__row_id__][year]">
        <?php foreach ( range(($y=intval(date('Y')))-3, $y+10) as $year ) : ?>
            <option value="<?php echo esc_attr($year); ?>" <?php selected(date('Y') == $year); ?>>
                <?php echo esc_attr($year); ?></option>
        <?php endforeach; ?>
    </select>

    <label onclick="jQuery(this).parent().hide().next().show()" style="margin-left:3px;color:#03a9f4;cursor:pointer;text-decoration:underline">
        <input type="radio" name="applied_date[__row_id__][criteria]" value="range" style="visibility:hidden;position:absolute;left:-9999999px" class="zp-criteria-range" />
        <?php _e('select period', 'zorgportal'); ?>
    </label>
</div>

<div data-row-id="__row_id__" style="display:none">
    <label style="display:table">
        <span><?php _e('From', 'zorgportal'); ?></span>
        <input type="date" name="applied_date[__row_id__][from]" />
    </label>

    <label style="display:table">
        <span><?php _e('To', 'zorgportal'); ?></span>
        <input type="date" name="applied_date[__row_id__][to]" />
    </label>

    <label onclick="jQuery(this).parent().hide().prev().show()" style="margin-left:3px;color:#03a9f4;cursor:pointer;text-decoration:underline">
        <input type="radio" name="applied_date[__row_id__][criteria]" value="year" style="visibility:hidden;position:absolute;left:-9999999px" class="zp-criteria-year" checked="checked" />
        <?php _e('select year', 'zorgportal'); ?>
    </label>
</div>