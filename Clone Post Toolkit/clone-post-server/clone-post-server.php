<?php
/*
Plugin Name: Clone Posts Server
Description: Clone Posts plugin allows you to sync posts between websites. This Server plugn is to be installed on the main website which will send post updates to the client websites.
Author: Axon Technologies
Author URI: https://axontech.pk
Version: 1.0.0
License: GPLv2 or later
*/

if ( !defined( 'ABSPATH' ) ) {
    die( 'Unauthorized Access' );
}

add_action( 'admin_enqueue_scripts', 'clone_post_server_enqueue_scripts' );

function clone_post_server_enqueue_scripts() {
    if (isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'clone_post_server' ){
    wp_enqueue_style( 'bootstrap-files', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' );
    wp_enqueue_script( 'popper', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js', array( 'jquery' ) );
    wp_enqueue_script( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js', array( 'jquery', 'popper' ) );
    wp_enqueue_script( 'bootstrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array( 'jquery', 'popper', 'bootstrap' ) );
    }
    wp_enqueue_script( 'pooster-script', plugins_url( '/assets/js/clone-post-server.js', __FILE__ ) );
    wp_enqueue_script( 'jquery', 'https://code.jquery.com/jquery-3.5.1.min.js' );
}

add_action( 'admin_menu', 'clone_post_server_menu' );

function clone_post_server_menu() {
    add_menu_page( 'clone_post_server', 'Clone Post Server', 'manage_options', 'clone_post_server', 'clone_post_server_admin_page', 'dashicons-admin-site', 69 );
}

function clone_post_server_admin_page()
 {

    $els = get_option( 'stored_urls', array () );
    if ( empty( $els ) )
 {

        echo '
        <center><h1>Manage Clone Post Client Sites</h1></center>
        <br>
        <form method="POST">

        <div class="form-group" id="wrapper">

        <div>
        <label>Site URL</label> <input type="text" name="inp[]" style="width:25%;" placeholder="Enter Site URL">
        <button class="btn btn-info rounded-circle" name="plus[]" style="font-size:14px"><strong>+</strong></button>
        </div>

        </div>

            <br>
            <button name="submit-btn" type="submit" class="btn btn-primary">Save</button>
        </form>
        ';
    } else {
        echo '<center><h1>Manage Clone Post Client Sites</h1></center>
            <br>
            <form method="POST">
    
            <div class="form-group" id="wrapper">
    ';

        $first_el = true;
        foreach ( $els as $el ) {
            if ( $first_el ) {
                echo '<div>
                    <label>Site URL</label> <input type="text" style="width:25%;" name="inp[]" value="' . esc_js( $el ) . '">
                    <button class="btn btn-info rounded-circle" name="plus[]" style="font-size:14px"><strong>+</strong></button>
                    </div>';
                $first_el = false;
                continue;
            }
            echo '<div>
                <label>Site URL</label>
                <input type="text" name="inp[]" style="width:25%;" value="' . esc_js( $el ) . '">
                <button class="btn btn-info rounded-circle" name="plus[]" style="font-size:14px"><strong>+</strong></button>
                <button class="btn btn-secondary rounded-circle" name="minus[]" style="font-size:14px"><strong>-</strong></button>
            </div>';

        }

        echo '</div>

            <br>
            <button name="submit-btn" type="submit" class="btn btn-primary">Save</button>
        </form>';
    }
}

add_action( 'init', 'clone_post_server_submit_data' );

function clone_post_server_submit_data()
 {
    $success = false;
    if ( isset( $_POST[ 'submit-btn' ] ) ) {
        $urls = $_POST[ 'inp' ];

        $sanitized_urls = array_map( 'sanitize_text_field', $urls );
        update_option( 'stored_urls', $sanitized_urls );
        $success = true;
        if ( $success == true ) {
            echo '<script>alert("Sites Successfully Saved")</script>';
        }
    }
}

function clone_post_server_add_custom_meta_box() {
    add_meta_box( 'custom-meta-box', 'Modify Post for Other Sites', 'clone_post_server_render_custom_meta_box', 'post', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'clone_post_server_add_custom_meta_box' );

function clone_post_server_render_custom_meta_box( $post ) {
    $post_id = $post->ID;
    $replacements = get_post_meta( $post_id, 'custom-meta-box-modified-content', true );
    ?>
    <div style = 'display: flex; flex-direction: column; gap: 8px; padding: 10px;'>
    <div style = 'margin-left:-19px;'>
    <label for = 'custom-meta-box-modified-content'>Replacements:</label>
    <textarea id = 'custom-meta-box-modified-content' name = 'custom-meta-box-modified-content'><?php echo $replacements;
    ?></textarea>
    <p style = 'color:red; font-size:12px; margin-top:10px;'>Enter Replacements in JSON Format to replace in the Post</p>
    <p style = 'color:red; font-size:12px; margin-top:10px;'>Replacements are case sensitive</p>
    </div>
    </div>
    <?php
}

add_action( 'save_post', 'clone_post_server_schedule_modified_data_cron_job', 999 );

function clone_post_server_schedule_modified_data_cron_job( $post_id ) {
    if ( get_post_type( $post_id ) != 'post' )
 {
        return;
    }
    if ( isset( $_POST[ 'custom-meta-box-modified-content' ] ) ) {
        $meta_value =  $_POST[ 'custom-meta-box-modified-content' ] ;
        set_transient( 'replacements', $meta_value, 60 * 60 * 24 );
        update_post_meta( $post_id, 'custom-meta-box-modified-content', $meta_value );
        $meta_value =  get_post_meta( $post_id, 'custom-meta-box-modified-content', true );
        $saved_value = get_post_meta( $post_id, 'custom-meta-box-modified-content', true );
        error_log( 'Saved Meta Value: ' . $saved_value );
    }

    if ( !wp_next_scheduled( 'send_modified_post_data_cron_job' ) ) {
        wp_schedule_single_event( time(), 'send_modified_post_data_cron_job', array( $post_id ) );
    }
}

add_action( 'send_modified_post_data_cron_job', 'clone_post_server_send_modified_post_data', 10 );

function clone_post_server_send_modified_post_data( $post_id ) {
    if ( get_post_type( $post_id ) != 'post' )
 {
        return;
    }
    $post = get_post( $post_id );
    $post_id = $post->ID;
    $post_title = $post->post_title;
    $post_content = $post->post_content;
    $post_categories_ids = wp_get_post_categories($post_id); 
	$post_categories = array(); 
	foreach ($post_categories_ids as $category_id) {
		$category = get_category($category_id); 
		$post_categories[] = $category->name; 
	}

	$post_featured_image_url = wp_get_attachment_image_url(get_post_thumbnail_id($post_id), 'full');
	$post_featured_image_size = filesize(get_attached_file(get_post_thumbnail_id($post_id)));
	$values = compact('post_title', 'post_content', 'post_categories', 'post_featured_image_url', 'post_featured_image_size');


    $replacements = get_transient( 'replacements' );
    $replacements = str_replace( '\\', '', $replacements );
    $replacements = json_decode( $replacements, true );

    foreach ( $replacements as $find => $replace ) {
        $find = preg_quote( $find, '/' );
        $replace = preg_quote( $replace, '/' );
        $values[ 'post_title' ] = preg_replace( '/\b' . $find . '\b/', $replace, $values[ 'post_title' ] );
        $values[ 'post_content' ] = preg_replace( '/\b' . $find . '\b/', $replace, $values[ 'post_content' ] );
    }

    $stored_urls = get_option( 'stored_urls', array() );
    foreach ( $stored_urls as $url )
 {
        $site_url     = $url.'/wp-json/clone-post/v1/post';
        $minutes      = date( 'i', time() );
        $minutes  = md5( 'md_validate' . $minutes );
        $values[ 'validation' ] = $minutes;
        $values[ 'parent_post_id' ] = $post_id;
        $post_meta = get_post_meta( $post_id );
        $filtered_meta = array_filter( $post_meta, function ( $meta_key ) {
            return strpos( $meta_key, '_' ) !== 0;
        }
        , ARRAY_FILTER_USE_KEY );

        $values[ 'post_meta' ] = $filtered_meta;
        $values['post_slug'] = $post->post_name;

        $response = wp_remote_post( $site_url, array(
            'body'        => json_encode( $values ),
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => [
                'content-type' => 'application/json',
            ]
        ) );
        
                    file_put_contents(plugin_dir_path(__FILE__).'/zee_logs.txt', $url.': '.print_r($response, true)."\n\r", FILE_APPEND);

        if ( !is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {

            echo '<script>alert("Post Sent Successfully");</script>';
        } else {
            $error_message = is_wp_error( $response ) ? $response->get_error_message() : 'An error occurred during the request.';
            echo '<script>alert("' . esc_js( $error_message ) . '");</script>';
        }
    }

}