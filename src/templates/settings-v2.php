<?php defined('ABSPATH') || exit; ?>

<div class="wrap" id="zp-wrap">
    <h2><?php _e('Zorgportal &lsaquo; Settings', 'zorgportal'); ?></h2>
    
    <div class="zp-sections">
        <div>
            <h2><?php _e('Exact Online Integration', 'zorgportal'); ?></h2>

            <!-- oauth connection -->
            <form method="post" action="admin.php?page=zorgportal-settings" class="<?php echo $connected ? 'collapsed' : ''; ?>">
                <div class="zp-chevron" onclick="jQuery(this).closest('form').toggleClass('collapsed')">
                    <h3><?php _e('Connect Exact', 'zorgportal'); ?></h3>
                    
                    <?php if ( $connected ) : ?>
                        <svg fill="green" class="zp-check" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m11.998 2.005c5.517 0 9.997 4.48 9.997 9.997 0 5.518-4.48 9.998-9.997 9.998-5.518 0-9.998-4.48-9.998-9.998 0-5.517 4.48-9.997 9.998-9.997zm-5.049 10.386 3.851 3.43c.142.128.321.19.499.19.202 0 .405-.081.552-.242l5.953-6.509c.131-.143.196-.323.196-.502 0-.41-.331-.747-.748-.747-.204 0-.405.082-.554.243l-5.453 5.962-3.298-2.938c-.144-.127-.321-.19-.499-.19-.415 0-.748.335-.748.746 0 .205.084.409.249.557z"/></svg>
                    <?php endif; ?>

                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 16.67l2.829 2.83 9.175-9.339 9.167 9.339 2.829-2.83-11.996-12.17z"/></svg>
                </div>
                
                <div class="zp-section-content">
                    <p><?php _e('Start your authentication flow.', 'zorgportal'); ?></p>
                    
                    <p>
                        <label>
                            <strong><?php _e('Exact Client ID', 'zorgportal'); ?></strong><br/>
                            <input type="text" spellcheck="false" name="client_id" value="<?php echo esc_attr($_POST['client_id'] ?? ''); ?>" placeholder="df37f717-c152-4765-873f-7dba0ac27a93" class="widefat" />
                        </label>
                    </p>

                    <p>
                        <label>
                            <strong><?php _e('Exact Client Secret', 'zorgportal'); ?></strong><br/>
                            <input type="text" spellcheck="false" name="client_secret" value="<?php echo esc_attr($_POST['client_secret'] ?? ''); ?>" placeholder="Xs0PZruFmk4H" class="widefat" />
                        </label>
                    </p>

                    <p>
                        <label>
                            <strong><?php _e('Webhook Secret', 'zorgportal'); ?></strong><br/>
                            <input type="text" spellcheck="false" name="webhook_secret" value="<?php echo esc_attr($_POST['webhook_secret'] ?? ''); ?>" placeholder="Xs0PZruFmk4H" class="widefat" />
                        </label>
                    </p>
    
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
                    <input type="hidden" name="oauth_redirect" value="1" />
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Login to Exact Online to Connect', 'zorgportal'); ?>">

                    <?php if ( $connected ) : ?>
                        <button class="button button-link-delete" name="oauth_disconnect" onclick="return confirm('<?php esc_attr_e('Are you sure?', 'zorgportal'); ?>')">
                            <?php esc_attr_e('Disconnect', 'zorgportal'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <!-- divisions -->
            <form method="post" action="admin.php?page=zorgportal-settings" style="margin-top:1rem">
                <div class="zp-chevron" onclick="jQuery(this).closest('form').toggleClass('collapsed')">
                    <h3><?php _e('Divisions', 'zorgportal'); ?></h3>

                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 16.67l2.829 2.83 9.175-9.339 9.167 9.339 2.829-2.83-11.996-12.17z"/></svg>
                </div>
                
                <div class="zp-section-content">
                    <p><?php _e('If you have multiple companies managed in the same Exact Online account, it could be the case that you have multiple divisions to connect. ZP will always select the first (default) division, so this step is not mandatory but optional. You can get All divisions and select a different one if you have user permissions in EO configured.'); ?></p>

                    <div style="display:flex;align-items:center">
                        <strong><?php _e('Current Divisions:', 'zorgportal'); ?></strong>
                        <form method="post" action="admin.php?page=zorgportal-settings">
                            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
                            <input type="hidden" name="ex_get_devisions" value="1" />
                            <button type="submit" class="dashicons dashicons-image-rotate" style="background:none;border:none;cursor:pointer;outline:none;overflow:hidden;margin-left:8px;padding:0" title="<?php esc_attr_e('Refresh list', 'zorgportal'); ?>"></button>
                        </form>
                    </div>

                    <form method="post" style="margin-top:10px">
                        <?php foreach ( $divisions as $div ) : ?>
                            <label style="display:table">
                                <input type="radio" name="current_division" value="<?php echo esc_attr($div['code']); ?>" <?php checked(!! ($div['current'] ?? null)); ?> />
                                <span><?php echo esc_attr(join(' - ', array_filter([$div['description'], $div['code']]))); ?></span>
                            </label>
                        <?php endforeach; ?>

                        <p>
                            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                            <input type="hidden" name="set_current_division" value="1">
                            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Update Division', 'zorgportal'); ?>">
                        </p>
                    </form>

                </div>
            </form>

            <!-- tokens -->
            <form method="post" action="admin.php?page=zorgportal-settings" style="margin-top:1rem">
                <div class="zp-chevron" onclick="jQuery(this).closest('form').toggleClass('collapsed')">
                    <h3><?php _e('Tokens', 'zorgportal'); ?></h3>

                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 16.67l2.829 2.83 9.175-9.339 9.167 9.339 2.829-2.83-11.996-12.17z"/></svg>
                </div>
                
                <div class="zp-section-content" style="margin-top:1rem">
                    <strong><?php _e('Access Token', 'zorgportal'); ?></strong><br/>
                    <pre style="white-space:pre-wrap;word-break:break-all"><?php echo ($tokens['access_token'] ?? '') ?: '-'; ?></pre>

                    <strong><?php _e('Refresh Token', 'zorgportal'); ?></strong><br/>
                    <pre style="white-space:pre-wrap;word-break:break-all"><?php echo ($tokens['refresh_token'] ?? '') ?: '-'; ?></pre>

                    <strong><?php _e('Expires in', 'zorgportal'); ?></strong><br/>
                    <pre style="white-space:pre-wrap;word-break:break-all"><?php echo ($tokens['_expires'] ?? '') ? call_user_func(function(int $ex)
                    {
                        return $ex < time() ?
                            __('<em>expired</em>', 'zorgportal')
                            : (human_time_diff($ex, time()) . '&nbsp;<em><small>(' . date('Y-m-d H:i:s', $ex) . ')</small></em>');
                    }, $tokens['_expires']) : __('None', 'zorgportal'); ?></pre>

                    <p><?php _e('A call has to be made at least once a month to stay logged in. Else the login flow has to be done again.', 'zorgportal'); ?></p>

                    <?php if ( $tokens['refresh_token'] ?? null ) : ?>
                        <form method="post" style="display:inline-block">
                            <p>
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
                                <input type="hidden" name="oauth_refresh" value="1">
                                <input type="submit" class="button button-primary" value="<?php esc_attr_e('Refresh Access Token Now', 'zorgportal'); ?>">
                            </p>
                        </form>

                        <p><?php _e('You can refresh the token if you have troubles.', 'zorgportal'); ?></p>
                    <?php endif; ?>
                </div>
            </form>

            <!-- rate limit -->
            <form method="post" action="admin.php?page=zorgportal-settings" style="margin-top:1rem">
                <div class="zp-chevron" onclick="jQuery(this).closest('form').toggleClass('collapsed')">
                    <h3><?php _e('Exact API Limits', 'zorgportal'); ?></h3>

                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 16.67l2.829 2.83 9.175-9.339 9.167 9.339 2.829-2.83-11.996-12.17z"/></svg>
                </div>
                
                <div class="zp-section-content" style="margin-top:1rem">
                    <p><?php _e('Exact Online has various limits. 60 per minute, 5000 per day, max 10 errors peer hour, and 200 refresh requests per day.', 'zorgportal'); ?></p>

                    <table id="zp-rate-limit-table"><tbody>
                        <tr>
                            <td><?php _e('Daily calls (max 5000)', 'zorgportal'); ?></td>
                            <td><?php echo $rate_limits['dailyLimitRemaining'] ?? __('-', 'zorgportal'), __(' / 5000', 'zorgportal'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Errors per hour (Max 10)', 'zorgportal'); ?></td>
                            <td><?php _e('TODO', 'zorgportal'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Daily Token calls (max 200)', 'zorgportal'); ?></td>
                            <td><?php _e('TODO', 'zorgportal'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Calls per minute (max 60)', 'zorgportal'); ?></td>
                            <td><?php echo $rate_limits['minutelyLimitRemaining'] ?? __('-', 'zorgportal'), __(' / 60', 'zorgportal'); ?></td>
                        </tr>
                    </tbody></table>
                </div>
            </form>
        </div>
        
        <div>
            <h2><?php _e('Transaction Auto Match Margin', 'zorgportal'); ?></h2>

            <p><?php _e('When patients pay you amounts, they can make small mistakes (cents). This setting allows you to define what amount falls in the margin you accept as Paid.'); ?></p>
            <div style="display:flex; justify-content:space-between">
                <p>
                  <?php _e('Value', 'zorgportal'); ?>
                </p>
                <div style="display:flex; align-items:center">
                    <input type="text" spellcheck="false" name="margin_cent" value="<?php echo esc_attr($_POST['margin_cent'] ?? ''); ?>"  class="widefat" />
                </div>
            </div>
        </div>
    </div>
    
</div>