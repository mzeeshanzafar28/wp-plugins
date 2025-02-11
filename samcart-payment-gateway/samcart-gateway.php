<?php 
/*
plugin name: SamCart Gateway
Description: Custom Payment Gateway for manual payments via SamCart checkout.
Version: 1.0
Author: Axon Technologies
Author URI: https://axontech.pk
*/

add_filter('woocommerce_payment_gateways', 'add_samcart_manual_gateway_class');
function add_samcart_manual_gateway_class($gateways) {
    $gateways[] = 'WC_Gateway_SamCart_Manual';
    return $gateways;
}

add_action('plugins_loaded', 'init_samcart_manual_gateway_class');
function init_samcart_manual_gateway_class() {
    class WC_Gateway_SamCart_Manual extends WC_Payment_Gateway {

        public function __construct() {
            $this->id = 'samcart_manual';
            $this->has_fields = false;
            $this->method_title = 'SamCart Manual Payment';
            $this->method_description = 'Allow customers to pay manually. Customize the thank you page content.';
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->thank_you_page_content = '<div id="content" class="content-area page-wrapper" role="main"><div class="row row-main"><style>.entry-title,.page-title{display:none} .order-details-table tr td img { max-width: 70px; max-height: 70px; vertical-align: middle; }</style><div class="large-12 col"><div class="col-inner"><figure class="wp-block-image aligncenter size-large is-resized"><img class="aligncenter" style="width:278px;height:260px" src="https://imarketing.courses/wp-content/plugins/yith-custom-thankyou-page-for-woocommerce.premium/assets/images/thankyou.jpg" alt=""></figure><div class="wp-block-spacer" style="height:20px;text-align:center" aria-hidden="true">&nbsp;</div><h1 class="wp-block-heading has-text-align-center has-vivid-red-color has-text-color" style="text-align:center"><strong>Please, pay below:</strong></h1><div class="wp-block-spacer" style="height:20px;text-align:center" aria-hidden="true">&nbsp;</div><div class="wp-block-columns is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex" style="text-align: center; align-items: center !important;"><div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">&nbsp;</div><div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><h2 class="wp-block-heading has-text-align-center">Order Number (COPY IT):</h2></div><div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">[order_id]</div><div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">&nbsp;</div></div><div class="wp-block-columns is-layout-flex wp-container-core-columns-is-layout-2 wp-block-columns-is-layout-flex" style="text-align: center; align-items: center !important;"><div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">&nbsp;</div><div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><h2 class="wp-block-heading has-text-align-center">Your Email:</h2></div><div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">[customer_email]</div><div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">&nbsp;</div></div><div id="yctpw_order_details" class="align-justified"><h2 class="woocommerce-order-details__title order_details_title" style="color:#01af8d">Order Details</h2>[order_details]</div><div class="wp-block-spacer" style="height:20px;text-align:center" aria-hidden="true">&nbsp;</div><p class="has-text-align-center" style="text-align:center"><strong>COPY</strong>&nbsp;<strong>YOUR ORDER NUMBER ABOVE</strong></p><h2 class="wp-block-heading has-text-align-center" style="text-align:center">Credit/Debit Card, Apple Pay, Google Pay</h2><p class="has-text-align-center has-vivid-red-color has-text-color" style="text-align:center">I will upgrade your order manually in a few hours (max) after your payment.&nbsp;<strong>You will receive an email</strong>&nbsp;with your download links or you can get them from your account under your orders.</p><div class="wp-block-buttons has-custom-font-size has-large-font-size is-horizontal is-content-justification-center is-layout-flex wp-container-core-buttons-is-layout-1 wp-block-buttons-is-layout-flex" style="font-style:normal;font-weight:700;text-align:center"><div class="wp-block-button is-style-outline is-style-outline--d0d7434e39c0afe1b2e9222a1e1190cd"><br><a class="wp-element-button" style="padding: .667em 1.333em; background: transparent none; border: 2px solid; border-radius: 100px; text-decoration: none; margin-top: 30px; display: block;" href="https://atslibrarypay.samcart.com/products/pay-atslibrary" target="_blank" rel="noreferrer noopener"><img width="40" height="40" class="wp-image-46697" style="width:40px" src="https://imarketing.courses/wp-content/uploads/2023/11/cashless-payment.png" alt=""><span class="has-inline-color has-alert-color"> PAY HERE (NEW TAB)</span></a></div></div></div></div></div></div>';
            $this->redirect_page_id = $this->get_option('redirect_page_id');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'custom_thank_you_page_redirect'));
            $this->maybe_create_or_update_thank_you_page();
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable SamCart Manual Payment',
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'default' => 'Manual Payment',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'default' => 'Please pay manually following the instructions.',
                ),
                'redirect_page_id' => array(
                    'title' => 'Select Redirection Page',
                    'type' => 'select',
                    'options' => array('disabled' => 'Disabled') + $this->get_pages(), // Add "Disabled" option
                    'description' => 'Select a page to redirect after checkout. If "Disabled" is selected, the plugin will create the default SamCart Thank You Page.',
                ),
            );
        }

        // Fetch all pages for the dropdown
        public function get_pages() {
            $pages = get_pages();
            $options = array();
            foreach ($pages as $page) {
                $options[$page->ID] = $page->post_title;
            }
            return $options;
        }

        // Create or update the custom thank you page dynamically
        public function maybe_create_or_update_thank_you_page() {
            if (empty($this->redirect_page_id)) {
               $new_page_id = wp_insert_post(array(
                    'post_title' => 'SamCart Thank You Page',
                    'post_name' => 'samcart_thank_you', 
                    'post_content' => $this->thank_you_page_content,
                    'post_status' => 'publish',
                    'post_type' => 'page',
                ));
                $this->set_gateway_option('redirect_page_id', $new_page_id);
            }
        }

        // Redirect to the custom thank you page or selected page after payment
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);

            // Mark as on-hold (or processing/completed depending on the manual logic)
            $order->update_status('on-hold', __('Awaiting manual payment', 'woocommerce'));

            // Reduce stock levels
            wc_reduce_stock_levels($order_id);

            // Remove cart
            WC()->cart->empty_cart();

            // Determine if the "Disabled" option is selected
            if ($this->redirect_page_id === 'disabled' || empty($this->redirect_page_id)) {
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else {
                $redirect_url = get_permalink($this->redirect_page_id);

                // Append the order key as a query parameter
                $redirect_url = add_query_arg('order_key', $order->get_order_key(), $redirect_url);
    
                // Return success and redirect to the custom thank you page
                return array(
                    'result' => 'success',
                    'redirect' => $redirect_url
                );
            }
        }

        public function custom_thank_you_page_redirect($order_id) {
            // The redirection is handled by process_payment
            return;
        }

        public function set_gateway_option($option_name, $value) {
            $options = get_option('woocommerce_' . $this->id . '_settings');
            $options[$option_name] = $value;
            update_option('woocommerce_' . $this->id . '_settings', $options);
        }
    }
}

