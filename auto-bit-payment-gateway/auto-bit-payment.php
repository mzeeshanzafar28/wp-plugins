<?php
/**
 * Plugin Name: Auto Bit Payment
 * Description: A custom WooCommerce payment gateway using webhook for automatic payments, Use shortcode [abp_order_total] on redirect page for price display.
 * Version: 1.0
 * Author: Axon Technologies
 * Author URI: https://axontech.pk/
 */

// Register the gateway
add_filter('woocommerce_payment_gateways', 'abp_add_gateway_class');
function abp_add_gateway_class($gateways) {
    $gateways[] = 'WC_ABP_Gateway';
    return $gateways;
}

// Create the gateway class
add_action('plugins_loaded', 'abp_init_gateway_class');
function abp_init_gateway_class() {
    class WC_ABP_Gateway extends WC_Payment_Gateway {

        public function __construct() {
            $this->id = 'abp';
            $this->has_fields = false;
            $this->method_title = 'Auto Bit Payment';
            $this->method_description = 'Handles payments via custom webhook.';

            // Define user settings
            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->payment_info_page = $this->get_option('payment_info_page');
            $this->webhook_slug = $this->get_option('webhook_slug');
            $this->webhook_method = $this->get_option('webhook_method', 'POST');
            $this->amount_param = $this->get_option('amount_param', 'amount');
            $this->content_type = $this->get_option('content_type', 'application/json');
            
            $this->enabled = $this->get_option('enabled');

            // Save admin options
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function init_form_fields() {
            $pages = get_pages();
            $pages_array = [];
            foreach ($pages as $page) {
                $pages_array[$page->ID] = $page->post_title;
            }

            $this->form_fields = [
                'enabled' => [
                    'title' => 'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable Auto Bit Payment',
                    'default' => 'yes'
                ],
                'title' => [
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title for the payment method the customer sees.',
                    'default' => 'Auto Bit Payment'
                ],
                'payment_info_page' => [
                    'title' => 'Payment Information Page',
                    'type' => 'select',
                    'options' => $pages_array,
                    'description' => 'Select the page that will display payment information.'
                ],
                'webhook_slug' => [
                    'title' => 'Webhook URL Slug',
                    'type' => 'text',
                    'description' => 'Enter the slug for the webhook URL.'
                ],
                'webhook_method' => [
                    'title' => 'Webhook Request Method',
                    'type' => 'select',
                    'options' => [
                        'GET' => 'GET',
                        'POST' => 'POST'
                    ],
                    'description' => 'Select the request method for the webhook.'
                ],
                'amount_param' => [
                    'title' => 'Amount Parameter Name',
                    'type' => 'text',
                    'description' => 'Enter the name of the parameter for amount in the webhook request.'
                ],
                'content_type' => [
                    'title' => 'Content Type',
                    'type' => 'select',
                    'options' => [
                        'www-form' => 'www-form',
                        'application/json' => 'application/json'
                    ],
                    'description' => 'Select the content type for the webhook request.'
                ]
            ];
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);

            // Get pending totals and adjust order total for uniqueness
            $pending_totals = abp_pending_totals();
            $order_total = round($order->get_total(), 2);  // Get and round the order total

            $i = 0.01;
            while (in_array(number_format($order_total, 2), $pending_totals)) {
                $adjustment = $i;
                $order_total = round($order_total - $adjustment, 2);
                if ($i < 1.99) {
                    $i += 0.01;
                }
            }
            
            $order->set_total($order_total);
            $order->save();

            // Redirect to payment info page
            $payment_info_url = get_permalink($this->payment_info_page);
            return [
                'result' => 'success',
                'redirect' => $payment_info_url . '?order_key=' . $order->get_order_key()
            ];
        }
    }
}

// Helper function to get pending order totals
function abp_pending_totals() {
    $pending_orders = wc_get_orders(['status' => 'pending']);
    $pending_totals = [];

    foreach ($pending_orders as $order) {
        $pending_totals[$order->get_id()] = number_format($order->get_total(), 2);
    }

    return $pending_totals;
}

// Shortcode to display order total
add_shortcode('abp_order_total', 'abp_order_total_shortcode');
function abp_order_total_shortcode() {
    if (isset($_GET['order_key'])) {
        $order = wc_get_order(wc_get_order_id_by_order_key($_GET['order_key']));
        if ($order) {
            return wc_format_decimal($order->get_total(), 2);
        }
    }
    return '';
}

// Ajax handler to check payment status
add_action('wp_enqueue_scripts', 'abp_enqueue_scripts');
function abp_enqueue_scripts() {
    if (is_page(get_option('abp_payment_info_page'))) {
        wp_enqueue_script('abp-check-payment', plugin_dir_url(__FILE__) . 'assets/js/abp-check-payment.js', ['jquery'], null, true);
        wp_localize_script('abp-check-payment', 'abp_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'order_key' => isset($_GET['order_key']) ? $_GET['order_key'] : ''
        ]);
    }
}

add_action('wp_ajax_nopriv_abp_check_payment', 'abp_check_payment');
function abp_check_payment() {
    $order = wc_get_order(wc_get_order_id_by_order_key($_POST['order_key']));
    if ($order && ($order->get_status() === 'completed' || $order->get_status() === 'processing')) {
        wp_send_json_success(['redirect' => $order->get_checkout_order_received_url()]);
    } else {
        wp_send_json_error('Payment not received.');
    }
}

function abp_get_gateway_settings() {
    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
    
    if (isset($gateways['abp'])) {
        $gateway = $gateways['abp'];
        $arr = [ 
            'webhook_method' => $gateway->webhook_method,
            'webhook_slug' => $gateway->webhook_slug,
            'amount_param' => $gateway->amount_param,
	        'content_type' => $gateway->content_type
        ];

        return $arr; 
    }

    return []; 
}

// Webhook listener
add_action('init', 'abp_webhook_listener');
function abp_webhook_listener() {
    $settings = abp_get_gateway_settings();
    $slug = $settings['webhook_slug'];
    $content_type = $settings['content_type']; 

    if (!empty($slug) && strpos($_SERVER['REQUEST_URI'], $slug) !== false) {
        $method = $settings['webhook_method'];
        $amount_param = $settings['amount_param'];

        if ($_SERVER['REQUEST_METHOD'] === $method) {
            // Handle different content types
            if ($content_type === 'application/json') {
                
                // Decode JSON body
                $request_body = file_get_contents('php://input');
                $json_data = json_decode($request_body, true);
                $amount = isset($json_data[$amount_param]) ? floatval($json_data[$amount_param]) : 0;
            } else {
                // Default to www-form (use $_POST)
                $amount = isset($_POST[$amount_param]) ? floatval($_POST[$amount_param]) : 0;
            }

            if ($amount > 0) {
                $pending_totals = abp_pending_totals();
                $order_id = array_search(number_format($amount, 2), $pending_totals);

                if ($order_id) {
                    $order = wc_get_order($order_id);
                    $order->payment_complete();
                    exit('Payment success');
                }
            }
        }
    }
}
