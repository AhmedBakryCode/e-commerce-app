<?php
// Add Getstart admin notice
function cosmetics_shop_admin_notice() { 
         $cosmetics_shop_meta = get_option( 'cosmetics_shop_admin_notice' );

       if( !$cosmetics_shop_meta ){
       if( is_network_admin() ){
            return;
        } if( ! current_user_can( 'manage_options' ) ){
            return;
        }if ( isset($_GET['page']) && $_GET['page'] === 'cosmetics-shop-info' ) {
            return;
        } ?>
        <div class="notice notice-success is-dismissible welcome-notice">
            <div class="notice-row">
                <div class="notice-text">
                    <p class="welcome-text1"><?php esc_html_e( '🎉 Welcome to VW Themes,', 'cosmetics-shop' ); ?></p>
                    <p class="welcome-text2"><?php esc_html_e( 'You are now using the Cosmetics Shop, a beautifully designed theme to kickstart your website.', 'cosmetics-shop' ); ?></p>
                    <p class="welcome-text3"><?php esc_html_e( 'To help you get started quickly, use the options below:', 'cosmetics-shop' ); ?></p>

                    <span class="import-btn">
                        <a href="javascript:void(0);" id="install-activate-button" class="button admin-button info-button">
                           <?php echo __('GET STARTED', 'cosmetics-shop'); ?>
                        </a>
                        <script type="text/javascript">
                            // Tolerate PHP notices printed before the JSON reply (WP_DEBUG) so a valid response is not read as a failure.
                            function cosmetics_shop_post(data, callback) {
                                jQuery.ajax({ url: ajaxurl, type: 'POST', data: data, dataType: 'text' }).always(function (raw, status) {
                                    var text = (typeof raw === 'string') ? raw : '';
                                    var start = text.indexOf('{"success"');
                                    var parsed = { success: false };
                                    for (var end = text.lastIndexOf('}'); start >= 0 && end > start; end = text.lastIndexOf('}', end - 1)) { try { parsed = JSON.parse(text.slice(start, end + 1)); break; } catch (e) {} }
                                    callback(parsed);
                                });
                            }
                            document.getElementById('install-activate-button').addEventListener('click', function () {
                                const cosmetics_shop_button = this;
                                const cosmetics_shop_redirectUrl = '<?php echo esc_url(admin_url("themes.php?page=cosmetics-shop-info")); ?>';
                                // First, check if plugin is already active
                                cosmetics_shop_post({ action: 'check_plugin_activation' }, function (response) {
                                    if (response.success && response.data.active) {
                                        // Plugin already active — just redirect
                                        window.location.href = cosmetics_shop_redirectUrl;
                                    } else {
                                        // Show Installing & Activating only if not already active
                                        cosmetics_shop_button.textContent = 'Installing & Activating...';

                                        cosmetics_shop_post({
                                            action: 'install_and_activate_required_plugin',
                                            nonce: '<?php echo wp_create_nonce("install_activate_nonce"); ?>'
                                        }, function (response) {
                                             if (response.success) {

                                            cosmetics_shop_button.textContent = 'Importing Demo...';

                                            cosmetics_shop_post({
                                                action: 'cosmetics_shop_run_demo_import',
                                                nonce: '<?php echo wp_create_nonce("cosmetics_shop_demo_nonce"); ?>'
                                            }, function (importResponse) {

                                                if (importResponse.success) {
                                                    window.location.href = cosmetics_shop_redirectUrl;
                                                } else {
                                                    cosmetics_shop_button.textContent = 'Try Again';
                                                }

                                            });
                                        } else {
                                                alert((response && response.data && response.data.message) || 'Failed to activate the plugin.');
                                                cosmetics_shop_button.textContent = 'Try Again';
                                            }
                                        });
                                    }
                                });
                            });
                        </script>
                    </span>

                    <span class="demo-btn">
                        <a href="https://www.vwthemes.net/cosmetics-shop-pro/" class="button button-primary" target="_blank">
                            <?php esc_html_e( 'VIEW DEMO', 'cosmetics-shop' ); ?>
                        </a>
                    </span>

                    <span class="upgrade-btn">
                        <a href="https://www.vwthemes.com/products/cosmetics-shop-wordpress-theme" class="button button-primary" target="_blank">
                            <?php esc_html_e( 'UPGRADE TO PRO', 'cosmetics-shop' ); ?>
                        </a>
                    </span>

                    <span class="bundle-btn">
                        <a href="https://www.vwthemes.com/products/wp-theme-bundle" class="button button-primary" target="_blank">
                            <?php esc_html_e( 'BUNDLE OF 500+ THEMES', 'cosmetics-shop' ); ?>
                        </a>
                    </span>
                </div>

                <div class="notice-img1">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/images/arrow-notice.png' ); ?>" width="180" alt="<?php esc_attr_e( 'Cosmetics Shop', 'cosmetics-shop' ); ?>" />
                </div>

                <div class="notice-img2">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/images/bundle-notice.png' ); ?>" width="180" alt="<?php esc_attr_e( 'Cosmetics Shop', 'cosmetics-shop' ); ?>" />
                </div>
            </div>
        </div>
        <?php

    }?>
        <?php

    }

add_action( 'admin_notices', 'cosmetics_shop_admin_notice' );

if( ! function_exists( 'cosmetics_shop_update_admin_notice' ) ) :
/**
 * Updating admin notice on dismiss
*/
function cosmetics_shop_update_admin_notice(){
    if ( isset( $_GET['cosmetics_shop_admin_notice'] ) && $_GET['cosmetics_shop_admin_notice'] == '1' ) {
        update_option( 'cosmetics_shop_admin_notice', true );
    }
}
endif;

add_action( 'admin_init', 'cosmetics_shop_update_admin_notice' );

//After Switch theme function
add_action('after_switch_theme', 'cosmetics_shop_getstart_setup_options');
function cosmetics_shop_getstart_setup_options () {
    update_option('cosmetics_shop_admin_notice', FALSE );
}