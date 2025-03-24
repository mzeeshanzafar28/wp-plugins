<?php
/*
* Plugin Name: Axon Shipping Protection
* Author: Axon Technologies
* Version: 1.0
* Author URI: https://axontech.pk
* Description: Adds shipping protection fee to WooCommerce checkout with toggle option
*/

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Axon_Shipping_Protection {
    private $fee_percentage = 2.5;
    private $is_enforced = false;

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        // add_action( 'enqueue_block_assets', array( $this, 'axon_enqueue_block_assets' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_shipping_protection_fee' ) );
        add_action( 'woocommerce_review_order_before_order_total', array( $this, 'add_shipping_protection_field' ) );
        add_action( 'wp_footer', array( $this, 'add_popup_html' ) );
        add_action( 'wp_ajax_update_shipping_protection', array( $this, 'ajax_update_shipping_protection' ) );
        add_action( 'wp_ajax_nopriv_update_shipping_protection', array( $this, 'ajax_update_shipping_protection' ) );
    }

    public function enqueue_scripts() {
        if ( is_checkout() ) {
            wp_enqueue_style( 'axon-shipping-css', plugins_url( 'assets/css/shipping-protection.css', __FILE__ ) );
            wp_enqueue_script( 'axon-shipping-js', plugins_url( 'assets/js/shipping-protection.js', __FILE__ ), array( 'jquery' ), '1.0', true );

            wp_localize_script( 'axon-shipping-js', 'axon_shipping', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'shipping_protection_nonce' )
            ) );
        }
    }

    // function axon_enqueue_block_assets() {
    //     if ( function_exists( 'is_checkout' ) && is_checkout() ) {
    //         wp_enqueue_script(
    //             'axon-shipping-protection-blocks',
    //             plugins_url( 'assets/js/blocks.js', __FILE__ ),
    //             array( 'wp-element', 'wp-components', 'wp-i18n', 'wc-blocks-checkout' ),
    //             filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/blocks.js' ),
    //             true
    // );

    //         wp_localize_script(
    //             'axon-shipping-protection-blocks',
    //             'axonShippingData',
    //             array(
    //                 'percentage' => get_option( 'axon_shipping_percentage', 5 ),
    // )
    // );

    //     }
    // }

    public function add_admin_menu() {
        add_menu_page(
            'Axon Shipping Protection',
            'Shipping Protection',
            'manage_options',
            'axon-shipping-protection',
            array( $this, 'settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'axon_shipping_group', 'axon_shipping_percentage' );
        register_setting( 'axon_shipping_group', 'axon_shipping_enforced' );
    }

    public function settings_page() {
        $percentage = get_option( 'axon_shipping_percentage', $this->fee_percentage );
        $enforced = get_option( 'axon_shipping_enforced', $this->is_enforced );
        ?>
        <div class = 'wrap'>
        <h1>Axon Shipping Protection Settings</h1>
        <form method = 'post' action = 'options.php'>
        <?php settings_fields( 'axon_shipping_group' );
        ?>
        <?php do_settings_sections( 'axon_shipping_group' );
        ?>
        <table class = 'form-table'>
        <tr>
        <th>Protection Percentage</th>
        <td>
        <input type = 'number' step = '0.1' min = '0' name = 'axon_shipping_percentage'
        value = "<?php echo esc_attr($percentage); ?>">%
        </td>
        </tr>
        <tr>
        <th>Enforce Protection</th>
        <td>
        <input type = 'checkbox' name = 'axon_shipping_enforced' value = '1'
        <?php checked( 1, $enforced );
        ?>>
        <label>Make shipping protection mandatory</label>
        </td>
        </tr>
        </table>
        <?php submit_button();
        ?>
        </form>
        </div>
        <?php
    }

    public function add_shipping_protection_fee( $cart ) {
        if ( is_admin() && !defined( 'DOING_AJAX' ) ) return;

        $percentage = floatval( get_option( 'axon_shipping_percentage', $this->fee_percentage ) );
        $enforced = get_option( 'axon_shipping_enforced', $this->is_enforced );

        $protection_active = WC()->session->get( 'shipping_protection_active', true );

        if ( $enforced || $protection_active ) {
            $fee = ( $cart->cart_contents_total * $percentage ) / 100;
            $cart->add_fee( 'Shipping Protection', $fee, true );
        }
    }

    public function add_shipping_protection_field() {
        $enforced = get_option( 'axon_shipping_enforced', $this->is_enforced );
        if ( !$enforced ) {
            $percentage = get_option( 'axon_shipping_percentage', $this->fee_percentage );
            $protection_active = WC()->session->get( 'shipping_protection_active', true );
            $fee = ( WC()->cart->cart_contents_total * $percentage ) / 100;
            ?>
            <tr class = 'shipping-protection-row'>
            <th colspan = '2'>
            <div class = 'shipping-protection-label'>
            <div class = 'protection-header'>
            <div class = 'image-and-title-wrapper'><img style = 'width:11%;' src = "<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/3.png'); ?>" alt = 'Shield Icon' class = 'protection-icon'>
            <span class = 'protection-title'>Shipping Protection</span></div>
            <div class = 'shipping-protection-toggle-wrapper'>
            <label class = 'shipping-protection-toggle'>
            <input type = 'checkbox' name = 'shipping_protection' id = 'shipping_protection' <?php echo $protection_active ? 'checked' : '';
            ?>>
            <span class = 'toggle-slider'></span>
            </label>
            <span class = 'protection-fee'><?php echo wc_price( $fee );
            ?></span>
            </div>
            </div>
            <span class = 'protection-subtitle'>Against loss, theft, or damage in transit and instant resolution with Route.</span>
            <span class = 'protection-carbon'>
            <img src = "<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/1.png'); ?>" alt = 'Tree Icon' class = 'carbon-icon'>
            100% Carbon Neutral Shipping
            <img src = "<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/2.png'); ?>" alt = 'Route Logo' class = 'route-logo'><span class = 'route-title'>Route</span>
            </span>
            </div>
            </th>
            </tr>
            <?php
        }
    }

    public function add_popup_html() {
        if ( is_checkout() ) {
            ?>
            <div id = 'shipping-protection-popup' class = 'popup-overlay' style = 'display: none;'>
            <div class = 'popup-content'>
            <h3>Warning</h3>
            <p>By declining shipping protection, we are not responsible for any shipping damage to your cargo. Do you accept this?</p>
            <button id = 'accept-decline'>Accept</button>
            <button id = 'cancel-decline'>Cancel</button>
            </div>
            </div>
            <?php
        }
    }

    public function ajax_update_shipping_protection() {
        check_ajax_referer( 'shipping_protection_nonce', 'nonce' );

        $protection_active = isset( $_POST[ 'protection_active' ] ) && $_POST[ 'protection_active' ] === 'true' ? true : false;
        WC()->session->set( 'shipping_protection_active', $protection_active );

        WC()->cart->calculate_totals();

        wp_send_json_success( array(
            'fragments' => apply_filters( 'woocommerce_update_order_review_fragments', array() ),
            'cart_hash' => WC()->cart->get_cart_hash()
        ) );
    }
}

new Axon_Shipping_Protection();