<?php
/*
Plugin Name: Wave WooCommerce Payment Gateway
Plugin URI: https://wave.com
Description: Wave WooCommerce Payment Gateway
Version: 1.0.4
Author: Axon Technologies
Author URI: https://axontech.pk
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WAVE_WC_VERSION', '1.0.3');
define('WAVE_WC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAVE_WC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
function wave_wc_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', array());
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

// Enqueue scripts and styles
function wave_wc_enqueue_scripts() {
    if (is_checkout()) {
        wp_enqueue_style('wave-payment-css', WAVE_WC_PLUGIN_URL . 'assets/css/wave-payment.css', array(), WAVE_WC_VERSION);
        wp_enqueue_script('wave-payment-js', WAVE_WC_PLUGIN_URL . 'assets/js/wave-payment.js', array('jquery'), WAVE_WC_VERSION, true);
    }
}
add_action('wp_enqueue_scripts', 'wave_wc_enqueue_scripts');

// Initialize the plugin
function wave_wc_init() {
    if (!wave_wc_is_woocommerce_active()) {
        add_action('admin_notices', 'wave_wc_woocommerce_missing_notice');
        return;
    }

    if (!class_exists('WC_Payment_Gateway')) {
        include_once(WC()->plugin_path() . '/includes/abstracts/abstract-wc-payment-gateway.php');
    }

    class WC_Wave_Gateway extends WC_Payment_Gateway {
        protected $api_key;

        public function __construct() {
            $this->id = 'wave';
            $this->icon = WAVE_WC_PLUGIN_URL . 'assets/images/wave-logo.png';
            $this->has_fields = false;
            $this->method_title = 'Wave Payment';
            $this->method_description = 'Accept payments via Wave mobile money';

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->api_key = $this->get_option('api_key');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
            add_action('woocommerce_api_wave_webhook', array($this, 'handle_webhook_fallback'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable Wave Payment',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'Wave Payment',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default' => 'Pay with Wave mobile money',
                ),
                'api_key' => array(
                    'title' => 'API Key',
                    'type' => 'text',
                    'description' => 'Enter your Wave API key',
                    'default' => '',
                ),
            );
        }

        public function register_webhook_endpoint() {
            error_log('Wave: Registering REST API webhook endpoint');
            
            register_rest_route('wave/v1', '/webhook', array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'handle_webhook'),
                'permission_callback' => '__return_true',
            ));

            global $wp_rest_server;
            $routes = $wp_rest_server->get_routes();
            if (isset($routes['/wave/v1/webhook'])) {
                error_log('Wave: Webhook route successfully registered');
            } else {
                error_log('Wave: Failed to register webhook route');
            }
        }

        public function handle_webhook($request) {
            error_log('Wave Webhook Request Received: ' . json_encode($request->get_params()));
            
            $order_id = isset($request['order_id']) ? sanitize_text_field($request['order_id']) : '';
            $status = isset($request['status']) ? sanitize_text_field($request['status']) : '';
            $hash = isset($request['hash']) ? sanitize_text_field($request['hash']) : '';

            error_log("Wave Webhook Parameters - Order ID: $order_id, Status: $status, Hash: $hash");

            if (empty($order_id) || empty($status) || empty($hash)) {
                error_log('Wave Webhook Error: Missing required parameters');
                return new WP_Error('missing_parameters', 'Missing required parameters', array('status' => 400));
            }

            $order = wc_get_order($order_id);
            if (!$order) {
                error_log("Wave Webhook Error: Order not found - $order_id");
                return new WP_Error('order_not_found', 'Order not found', array('status' => 404));
            }

            $stored_hash = $status === 'success' 
                ? $order->get_meta('_wave_success_hash')
                : $order->get_meta('_wave_error_hash');

            error_log("Wave Webhook - Stored Hash: $stored_hash, Received Hash: $hash");

            if ($hash !== $stored_hash) {
                error_log("Wave Webhook Error: Hash mismatch - Stored: $stored_hash, Received: $hash");
                $order->add_order_note('Wave payment hash mismatch. Order kept pending for manual review.');
                return new WP_Error('hash_mismatch', 'Hash mismatch', array('status' => 400));
            }

            if ($status === 'success') {
                error_log("Wave Webhook: Processing successful payment for order $order_id");
                $order->payment_complete();
                $order->add_order_note('Wave payment completed successfully.');
                wp_redirect(add_query_arg('key', $order->get_order_key(), $order->get_checkout_order_received_url()));
                exit;
            } elseif ($status === 'error') {
                error_log("Wave Webhook: Processing failed payment for order $order_id");
                $order->update_status('failed', 'Wave payment failed or was cancelled.');
                wc_add_notice('Payment failed. Please try again.', 'error');
                wp_redirect(wc_get_checkout_url());
                exit;
            } else {
                error_log("Wave Webhook Error: Invalid status received - $status");
                return new WP_Error('invalid_status', 'Invalid status received', array('status' => 400));
            }

            $order->delete_meta_data('_wave_random_string');
            $order->delete_meta_data('_wave_success_hash');
            $order->delete_meta_data('_wave_error_hash');
            $order->save();

            error_log("Wave Webhook: Successfully processed webhook for order $order_id");
            return new WP_REST_Response(array('message' => 'Webhook processed successfully'), 200);
        }

        public function handle_webhook_fallback() {
            error_log('Wave Webhook Fallback Triggered');
            
            $order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
            $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
            $hash = isset($_GET['hash']) ? sanitize_text_field($_GET['hash']) : '';

            $request = new WP_REST_Request('POST', '/wave/v1/webhook');
            $request->set_query_params(array(
                'order_id' => $order_id,
                'status' => $status,
                'hash' => $hash
            ));

            return $this->handle_webhook($request);
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $api_url = 'https://api.wave.com/v1/checkout/sessions';
            
            $random_string = wp_generate_password(32, false);
            $success_hash = hash('sha256', $order_id . $random_string . 'success');
            $error_hash = hash('sha256', $order_id . $random_string . 'fail');

            $order->update_meta_data('_wave_random_string', $random_string);
            $order->update_meta_data('_wave_success_hash', $success_hash);
            $order->update_meta_data('_wave_error_hash', $error_hash);
            $order->save();

            $webhook_url = WC()->api_request_url('wave_webhook');
            $success_url = add_query_arg(array(
                'order_id' => $order_id,
                'status' => 'success',
                'hash' => $success_hash
            ), $webhook_url);
            
            $error_url = add_query_arg(array(
                'order_id' => $order_id,
                'status' => 'error',
                'hash' => $error_hash
            ), $webhook_url);

            error_log('Wave API Success URL: ' . $success_url);
            error_log('Wave API Error URL: ' . $error_url);

            $checkout_params = array(
                'amount' => (string)($order->get_total() * 100),
                'currency' => $order->get_currency(),
                'client_reference' => (string)$order->get_id(),
                'success_url' => $success_url,
                'error_url' => $error_url,
            );

            error_log('Wave API Request Parameters: ' . json_encode($checkout_params));
            error_log('Wave API Key: ' . substr($this->api_key, 0, 10) . '...');

            $response = wp_remote_post($api_url, array(
                'method' => 'POST',
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ),
                'body' => json_encode($checkout_params),
                'sslverify' => true
            ));

            error_log('Wave API Response: ' . wp_remote_retrieve_body($response));
            error_log('Wave API HTTP Code: ' . wp_remote_retrieve_response_code($response));

            if (is_wp_error($response)) {
                error_log('Wave API Error: ' . $response->get_error_message());
                wc_add_notice('Payment error: ' . $response->get_error_message(), 'error');
                return array(
                    'result' => 'fail',
                    'redirect' => '',
                );
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $http_code = wp_remote_retrieve_response_code($response);

            if ($http_code !== 200) {
                $error_message = isset($body['message']) ? $body['message'] : 'Invalid response from Wave';
                $error_code = isset($body['code']) ? $body['code'] : 'unknown';
                error_log('Wave API Error: ' . $error_message . ' (Code: ' . $error_code . ')');
                wc_add_notice('Payment error: ' . $error_message, 'error');
                return array(
                    'result' => 'fail',
                    'redirect' => '',
                );
            }

            if (isset($body['wave_launch_url'])) {
                $order->update_meta_data('_wave_checkout_session_id', $body['id']);
                $order->save();

                return array(
                    'result' => 'success',
                    'redirect' => $body['wave_launch_url'],
                );
            } else {
                error_log('Wave API Error: Missing wave_launch_url in response');
                wc_add_notice('Payment error: Invalid response from Wave', 'error');
                return array(
                    'result' => 'fail',
                    'redirect' => '',
                );
            }
        }
    }

    function add_wave_gateway($methods) {
        $methods[] = 'WC_Wave_Gateway';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'add_wave_gateway');
}
add_action('plugins_loaded', 'wave_wc_init');

function wave_wc_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('Wave WooCommerce Payment Gateway requires WooCommerce to be installed and active.', 'wave-woocommerce'); ?></p>
    </div>
    <?php
}

register_activation_hook(__FILE__, 'wave_wc_activate');
function wave_wc_activate() {
    if (!wave_wc_is_woocommerce_active()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Wave WooCommerce Payment Gateway requires WooCommerce to be installed and active.', 'wave-woocommerce'));
    }

    if (!get_option('wave_wc_settings')) {
        add_option('wave_wc_settings', array(
            'enabled' => 'no',
            'title' => 'Wave Payment',
            'description' => 'Pay with Wave mobile money',
            'api_key' => '',
        ));
    }

    flush_rewrite_rules();
    do_action('rest_api_init');
}

register_deactivation_hook(__FILE__, 'wave_wc_deactivate');
function wave_wc_deactivate() {
    flush_rewrite_rules();
}
?>