<?php
/*
Plugin Name: WC Kesher Integration
Description: Integrate Kesher payment gateway with WooCommerce.
Version: 2.0
Author: Axon Technologies
*/


add_action('plugins_loaded', 'wc_kesher_integration_init');
function wc_kesher_integration_init()
{
    if (class_exists('WC_Payment_Gateway')) {

        if (!class_exists('WC_Kesher_Integration')) {

            class WC_Kesher_Integration
            {

                public function __construct()
                {
                    add_action('woocommerce_payment_gateways', array($this, 'add_kesher_gateway'));
                    add_filter('woocommerce_available_payment_gateways', function ($gateways) {
                        $currency_code = get_woocommerce_currency();
                        $currency_values = array (
                            'ILS' => 1,
                            'USD' => 2,
                            'GBP' => 826,
                            'EUR' => 978
                        );
                        if (!array_key_exists($currency_code, $currency_values)) {
                            unset ($gateways['kesher']);
                        }
                        return $gateways;
                    });
                    add_action('wp_enqueue_scripts', array($this, 'wc_kesher_enqueue_scripts'));
                    add_action('admin_enqueue_scripts', array($this, 'wc_kesher_admin_enqueue_scripts'));

                }

                function wc_kesher_admin_enqueue_scripts()
                {
                    wp_enqueue_style('kesher-css', plugin_dir_url(__FILE__) . 'assets/css/kesher.css');
                    wp_enqueue_script('kesher-admin-script', plugin_dir_url(__FILE__) . 'assets/js/kesher-admin.js', array('jquery'), null, true);

                }
                function wc_kesher_enqueue_scripts()
                {
                    wp_enqueue_script('jQuery-mask-plugin', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.10/jquery.mask.js', array('jquery'), null, true);
                    wp_enqueue_script('custom-jquery-mask-kesher', plugin_dir_url(__FILE__) . 'assets/js/kesher.js', array('jQuery-mask-plugin'), null, true);
                }

                public function add_kesher_gateway($gateways)
                {
                    $gateways[] = 'WC_Kesher_Gateway';
                    return $gateways;
                }
            }

            new WC_Kesher_Integration();
        }

        if (!class_exists('WC_Kesher_Gateway')) {

            class WC_Kesher_Gateway extends WC_Payment_Gateway
            {
                public function __construct()
                {
                    $this->id = 'kesher';
                    $this->method_title = 'Kesher';
                    $this->method_description = 'Pay with Kesher';
                    $this->title = 'תשלום בכרטיס אשראי דרך קשר';
                    $this->supports = array('products');
                    $this->has_fields = true;

                    $this->init_form_fields();
                    $this->init_settings();

                    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                }

                public function init_form_fields()
                {

                    $payment_fields = array(
                        'credit_card_number' => __('Credit Card Number', 'woocommerce'),
                        'expiry_date' => __('Expiry Date (MM/YY)', 'woocommerce'),
                        'cvv' => __('CVV', 'woocommerce'),
                        'id' => __('ID', 'woocommerce'),

                    );

                    $css_properties = array(
                        'label_name' => __('Label', 'woocommerce'),
                        'placeholder' => __('Placeholder', 'woocommerce'),
                    );
                    $css_properties_common = array(
                        'height' => __('Height', 'woocommerce'),
                        'width' => __('Width', 'woocommerce'),
                        'background_color' => __('Background Color', 'woocommerce'),
                        'border_color' => __('Border Color', 'woocommerce'),
                        'border_radius' => __('Border Radius', 'woocommerce'),
                        'font_size' => __('Font Size', 'woocommerce'),
                        'font_weight' => __('Font Weight', 'woocommerce'),
                        'text_color' => __('Text Color', 'woocommerce'),
                        'label_color' => __('Label Color', 'woocommerce'),
                        'label_font_size' => __('Label Font Size', 'woocommerce'),
                        'label_font_weight' => __('Label Font Weight', 'woocommerce'),
                    );


                    $css_properties_form = array(
                        'background_color' => __('Background Color', 'woocommerce'),
                        'border_radius' => __('Border Radius', 'woocommerce'),
                        'border_color' => __('Border Color', 'woocommerce'),
                        'border_width' => __('Border Width', 'woocommerce'),

                    );


                    $fields = array();

                    $fields['enabled'] = array(
                        'title' => __('Enable/Disable', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Enable Kesher Gateway', 'woocommerce'),
                        'default' => 'yes',
                    );
                    $fields['username'] = array(
                        'title' => __('Username', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('Your Kesher API username.', 'woocommerce'),
                        'default' => '',
                    );
                    $fields['password'] = array(
                        'title' => __('Password', 'woocommerce'),
                        'type' => 'password',
                        'description' => __('Your Kesher API password.', 'woocommerce'),
                        'default' => '',
                    );

                    $fields['installments'] = array(
                        'title' => __('Enable/Disable Installments', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Enable Kesher Installments', 'woocommerce'),
                        'default' => 'no',
                    );
                    $fields['ins_rules'] = array(
                        'title' => __('', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('', 'woocommerce'),
                        'default' => '',
                        'class' => 'hidden',
                    );

                    $fields['1'] = array(
                        'title' => __('<hr><hr><hr>', 'woocommerce'),
                        'type' => 'title',
                    );


                    $fields['fields_styling_label'] = array(
                        'title' => __('<h1>Checkout Fields Styling</h1><br><br>', 'woocommerce'),
                        'type' => 'title',
                    );

                    $fields['credit_card_label'] = array(
                        'title' => __('Credit Card Field', 'woocommerce'),
                        'type' => 'title',
                        'class' => 'kesher-label'
                    );

                    foreach ($css_properties as $property => $label) {
                        $fields['ccn_' . $property] = array(
                            'title' => $label,
                            'type' => 'text',
                            'description' => sprintf(__('Enter CSS value for %s.', 'woocommerce'), $label),
                            'default' => '',
                        );
                    }

                    $fields['2'] = array(
                        'title' => __('<hr>', 'woocommerce'),
                        'type' => 'title',
                    );

                    $fields['cvv_label'] = array(
                        'title' => __('CVV Field', 'woocommerce'),
                        'type' => 'title',
                        'class' => 'kesher-label'
                    );

                    foreach ($css_properties as $property => $label) {
                        $fields['cvv_' . $property] = array(
                            'title' => $label,
                            'type' => 'text',
                            'description' => sprintf(__('Enter CSS value for %s.', 'woocommerce'), $label),
                            'default' => '',
                        );
                    }

                    $fields['3'] = array(
                        'title' => __('<hr>', 'woocommerce'),
                        'type' => 'title',
                    );

                    $fields['expiry_label'] = array(
                        'title' => __('Expiry Field', 'woocommerce'),
                        'type' => 'title',
                        'class' => 'kesher-label'
                    );

                    foreach ($css_properties as $property => $label) {
                        $fields['expiry_' . $property] = array(
                            'title' => $label,
                            'type' => 'text',
                            'description' => sprintf(__('Enter CSS value for %s.', 'woocommerce'), $label),
                            'default' => '',
                        );
                    }

                    $fields['6'] = array(
                        'title' => __('<hr>', 'woocommerce'),
                        'type' => 'title',
                    );

                    $fields['id_label'] = array(
                        'title' => __('ID Field', 'woocommerce'),
                        'type' => 'title',
                        'class' => 'kesher-label'
                    );

                    foreach ($css_properties as $property => $label) {
                        $fields['id_' . $property] = array(
                            'title' => $label,
                            'type' => 'text',
                            'description' => sprintf(__('Enter CSS value for %s.', 'woocommerce'), $label),
                            'default' => '',
                        );
                    }

                    $fields['4'] = array(
                        'title' => __('<hr>', 'woocommerce'),
                        'type' => 'title',
                    );

                    $fields['common_label'] = array(
                        'title' => __('OTHER FIELDS CSS', 'woocommerce'),
                        'type' => 'title',
                        'class' => 'kesher-label'
                    );

                    foreach ($css_properties_common as $property => $label) {
                        $fields[$property] = array(
                            'title' => $label,
                            'type' => str_contains($property, 'color') ? 'color' :'text',
                            'class' => str_contains($property, 'color') ? 'kesher-color-field' :'',
                            'description' => sprintf(__('Enter CSS value for %s.', 'woocommerce'), $label),
                            'default' => '',
                        );
                    }

                    $fields['5'] = array(
                        'title' => __('<hr>', 'woocommerce'),
                        'type' => 'title',
                    );

                    $fields['form_label'] = array(
                        'title' => __('Form CSS', 'woocommerce'),
                        'type' => 'title',
                        'class' => 'kesher-label'
                    );

                    foreach ($css_properties_form as $property => $label) {
                        $fields['form_' . $property] = array(
                            'title' => $label,
                            'type' => str_contains($property, 'color') ? 'color' :'text',
                            'class' => str_contains($property, 'color') ? 'kesher-color-field' :'',
                            'description' => sprintf(__('Enter CSS value for %s.', 'woocommerce'), $label),
                            'default' => '',
                        );
                    }

                    $this->form_fields = $fields;
                }

                public function payment_fields()
                {
                    $ccn_label_name = !empty($this->get_option('ccn_label_name')) ? $this->get_option('ccn_label_name') : "מספר כרטיס אשראי";
                    $ccn_placeholder = $this->get_option('ccn_placeholder');

                    $cvv_label_name = !empty($this->get_option('cvv_label_name')) ? $this->get_option('cvv_label_name') : "3 ספרות בגב הכרטיס";
                    $cvv_placeholder = $this->get_option('cvv_placeholder');

                    $expiry_label_name = !empty($this->get_option('expiry_label_name')) ? $this->get_option('expiry_label_name') : "תוקף";
                    $expiry_placeholder = $this->get_option('expiry_placeholder');

                    $id_label_name = !empty($this->get_option('id_label_name')) ? $this->get_option('id_label_name') : "מספר תעודת זהות";
                    $id_placeholder = $this->get_option('id_placeholder');


                    $height = $this->get_option('height');
                    $width = $this->get_option('width');
                    $background = $this->get_option('background_color');
                    $border_color = $this->get_option('border_color');
                    $border_radius = $this->get_option('border_radius');
                    $font_size = $this->get_option('font_size');
                    $font_weight = $this->get_option('font_weight');
                    $text_color = $this->get_option('text_color');
                    $label_color = $this->get_option('label_color');
                    $label_font_size = $this->get_option('label_font_size');
                    $label_font_weight = $this->get_option('label_font_weight');

                    $form_background = $this->get_option('form_background_color');
                    $form_border_radius = $this->get_option('form_border_radius');
                    $form_border_color = $this->get_option('form_border_color');
                    $form_border_width = $this->get_option('form_border_width');


                    ?>
                    <div id="kesher-credit-card-fields"
                        style="background:<?php echo esc_attr($form_background); ?>;
                    border-radius:<?php echo esc_attr($form_border_radius); ?>; border-color:<?php echo esc_attr($form_border_color); ?>; border-width:<?php echo esc_attr($form_border_width); ?>; ">
                        <p class="form-row form-row-wide">
                            <label for="kesher_credit_card_number" style="color: <?php echo esc_attr($label_color); ?>;
                                font-size: <?php echo esc_attr($label_font_size); ?>;
                                font-weight: <?php echo esc_attr($label_font_weight); ?>">
                                <?php echo esc_html($ccn_label_name); ?> <span class="required">*</span>
                            </label>
                            <input type="text" min=13 placeholder="<?php echo esc_attr($ccn_placeholder); ?> " class="input-text"
                                name="kesher_credit_card_number" id="kesher_credit_card_number" style="height: <?php echo esc_attr($height); ?>; width: <?php echo esc_attr($width); ?>;
                                          border-color: <?php echo esc_attr($border_color); ?>;
                                          border-radius: <?php echo esc_attr($border_radius); ?>;
                                          font-size: <?php echo esc_attr($font_size); ?>;
                                          font-weight: <?php echo esc_attr($font_weight); ?>;
                                          background: <?php echo esc_attr($background); ?>;
                                          color: <?php echo esc_attr($text_color); ?>" />
                                          
                        </p>
                        <p class="form-row form-row-first">
                            <label for="kesher_expiry_date" style="color: <?php echo esc_attr($label_color); ?>;
                                font-size: <?php echo esc_attr($label_font_size); ?>;
                                font-weight: <?php echo esc_attr($label_font_weight); ?>">
                                <?php echo esc_html($expiry_label_name); ?> <span class="required">*</span>
                            </label>
                            <input type="text" placeholder="<?php echo esc_attr($expiry_placeholder); ?> " class="input-text"
                                name="kesher_expiry_date" id="kesher_expiry_date" style="height: <?php echo esc_attr($height); ?>; width: <?php echo esc_attr($width); ?>;
                                          border-color: <?php echo esc_attr($border_color); ?>;
                                          border-radius: <?php echo esc_attr($border_radius); ?>;
                                          font-size: <?php echo esc_attr($font_size); ?>;
                                          font-weight: <?php echo esc_attr($font_weight); ?>;
                                          background: <?php echo esc_attr($background); ?>;
                                          color: <?php echo esc_attr($text_color); ?>" />
                        </p>
                        <p class="form-row form-row-last">
                            <label for="kesher_cvv" style="color: <?php echo esc_attr($label_color); ?>;
                                font-size: <?php echo esc_attr($label_font_size); ?>;
                                font-weight: <?php echo esc_attr($label_font_weight); ?>">
                                <?php echo esc_html($cvv_label_name); ?> <span class="required">*</span>
                            </label>
                            <input type="text" placeholder="<?php echo esc_attr($cvv_placeholder); ?> " class="input-text"
                                name="kesher_cvv" id="kesher_cvv" style="height: <?php echo esc_attr($height); ?>; width: <?php echo esc_attr($width); ?>;
                                          border-color: <?php echo esc_attr($border_color); ?>;
                                          border-radius: <?php echo esc_attr($border_radius); ?>;
                                          font-size: <?php echo esc_attr($font_size); ?>;
                                          font-weight: <?php echo esc_attr($font_weight); ?>;
                                          background: <?php echo esc_attr($background); ?>;
                                          color: <?php echo esc_attr($text_color); ?>" />
                        </p>
                        <p class="form-row form-row-wide">
                            <label for="kesher_id" style="color: <?php echo esc_attr($label_color); ?>;
                                font-size: <?php echo esc_attr($label_font_size); ?>;
                                font-weight: <?php echo esc_attr($label_font_weight); ?>">
                                <?php echo esc_html($id_label_name); ?> <span class="required">*</span>
                            </label>
                            <input type="text" min=1 placeholder="<?php echo esc_attr($id_placeholder); ?> " class="input-text"
                                name="kesher_id" id="kesher_id" style="height: <?php echo esc_attr($height); ?>; width: <?php echo esc_attr($width); ?>;
                                          border-color: <?php echo esc_attr($border_color); ?>;
                                          border-radius: <?php echo esc_attr($border_radius); ?>;
                                          font-size: <?php echo esc_attr($font_size); ?>;
                                          font-weight: <?php echo esc_attr($font_weight); ?>;
                                          background: <?php echo esc_attr($background); ?>;
                                          color: <?php echo esc_attr($text_color); ?>" />
                        </p>
                        <div class="clear"></div>
                    </div>
                    <?php
                }

                public function process_payment($order_id)
                {
                    global $woocommerce;

                    $order = wc_get_order($order_id);

                    $credit_card_number = isset($_POST['kesher_credit_card_number']) ? wc_clean($_POST['kesher_credit_card_number']) : '';
                    $expiry_date = isset($_POST['kesher_expiry_date']) ? wc_clean($_POST['kesher_expiry_date']) : '';
                    $cvv = isset($_POST['kesher_cvv']) ? wc_clean($_POST['kesher_cvv']) : '';
                    $id = isset($_POST['kesher_id']) ? wc_clean($_POST['kesher_id']) : '';


                    if (empty($credit_card_number) || empty($expiry_date) || empty($cvv)) {
                        wc_add_notice(__('Please fill in all credit card details.', 'woocommerce'), 'error');
                        return;
                    }

                    $order_info = $this->extract_order_info($order);
                    $expiry_parts = explode('/', $expiry_date);
                    $new_expiry_date = $expiry_parts[1] . $expiry_parts[0];
                    $currency_code = get_woocommerce_currency();
                    $currency_values = array(
                        'ILS' => 1,
                        'USD' => 2,
                        'GBP' => 826,
                        'EUR' => 978
                    );
                    $custom_currency_numeric_value = $currency_values[$currency_code];

                    $installments = '';
                    $order_total = $order_info['order_total'];
                    if (isset($this->settings['installments']) && $this->settings['installments'] == 'yes') {
                        if (!empty($this->settings['ins_rules'])) {
                            $rules = json_decode($this->settings['ins_rules'], true);
                            if (!empty($rules) && is_array($rules)) {
                                foreach ($rules as $r) {
                                    if ($order_total >= $r['from'] && $order_total <= $r['to']) {
                                        $num_payments = $r['installments'];
                                        $first_payment = $order_total / $r['installments'];
                                        $installments = '"NumPayment": ' . intval($num_payments - 1) . ',
                                        "FirstPayment": ' . $first_payment . ',';
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    $request_body = '{
                        "Json": {
                            "userName": "' . $this->settings['username'] . '",
                            "password": "' . $this->settings['password'] . '",
                            "func": "SendTransaction",
                            "format": "json",
                            "tran": {
                                "FirstName": "' . $order_info['billing_first_name'] . '",
                                "LastName": "' . $order_info['billing_last_name'] . '",
                                "Phone": "' . $order_info['billing_phone'] . '",
                                "Mail": "' . $order_info['billing_email'] . '",
                                "Address": "' . $order_info['billing_address_1'] . '",
                                "City": "' . $order_info['billing_city'] . '",
                                "CreditNum": "' . str_replace(" ", "", $credit_card_number) . '",
                                "Expiry": "' . $new_expiry_date . '",
                                "Cvv2": "' . $cvv . '",
                                "Total": "' . $order_total * 100 . '",
                                "Id": "' . $id . '",
                                "Currency": "' . $custom_currency_numeric_value . '",
                                "CreditType": 1,
                                "ParamJ": "J4",
                                "TransactionType": "debit",
                                "ProjectNumber": "' . $order_info['order_id'] . '"
                            }
                        },
                        "format": "json"
                    }';
                    


                    $response = wp_remote_post(
                        'https://kesherhk.info/ConnectToKesher/ConnectToKesher',
                        array(
                            'body' => $request_body,
                            'headers' => array(
                                'Content-Type' => 'application/json'
                            ),
                            'timeout' => 120
                        )
                    );

                    if (is_wp_error($response)) {
                        $error_message = $response->get_error_message();

                    } else {
                        $response_body = json_decode(wp_remote_retrieve_body($response), true);
                        if (!(isset($response_body['RequestResult']['Code']) && $response_body['RequestResult']['Code'] == 0)) {
                            wc_add_notice('Error: ' . $response_body['RequestResult']['Description'] ?? 'Unknown Error', 'error');
                            return;
                        }

                        $order->payment_complete();
                        $order->reduce_order_stock();
                        $woocommerce->cart->empty_cart();

                        return array(
                            'result' => 'success',
                            'redirect' => $this->get_return_url($order),
                        );
                    }
                }

                private function extract_order_info($order)
                {
                    $order_info = array(
                        'order_id' => $order->get_id(),
                        'order_number' => $order->get_order_number(),
                        'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                        'order_total' => $order->get_total(),
                        'currency' => $order->get_currency(),
                        'billing_phone' => $order->get_billing_phone(),
                        'billing_first_name' => $order->get_billing_first_name(),
                        'billing_last_name' => $order->get_billing_last_name(),
                        'billing_email' => $order->get_billing_email(),
                        'billing_address_1' => $order->get_billing_address_1(),
                        'billing_city' => $order->get_billing_city(),
                        'billing_country' => $order->get_billing_country(),

                    );

                    return $order_info;
                }
            }
        }
    }
}

//KESHER BIT CODE BEGINS HERE

add_action('plugins_loaded', 'wc_kesher_bit_integration_init');
function wc_kesher_bit_integration_init()
{
    if (class_exists('WC_Payment_Gateway')) {

        if (!class_exists('WC_Kesher_Bit_Integration')) {

            class WC_Kesher_Bit_Integration
            {

                public function __construct()
                {
                    add_action('woocommerce_payment_gateways', array($this, 'add_kesher_bit_gateway'));
                    add_filter('woocommerce_available_payment_gateways', function ($gateways) {
                        $currency_code = get_woocommerce_currency();
                        $currency_values = array (
                            'ILS' => 1,
                            'USD' => 2,
                            'GBP' => 826,
                            'EUR' => 978
                        );
                        if (!array_key_exists($currency_code, $currency_values)) {
                            unset ($gateways['kesher-bit']);
                        }
                        return $gateways;
                    });
                    add_action('wp_enqueue_scripts', array($this, 'wc_kesher_bit_enqueue_scripts'));
                    add_action('admin_enqueue_scripts', array($this, 'wc_kesher_bit_admin_enqueue_scripts'));

                }

                function wc_kesher_bit_admin_enqueue_scripts()
                {
                    wp_enqueue_style('kesher-bit-css', plugin_dir_url(__FILE__) . 'assets/css/kesher-bit.css');

                }

                function wc_kesher_bit_enqueue_scripts()
                {
                    wp_enqueue_script('jQuery-mask-plugin', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.10/jquery.mask.js', array('jquery'), null, true);
                    wp_enqueue_script('custom-jquery-mask-kesher-bit', plugin_dir_url(__FILE__) . 'assets/js/kesher-bit.js', array('jQuery-mask-plugin'), null, true);
                }

                public function add_kesher_bit_gateway($gateways)
                {
                    $gateways[] = 'WC_Kesher_Bit_Gateway';
                    return $gateways;
                }
            }

            new WC_Kesher_Bit_Integration();
        }

        if (!class_exists('WC_Kesher_Bit_Gateway')) {

            class WC_Kesher_Bit_Gateway extends WC_Payment_Gateway
            {

                public function __construct()
                {
                    $this->id = 'kesher-bit';
                    $this->method_title = 'Kesher Bit';
                    $this->method_description = 'Pay with Kesher Bit';
                    $this->title = 'תשלום בביט דרך קשר';
                    $this->supports = array('products');
                    $this->has_fields = true;

                    $this->init_form_fields();
                    $this->init_settings();

                    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

                }

                public function init_form_fields()
                {
                    $payment_fields = array(
                        'phone' => __('Phone', 'woocommerce'),
                        'id' => __('ID', 'woocommerce'),

                    );

                    $css_properties = array(
                        'label_name' => __('Label', 'woocommerce'),
                        'placeholder' => __('Placeholder', 'woocommerce'),
                    );

                    $fields = array();

                    $fields['enabled'] = array(
                        'title' => __('Enable/Disable', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Enable Kesher Bit Gateway', 'woocommerce'),
                        'default' => 'yes',
                    );
                    $fields['username'] = array(
                        'title' => __('Username', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('Your Kesher Bit API username.', 'woocommerce'),
                        'default' => '',
                    );
                    $fields['password'] = array(
                        'title' => __('Password', 'woocommerce'),
                        'type' => 'password',
                        'description' => __('Your Kesher Bit API password.', 'woocommerce'),
                        'default' => '',
                    );

                    $fields['1'] = array(
                        'title' => __('<hr><hr><hr>', 'woocommerce'),
                        'type' => 'title',
                    );


                    $fields['fields_styling_label'] = array(
                        'title' => __('<h1>Checkout Fields Styling</h1><br><br>', 'woocommerce'),
                        'type' => 'title',
                    );

                    $fields['phone_label'] = array(
                        'title' => __(' Phone Number Field ', 'woocommerce'),
                        'type' => 'title',
                        'class' => 'kesher-bit-label'
                    );

                    foreach ($css_properties as $property => $label) {
                        $fields['phone_' . $property] = array(
                            'title' => $label,
                            'type' => 'text',
                            'description' => sprintf(__('Enter CSS value for %s.', 'woocommerce'), $label),
                            'default' => '',
                        );
                    }

                    $fields['2'] = array(
                        'title' => __('<hr>', 'woocommerce'),
                        'type' => 'title',
                    );

                    $fields['id_label'] = array(
                        'title' => __('ID Field', 'woocommerce'),
                        'type' => 'title',
                        'class' => 'kesher-bit-label'
                    );

                    foreach ($css_properties as $property => $label) {
                        $fields['id_' . $property] = array(
                            'title' => $label,
                            'type' => 'text',
                            'description' => sprintf(__('Enter CSS value for %s.', 'woocommerce'), $label),
                            'default' => '',
                        );
                    }

                    $fields['3'] = array(
                        'title' => __('<hr>', 'woocommerce'),
                        'type' => 'title',
                    );

                    $fields['timer_label'] = array(
                        'title' => __('Timer Field', 'woocommerce'),
                        'type' => 'title',
                        'class' => 'kesher-bit-label'
                    );

                    $fields['timer'] = array(
                        'title' => __('Timer', 'woocommerce'),
                        'type' => 'number',
                        'description' => __('Your Kesher Bit Waiting Time.', 'woocommerce'),
                        'default' => 10,
                    );

                    $this->form_fields = $fields;
                }

                public function payment_fields()
                {

                    global $woocommerce;
                    $orderID = $woocommerce->session->order_awaiting_payment ?? 0;
                    if (!empty($orderID)) {
                        $order = wc_get_order($orderID);
                        $status = $order->get_status();
                        if ($status == 'completed'){
                            header("Location: '.$order->get_checkout_order_received_url().'");
                            return;
                        }

                        $num_transaction = $order->get_meta('num_transaction');
                        if (!empty($num_transaction)) {
                            $remaining = $order->get_meta('transaction_time') - time();
                            $minutes = intval($remaining / 60);
                            $seconds = intval($remaining % 60);
                            ?>
                           <script>
                                jQuery(document).ready(function($){
                                    let minutes = <?php echo json_encode($minutes); ?>;
                                    let seconds = <?php echo json_encode($seconds); ?>;
                                    let remaining = <?php echo json_encode($remaining); ?>;
                                    if (remaining > 0){
                                        let pTag = "<p id='kesher-bit-timer' style='color:red; font-weight:bold; -webkit-text-stroke: 1px black; margin:5px; text-align:center; font-size:x-large;'>Complete Payment in " + minutes + " minutes " + seconds + " seconds</p>";
                                        pTag += '<style>#place_order, #kesher_bit_phone_number, #kesher_bit_id, .kesher-bit-label, .kesher-bit-span {display:none !important;}</style>';
                                        jQuery("#kesher_bit_id").after(pTag);
                                    }
                                });
                            </script>
                            <?php
                                

                            $request_body = '{
                                "Json": {
                                    "userName": "' . $this->settings['username'] . '",
                                    "password": "' . $this->settings['password'] . '",
                                    "func": "GetTranData",
                                    "format": "json",
                                    "transactionNum": "'.$num_transaction.'"
                                },
                                "format": "json"
                            }';
        
                            $response = wp_remote_post('https://kesherhk.info/ConnectToKesher/ConnectToKesher', array(
                                'body' => $request_body,
                                'headers' => array(
                                    'Content-Type' => 'application/json'
                                ),
                                'timeout' => 120
                                )
                            );
                            $response_body = json_decode(wp_remote_retrieve_body($response), true);
                            $text_status = $response_body['Status'] ?? '';
                            if ($text_status === 'עבר בהצלחה')
                            {
                                $order->payment_complete();
                                $order->reduce_order_stock();
                                header("Location: " . $order->get_checkout_order_received_url());
                                $woocommerce->cart->empty_cart();
                                }

                            }
                        }
                    

                    $phone_label_name = !empty($this->get_option('phone_label_name')) ? $this->get_option('phone_label_name') : "מספר טלפון";
                    $phone_placeholder = $this->get_option('phone_placeholder');

                    $id_label_name = !empty($this->get_option('id_label_name')) ? $this->get_option('id_label_name') : "תעודת זהות";
                    $id_placeholder = $this->get_option('id_placeholder');

                    $kesher_gateway = new WC_Kesher_Gateway();

                    $height = $kesher_gateway->get_option('height');
                    $width = $kesher_gateway->get_option('width');
                    $background = $kesher_gateway->get_option('background_color');
                    $border_color = $kesher_gateway->get_option('border_color');
                    $border_radius = $kesher_gateway->get_option('border_radius');
                    $font_size = $kesher_gateway->get_option('font_size');
                    $font_weight = $kesher_gateway->get_option('font_weight');
                    $text_color = $kesher_gateway->get_option('text_color');
                    $label_color = $kesher_gateway->get_option('label_color');
                    $label_font_size = $kesher_gateway->get_option('label_font_size');
                    $label_font_weight = $kesher_gateway->get_option('label_font_weight');

                    $form_background = $kesher_gateway->get_option('form_background_color');
                    $form_border_radius = $kesher_gateway->get_option('form_border_radius');
                    $form_border_color = $kesher_gateway->get_option('form_border_color');
                    $form_border_width = $kesher_gateway->get_option('form_border_width');

                    ?>
                    <div id="kesher-bit-credit-card-fields"
                        style="background:<?php echo esc_attr($form_background); ?>;
                    border-radius:<?php echo esc_attr($form_border_radius); ?>; border-color:<?php echo esc_attr($form_border_color); ?>; border-width:<?php echo esc_attr($form_border_width); ?>; ">
                        <p class="form-row form-row-wide">
                            <label for="kesher_bit_phone_number" class="kesher-bit-label" style="color: <?php echo esc_attr($label_color); ?>;
                            font-size: <?php echo esc_attr($label_font_size); ?>;
                            font-weight: <?php echo esc_attr($label_font_weight); ?>">
                                <?php echo esc_html($phone_label_name); ?> <span class="required kesher-bit-span">*</span>
                            </label>
                            <input type="text" placeholder="<?php echo esc_attr($phone_placeholder); ?> "  class="input-text"
                                name="kesher_bit_phone_number" id="kesher_bit_phone_number" maxlength="10" style="height: <?php echo esc_attr($height); ?>; width: <?php echo esc_attr($width); ?>;
                                          border-color: <?php echo esc_attr($border_color); ?>;
                                          border-radius: <?php echo esc_attr($border_radius); ?>;
                                          font-size: <?php echo esc_attr($font_size); ?>;
                                          font-weight: <?php echo esc_attr($font_weight); ?>;
                                          background: <?php echo esc_attr($background); ?>;
                                          color: <?php echo esc_attr($text_color); ?>" />
                        </p>
                        <p class="form-row form-row-wide">
                            <label for="kesher_bit_id" class="kesher-bit-label" style="color: <?php echo esc_attr($label_color); ?>;
                                font-size: <?php echo esc_attr($label_font_size); ?>;
                                font-weight: <?php echo esc_attr($label_font_weight); ?>">
                                <?php echo esc_html($id_label_name); ?> <span class="required kesher-bit-span">*</span>
                            </label>
                            <input type="text" min=1 placeholder="<?php echo esc_attr($id_placeholder); ?> " class="input-text"
                                name="kesher_bit_id" id="kesher_bit_id" style="height: <?php echo esc_attr($height); ?>; width: <?php echo esc_attr($width); ?>;
                                          border-color: <?php echo esc_attr($border_color); ?>;
                                          border-radius: <?php echo esc_attr($border_radius); ?>;
                                          font-size: <?php echo esc_attr($font_size); ?>;
                                          font-weight: <?php echo esc_attr($font_weight); ?>;
                                          background: <?php echo esc_attr($background); ?>;
                                          color: <?php echo esc_attr($text_color); ?>" />
                        </p>
                        <div class="clear"></div>
                        </div>
                    <?php
                    if (isset($remaining) && $remaining >= 1)
                    {
                        ?>
                        <script>
                                jQuery(document).ready(function($){
                                setTimeout(function() {
                                $('body').trigger('update_checkout');
                                },
                                10000);
                                });
                        </script>
                        <?php
                    }
                }


                public function process_payment($order_id)
                {
                    global $woocommerce;

                    $order = wc_get_order($order_id);

                    $phone_number = isset($_POST['kesher_bit_phone_number']) ? wc_clean($_POST['kesher_bit_phone_number']) : '';
                    $id = isset($_POST['kesher_bit_id']) ? wc_clean($_POST['kesher_bit_id']) : '';

                    if (empty($phone_number) || empty($id)) {
                        wc_add_notice(__('Please fill in all details.', 'woocommerce'), 'error');
                        return;
                    }

                    $order_info = $this->extract_order_info($order);
                    $currency_code = get_woocommerce_currency();
                    $currency_values = array(
                        'ILS' => 1,
                        'USD' => 2,
                        'GBP' => 826,
                        'EUR' => 978
                    );
                    $custom_currency_numeric_value = $currency_values[$currency_code];
                    $order_total = $order_info['order_total'];

                    $request_body = '{
                        "Json": {
                            "userName": "' . $this->settings['username'] . '",
                            "password": "' . $this->settings['password'] . '",
                            "func": "SendBitTransaction",
                            "format": "json",
                            "transaction": {
                                "FirstName": "' . $order_info['billing_first_name'] . '",
                                "LastName": "' . $order_info['billing_last_name'] . '",
                                "Mail": "' . $order_info['billing_email'] . '",
                                "Address": "' . $order_info['billing_address_1'] . '",
                                "City": "' . $order_info['billing_city'] . '",
                                "Total": "' . $order_total * 100 . '",
                                "Id": "' . $id . '",
                                "Currency": "' . $custom_currency_numeric_value . '",
                                "CreditType": 1,
                                "Phone": "' . $phone_number . '",
                                "ParamJ": "J4",
                                "TransactionType": "debit",
                                "ProjectNumber": "' . $order_info['order_id'] . '"
                            }
                        },
                        "format": "json"
                    }';


                    $response = wp_remote_post('https://kesherhk.info/ConnectToKesher/ConnectToKesher', array(
                        'body' => $request_body,
                        'headers' => array(
                            'Content-Type' => 'application/json'
                        ),
                        'timeout' => 120
                    )
                    );

                    if (is_wp_error($response)) {
                        $error_message = $response->get_error_message();

                    } else {
                        $response_body = json_decode(wp_remote_retrieve_body($response), true);

                        if (isset($response_body['RequestResult']['Code']) && $response_body['RequestResult']['Code'] == 30001087) {
                            $transaction_url = $response_body['BitUrl'];
                            $num_transaction = $response_body['NumTransaction'];
                            $order->update_meta_data('num_transaction', $num_transaction);
                            $order->update_meta_data('transaction_url', $transaction_url);
                            $order->update_meta_data('transaction_time', time() + (intval($this->settings['timer']) * 60));
                            $order->save();
                            wc_add_notice('Please check your mobile phone and approve the payment request within 2 minutes.', 'notice');
                            return ['refresh' => false, 'reload' => true, 'result' => "success" ];
                        }

                        if (!(isset($response_body['RequestResult']['Code']) && $response_body['RequestResult']['Code'] == 0)) {
                            wc_add_notice('Error: ' . $response_body['RequestResult']['Description'] ?? 'Unknown Error', 'error');
                            return;
                        }

                        wc_add_notice('Error: Payment request could not be sent.', 'error');
                        return;
                    }
                }

                private function extract_order_info($order)
                {
                    $order_info = array(
                        'order_id' => $order->get_id(),
                        'order_number' => $order->get_order_number(),
                        'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                        'order_total' => $order->get_total(),
                        'currency' => $order->get_currency(),
                        'billing_phone' => $order->get_billing_phone(),
                        'billing_first_name' => $order->get_billing_first_name(),
                        'billing_last_name' => $order->get_billing_last_name(),
                        'billing_email' => $order->get_billing_email(),
                        'billing_address_1' => $order->get_billing_address_1(),
                        'billing_city' => $order->get_billing_city(),
                        'billing_country' => $order->get_billing_country(),

                    );

                    return $order_info;
                }
            }
        }
    }
}

