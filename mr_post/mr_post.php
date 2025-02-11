<?php
/*
Plugin Name: Mr Post
Description: Handles single as well as bulk posts received on the API endpoints and provides authority over their management.
Author: M Zeeshan Zafar
Author URI: https://www.linkedin.com/in/m-zeeshan-zafar-9205a1248/
License: GPLv2 or later
*/

defined('ABSPATH') or die('No script kiddies please!');

Class MrPost {

       

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'handle_key']);
        add_action('admin_menu', [$this, 'render_page']);
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
    }

    function handle_key() {
        $web_key = get_option('mr_post_web_key');
        if (empty($web_key)) {
            $web_key = wp_generate_password(32, false);
            update_option('mr_post_web_key', $web_key);
             }
    }

    function render_page() {
        add_menu_page('Mr Post', 'Mr Post', 'manage_options', 'mr_post', [$this, 'render_page_content']);
    }

    function render_page_content() {

        $single_post_status = get_option('mr_post_single_post_status', 'draft');
        $single_post_category = get_option('mr_post_single_post_category', 'uncategorized');
        $bulk_posts_status = get_option('mr_post_bulk_posts_status', 'draft');
        $bulk_posts_category = get_option('mr_post_bulk_posts_category', 'uncategorized');
        $web_key = get_option('mr_post_web_key', '');
        
        echo '<style>body {background-repeat:no-repeat; background-size:cover; background-image: url("https://images.unsplash.com/photo-1594636797501-ef436e157819?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=870&q=80"); }</style>';
        echo '<h1 style="text-align: center; font-size: 35px;">Mr Post</h1>';
        echo '<br><br>';
        echo '<form method="post" action="">'; 
        echo '<label>Web Key</label> &nbsp;&nbsp;';
        echo '<input type="text" style="width: 22%;" readonly name="web_key" value="' . esc_attr($web_key) . '">'; 
    
        echo '<h2>Single Post Settings</h2>';
        echo '<label>Status</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        echo '<select name="single_post_status">';
        echo '<option value="publish" ' . selected($single_post_status, 'publish', false) . '>Publish</option>';
        echo '<option value="draft" ' . selected($single_post_status, 'draft', false) . '>Draft</option>';
        echo '</select><br><br>';

        echo '<label>Category</label>&nbsp;&nbsp;&nbsp;';
        echo '<select name="single_post_category">';
        $categories = get_categories(array('hide_empty' => false));
	
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->slug) . '" ' . selected($single_post_category, $category->slug, false) . '>' . esc_html($category->name) . '</option>';
        }
        echo '</select>';
    
        echo '<h2>Bulk Posts Settings</h2>';
        echo '<label>Status</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        echo '<select name="bulk_posts_status">';
        echo '<option value="publish" ' . selected($bulk_posts_status, 'publish', false) . '>Publish</option>';
        echo '<option value="draft" ' . selected($bulk_posts_status, 'draft', false) . '>Draft</option>';
        
        echo '</select><br><br>';
    
        echo '<label>Category</label>&nbsp;&nbsp;&nbsp;';
        echo '<select name="bulk_posts_category">';
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->slug) . '" ' . selected($bulk_posts_category, $category->slug, false) . '>' . esc_html($category->name) . '</option>';
        }
        echo '</select>';
    
        echo '<br><br>';
    
        wp_nonce_field('mr_post_settings', 'mr_post_nonce'); 
    
        if (isset($_POST['web_key']) && isset($_POST['mr_post_nonce']) && wp_verify_nonce($_POST['mr_post_nonce'], 'mr_post_settings')) {
            update_option('mr_post_single_post_status', sanitize_text_field($_POST['single_post_status']));
            update_option('mr_post_single_post_category', sanitize_text_field($_POST['single_post_category']));
            update_option('mr_post_bulk_posts_status', sanitize_text_field($_POST['bulk_posts_status']));
            update_option('mr_post_bulk_posts_category', sanitize_text_field($_POST['bulk_posts_category']));
    
            echo '<div class="updated"><p><strong>Settings saved!</strong></p></div>';
	echo '<script>';
        echo 'setTimeout(function() { window.location.reload(); }, 200);';
        echo '</script>';
        }
    
        echo '<input type="submit" class="button-primary" value="Save Changes">';
        echo '</form>';
    }
    

    function register_api_endpoints() {

        register_rest_route('mr-post/v1', '/single-post', array(
            'methods' => 'POST',
            'callback' => [$this, 'handle_single_post'],
        ));
        register_rest_route('mr-post/v1', '/bulk-posts', array(
            'methods' => 'POST',
            'callback' => [$this, 'handle_bulk_posts'],
        ));
    }

    function handle_single_post($request) {
	
	$data = $request->get_json_params();
	$web_key_param = $data['web_key'];
        $web_key = get_option('mr_post_web_key');

        if (!$web_key_param || $web_key_param !== $web_key) {
            return new WP_Error('You are not allowed to be here', 'Unauthorized', array('status' => 401));
        }

        $title = $data['title'];
        $content = $data['content'];
        $status = get_option('mr_post_single_post_status', 'draft');
        $category = get_option('mr_post_single_post_category', 'uncategorized');

        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $status,
            'post_category' => array(get_cat_ID($category)),
        ));

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        return 'Single post added successfully!';
    }



   function handle_bulk_posts($request) {
    $data = $request->get_json_params();
    $web_key_param = $data['web_key'];
    $web_key = get_option('mr_post_web_key');

    if (!$web_key_param || $web_key_param !== $web_key) {
        return new WP_Error('You are not allowed to be here', 'Unauthorized', array('status' => 401));
    }

    $time_interval = isset($data['time_interval']) ? intval($data['time_interval']) * 60 : 0;
    $posts = $data['posts'];
    $status = get_option('mr_post_bulk_posts_status', 'draft');
    $category = get_option('mr_post_bulk_posts_category', 'uncategorized');

    foreach ($posts as $index => $post) {
        if ($status === 'publish' && $time_interval > 0) {
            $scheduled_time = time() + ($index + 1) * $time_interval;
            $date = date('Y-m-d H:i:s', $scheduled_time);
            $post_status = 'future';
            $post_date = $date;
        } else {
            $post_status = ($status === 'publish') ? 'publish' : 'draft';
            $post_date = '';
        }

        $post_id = wp_insert_post(array(
            'post_title' => $post['title'],
            'post_content' => $post['content'],
            'post_status' => $post_status,
            'post_date' => $post_date,
            'post_category' => array(get_cat_ID($category)),
        ));
    }

    return 'Bulk posts added successfully!';
}

 

}

new MrPost();
