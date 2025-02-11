<?php
/*
Plugin Name: Maxxess Assistant
Description: An efficient plugin to create CPTs, Taxonomies, insert and handle data.
Version: 1.0
Author: Axon Tech
License: GPL2
*/
if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}
require_once plugin_dir_path(__FILE__) . 'assistant-admin.php';
require_once plugin_dir_path(__FILE__) . 'assistant-user.php';
class Assistant
{
    public function __construct()
    {
        //Enqueue The css and JS files for custom styling and scripts for admin 
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        //Enqueue The JS files for custom scripts for user
        add_action('wp_enqueue_scripts', array($this, 'enqueue_script'));

        //Creating Instance of Admin And user Class
        $assisant_admin = new AssistantAdmin();
        $assisant_user = new AssistantUser();
    }
    function enqueue_scripts($hook)
    {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('assistant-scripts', plugin_dir_url(__FILE__) . 'assets/js/scripts.js', array('jquery'), '1.0', true);
        }
    }
    function enqueue_styles()
    {
        wp_enqueue_style('assistant-custom-css', plugin_dir_url(__FILE__) . 'assets/style/style.css');
    }
    public function enqueue_script()
    {
        wp_enqueue_script('assistant-scripts', plugin_dir_url(__FILE__) . 'assets/js/user-script.js', array('jquery'), '1.0', true);
        //localize Ajax url  for user 
        wp_localize_script('assistant-scripts', 'searchman_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }
}
$assisant = new Assistant();