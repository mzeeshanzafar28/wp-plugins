<?php
/*
Plugin Name: Clone Posts Client
Description: Clone Posts plugin allows you to sync posts between websites. This Client plugn is to be installed on the client websites which will receive post updates from the server website.
Author: Axon Technologies
Author URI: https://axontech.pk
License: GPLv2 or later
*/

if ( !defined( 'ABSPATH' ) ) {
    die( 'You are not allowed to be here' );
}
add_action( 'admin_menu', 'clone_post_client_v2_admin_menu_page' );
add_action( 'admin_init', 'clone_post_client_v2_save_replacements' );
add_action( 'rest_api_init', 'clone_post_client_v2_register_post_endpoint' );

function clone_post_client_v2_admin_menu_page() {
    add_menu_page( 'Clone Post Client', 'Clone Post Client', 'manage_options', 'clone-post-client', 'clone_post_client_v2_settings_page' );
}

function clone_post_client_v2_settings_page() {
    $replacements = get_option( 'custom-meta-box-modified-content' );
    $replacements = str_replace( '\\', '', $replacements );

    ?>
    <form method = 'post' action = ''>
    <center><h1>Manage Clone Post Replacements</h1></center>
    <div style = 'display: flex; flex-direction: column; gap: 8px; padding: 10px;'>
    <div style = 'margin-left:-19px;'>
    <label for = 'custom-meta-box-modified-content'>Replacements:</label>
    <textarea id = 'custom-meta-box-modified-content' name = 'custom-meta-box-modified-content'><?php echo $replacements;
    ?></textarea>
    <?php submit_button() ?>
    <p style = 'color:red; font-size:12px; margin-top:10px;'>Enter Replacements in JSON Format to replace in the Post</p>
    <p style = 'color:red; font-size:12px; margin-top:10px;'>Replacements are case sensitive</p>
    </div>
    </div>
    </form>
    <?php
}

function clone_post_client_v2_save_replacements() {
    if ( isset( $_POST[ 'custom-meta-box-modified-content' ] ) ) {
        update_option( 'custom-meta-box-modified-content', strval( $_POST[ 'custom-meta-box-modified-content' ] ) );
        echo
        '<div class="updated">
    <p>' . __( 'Success! Values have been saved.' ) . '</p>
  </div>';
    }
}

function clone_post_client_v2_register_post_endpoint() {
    register_rest_route( 'clone-post/v1', '/post', array(
        'methods' => 'POST',
        'callback' => 'clone_post_client_handle_post_request',
        'permission_callback' => function () {
            return true;
        }
        ,
    ) );
}

function clone_post_client_handle_post_request( $request ) {

    $data = $request->get_json_params();
    if ( empty( $data[ 'post_title' ] ) || empty( $data[ 'post_content' ] ) || empty( $data[ 'validation' ] ) || empty( $data[ 'parent_post_id' ] ) ) {
        return new WP_Error( 'invalid_data', 'Invalid post data', array( 'status' => 400 ) );
    }

    $parent_post_id = intval( sanitize_text_field( $data[ 'parent_post_id' ] ) );
    $post_title = sanitize_text_field( $data[ 'post_title' ] );
    $post_content = wp_kses_post( $data[ 'post_content' ] );
    $post_categories = $data['post_categories'];
    $request_time = sanitize_text_field( $data[ 'validation' ] );
    $post_featured_image_url = $data['post_featured_image_url'];
    $post_featured_image_size = $data['post_featured_image_size'];
    $server_post_slug = $data['post_slug'];


    $current_minutes = md5( 'md_validate' . date( 'i' ) );
    $one_min_back = md5( 'md_validate' . date( 'i', time() - 60 ) );

    $replacements = get_option( 'custom-meta-box-modified-content' );
    $replacements = str_replace( '\\', '', $replacements );
    $replacements = json_decode( $replacements, true );

    foreach ( $replacements as $find => $replace ) {
        $post_title = str_replace( $find, $replace, $post_title );
        $post_content = str_replace( $find, $replace, $post_content );
    }
    
        // file_put_contents(plugin_dir_path(__FILE__).'/zee_logs.txt', "I AM HERE." );

    if ( $request_time === $current_minutes || $request_time === $one_min_back ) {
        $post_id = null;

        $query = new WP_Query( array(
            'meta_key' => 'parent_post_id',
            'meta_value' => $parent_post_id,
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => 1,
        ) );

        if ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
        }
        wp_reset_postdata();

        if ( $post_id ) {
            wp_update_post( array(
                'ID' => $post_id,
                'post_title' => $post_title,
                'post_content' => $post_content,
            ) );
        } else {
			$args = array(
			  'name' => $server_post_slug,
			  'posts_per_page' => 1,
			  'post_type'      => 'post',
			  'post_status'    => 'publish'
			);
			$updated = false;
			$my_post = get_posts($args);
			if (!empty($my_post)){
				$post_id = wp_update_post( array(
                        'ID' => is_array($my_post) ? $my_post[0]->ID : $my_post->ID,
                        'post_title' => $post_title,
                        'post_content' => $post_content,
                    ) );
				$updated = true;
			}
            
            if (!$updated){
                $post_id = wp_insert_post( array(
                    'post_title' => $post_title,
                    'post_content' => $post_content,
                    'post_status' => 'publish',
                ) );
            }
        }


        add_post_meta($post_id, 'parent_post_id', $parent_post_id, true);
        wp_set_object_terms($post_id, $post_categories, 'category');

        $old_featured_image_id = get_post_thumbnail_id($post_id);
        $old_image_size = filesize(get_attached_file($old_featured_image_id));

        if (intval($old_image_size) !== intval($post_featured_image_size)) {
            if (!empty($post_featured_image_size)){
                $image_id = clone_post_client_upload_featured_image($post_featured_image_url);
                set_post_thumbnail($post_id, $image_id);
            }
        }
        if (!$post_featured_image_size){
            delete_post_thumbnail($post_id);
        }


        if ( is_wp_error( $post_id ) ) {
            return new WP_Error( 'post_creation_failed', 'Failed to create/update post', array( 'status' => 500 ) );
        }

        $post_meta = $data[ 'post_meta' ];

        foreach ( $post_meta as $meta_key => $meta_value ) {
            update_post_meta( $post_id, $meta_key, $meta_value );
        }

        $response = array(
            'post_id' => $post_id,
        );

        return $response;
    }

    return new WP_Error( 'request_timeout', 'Request has expired', array( 'status' => 408 ) );
}

function clone_post_client_upload_featured_image( $image_url ) {
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents( $image_url );
    $filename = basename( $image_url );
    if ( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }
    file_put_contents( $file, $image_data );

    $wp_filetype = wp_check_filetype( $filename, null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name( $filename ),
        'post_content' => '',
        'post_status' => 'inherit',
    );

    $attachment_id = wp_insert_attachment( $attachment, $file );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $attachment_data = wp_generate_attachment_metadata( $attachment_id, $file );
    wp_update_attachment_metadata( $attachment_id, $attachment_data );

    return $attachment_id;
}
