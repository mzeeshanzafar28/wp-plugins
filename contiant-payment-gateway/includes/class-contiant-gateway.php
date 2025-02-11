<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
#[ \AllowDynamicProperties ]

class WC_Contiant_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'contiant';
        $this->method_title = 'Contiant Payment Gateway';
        $this->method_description = 'Pay via Contiant Payment Gateway.';
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->client_id = $this->get_option( 'client_id' );
        $this->client_secret = $this->get_option( 'client_secret' );
        $this->api_url = $this->get_option( 'api_url' );
        // $this->contiant_callback = $this->get_option( 'contiant_callback' );
        $this->sort_code_identification = $this->get_option( 'sort_code_identification' );
        $this->account_number_identification = $this->get_option( 'account_number_identification' );
        $this->iban_identification = $this->get_option( 'iban_identification' );
        $this->bic_identification = $this->get_option( 'bic_identification' );
        $this->return_message = $this->get_option( 'return_message' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
        //wait case
        add_action( 'woocommerce_api_contiant_webhook', array( $this, 'handle_webhook' ) );
        //fail case
        add_action( 'woocommerce_api_contiant_fail_webhook', array( $this, 'handle_fail_webhook' ) );
        //success case
        add_action( 'woocommerce_api_contiant_callback', array( $this, 'contiant_callback' ) );
        //enp to get status
        add_action( 'woocommerce_api_get_order_status', array( $this, 'get_order_status' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable Contiant Payment Gateway',
                'default' => 'yes',
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Contiant Payment Gateway',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'Pay via Contiant Payment Gateway.',
                'desc_tip' => true,
            ),
            'client_id' => array(
                'title' => 'Client ID',
                'type' => 'text',
                'description' => 'Enter your Contiant Client ID.',
                'default' => '',
                'desc_tip' => true,
            ),
            'client_secret' => array(
                'title' => 'Client Secret',
                'type' => 'text',
                'description' => 'Enter your Contiant Client Secret.',
                'default' => '',
                'desc_tip' => true,
            ),
            'api_url' => array(
                'title' => 'API URL',
                'type' => 'text',
                'description' => 'Enter the Contiant API URL.',
                'default' => 'https://api.contiant.com',
                'desc_tip' => true,
            ),
            // 'contiant_callback' => array(
            //     'title' => 'Contiant Callback',
            //     'type' => 'text',
            //     'description' => 'Your Contiant Success URL that you will set in your Contiant dashboard.',
            //     'default' => get_home_url() . '/?wc-api=contiant_callback',
            //     'value  ' => get_home_url() . '/?wc-api=contiant_callback',
            //     'custom_attributes' => array( 'readonly' => 'readonly' ),
            //     'desc_tip' => true,
            //     'disabled' => true,
            // ),
            'return_message' => array(
                'title' => 'Return Message',
                'type' => 'textarea',
                'description' => 'The notice that will be added on order thank-you page after returning from payment page.',
                'default' => 'Your payment is being processed. It may take some time for the payment to be settled',
                'desc_tip' => true,
            ),
            'sort_code_identification' => array(
                'title' => 'Sort Code',
                'type' => 'number',
                'description' => 'Your Contiant Sort Code.',
                'desc_tip' => true,
            ),
            'account_number_identification'  => array(
                'title' => 'Account Number',
                'type' => 'number',
                'description' => 'Your Contiant Account Number.',
                'desc_tip' => true,
            ),
            'iban_identification' => array(
                'title' => 'IBAN',
                'type' => 'text',
                'description' => 'Your Contiant IBAN.',
                'desc_tip' => true,
            ),
            'bic_identification' => array(
                'title' => 'BIC',
                'type' => 'text',
                'description' => 'Your Contiant BIC.',
                'desc_tip' => true,
            ),
            'contiant_callback' => array(
                'type' => 'title',
                'title' => __(
                    '<div style="display: flex!important; align-items: center; justify-content: space-between;">' .
                    '<p>Contiant Callback</p>' .
                    '<strong style="margin-right: 50%;">' . esc_html__( get_home_url() . '/?wc-api=contiant_callback', 'contiant-payment-gateway' ) . '</strong>' .
                    '</div>',
                    'contiant-payment-gateway' ),
                ),

            );

        }

        private function generate_merchant_reference_id() {
            $prefix = uniqid( 'contiant', true );
            $random = bin2hex( random_bytes( 4 ) );
            $merchant_reference_id = $prefix . '_' . $random;
            $merchant_reference_id = preg_replace( '/[^a-zA-Z0-9]/', '', $merchant_reference_id );

            return substr( $merchant_reference_id, 0, 20 );
        }

        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            $contiant_api = new Contiant_API();
            $token = $contiant_api->authenticate();

            if ( !$token ) {
                wc_add_notice( 'Authentication failed. Please try again.', 'error' );
                return;
            }

            $account_identifications = array(
                array( 'type' => 'IBAN', 'identification' => strval( $this->get_option( 'iban_identification' ) ) ),
                array( 'type' => 'BIC', 'identification' => strval( $this->get_option( 'bic_identification' ) ) ),
            );

            if ( $order->get_currency() === 'GBP' ) {
                $account_identifications = [];
                $account_identifications[] = array( 'type' => 'SORT_CODE', 'identification' =>  $this->get_option( 'sort_code_identification' ) );
                $account_identifications[] = array( 'type' => 'ACCOUNT_NUMBER', 'identification' => $this->get_option( 'account_number_identification' ) );
            }

            $unique_id = strval( $order_id ) . '_' . uniqid( 'contiant_', true );

            $payment_data = array(
                'accountsDetails' => array(
                    'merchant' => array(
                        'address' => array( 'country' => 'GB', 'postCode' => '1000' ),
                        'name' => get_bloginfo( 'name' ),
                        'accountIdentifications' => $account_identifications,
                    )
                ),
                'amountInMinorUnit' => ( string ) ( $order->get_total() * 100 ),
                'currency' => $order->get_currency(),
                'country' => $order->get_billing_country(),
                'consumerId' => strval( $order->get_customer_id() ),
                'consumerFirstName' => $order->get_billing_first_name() ?? '',
                'consumerLastName' => $order->get_billing_last_name() ?? '',
                'consumerEmail' => $order->get_billing_email() ?? '',
                'merchantName' => get_bloginfo( 'name' ),
                'merchantReferenceId' => $unique_id,
                // 'bankId' => $order->get_currency() === 'GBP' ? '' : 'revolut_eu',
                'returnUrl' => get_home_url() . '/?wc-api=contiant_webhook&order_id=' . $order_id,
                'failUrl' => get_home_url() . '/?wc-api=contiant_fail_webhook&order_id=' . $order_id,
            );

            update_post_meta( $order_id, 'contiant_unique_id', $unique_id );

            $payment = $contiant_api->create_payment( $payment_data );
            
            if ( !$payment ) {
                wc_add_notice( 'Payment creation failed. Please try again.', 'error' );
                return;
            }

            if ( isset( $payment[ 'errorMessages' ] ) ) {
                wc_add_notice( strval( $payment[ 'errorMessages' ][ 0 ] ), 'error' );
                return;
            }

            return array(
                'result' => 'success',
                'redirect' => $payment[ 'paymentUrl' ],
            );
        }

        public function handle_webhook() {
        // return url - waiting page
            $order_id = $_GET[ 'order_id' ];
            $order = wc_get_order( $order_id );
            if ( $order ) {
                wc_add_notice( __( $this->get_option('return_message'), 'contiant-payment-gateway' ), 'notice' );
                wp_safe_redirect( $order->get_checkout_order_received_url() );
                exit;
            }

            wp_send_json_error( [ 'message' => 'Order not found' ] );
        }

        public function handle_fail_webhook() {
            //fail url
            $order_id = $_GET[ 'order_id' ] ;
            $order = wc_get_order( $order_id );

            if ( $order ) {
                // $order->update_status( 'failed', __( 'Payment failed.', 'contiant-payment-gateway' ) );
                wc_add_notice( __( 'Payment failed. Please try again.', 'contiant-payment-gateway' ), 'error' );
                wp_safe_redirect( wc_get_checkout_url() );
                exit;
            }

            wp_send_json_error( [ 'message' => 'Order not found' ] );

        }

        public function contiant_callback() {

            $payload = file_get_contents('php://input');
            $data = json_decode( $payload, true );

            if ( empty( $data ) ) {
                wp_send_json_error( [ 'message' => 'Invalid data' ], 400 );
                exit;
            }

            $contiant_unique_id = sanitize_text_field( $data[ 'merchantReferenceId' ] );
            $status = sanitize_text_field( $data[ 'status' ] ?? '' );

            
            global $wpdb;
            $query = $wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", 'contiant_unique_id', $contiant_unique_id);
            $response = $wpdb->get_row($query);
            $order_id = $response->post_id;
            $order = wc_get_order( $order_id );
            
            if ( !$order ) {
                wp_send_json_error( [ 'message' => 'Invalid order' ], 400 );
                exit;
            }

            if ( $status === 'SETTLED' ) {
                $order->payment_complete();
                $order->add_order_note( __( 'Payment successfully completed via Contiant.', 'contiant-payment-gateway' ) );
                wp_send_json_success( [ 'message' => 'Payment completed' ] );
            } else {
                $order->update_status( 'failed', __( 'Payment failed.', 'contiant-payment-gateway' ) );
                wp_send_json_error( [ 'message' => 'Payment failed' ], 400 );
            }

            exit;
        }

        public function get_order_status() {
            $order_id = $_REQUEST[ 'order_id' ] ?? $_POST[ 'order_id' ];
            $order = wc_get_order( $order_id );

            if ( $order ) {
                wp_send_json_success( [ 'status' => $order->get_status() ] );
            } else {
                wp_send_json_error( [ 'message' => 'Order not found' ] );
            }
        }

        public function receipt_page( $order_id ) {
            echo '<p>Thank you for your order. You will be redirected to Contiant Payment Gateway to complete your payment.</p>';
        }
    }
