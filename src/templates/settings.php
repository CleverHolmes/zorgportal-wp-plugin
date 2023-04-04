<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h2><?php _e('Zorgportal &lsaquo; Settings', 'zorgportal'); ?></h2>
    <h3><?php _e('Connect Exact Online', 'zorgportal'); ?></h3>

    <?php if ( $connected ) : ?>
        <div style="border: 1px solid rgb(96, 125, 139); padding: 4px 13px; display: table;">
            <div style="display:flex;align-items:center;margin: 0 0 -12px;padding-top: 7px;">
                <h3 style="margin:0"><?php _e('Exact Connected Online', 'zorgportal'); ?></h3>
                <svg style="margin-left:10px;width:28px;fill:rgb(50,185,124)" clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m11.998 2.005c5.517 0 9.997 4.48 9.997 9.997 0 5.518-4.48 9.998-9.997 9.998-5.518 0-9.998-4.48-9.998-9.998 0-5.517 4.48-9.997 9.998-9.997zm-5.049 10.386 3.851 3.43c.142.128.321.19.499.19.202 0 .405-.081.552-.242l5.953-6.509c.131-.143.196-.323.196-.502 0-.41-.331-.747-.748-.747-.204 0-.405.082-.554.243l-5.453 5.962-3.298-2.938c-.144-.127-.321-.19-.499-.19-.415 0-.748.335-.748.746 0 .205.084.409.249.557z" fill-rule="nonzero"/></svg>
            </div>
            <p style="margin-bottom:0"><?php _e('Access token timer:', 'zorgportal'); ?> <span id="timer-countdown"><?php echo $tokens['_expires'] - time(); ?></span></p>
            <p style="margin-top:0;margin-bottom:8px"><?php _e('Refresh token timer: 30 days', 'zorgportal'); ?></p>
        </div>

        <form method="post" style="display:inline-block" onsubmit="return confirm('<?php esc_attr_e('Are you sure?'); ?>')">
            <p>
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <input type="hidden" name="oauth_disconnect" value="1">
                <input type="submit" class="button button-primary" value="<?php esc_attr_e('Break Connection', 'zorgportal'); ?>">
            </p>
        </form>
    <?php else : ?>
        <div style="border: 1px solid rgb(96, 125, 139); padding: 4px 13px; display: table;">
            <div style="display:flex;align-items:center;margin: 0 0 -12px;padding-top: 7px;">
                <h3 style="margin:0"><?php _e('Exact Not Connected', 'zorgportal'); ?></h3>
                <svg style="margin-left:10px;width:28px;fill:#f34336" clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m12.002 2.005c5.518 0 9.998 4.48 9.998 9.997 0 5.518-4.48 9.998-9.998 9.998-5.517 0-9.997-4.48-9.997-9.998 0-5.517 4.48-9.997 9.997-9.997zm0 8.933-2.721-2.722c-.146-.146-.339-.219-.531-.219-.404 0-.75.324-.75.749 0 .193.073.384.219.531l2.722 2.722-2.728 2.728c-.147.147-.22.34-.22.531 0 .427.35.75.751.75.192 0 .384-.073.53-.219l2.728-2.728 2.729 2.728c.146.146.338.219.53.219.401 0 .75-.323.75-.75 0-.191-.073-.384-.22-.531l-2.727-2.728 2.717-2.717c.146-.147.219-.338.219-.531 0-.425-.346-.75-.75-.75-.192 0-.385.073-.531.22z" fill-rule="nonzero"/></svg>
            </div>
            <p style="margin-bottom:8px"><?php _e('Authenticate below', 'zorgportal'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( $tokens['refresh_token'] ?? null ) : ?>
        <form method="post" style="display:inline-block">
            <p>
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                <input type="hidden" name="oauth_refresh" value="1">
                <input type="submit" class="button button-primary" value="<?php esc_attr_e('Refresh Access Token Now', 'zorgportal'); ?>">
            </p>
        </form>
    <?php endif; ?>

    <div style="display:flex;align-items:flex-start;flex-wrap:wrap;margin-left:-20px">
        <div style="flex:1;max-width:600px;margin-left:20px">
            <form method="post">
                <p><?php _e('Start your authentication flow', 'zorgportal'); ?></p>

                <p>
                    <label>
                        <strong style="display:table;margin-bottom:5px">
                            <?php _e('Exact Client ID', 'zorgportal'); ?>
                        </strong>
                        
                        <input type="text" class="widefat" name="client_id" value="<?php echo esc_attr($_POST['client_id'] ?? ''); ?>" />
                    </label>
                </p>

                <p>
                    <label>
                        <strong style="display:table;margin-bottom:5px">
                            <?php _e('Exact Client Secret', 'zorgportal'); ?>
                        </strong>
                        
                        <input type="text" class="widefat" name="client_secret" value="<?php echo esc_attr($_POST['client_secret'] ?? ''); ?>" />
                    </label>
                </p>

                <p>
                    <label>
                        <strong style="display:table;margin-bottom:5px">
                            <?php _e('Webhook Secret', 'zorgportal'); ?>
                        </strong>
                        
                        <input type="text" class="widefat" name="webhook_secret" value="<?php echo esc_attr($_POST['webhook_secret'] ?? ''); ?>" />
                    </label>
                </p>

                <p>
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="oauth_redirect" value="1">
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Login to Exact Online to Connect', 'zorgportal'); ?>">
                </p>
            </form>

            <?php if ( $tokens ) : ?>
                <h3 style="margin:0;margin-top:20px"><?php _e('Access Token', 'zorgportal'); ?></h3>
                <pre style="white-space:pre-wrap;word-break:break-all"><?php echo ($tokens['access_token'] ?? '') ?: '-'; ?></pre>

                <h3 style="margin:0"><?php _e('Refresh Token', 'zorgportal'); ?></h3>
                <pre style="white-space:pre-wrap;word-break:break-all"><?php echo ($tokens['refresh_token'] ?? '') ?: '-'; ?></pre>

                <h3 style="margin:0"><?php _e('Expires in', 'zorgportal'); ?></h3>
                <pre style="white-space:pre-wrap;word-break:break-all"><?php echo ($tokens['_expires'] ?? '') ? call_user_func(function(int $ex)
                {
                    return $ex < time() ? __('<em>expired</em>', 'zorgportal') : human_time_diff($ex, time());
                }, $tokens['_expires']) : '-'; ?></pre>
            <?php endif; ?>

            <h3 style="margin:0;margin-top:20px"><?php _e('API counters', 'zorgportal'); ?></h3>
            <p><?php _e('API calls:', 'zorgportal'); ?></p>
            <ul style="list-style:inherit;margin-left:14px">
                <li><?php printf(__('per minute 60 calls; %d left.', 'zorgportal'), 60-$api_usage_minute); ?></li>
                <li><?php printf(__('token endpoint calls per day 200; %d left.', 'zorgportal'), 200-$token_usage_day); ?></li>
                <li><?php printf(__('errors per hour per api key 10.; %d left', 'zorgportal'), 10-$api_errors_hour); ?></li>
                <li><?php printf(__('5000 calls per day; %d left.', 'zorgportal'), 5000-$api_usage_day); ?></li>
            </ul>
        </div>

        <?php if ( $tokens['access_token'] ?? '' ) : ?>
            <div style="flex:1;margin-left:20px">
                <h3><?php _e('Select your division', 'zorgportal'); ?></h3>

                <form method="post">
                    <p>
                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                        <input type="hidden" name="ex_get_devision" value="1">
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Get My Division', 'zorgportal'); ?>">
                    </p>
                </form>

                <?php if ( $division ) : ?>
                    <h3 style="margin-top:1.5rem"><?php _e('Division', 'zorgportal'); ?></h3>
                    <p><?php echo esc_attr($division); ?></p>

                    <h3 style="margin-top:1.5rem"><?php _e('Get All Divisions', 'zorgportal'); ?></h3>

                    <form method="post">
                        <p>
                            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                            <input type="hidden" name="ex_get_devisions" value="1">
                            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Get All Divisions', 'zorgportal'); ?>">
                        </p>
                    </form>

                    <?php if ( $divisions ) : ?>
                        <form method="post">
                            <?php foreach ( array_unique(wp_list_pluck($divisions, 'customer')) as $customer ) : ?>
                                <h4 style="margin-bottom:5px"><?php echo esc_attr($customer); ?></h4>

                                <?php foreach ( $divisions as $div ) : if ( $div['customer'] != $customer ) continue ?>
                                    <label style="display:table">
                                        <input type="radio" name="current_division" value="<?php echo esc_attr($div['code']); ?>" <?php checked(!! ($div['current'] ?? null)); ?> />
                                        <span><?php echo esc_attr(join(' - ', array_filter([$div['description'], $div['code']]))); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                            <p>
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                                <input type="hidden" name="set_current_division" value="1">
                                <input type="submit" class="button button-primary" value="<?php esc_attr_e('Select Division', 'zorgportal'); ?>">
                            </p>
                        </form>

                        <?php foreach ( $divisions as $div ) : ?>
                            <?php if ( $div['current'] ?? null ) : ?>
                                <h3 style="margin-top:1.5rem"><?php _e('Current Division', 'zorgportal'); ?></h3>
                                <p><?php echo esc_attr(join(' - ', array_filter([$div['description'], $div['code']]))); ?></p>
                                <?php break; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>