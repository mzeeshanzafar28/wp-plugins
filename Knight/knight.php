<?php
/*
Plugin Name:Knight
Plugin URI:https://github.com/mzeeshanzafar28
Author:Muhammad Zeeshan Zafar
Author URI:https://www.linkedin.com/in/m-zeeshan-zafar-9205a1248/
Description:Knight will be the very first functional plugin built by M Zeeshan Zafar @ AxonTech. Not too much but the plugin will do a very little by enabling dark mode on the user site.
Version:1.0.0
*/
register_activation_hook(__FILE__,'my_activation_action');
register_deactivation_hook( __FILE__, 'my_deactivation_action' );
register_uninstall_hook( __FILE__, 'my_uninstall_action' );

/*function my_activation_action()
{
    global $wpdb,$table_prefix;
    $wp_users = $table_prefix . "users";
    $q = "CREATE TABLE `$wp_users` (`id` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(50) NOT NULL , `email` VARCHAR(150) NOT NULL , `password` VARCHAR(150) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;";
    $wpdb->query($q);

}
*/

function my_activation_action()
{

}
function my_deactivation_action()
{

}
function my_uninstall_action()
{

}

add_action('wp_body_open','get_btn');
function get_btn()
{
    include_once ('button.php');
    // echo "<h1>Here</h1>";
     my_btn();
}
add_action('admin_menu','custom_Function1');
add_action('admin_enqueue_scripts', 'custom_Function3');
function custom_Function1() //function to wrap plugins UI and Appearances 
{
    add_menu_page(
        'knight 1.0',
        'knight',
        'manage_options',
        'knight',
        'custom_Function2',
        plugins_url('/assets/icons/lion.ico',__FILE__)

    );
}
function custom_Function2() //function to wrap design | manual include
{
    include_once('manual.php');
}
function custom_Function3() //funtion to wrap css include for icon
{ 
    wp_enqueue_style( 'stylesheet', plugins_url('/assets/css/style.css',__FILE__) );
}
?>