<?php
/*
Plugin Name: Contiant Payment Gateway
Plugin URI: https://contiant.com
Description: Integrates Contiant Payment Gateway with WooCommerce.
Version: 1.1
Author: Axon Technologies
Author URI: https://axontech.pk
License: MIT
Text Domain: contiant-payment-gateway
*/

if ( !defined( 'ABSPATH' ) ) {
    exit;

}

if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-contiant-api.php';

function init_contiant_payment_gateway() {
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    require_once plugin_dir_path( __FILE__ ) . 'includes/class-contiant-gateway.php';

    function add_contiant_gateway( $methods ) {
        $methods[] = 'WC_Contiant_Gateway';
        return $methods;
    }
    add_filter( 'woocommerce_payment_gateways', 'add_contiant_gateway' );
    new WC_Contiant_Gateway();
}
add_action( 'woocommerce_loaded', 'init_contiant_payment_gateway', 0 );