<?php
/*
Plugin Name: Custom Options by Zeeshan
Description: Enable custom options on your site without installing multiple plugins in Classic Editor & on WooCommerce Shortcode Checkout.
Version: 1.1
Plugin URI: https://github.com/mzeeshanzafar28
Author: M. Zeeshan Zafar
Author URI: https://github.com/mzeeshanzafar28
Text Domain: custom-options
License: GPLv2 or later
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CustomOptionsPlugin {
    
    public function __construct() {
        // Hook to add settings page in the admin menu
        add_action('admin_menu', [$this, 'add_settings_page']);
        
        // Hook to enqueue admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Hook to disable site inspection features (e.g. right-click, F12)
        add_action('wp_footer', [$this, 'disable_site_inspection']);
        
        // Hook to add custom buttons to WooCommerce product pages
        add_action('woocommerce_single_product_summary', [$this, 'add_buy_now_button'], 15);
        add_action('woocommerce_single_product_summary', [$this, 'add_whatsapp_button'], 20);
        
        // Hook to modify checkout fields
        add_filter('woocommerce_checkout_fields', [$this, 'modify_checkout_fields']);
        add_filter( 'woocommerce_default_address_fields', [$this, 'modify_address_fields']);
        
        
        //Hook to modify same address for shipping and billing
        add_filter('woocommerce_ship_to_different_address_checked', [$this,'set_default_ship_to_billing']);
        
        //Accept terms and conditions by default
        add_filter('woocommerce_terms_is_checked_default', [$this,'accept_terms_and_conditions']);
        
        // Hook to add custom CSS styles to frontend
        add_action('wp_head', [$this, 'add_custom_css']);
        
        add_action('wp_enqueue_scripts', [$this,'enqueue_buy_now_script']);
        
        add_action('wp_ajax_add_to_cart', [$this,'add_product_to_cart']);
        add_action('wp_ajax_nopriv_add_to_cart',[$this, 'add_product_to_cart']);
    
        
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public function add_settings_page() {
        add_menu_page(
            'Custom Options Settings', 
            'Custom Options', 
            'manage_options', 
            'custom-options-settings', 
            [$this, 'render_settings_page'],
            'dashicons-admin-generic', 
            90
        );
    }

    /**
     * Enqueue admin styles and scripts
     */
    public function enqueue_admin_assets($hook) {
        if ($hook === 'toplevel_page_custom-options-settings') {
            wp_enqueue_style(
                'coz-admin-style',
                plugin_dir_url(__FILE__) . 'assets/css/admin-style.css',
                [],
                '1.0'
            );
            wp_enqueue_script(
                'coz-admin-script',
                plugin_dir_url(__FILE__) . 'assets/js/admin-script.js',
                ['jquery'],
                '1.0',
                true
            );
        }
    }
    
    function enqueue_buy_now_script() {
            wp_enqueue_script(
                'buy-now-js',
                plugin_dir_url(__FILE__) . 'assets/js/buy-now.js',
                ['jquery'],
                '1.0',
                true
            );
            wp_localize_script('buy-now', 'wc_checkout_params', array(
        'checkout_url' => wc_get_checkout_url(),  // Get the WooCommerce checkout URL
    ));
        }


    /**
     * Render the settings page in the WordPress admin
     */
    public function render_settings_page() {
        // Handle form submission and save settings
        if (isset($_POST['coz_save_settings'])) {
            update_option('coz_disable_site_inspection', isset($_POST['disable_site_inspection']) ? 1 : 0);
            update_option('coz_add_buy_now_button', isset($_POST['add_buy_now_button']) ? 1 : 0);
            update_option('coz_enable_shake_animation', isset($_POST['enable_shake_animation']) ? 1 : 0);
            update_option('coz_enable_color_change_animation', isset($_POST['enable_color_change_animation']) ? 1 : 0);
            update_option('coz_add_whatsapp_button', isset($_POST['add_whatsapp_button']) ? 1 : 0);
            update_option('coz_enable_shake_animation_whatsapp', isset($_POST['enable_shake_animation_whatsapp']) ? 1 : 0);
            update_option('coz_whatsapp_number', sanitize_text_field($_POST['whatsapp_number'] ?? ''));
            update_option('coz_phone_required_checkout', isset($_POST['phone_required_checkout']) ? 1 : 0);
            update_option('coz_email_optional_checkout', isset($_POST['email_optional_checkout']) ? 1 : 0);
            update_option('coz_same_address_checkout', isset($_POST['same_address_checkout']) ? 1 : 0);
            update_option('coz_state_validate_checkout', isset($_POST['state_validate_checkout']) ? 1 : 0);
            update_option('coz_postcode_validate_checkout', isset($_POST['postcode_validate_checkout']) ? 1 : 0);
            update_option('coz_accept_terms_checkout', isset($_POST['accept_terms_checkout']) ? 1 : 0);
        }

        // Retrieve current settings
        $settings = [
            'disable_site_inspection' => get_option('coz_disable_site_inspection', 0),
            'add_buy_now_button' => get_option('coz_add_buy_now_button', 0),
            'enable_shake_animation' => get_option('coz_enable_shake_animation', 0),
            'enable_color_change_animation' => get_option('coz_enable_color_change_animation', 0),
            'add_whatsapp_button' => get_option('coz_add_whatsapp_button', 0),
            'enable_shake_animation_whatsapp' => get_option('coz_enable_shake_animation_whatsapp', 0),
            'whatsapp_number' => get_option('coz_whatsapp_number', ''),
            'phone_required_checkout' => get_option('coz_phone_required_checkout', 0),
            'email_optional_checkout' => get_option('coz_email_optional_checkout', 0),
            'same_address_checkout' => get_option('coz_same_address_checkout', 0),
            'state_validate_checkout' => get_option('coz_state_validate_checkout', 0),
            'postcode_validate_checkout' => get_option('coz_postcode_validate_checkout', 0),
            'accept_terms_checkout' => get_option('coz_accept_terms_checkout', 0),
        ];
        ?>
        <div class="coz-settings-page">
            <hr>
            <h1 style="text-align:center; !important">Custom Options Settings by Zeeshan</h1>
            <hr>
            <form method="POST">
                
                <h1>General</h1>
                
                <!-- Disable Site Inspection -->
                <div class="coz-setting-group">
                    <label class="coz-toggle">
                        Disable Site Inspection for Users
                        <input type="checkbox" name="disable_site_inspection" <?php checked($settings['disable_site_inspection'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                
                <h1>Single Product Page</h1>
                
                <!-- Buy Now Button Settings -->
                <div class="coz-setting-group">
                    <label class="coz-toggle">
                        Add "Buy Now" Button
                        <input type="checkbox" name="add_buy_now_button" id="add_buy_now_button" <?php checked($settings['add_buy_now_button'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                    
                    <!-- Enable Bounce/Shake Animation -->
                    <div id="enable_shake_animation_group" class="coz-sub-group" style="display: <?php echo $settings['add_buy_now_button'] ? 'block' : 'none'; ?>;">
                        <label class="coz-toggle">
                            Enable Bouning Animation
                            <input type="checkbox" name="enable_shake_animation" <?php checked($settings['enable_shake_animation'], 1); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <!-- Enable Color Change Animation -->
                    <div id="enable_color_change_animation_group" class="coz-sub-group" style="display: <?php echo $settings['add_buy_now_button'] ? 'block' : 'none'; ?>;">
                        <label class="coz-toggle">
                            Enable Color Change Animation
                            <input type="checkbox" name="enable_color_change_animation" <?php checked($settings['enable_color_change_animation'], 1); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                
                <!--<hr>-->
                
                <!-- WhatsApp Order Button Settings -->
                <div class="coz-setting-group">
                    <label class="coz-toggle">
                        Add "Order on WhatsApp" Button
                        <input type="checkbox" name="add_whatsapp_button" id="add_whatsapp_button" <?php checked($settings['add_whatsapp_button'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                    
                     <!-- Enable Shake Animation for WhatsApp Button -->
                    <div id="whatsapp_shake_animation_group" class="coz-sub-group" style="display: <?php echo $settings['add_buy_now_button'] ? 'block' : 'none'; ?>;">
                        <label class="coz-toggle">
                            Enable Shake Animation
                            <input type="checkbox" name="enable_shake_animation_whatsapp" <?php checked($settings['enable_shake_animation_whatsapp'], 1); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <!--WhatsApp Number-->
                    <div id="whatsapp_number_group" class="coz-sub-group" style="display: <?php echo $settings['add_whatsapp_button'] ? 'block' : 'none'; ?>;">
                        <label>
                            WhatsApp Number
                            <input type="text" name="whatsapp_number" value="<?php echo esc_attr($settings['whatsapp_number']); ?>">
                        </label>
                    </div>
                </div>
                
                
                <!-- Checkout Fields Settings -->
                
                <h1> Checkout Fields </h1>
                <div class="coz-setting-group">
                    <label class="coz-toggle">
                        Make Phone Number Required
                        <input type="checkbox" name="phone_required_checkout" <?php checked($settings['phone_required_checkout'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <!--<hr>-->
                
                
                <div class="coz-setting-group">
                    <label class="coz-toggle">
                        Make Email Optional
                        <input type="checkbox" name="email_optional_checkout" <?php checked($settings['email_optional_checkout'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <!--<hr>-->
                
                 <div class="coz-setting-group">
                    <label class="coz-toggle">
                        Use Different Address for Shipping and Billing
                        <input type="checkbox" name="same_address_checkout" <?php checked($settings['same_address_checkout'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <!--<hr>-->
                
                  <div class="coz-setting-group">
                    <label class="coz-toggle">
                        Make State/Country Field Optional
                        <input type="checkbox" name="state_validate_checkout" <?php checked($settings['state_validate_checkout'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <!--<hr>-->
                
                  <div class="coz-setting-group">
                    <label class="coz-toggle">
                        Make Postcode/ZIP Field Optional
                        <input type="checkbox" name="postcode_validate_checkout" <?php checked($settings['postcode_validate_checkout'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                 <div class="coz-setting-group">
                    <label class="coz-toggle">
                        Make Terms and Conditions Accepted by Default
                        <input type="checkbox" name="accept_terms_checkout" <?php checked($settings['accept_terms_checkout'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                
                <!-- Save Settings Button -->
                <?php submit_button('Save Settings', 'primary', 'coz_save_settings'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Disable right-click, F12, and other dev tools for the site inspection
     */
    public function disable_site_inspection() {
        if (!is_admin() && get_option('coz_disable_site_inspection', 0)) {
            echo "<script>
                document.addEventListener('contextmenu', event => event.preventDefault());
                document.addEventListener('keydown', event => {
                    if (event.key === 'F12' || (event.ctrlKey && event.shiftKey && event.key === 'I')) {
                        event.preventDefault();
                    }
                });
            </script>";
        }
    }

    /**
     * Add the "Buy Now" button on WooCommerce product pages
     */
    public function add_buy_now_button() {
    if (get_option('coz_add_buy_now_button', 0)) {
        $shake_class = get_option('coz_enable_shake_animation', 0) ? 'vibrate-button' : '';
        $color_change_class = get_option('coz_enable_color_change_animation', 0) ? 'change-color' : '';
        $classes = '';

        if (!empty($shake_class) && !empty($color_change_class)) {
            $classes = 'shake-and-color-change';
        } elseif (!empty($shake_class)) {
            $classes = $shake_class;
        } elseif (!empty($color_change_class)) {
            $classes = $color_change_class;
        }

     echo '<form class="cart" method="post" enctype="multipart/form-data">
        <input type="hidden" name="variation_id" value="" /> <!-- Hidden input for variation id -->
        <a href="#" class="button buy-now-button ' . $classes . '" 
           data-product-id="' . get_the_ID() . '" 
           data-variation-id="" 
           data-quantity="' . ( isset( $_POST['quantity'] ) ? $_POST['quantity'] : 1 ) . '"> 
            <svg width="30" height="30" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin-right:8px;">
                <rect width="24" height="24" stroke="none" fill="#FFFFFF" opacity="0"/>
                <g transform="matrix(1.43 0 0 1.43 12 12)">
                    <g>
                        <g transform="matrix(1 0 0 1 -0.45 -3.7)">
                            <path style="stroke: rgb(255,255,255); stroke-width: 1; fill: none; opacity: 1;" 
                                  transform="translate(-6.55, -3.3)" 
                                  d="M 4.24005 4.91858 L 4.24005 3.99287 C 4.24005 2.71473 5.27619 1.67859 6.55433 1.67859 L 6.55433 1.67859 C 7.83248 1.67859 8.86862 2.71473 8.86862 3.99287 L 8.86862 4.42466" 
                                  stroke-linecap="round"/>
                        </g>
                        <g transform="matrix(1 0 0 1 -0.45 2.1)">
                            <path style="stroke: rgb(255,255,255); stroke-width: 1; fill: none; opacity: 1;" 
                                  transform="translate(-6.55, -9.1)" 
                                  d="M 8.66667 4.91858 L 2.84375 4.91858 C 2.2509 4.91858 1.84174 5.19878 1.68499 5.78057 C 1.31492 7.15411 1 8.64028 1 10.4729 C 1 13.2978 4.45431 13.272 6.5 13.2771 C 8.56038 13.2823 12.1086 13.3329 12.1086 10.4729 C 12.1086 9.7571 12.0605 9.09419 11.9799 8.47019" 
                                  stroke-linecap="round"/>
                        </g>
                        <g transform="matrix(1 0 0 1 3.86 -0.52)">
                            <path style="stroke: rgb(255,255,255); stroke-width: 1; fill: none; opacity: 1;" 
                                  transform="translate(-10.86, -6.48)" 
                                  d="M 11.4923 4.47547 C 11.7822 4.83823 12.0828 5.21428 12.353 5.62456 C 12.6233 6.03485 12.8501 6.45946 13.0689 6.86906 C 13.0957 6.91906 13.1223 6.96885 13.1488 7.01835 C 13.2587 7.2234 13.2111 7.47889 13.0365 7.63263 C 13.0126 7.65368 12.9886 7.67483 12.9645 7.69605 C 12.6824 7.94498 12.3902 8.20276 12.0602 8.42015 C 11.7301 8.63754 11.3779 8.80422 11.0378 8.96517 C 11.0088 8.97889 10.9799 8.99256 10.9512 9.00622 C 10.741 9.10597 10.4874 9.04885 10.3424 8.86689 C 10.3074 8.82297 10.2722 8.77888 10.2368 8.73459 C 9.94687 8.37183 9.64632 7.99578 9.37606 7.58549 C 9.1058 7.1752 8.87896 6.7506 8.66014 6.341 C 8.5817 6.19417 8.50443 6.0757 8.5247 5.89916 C 8.51961 5.33013 8.65377 4.72423 9.01841 4.12901 C 9.08409 4.0218 9.19413 3.94931 9.31856 3.93129 C 10.0095 3.83121 10.6193 3.94725 11.1402 4.17669 C 11.3102 4.22785 11.3884 4.34552 11.4923 4.47547 Z" 
                                  stroke-linecap="round"/>
                        </g>
                    </g>
                </g>
            </g>
        </svg>
        Buy Now
      </a>
    </form>';


    }
}

    /**
     * Add the WhatsApp order button on WooCommerce product pages
     */
    public function add_whatsapp_button() {
        if (get_option('coz_add_whatsapp_button', 0)) {
            $number = get_option('coz_whatsapp_number', '');
            $shake_class = get_option('coz_enable_shake_animation_whatsapp', 0) ? 'rapid-shake-button' : '';

            if ($number) {
                $url = 'https://wa.me/' . $number . '?text=' . urlencode('I am interested in buying this product: ' . get_the_title());
                echo '<a href="' . esc_url($url) . '" class="button whatsapp-button ' . $shake_class . '"><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="33" height="33" viewBox="0 0 128 128">
                        <path fill="#fff" d="M64,123c32.5,0,59-26.5,59-59c0-15.8-6.1-30.6-17.3-41.7C94.6,11.1,79.8,5,64,5C31.5,5,5,31.4,5,64 c0,10.4,2.7,20.5,7.9,29.5l-5.6,20.6c-1.2,4.4,2.8,8.5,7.3,7.3l21.3-5.6C44.4,120.5,54.1,123,64,123L64,123z"></path><path fill="#5bf979" d="M64,111c-7.8,0-15.6-2-22.5-5.7c-1.8-1-3.8-1.5-5.7-1.5c-1,0-2,0.1-3,0.4l-11.2,2.9l2.9-10.5 c0.8-3.1,0.4-6.4-1.2-9.2C19.1,80.3,17,72.2,17,64C17,38,38.1,17,64,17c12.6,0,24.4,4.9,33.3,13.8C106.1,39.6,111,51.4,111,64 C111,89.9,89.9,111,64,111L64,111z"></path><path fill="#444b54" d="M107.9,20.2C96.2,8.5,80.6,2,64,2C29.8,2,2,29.8,2,64c0,10.5,2.6,20.8,7.7,29.9l-5.3,19.4 c-0.9,3.1,0,6.3,2.3,8.6c2.3,2.3,5.5,3.2,8.6,2.4l20.2-5.3c8.8,4.6,18.6,7,28.6,7c34.2,0,62-27.8,62-62 C126,47.5,119.6,31.9,107.9,20.2z M64,120c-9.3,0-18.6-2.4-26.8-6.8c-0.4-0.2-0.9-0.4-1.4-0.4c-0.3,0-0.5,0-0.8,0.1l-21.3,5.6 c-1.5,0.4-2.5-0.4-2.9-0.8c-0.4-0.4-1.2-1.4-0.8-2.9l5.6-20.6c0.2-0.8,0.1-1.6-0.3-2.3C10.5,83.5,8,73.8,8,64C8,33.1,33.1,8,64,8 c15,0,29.1,5.8,39.6,16.4C114.2,35,120.1,49.1,120,64C120,94.9,94.9,120,64,120z"></path><g><path fill="#006475" d="M92.9,85.1c-1.2,3.4-7.2,6.8-10,7c-2.7,0.2-5.2,1.2-17.7-3.7c-15-5.9-24.5-21.3-25.2-22.3c-0.7-1-6-8-6-15.3 c0-7.3,3.8-10.8,5.2-12.3c1.4-1.5,2.9-1.8,3.9-1.8c1,0,2,0,2.8,0c1.1,0,2.2,0.1,3.3,2.5c1.3,2.9,4.2,10.2,4.5,10.9 c0.4,0.7,0.6,1.6,0.1,2.6c-0.5,1-0.7,1.6-1.5,2.5c-0.7,0.9-1.6,1.9-2.2,2.6c-0.7,0.7-1.5,1.5-0.6,3c0.9,1.5,3.8,6.3,8.2,10.2 c5.6,5,10.4,6.6,11.9,7.3c1.5,0.7,2.3,0.6,3.2-0.4c0.9-1,3.7-4.3,4.7-5.8c1-1.5,2-1.2,3.3-0.7c1.4,0.5,8.6,4.1,10.1,4.8 c1.5,0.7,2.5,1.1,2.8,1.7C94.1,78.7,94.1,81.6,92.9,85.1z"></path></g>
                        </svg>Order on WhatsApp</a>';
            }
        }
    }
    
    
    function add_product_to_cart() {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']) ?: 1;
        $variation_id = intval($_POST['variation_id']) ?: 0;
        $variations = [];
    
        // Parse variation data
        if (!empty($_POST['variations']) && is_array($_POST['variations'])) {
            foreach ($_POST['variations'] as $variation) {
                if (!empty($variation['name']) && !empty($variation['value'])) {
                    $key = str_replace('attribute_', '', sanitize_text_field($variation['name']));
                    $variations[$key] = sanitize_text_field($variation['value']);
                }
            }
        }
    
        // Add to cart with variations
        $result = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variations);
    
        if ($result) {
            // Get checkout URL
            $checkout_url = wc_get_checkout_url();
            wp_send_json_success([
                // 'cart_key' => $result,
                'checkout_url' => $checkout_url
            ]);
        } else {
            wp_send_json_error('Could not add to cart.');
        }
    }



    /**
     * Modify the checkout fields based on the settings
     */
    function modify_checkout_fields($fields) {
        // Check if the phone number is required
        if (get_option('coz_phone_required_checkout', 0)) {
            if (isset($fields['billing']['billing_phone'])) {
                $fields['billing']['billing_phone']['required'] = true;
                $fields['billing']['billing_phone']['label'] = __('Phone (Required)', 'custom-options');
                $fields['billing']['billing_phone']['validate'] = true;
            }
            if (isset($fields['shipping']['shipping_phone'])) {
                // $fields['shipping']['shipping_phone']['required'] = true;
                // $fields['shipping']['shipping_phone']['label'] = __('Phone (Required)', 'custom-options');
                // $fields['shipping']['shipping_phone']['validate'] = true;
            }
        }
    
        // Check if the email field is optional
        if (get_option('coz_email_optional_checkout', 0)) {
            if (isset($fields['billing']['billing_email'])) {
                $fields['billing']['billing_email']['required'] = false;
                // $fields['billing']['billing_email']['label'] = __('Email address', 'custom-options');
                unset($fields['billing']['billing_email']['validate']);
            }
            if (isset($fields['shipping']['shipping_email'])) {
                // $fields['shipping']['shipping_email']['required'] = false;
                // $fields['shipping']['shipping_email']['label'] = __('Email address', 'custom-options');
                // unset($fields['shipping']['shipping_email']['validate']);
            }
        }
        
        
        
        
        return $fields;
    }
    
    
    function modify_address_fields($address_fields_array) {
    // Make the state field optional if the option is enabled
    if (get_option('coz_state_validate_checkout', 0)) {
        if (isset($address_fields_array['state'])) {
            unset($address_fields_array['state']['validate']); // Remove validation
            $address_fields_array['state']['required'] = false; // Make it optional
        }
    }

    // Make the postcode field optional if the option is enabled
    if (get_option('coz_postcode_validate_checkout', 0)) {
        if (isset($address_fields_array['postcode'])) {
            unset($address_fields_array['postcode']['validate']); // Remove validation
            $address_fields_array['postcode']['required'] = false; // Make it optional
        }
    }

    return $address_fields_array;
}


    public function set_default_ship_to_billing($ship_to_billing) {
        if (get_option('coz_same_address_checkout', 0)) {
            return true;
        }
    }
    
    public function accept_terms_and_conditions($default) {
        if (get_option('coz_accept_terms_checkout', 0)) {
            return true;
        }
    }

    /**
     * Add custom CSS styles to the frontend
     */
    public function add_custom_css() {
        ?>
    <style>
    .vibrate-button {
        animation: vibrate 0.5s infinite;
    }

    .change-color {
        animation: colorChange 10s infinite;
    }
    
    .shake-and-color-change {
    animation: vibrate 0.5s infinite, colorChange 10s infinite;
    }

    .buy-now-button, .whatsapp-order-button {
        margin: 7px 0;
        display: inline-block;
        padding: 10px 20px;
        text-align: center;
        color: white;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        cursor: pointer;
        display: flex; /* Flexbox to align text and icon */
        align-items: center; /* Vertically align */
        justify-content: center; /* Horizontally align */
    }

    .whatsapp-button {
        background: #38a538 !important;
        display: flex;
        align-items: center;
        border-radius: 5px;
        justify-content: center;
        gap: 8px; /* Space between icon and text */
    }
    
    .rapid-shake-button{
        animation: shake 3s infinite;
    }

    .whatsapp-button svg {
        width: 25px; /* Adjust icon size */
        height: 25px; /* Adjust icon size */
    }

    @keyframes vibrate {
        0%, 100% { transform: translateY(0); }
        25% { transform: translateY(-5px); }
        75% { transform: translateY(5px); }
    }

    @keyframes colorChange {
        0%, 100% { background-color: #ff0000; }
        20% { background-color: #00ff00; }
        40% { background-color: #0000ff; }
        60% { background-color: #ffff00; }
        80% { background-color: #ff00ff; }
    }

    @keyframes shake {
        0% { transform: translate(1px, 1px) rotate(0deg); }
        1% { transform: translate(-2px, -3px) rotate(-5deg); }
        2% { transform: translate(-4px, 0px) rotate(5deg); }
        3% { transform: translate(4px, 3px) rotate(0deg); }
        4% { transform: translate(2px, -2px) rotate(5deg); }
        5% { transform: translate(-1px, 3px) rotate(-5deg); }
        6% { transform: translate(-3px, 1px) rotate(0deg); }
        7% { transform: translate(0px, 0px) rotate(0deg); }
        80% { transform: translate(0px, 0px) rotate(0deg); }
        90% { transform: translate(0px, 0px) rotate(0deg); }
        100% { transform: translate(0px, 0px) rotate(0deg); }
    }

</style>

        <?php
    }
    
    
}

// Initialize the plugin
new CustomOptionsPlugin();


?>