// Shortcodes to display dynamic data
add_shortcode('order_details', 'scg_order_details_shortcode');
add_shortcode('order_id', 'scg_order_id_shortcode');
add_shortcode('order_total', 'scg_order_total_shortcode');
add_shortcode('customer_email', 'scg_customer_email_shortcode');
add_shortcode('order_subtotal', 'scg_order_subtotal_shortcode');
add_shortcode('payment_method', 'scg_payment_method_shortcode');
add_shortcode('order_items', 'scg_order_items_shortcode'); 

function scg_order_details_shortcode() {
    if (isset($_GET['order_key'])) {
        $order_key = sanitize_text_field($_GET['order_key']);
        
        // Get the order ID using the order key
        $order_id = wc_get_order_id_by_order_key($order_key);
        
        if ($order_id) {
            // Load the order
            $order = wc_get_order($order_id);

            if ($order) {
                ob_start(); // Start output buffering

                // Begin HTML output
                echo '<table class="order-details-table" style="width:100%; border:1px solid #ddd; border-collapse: collapse;">';
                echo '<tr><th style="border: 1px solid #ddd; padding: 8px;">Product</th><th style="border: 1px solid #ddd; padding: 8px;">Total</th></tr>';
                
                // Loop through each order item (products)
                foreach ($order->get_items() as $item_id => $item) {
                    $product = $item->get_product();
                    $product_name = $item->get_name(); // Product name
                    $product_qty = $item->get_quantity(); // Quantity
                    $product_total = $item->get_total(); // Line total
                    $product_image = $product->get_image('thumbnail'); // Product image

                    // Output product info with image in a table row
                    echo '<tr>';
                    echo '<td style="border: 1px solid #ddd; padding: 8px;">' . $product_image . ' ' . esc_html($product_name) . ' × ' . esc_html($product_qty) . '</td>';
                    echo '<td style="border: 1px solid #ddd; padding: 8px;">' . wc_price($product_total) . '</td>';
                    echo '</tr>';
                }

                // Display subtotal, payment method, and total amount
                echo '<tr><td style="border: 1px solid #ddd; padding: 8px;"><strong>Subtotal:</strong></td><td style="border: 1px solid #ddd; padding: 8px;">' . wc_price($order->get_subtotal()) . '</td></tr>';
                echo '<tr><td style="border: 1px solid #ddd; padding: 8px;"><strong>Payment method:</strong></td><td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($order->get_payment_method_title()) . '</td></tr>';
                echo '<tr><td style="border: 1px solid #ddd; padding: 8px;"><strong>Total:</strong></td><td style="border: 1px solid #ddd; padding: 8px;">' . wc_price($order->get_total()) . '</td></tr>';
                
                echo '</table>';

                return ob_get_clean(); // Return buffered content
            } else {
                return '<p>Order not found.</p>';
            }
        } else {
            return '<p>Invalid order key.</p>';
        }
    } else {
        return '<p>No order key provided.</p>';
    }
}

