<?php
/*
Plugin Name: WhatsMate
Author: M Zeeshan Zafar
Description: WhatsMate is a plugin that allows you to  chat in WhatsApp with your client directly from the Orders page.
Author URI: https://www.linkedin.com/in/m-zeeshan-zafar-9205a1248/
Version: 1.0.0
License: GPLv2 or Later
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

add_action( 'admin_init', 'whatsmate_register_settings' );
add_action( 'admin_menu', 'menu_page' );

function menu_page()
 {
    add_menu_page( 'WhatsMate', 'WhatsMate', 'manage_options', 'WhatsMate', 'whatsmate_settings' );
}

function whatsmate_settings()
 {
    echo '<style>body {background-image : url("https://images.unsplash.com/photo-1496181133206-80ce9b88a853?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1171&q=80"); background-position: center;
        background-repeat: no-repeat;
        background-size: cover}</style>';
    echo '<h1 style="display:flex; justify-content:center; align-items: center;">WhatsMate Settings</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields( 'whatsmate_settings_group' );
    do_settings_sections( 'whatsmate_settings_group' );

    $msg = get_option( 'whatsmate_message' );
    $customer_name = '$customer_name';
    $order_id = '$order_id';

    echo '<p style="color:red; font-weight : bold;"><strong style="color:black;">Note: </strong>Use <strong>$customer_name</strong> and <strong>$order_id</strong> for customer name and order id.<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Refresh the Orders Page after making changes here.</p>';
    echo '<p></p>';

    submit_button();
    echo '</form>';
}

function add_whatsapp_chat_column_header( $columns )
 {
    $columns[ 'whatsapp_chat' ] = 'WhatsApp Chat';
    return $columns;
}
add_filter( 'manage_edit-shop_order_columns', 'add_whatsapp_chat_column_header' );

function add_whatsapp_chat_column_data( $column, $post_id )
 {
    if ( $column === 'whatsapp_chat' ) {
        $order = wc_get_order( $post_id );
        $customer_phone = $order->get_billing_phone();

        if ( !empty( $customer_phone ) ) {
            $phone_number = preg_replace( '/[^0-9]/', '', $customer_phone );

            if ( $phone_number[ 0 ] == '0' ) {
                $formatted_phone = '+92' . ltrim( $phone_number, '0' );
            } else {
                $formatted_phone = $phone_number;
            }

            $message = get_option( 'whatsmate_message' );
            $customer_name = $order->get_billing_first_name();
            $order_id = $order->get_id();

            $message = str_replace( '$customer_name', $customer_name, $message );
            $message = str_replace( '$order_id', $order_id, $message );
            $message = urlencode( $message );

            $whatsapp_url = "https://web.whatsapp.com/send/?phone={$formatted_phone}&text={$message}";

            echo '<a href="' . esc_url( $whatsapp_url ) . '" target="_blank" id="whatsapp-link">Chat on WhatsApp</a>';

        } else {
            echo 'N/A';
        }
    }
}
add_action( 'manage_shop_order_posts_custom_column', 'add_whatsapp_chat_column_data', 10, 2 );

// Register a settings section and field for the custom message

function whatsmate_register_settings()
 {
    add_settings_section(
        'whatsmate_settings_section',
        'Configure the default WhatsApp message',
        '',
        'whatsmate_settings_group'
    );

    add_settings_field(
        'whatsmate_message',
        'WhatsApp Message',
        'whatsmate_message_callback',
        'whatsmate_settings_group',
        'whatsmate_settings_section'
    );

    register_setting( 'whatsmate_settings_group', 'whatsmate_message', 'sanitize_text_field' );
    // Add sanitize callback
    if ( false === get_option( 'whatsmate_message' ) ) {
        update_option( 'whatsmate_message', 'Hello $customer_name, hope you are doing great, order with id $order_id has been placed successfully and is being processed. Thank you for choosing us.' );
    }
}

 
function whatsmate_message_callback()
 {
    $msg = get_option( 'whatsmate_message' );
    echo '<br><br><textarea style="margin-left:-200px;" name="whatsmate_message" rows="5" cols="50">' . esc_textarea( $msg ) . '</textarea>';
}