function scg_order_id_shortcode() {
    if (isset($_GET['order_key'])) {
        $order_key = sanitize_text_field($_GET['order_key']);
        $order = wc_get_order(wc_get_order_id_by_order_key($order_key));
        if ($order) {
            return $order->get_id();
        }
    }
    return 'Order ID not found.';
}

function scg_order_total_shortcode() {
    if (isset($_GET['order_key'])) {
        $order_key = sanitize_text_field($_GET['order_key']);
        $order = wc_get_order(wc_get_order_id_by_order_key($order_key));
        if ($order) {
            return wc_price($order->get_total());
        }
    }
    return 'Order total not found.';
}

function scg_customer_email_shortcode() {
    if (isset($_GET['order_key'])) {
        $order_key = sanitize_text_field($_GET['order_key']);
        $order = wc_get_order(wc_get_order_id_by_order_key($order_key));
        if ($order) {
            return $order->get_billing_email(); 
        }
    }
    return 'Customer email not found.';
}

function scg_order_subtotal_shortcode() {
    if (isset($_GET['order_key'])) {
        $order_key = sanitize_text_field($_GET['order_key']);
        $order = wc_get_order(wc_get_order_id_by_order_key($order_key));
        if ($order) {
            return wc_price($order->get_subtotal());
        }
    }
    return 'Order subtotal not found.';
}

function scg_payment_method_shortcode() {
    if (isset($_GET['order_key'])) {
        $order_key = sanitize_text_field($_GET['order_key']);
        $order = wc_get_order(wc_get_order_id_by_order_key($order_key));
        if ($order) {
            return $order->get_payment_method_title(); 
        }
    }
    return 'Payment method not found.';
}

function scg_order_items_shortcode() {
    if (isset($_GET['order_key'])) {
        $order_key = sanitize_text_field($_GET['order_key']);
        $order = wc_get_order(wc_get_order_id_by_order_key($order_key));
        if ($order) {
            $items = $order->get_items();
            $output = '<ul>'; 
            foreach ($items as $item_id => $item) {
                $product = $item->get_product();
                if ($product) {
                    $output .= '<li>' . esc_html($product->get_name()) . ' - ' . esc_html($item->get_quantity()) . ' @ ' . wc_price($item->get_total()) . '</li>';
                }
            }
            $output .= '</ul>'; 
            return $output;
        }
    }
    return 'No items found in this order.';
}