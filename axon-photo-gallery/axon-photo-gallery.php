<?php
/**
 * Plugin Name: Axon Photo Gallery
 * Description: Plugin to create Photo Galleries for clients, Easy Digital downloading, Integrated with woocommerce for gallery Products [my_photo_galleries].
 * Version: 2.1.2
 * Author: Axon Technologies
 * Author URI: https://axontech.pk
 * License: GPL2
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

define('AXON_PLUGIN_VERSION', '1.0.0');
define('AXON_SCRIPTS_VERSION', '1.0.0.2');
define('AXON_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AXON_PLUGIN_URL', plugin_dir_url(__FILE__));

function axon_plugin_activate() {
    add_option('axon_plugin_activated', true);
}
register_activation_hook(__FILE__, 'axon_plugin_activate');

function axon_plugin_deactivate() {
    delete_option('axon_plugin_activated');
}
register_deactivation_hook(__FILE__, 'axon_plugin_deactivate');

function create_photo_gallery_cpt() {
    $labels = array(
        'name'               => 'Photo Galleries',
        'singular_name'      => 'Photo Gallery',
        'menu_name'          => 'Photo Galleries',
        'name_admin_bar'     => 'Photo Gallery',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Photo Gallery',
        'new_item'           => 'New Photo Gallery',
        'edit_item'          => 'Edit Photo Gallery',
        'view_item'          => 'View Photo Gallery',
        'all_items'          => 'All Photo Galleries',
        'search_items'       => 'Search Photo Galleries',
        'parent_item_colon'  => 'Parent Photo Galleries:',
        'not_found'          => 'No photo galleries found.',
        'not_found_in_trash' => 'No photo galleries found in Trash.',
        'featured_image'     => 'Featured Image',
        'set_featured_image' => 'Set featured image',
        'remove_featured_image' => 'Remove featured image',
        'use_featured_image' => 'Use as featured image',
        'archives'           => 'Photo Gallery Archives',
        'insert_into_item'   => 'Insert into photo gallery',
        'uploaded_to_this_item' => 'Uploaded to this photo gallery',
        'filter_items_list'  => 'Filter photo galleries list',
        'items_list'         => 'Photo galleries list',
        'items_list_navigation' => 'Photo galleries list navigation',
    );
  
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'photo-gallery' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'supports'           => array('title', 'thumbnail'),
        'menu_icon'          => 'dashicons-format-gallery',
    );

    register_post_type( 'photo_gallery', $args );
}

add_action( 'init', 'create_photo_gallery_cpt' );


function axon_enqueue_admin_scripts($hook) {
    wp_enqueue_style('gallery-style', plugin_dir_url(__FILE__) . '/assets/css/gallery-style.css');
    if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], null, true);
        wp_enqueue_style('select2-style', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
}
add_action('admin_enqueue_scripts', 'axon_enqueue_admin_scripts');
function my_plugin_enqueue_styles() {
    
    wp_enqueue_style('gallery-style', plugin_dir_url(__FILE__) . '/assets/css/gallery-style.css',[],AXON_SCRIPTS_VERSION);
    

    //by zeeshan

    wp_enqueue_script(
        'tui-color-picker',
        'https://uicdn.toast.com/tui-color-picker/latest/tui-color-picker.min.js',
        array(),
        null,
        true
    );

    wp_enqueue_script(
        'tui-image-editor-bundle',
        'https://uicdn.toast.com/tui-image-editor/latest/tui-image-editor.js',
        array(),
        null,
        true
    );
    
    wp_enqueue_style(
        'tui-image-editor-style',
        'https://uicdn.toast.com/tui-image-editor/latest/tui-image-editor.min.css'
    );

    // wp_enqueue_style(
    //     'bootstrap-css',
    //     'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    //     array(),
    //     '5.3.0'
    // );
    
    
    // wp_enqueue_script('axon-gallery-script', plugin_dir_url(__FILE__) . 'dist/gallery-bundle.js', array(), '1.0.0', true);

}

// Hook into both front-end and admin hooks
add_action( 'wp_enqueue_scripts', 'my_plugin_enqueue_styles' );

function axon_allowed_users_metabox_callback($post) {
    $allowed_users = get_post_meta($post->ID, 'axon_allowed_users', true) ?: [];
    
    wp_nonce_field('axon_allowed_users_nonce', 'axon_allowed_users_nonce');

    $users = get_users(['fields' => ['ID', 'display_name', 'user_email']]);
    echo '<div id="axon_allowed_users_wrapper">';
    echo '<select id="axon_allowed_users" name="axon_allowed_users[]" multiple>';
    foreach ($users as $user) {
        $selected = in_array($user->ID, $allowed_users) ? 'selected' : '';
        echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
    }
    echo '</select></div><div>';
    echo '</div>';

    echo '<script>
            jQuery(document).ready(function($) {
                $("#axon_allowed_users_wrapper #axon_allowed_users").select2({ 
                    tags: true, 
                    tokenSeparators: [","] ,
                    createTag: function(params) {
                        var emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com)$/;
                        var term = $.trim(params.term);
                        if (term && emailPattern.test(term)) {
                            return { id: term, text: term };
                        }
                        return null;
                    }
                });
            });
          </script>';
}
function axon_gallery_metabox_callback($post) {
    $gallery_images = get_post_meta($post->ID, 'axon_gallery_images', true) ?: [];
    wp_nonce_field('axon_gallery_nonce', 'axon_gallery_nonce');
    echo '<div id="axon_gallery_images_wrapper" class="axon-gallery-container">';
    if (!empty($gallery_images)) {
        foreach ($gallery_images as $image_id) {
            $image_url = wp_get_attachment_url($image_id);
            echo '<div class="axon-gallery-item" data-id="' . esc_attr($image_id) . '">
                    <div class="axon-gallery-image-container">
                        <img src="' . esc_url($image_url) . '" width="100" height="100" alt="Gallery Image" class="axon-gallery-image" />
                    </div>
                    <div class="image-footer">
                        <p class="axon-image-id">ID: ' . esc_html($image_id) . '</p>
                        <input type="hidden" name="axon_gallery_images[]" value="' . esc_attr($image_id) . '" />
                        <button class="button del-btn remove-gallery-image">&#128465;</button>
                    </div>
                  </div>';
        }
    }
    echo '</div>';
    echo '<button class="button" id="add-gallery-image">Add Images</button>';
    echo '<script>
            jQuery(document).ready(function($) {
                $(document).on("click", "#add-gallery-image", function(e) {
                    e.preventDefault();
                    var frame = wp.media({
                        title: "Select or Upload Images",
                        button: {
                            text: "Use these images"
                        },
                        multiple: true // Enable multiple image selection
                    });
                    frame.open();
                    frame.on("select", function() {
                        var attachments = frame.state().get("selection").toJSON();
                        attachments.forEach(function(attachment) {
                            $("#axon_gallery_images_wrapper").append(`<div class="axon-gallery-item" data-id="` + attachment.id + `">
                                <div class="axon-gallery-image-container">
                                    <img src="` + attachment.url + `" width="100" height="100" alt="Gallery Image" class="axon-gallery-image" />
                                </div>
                                <div class="image-footer">
                                    <p class="axon-image-id">ID: ` + attachment.id + `</p>
                                    <input type="hidden" name="axon_gallery_images[]" value="` + attachment.id + `" />
                                    <button class="button del-btn remove-gallery-image">&#128465;</button>
                                </div>
                            </div>`);
                        });
                    });
                });

                $(document).on("click", ".remove-gallery-image", function(e) {
                    e.preventDefault();
                    $(this).closest(".axon-gallery-item").remove();
                });
            });
          </script>';
}



// Save Meta Box Data
function axon_save_gallery_meta($post_id) {
    if (!isset($_POST['axon_gallery_nonce']) || !wp_verify_nonce($_POST['axon_gallery_nonce'], 'axon_gallery_nonce')) return;
    if (!isset($_POST['axon_allowed_users_nonce']) || !wp_verify_nonce($_POST['axon_allowed_users_nonce'], 'axon_allowed_users_nonce')) return;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['axon_gallery_images'])) {
        update_post_meta($post_id, 'axon_gallery_images', array_map('intval', $_POST['axon_gallery_images']));
    } else {
        delete_post_meta($post_id, 'axon_gallery_images');
    }
    if (isset($_POST['axon_allowed_users'])) {
        $allowed_users = $_POST['axon_allowed_users'];
        $user_ids = [];

        foreach ($allowed_users as $user) {
            if (is_numeric($user) && intval($user) > 0) {
                $user_ids[] = intval($user);
            }
            elseif (filter_var($user, FILTER_VALIDATE_EMAIL)) {
                $existing_user = get_user_by('email', $user);
                
                if ($existing_user) {
                    $user_ids[] = $existing_user->ID;
                } else {
                    $new_user_id = wp_create_user($user, wp_generate_password(), $user);
                    if (!is_wp_error($new_user_id)) {
                        $user_ids[] = $new_user_id;
                    }
                }
            }
        }
        if (!empty($user_ids)) {
            update_post_meta($post_id, 'axon_allowed_users', $user_ids);
        } else {
            delete_post_meta($post_id, 'axon_allowed_users');
        }
    } else {
        delete_post_meta($post_id, 'axon_allowed_users');
    }
}
add_action('save_post', 'axon_save_gallery_meta');

function axon_search_users() {
    if (!isset($_GET['search'])) {
        wp_send_json([]);
    }

    $search = sanitize_text_field($_GET['search']);
    $users = get_users([
        'search'         => '*' . $search . '*',
        'search_columns' => ['user_login', 'user_email', 'display_name'],
        'fields'         => ['ID', 'display_name', 'user_email'],
        'number'         => 10
    ]);

    $results = [];
    foreach ($users as $user) {
        $results[] = [
            'id'   => $user->ID,
            'text' => $user->display_name . ' (' . $user->user_email . ')'
        ];
    }

    wp_send_json($results);
}
// Shortcode to display gallery
function axon_gallery_shortcode($atts) {
    // Check if you got a Post id
    $atts = shortcode_atts(['id' => ''], $atts, 'axon_gallery');
    if (empty($atts['id'])) return 'Invalid Photo Gallery';
    
    // Check if the user is allowed to access the gallery
    $allowed_users = get_post_meta($atts['id'], 'axon_allowed_users', true) ?: [];
    if (!in_array(get_current_user_id(), $allowed_users)) return 'You are not allowed to access this Photo Gallery.';
    
    // Check if there are any images inside the Photo Gallery
    $gallery_images = get_post_meta($atts['id'], 'axon_gallery_images', true) ?: [];
    if (empty($gallery_images)) return 'There are no Images in this Photo Gallery.';
    
    $output = '<div class="image-mosaic">';

    // Loop through the images and create the mosaic cards
    foreach ($gallery_images as $index => $image_id) {
        $image_url = wp_get_attachment_url($image_id);
        $image_sizes = wp_get_attachment_metadata($image_id)['sizes']; // Get all available sizes
        
        // Alternate the class between card-tall and card-wide for variety
        $card_class = 'card';
        if ($index % 3 == 0) { // Every third image gets the 'card-wide' class
            $card_class .= ' card-wide';
        }
        if ($index % 4 == 0) { // Every fourth image gets the 'card-tall' class
            $card_class .= ' card-tall';
        }
        
        // Create a checkbox for each image
        $output .= '<div class="' . esc_attr($card_class) . '" style="background-image: url(' . esc_url($image_url) . ')">';
        $output .= '<input type="checkbox" class="axon-image-checkbox" data-image-id="' . esc_attr($image_id) . '" style=" top: 10px; right: 10px;" />';
        $output .= '</div>';
    }
    
    // Close the mosaic container
    $output .= '</div>';

    // Add size dropdown and download button below the gallery
    $output .= '<div class="donwload-container">
                    <label for="axon-size-selector">Select Image Size:</label>
                    <select id="axon-size-selector">
                        <option value="thumbnail">Thumbnail</option>
                        <option value="medium">Medium</option>
                        <option value="large">Large</option>
                        <option value="full">Full Size</option>
                    </select>
                    <button id="axon-download-selected" class="button">Download Selected Images</button>
                 </div>';

    // JavaScript to handle the downloading process
    $output .= '
        <script type="text/javascript">
            document.getElementById("axon-download-selected").addEventListener("click", function() {
                var selectedImages = [];
                var checkboxes = document.querySelectorAll(".axon-image-checkbox:checked");
                var selectedSize = document.getElementById("axon-size-selector").value;

                checkboxes.forEach(function(checkbox) {
                    var imageId = checkbox.getAttribute("data-image-id");
                    selectedImages.push(imageId);
                });

                if (selectedImages.length > 0) {
                    selectedImages.forEach(function(imageId) {
                        var downloadUrl = "' . admin_url('admin-ajax.php') . '?action=axon_download_image&image_id=" + imageId + "&size=" + selectedSize;
                        var link = document.createElement("a");
                        link.href = downloadUrl;
                        link.download = "image_" + imageId + "_" + selectedSize + ".jpg";
                        link.click();
                    });
                } else {
                    alert("Please select at least one image.");
                }
            });
        </script>';

    return $output;
}
add_shortcode('axon_gallery', 'axon_gallery_shortcode');


// Handle the download request via AJAX
function axon_download_image() {
    if (isset($_GET['image_id']) && isset($_GET['size'])) {
        $image_id = intval($_GET['image_id']);
        $size = sanitize_text_field($_GET['size']);
        
        $image_path = get_attached_file($image_id);
        $metadata = wp_get_attachment_metadata($image_id);
        
        // Get the path for the selected size
        if (isset($metadata['sizes'][$size])) {
            $image_path = str_replace(basename($image_path), $metadata['sizes'][$size]['file'], $image_path);
        }
        
        if (file_exists($image_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($image_path) . '"');
            header('Content-Length: ' . filesize($image_path));
            readfile($image_path);
            exit;
        }
    }
}
add_action('wp_ajax_axon_download_image', 'axon_download_image');
add_action('wp_ajax_nopriv_axon_download_image', 'axon_download_image');



function axon_create_user() {
    check_ajax_referer('axon_create_user_nonce', 'security');

    if (!isset($_POST['email']) || !is_email($_POST['email'])) {
        wp_send_json_error(['message' => 'Invalid email address.']);
    }

    $email = sanitize_email($_POST['email']);
    if (email_exists($email)) {
        $user = get_user_by('email', $email);
        wp_send_json_success(['user_id' => $user->ID, 'user_name' => $user->display_name]);
    }

    $random_password = wp_generate_password();
    $user_id = wp_create_user($email, $random_password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => 'Could not create user.']);
    }

    wp_update_user(['ID' => $user_id, 'role' => 'subscriber']);

    $user = get_user_by('ID', $user_id);
    wp_send_json_success(['user_id' => $user->ID, 'user_name' => $user->display_name]);
}
add_action('wp_ajax_axon_create_user', 'axon_create_user');
function axon_display_gallery_on_post($content) {
    if (is_singular('photo_gallery')) {
        global $post;
        $gallery_shortcode = do_shortcode('[axon_gallery id="' . $post->ID . '"]');
        return $content . $gallery_shortcode;
    }
    return $content;
}
add_filter('the_content', 'axon_display_gallery_on_post');

function gallery_image_select3() {
    global $post;
    $product = wc_get_product( $post->ID );
    $product_type = $product->get_type();
    if (is_product() && $product_type != "package") {
        $user_id = get_current_user_id();

        // Get the gallery settings from product meta
        $gallery_option = get_post_meta($post->ID, '_gallery_option', true);
        $max_images = get_post_meta($post->ID, '_max_images', true);

        // If the gallery is set to "Don't Show"
        if (empty($gallery_option) || $gallery_option === 'dont_show') {
            return; // Do not display the gallery
        }

        $args = array(
            'post_type' => 'photo_gallery',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        $photo_gallery_query = new WP_Query($args);

        echo '<div id="gallery-modal" style="display:none;">
        <div id="modal-content">';
        $image_ids = [];
        if ($photo_gallery_query->have_posts()) {
            while ($photo_gallery_query->have_posts()) {
                $photo_gallery_query->the_post();
                $post_title = get_the_title();
        
                $allowed_users = get_post_meta(get_the_ID(), 'axon_allowed_users', true) ?: [];
                if (in_array($user_id, $allowed_users)) {
                    $image_ids[] = [ 
                        "title" => $post_title,
                        "image_ids" => get_post_meta(get_the_ID(), 'axon_gallery_images', true)
                    ];
                }
            }
            wp_reset_postdata();
        }
        if (!empty($image_ids)) {
            foreach ($image_ids as $gallery) {
                echo '<h3>' . esc_html($gallery['title']) . '</h3>';
                if (!empty($gallery['image_ids'])) {
                    echo '<div class="gallery-images">';
                    foreach ($gallery['image_ids'] as $image_id) {
                        $image_url = wp_get_attachment_url($image_id);
                        echo '<div><input type="checkbox" class="gallery-image" value="' . esc_attr($image_id) . '">';
                        echo '<img src="' . esc_url($image_url) . '" class="zoomable-image" alt="' . esc_attr($gallery['title']) . '"></div>';
                    }
                    echo '</div>';
                }
            }
            
        
            echo'<button id="close-modal">Done</button>';
        }else{
            echo '<p>No galleries available login to see your allowed galleries for selection.</p>';
        }
        echo '</div></div>';
        echo '<div class="image-selection-container"  data-product="'.$post->ID.'">
            <input type="hidden" class="selected-image-ids" data-galleryoption="' . esc_attr($gallery_option) . '" data-max_images="' . esc_attr($max_images) . '"name="selected_image_ids[]" value="">
            <div class="selected-images-container"></div>
            <button class="view-gallery-btn" type="button">Select Photos (0)</button>
        </div>';


        echo '<div id="editor-modal" class="modal fade" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">';
        echo '    <div class="modal-dialog modal-lg" style="position: relative; margin: 50px auto; max-width: 800px; background: #fff; padding: 20px;">';
        echo '        <div class="modal-content" style="width:auto;">';
        echo '            <div class="modal-header" style="padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between;">';
        echo '                <h5 style="margin: 0; font-size: 18px;">Image Editor</h5>';
        echo '                <button id="close-editor-modal" class="close" style="background: none; border: none; font-size: 24px; cursor: pointer;">×</button>';
        echo '            </div>';
        echo '            <div class="modal-body" style="padding: 20px;">';
        echo '                <div id="tui-image-editor" style="width: 95vw; height: 500px;"></div>'; // Editor container
        echo '            </div>';
        echo '        </div>';
        echo '    </div>';
        echo '</div>';


    }
}

// Add necessary CSS and JavaScript
add_action('wp_footer', function() {
    // echo '<script defer src="https://chat-widget-matlil.s3.eu-west-3.amazonaws.com/insert.js?organization_id=5&chatbot_id=3284263b-e62c-46c1-8045-69ec4ae6f215" id="matil-chat-widget"></script>';
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const images = document.querySelectorAll(".zoomable-image");
            const alightbox = document.createElement("div");
            alightbox.classList.add("alightbox");
            document.body.appendChild(alightbox);
            
            const img = document.createElement("img");
            alightbox.appendChild(img);
            
            const close = document.createElement("span");
            close.innerHTML = "&times;";
            close.classList.add("alightbox-close");
            alightbox.appendChild(close);
            
            images.forEach(image => {
                image.addEventListener("click", function() {
                    img.src = this.src;
                    alightbox.style.display = "flex";
                });
            });
            
            close.addEventListener("click", function() {
                alightbox.style.display = "none";
            });
            // Close the modal if the user clicks outside of the modal content
            window.addEventListener("click", function(event) {
                if (event.target === alightbox) {
                    alightbox.style.display = "none";
                }
            });


        });
    </script>';
});

add_filter('woocommerce_before_add_to_cart_form', 'gallery_image_select3');
function hide_quantity() {
    global $post;
    $gallery_option = get_post_meta($post->ID, '_gallery_option', true);
    $max_images = get_post_meta($post->ID, '_max_images', true);
    if (!empty($gallery_option) && $gallery_option === 'single_image') {
        ?>
        <script>
            jQuery('div[data-block-name="woocommerce/add-to-cart-form"] .quantity').hide();
            jQuery('#gallery-modal input').on('change', function() {
                if (jQuery(this).prop('checked')) {
                    jQuery('div[data-block-name="woocommerce/add-to-cart-form"] .quantity').val(jQuery('div[data-block-name="woocommerce/add-to-cart-form"] .quantity').val()+1);
                }else{
                    jQuery('div[data-block-name="woocommerce/add-to-cart-form"] .quantity').val(jQuery('div[data-block-name="woocommerce/add-to-cart-form"] .quantity').val()-1);
                }
                console.log("changes in normal single");
            });
        </script>
        <?php
    
    }
}

add_action('woocommerce_after_add_to_cart_quantity', 'hide_quantity');

function custom_woocommerce_quantity_input_args( $args, $product ) {
    // Set the 'input_value' to 'readonly' to make the field non-editable
    // print_r($product->get_id());
    $gallery_option = get_post_meta($product->get_id(), '_gallery_option', true);
    if (!empty($gallery_option) && $gallery_option === 'single_image') {
        $args['readonly'] = true; // This will set the max_value to the min_value, making it unchangeable
        // $args['min_value'] = 0; // This will set the max_value to the min_value, making it unchangeable
        // $args['input_value'] = 0; // This will set the max_value to the min_value, making it unchangeable
        ?>
        <script>
            jQuery(document).ready(function($) {
                $(document).on('singleQtyChange', function(e , count) {
                    $("#<?php echo $args['input_id'];?>").val(count);
                    console.log("changes in normal single changed");
                });
                setTimeout(function() {
                    // $("#<?php echo $args['input_id'];?>").val(0);
                }, 500);
            });
        </script>
        <?php
    }
    // print_r($args);
    // die();
    
    return $args;
}
add_filter( 'woocommerce_quantity_input_args', 'custom_woocommerce_quantity_input_args', 10, 2 );


function add_gallery_metabox() {
    add_meta_box(
        'gallery_metabox',           // ID
        'Gallery Settings',          // Title
        'gallery_metabox_callback',  // Callback function
        'product',                   // Post type
        'side',                      // Context (location on the page)
        'high'                       // Priority
    );
}
add_action('add_meta_boxes', 'add_gallery_metabox');

function gallery_metabox_callback($post) {
    $gallery_option = get_post_meta($post->ID, '_gallery_option', true);
    $max_images = get_post_meta($post->ID, '_max_images', true);
    
    ?>
    <label for="gallery_option">Gallery Option:</label>
    <select name="gallery_option" id="gallery_option">
        <option value="dont_show" <?php selected($gallery_option, 'dont_show'); ?>>Don't Show Gallery</option>
        <option value="single_image" <?php selected($gallery_option, 'single_image'); ?>>Single Image Product</option>
        <option value="multiple_images" <?php selected($gallery_option, 'multiple_images'); ?>>Multiple Image Product</option>
    </select>

    <div id="max_images_container" style="display: <?php echo ($gallery_option == 'multiple_images') ? 'block' : 'none'; ?>;">
        <label for="max_images">Max Images:</label>
        <input type="number" name="max_images" id="max_images" value="<?php echo esc_attr($max_images); ?>" min="-1" />
    </div>

    <script>
        document.getElementById('gallery_option').addEventListener('change', function() {
            var selectedOption = this.value;
            if (selectedOption === 'multiple_images') {
                document.getElementById('max_images_container').style.display = 'block';
            } else {
                document.getElementById('max_images_container').style.display = 'none';
            }
        });
    </script>
    <?php
}

function save_gallery_metabox_data($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    if (isset($_POST['gallery_option'])) {
        update_post_meta($post_id, '_gallery_option', sanitize_text_field($_POST['gallery_option']));
    }

    if (isset($_POST['max_images']) ) {
        if($_POST['gallery_option']=="single_image") $_POST['max_images']=-1;
        update_post_meta($post_id, '_max_images', $_POST['max_images']);
    }
}
add_action('save_post', 'save_gallery_metabox_data2');
function save_gallery_metabox_data2($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    if (isset($_POST['gallery_option'])) {
        update_post_meta($post_id, '_gallery_option', sanitize_text_field($_POST['gallery_option']));
    }

    if (isset($_POST['max_images'])) {
        if($_POST['gallery_option'] == "single_image") {
            $_POST['max_images'] = -1;
        }
        update_post_meta($post_id, '_max_images', intval($_POST['max_images']));
    }

    // Check if this is a variable product
    if ('product' === get_post_type($post_id)) {
        $product = wc_get_product($post_id);
        if ($product->is_type('variable')) {
            // Loop through variations
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                if (isset($_POST['gallery_option'])) {
                    update_post_meta($variation_id, '_gallery_option', sanitize_text_field($_POST['gallery_option']));
                }
                if (isset($_POST['max_images'])) {
                    update_post_meta($variation_id, '_max_images', intval($_POST['max_images']));
                }
            }
        }
    }
}
function save_selected_gallery_images_in_cart($cart_item_data, $product_id) {
    if (isset($_POST['selected_gallery_images'])) {
        $selected_images = json_decode(stripslashes($_POST['selected_gallery_images']), true);
        if (!empty($selected_images)) {
            // Save the selected images as custom cart item data
            $cart_item_data['selected_gallery_images'] = $selected_images;
        }
    }
    // print_r($cart_item_data);
    // exit;
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'save_selected_gallery_images_in_cart', 10, 2);

function save_selected_gallery_images_in_order_meta($item, $cart_item_key, $values, $order) {
    $cart_item = $values;
    if (isset($cart_item['selected_gallery_images'])) {
        $selected_images = $cart_item['selected_gallery_images'];
        $data = "";        
        if($cart_item["data"]->get_type() == "package"){
            $package_products = $cart_item["data"]->get_meta("_package_products");
            $data .= "<br>";
            $data .= '<ol class="package-products-list" style="margin-left: 1em; padding:0;width: 100%; float: left;">';
            foreach ($package_products as $index => $product) {
                $product_id = $product['product'];
                $quantity = $product['quantity'];
                $product_obj = wc_get_product($product_id);
                
                if ($product_obj) {
                    $product_thumbnail = $product_obj->get_image();
                    $product_title = $product_obj->get_title();
                    $product_price = wc_price($product_obj->get_price()*(int)$quantity);
                    
                    $data .= '<li class="package-product-item" style="text-align: left;">';
                    $data .= '<span class="product-title">' . $product_title . '</span>';
                    $data .= '<span class="product-quantity"> x ' . $quantity . '</span>';
                    $data .= '<span class="product-price"> - ' . $product_price . '</span><ul style="margin: 0; padding: 0;list-style: none !important;" type="none">';
                    foreach ($selected_images[$product_id] as $group) {
                        // $data .= "<li style='padding: 2px 0; border: 1px solid black;border-width: 0 0 1px;padding-bottom: 4px;'type='none'><div class='cart-images' style='display: flex; flex-wrap: wrap; justify-content: flex-start; 	gap: 5px;width: 100%;'>";
                        $data .= "<li style='padding: 2px 0;' type='none'><div class='cart-images' style='display: flex; flex-wrap: wrap; justify-content: flex-start; 	gap: 5px;width: 100%;'>";
                        foreach ($group as $image_id) {
                            $image_url = wp_get_attachment_image_src($image_id, 'thumbnail')[0];
                            $data .= '<img src="'.esc_url($image_url).'" alt="Selected Image" style="width: 70px; height: 70px; object-fit: cover;" />';
                        }
                        $data .= "</div></li>";
                    }
                    $data .= '</ul></li>';
                }
            }
            $data .= '</ol>';
        }else{
            foreach ($selected_images[$cart_item["product_id"]] as $group) {
                $data .= "<div class='cart-images' style='display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 5px;width: 100%;float: left;'>";
                foreach ($group as $image_id) {
                    $image_url = wp_get_attachment_image_src($image_id, 'thumbnail')[0];
                    $data .= '<img src="'.esc_url($image_url).'" alt="Selected Image" style="width: 70px; height: 70px; object-fit: cover;" />';
                }
                $data .= "</div>";
            }
        }
        $item->add_meta_data(
            'Selected Images ',
            $data,
            true
        );
        
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'save_selected_gallery_images_in_order_meta', 10, 4);

function display_selected_gallery_images_on_cart($item_data, $cart_item) {
    // Check if selected gallery images exist in the cart item data
    // print_r($cart_item);
        // print_r($cart_item["product_id"]);
        // print_r($cart_item["data"]->get_type());
        // print_r($cart_item["data"]->get_meta("_package_products"));
    // die();
    $cart_item = $cart_item;
    if (isset($cart_item['selected_gallery_images'])) {
        $selected_images = $cart_item['selected_gallery_images'];
        if($cart_item["data"]->get_type() == "package"){
            $package_products = $cart_item["data"]->get_meta("_package_products");
            echo '<ol class="package-products-list" style="margin-left: 1em; padding:0;width: 100%;float: left;">';
            foreach ($package_products as $index => $product) {
                $product_id = $product['product'];
                $quantity = $product['quantity'];
                $product_obj = wc_get_product($product_id);
                
                if ($product_obj) {
                    $product_thumbnail = $product_obj->get_image();
                    $product_title = $product_obj->get_title();
                    $product_price = wc_price($product_obj->get_price()*(int)$quantity);
                    
                    echo '<li class="package-product-item" style="text-align: left;">';
                    echo '<span class="product-title">' . $product_title . '</span>';
                    echo '<span class="product-quantity"> x ' . $quantity . '</span>';
                    echo '<span class="product-price"> - ' . $product_price . '</span><ul style="margin: 0; padding: 0;" type="none">';
                    foreach ($selected_images[$product_id] as $group) {
                        // echo "<li style='padding: 2px 0; border: 1px solid black;border-width: 0 0 1px;padding-bottom: 4px;' type='none'><div class='cart-images' style='display: flex; flex-wrap: wrap; justify-content: flex-start; 	gap: 5px;width: 100%;'>";
                        echo "<li style='padding: 2px 0;' type='none'><div class='cart-images' style='display: flex; flex-wrap: wrap; justify-content: flex-start; 	gap: 5px;width: 100%;'>";
                        foreach ($group as $image_id) {
                            $image_url = wp_get_attachment_image_src($image_id, 'thumbnail')[0];
                            echo '<img src="'.esc_url($image_url).'" alt="Selected Image" style="width: 70px; height: 70px; object-fit: cover;" />';
                        }
                        echo "</div></li>";
                    }
                    echo '</ul></li>';
                }
            }
            echo '</ol>';
        }else{
            $value = "";
            foreach ($selected_images[$cart_item["product_id"]] as $group) {
                $value .= "<div class='cart-images' style='display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 5px;width: 100%;float: left;'>";
                foreach ($group as $image_id) {
                    $image_url = wp_get_attachment_image_src($image_id, 'thumbnail')[0];
                    $value .= '<img src="'.esc_url($image_url).'" alt="Selected Image" style="width: 70px; height: 70px; object-fit: cover;" />';
                }
                $value .= "</div>";
            }
            $item_data[] = array(
                'key'   => 'Selected Image ',
                'value' => $value,
                'display' => '', 
            );
        }
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_selected_gallery_images_on_cart', 10, 2);


function check_selected_images_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id=null ) {
    $gallery_option = get_post_meta($product_id, '_gallery_option', true);
    $product = wc_get_product( $product_id );
    $product_type = $product->get_type();
    $_POST['selected_gallery_images'] = stripslashes($_POST['selected_gallery_images']);
    if($product_type == "package"){
        
        // $passed = false;
        // print_r($_POST['selected_gallery_images']);
        // die();
        // wc_add_notice(print_r(json_decode($_POST['selected_gallery_images']),true), 'error' );
        foreach(json_decode($_POST['selected_gallery_images']) as $images) {
            foreach($images as $sets) {
                if(is_array($sets) && empty($sets)){
                    $passed = false;
                    wc_add_notice('All Galleries Should Have Atleast 1 Images Selected.', 'error' );
                    break 2;
                }
            }
        }
    }
    if (empty($gallery_option) || $gallery_option === 'dont_show') {
        return $passed;
    }
    foreach(json_decode($_POST['selected_gallery_images']) as $images) {
        foreach($images as $sets) {
            if(is_array($sets) && empty($sets)){
                $passed = false;
                wc_add_notice('Atleast 1 Images is required to be selected.', 'error' );
                break 2;
            }
        }
    }
    return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'check_selected_images_add_to_cart_validation', 10, 4 );
 
  


// Register custom product type 'package'
function register_package_product_type() {
    #[AllowDynamicProperties]
    class WC_Product_Package extends WC_Product {
        
        public function __construct( $product ) {
            $this->product_type = 'package';
            parent::__construct( $product );
        }
        public function get_type() {
            return 'package';
         }
    }
}
add_action( 'init', 'register_package_product_type' );
// Add custom product type 'package' to the product type dropdown
function add_package_product_type( $types ) {
    $types['package'] = 'Package';
    return $types;
}
add_filter( 'product_type_selector', 'add_package_product_type' );
add_filter( 'woocommerce_product_class', 'bbloomer_woocommerce_product_class', 10, 2 );
  
function bbloomer_woocommerce_product_class( $classname, $product_type ) {
    if ( $product_type == 'package' ) {
        $classname = 'WC_Product_Package';
    }
    return $classname;
}


// Add custom tab for Package product type
function add_package_product_data_tab( $tabs ) {
    $tabs['package_settings'] = array(
        'label'    => __( 'Package Settings', 'woocommerce' ),
        'target'   => 'package_settings_product_data',
        'class'    => array( 'show_if_package' ),
        'priority' => 21,
    );
    
    // $tabs['inventory']['class'][] = 'hide_if_package';
    // $tabs['shipping']['class'][] = 'hide_if_package';
    $tabs['linked_product']['class'][] = 'hide_if_package';
    $tabs['attribute']['class'][] = 'hide_if_package';
    $tabs['advanced']['class'][] = 'hide_if_package';
   return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'add_package_product_data_tab' ,10,1);



// Step 1: Add package fields in the product settings panel (with repeater for selecting products)
function add_package_product_data_panel() {
    global $post;

    ?>
    <div id="package_settings_product_data" class="panel woocommerce_options_panel">
        <div class="options_group">
            <h3><?php _e('Package Products', 'woocommerce'); ?></h3>
            <div id="product-package-repeater" class="product-package-repeater">
                <?php
                // Get existing package data (if any)
                $package_products = get_post_meta($post->ID, '_package_products', true);
                $products = wc_get_products(array(
                    'status' => 'publish',
                    'limit' => -1,
                    'type' => array('simple', 'grouped', 'external', 'variation'),
                ));
                $products = array_filter($products, function($product) {
                    return !$product->is_type('variable');
                });
                
                // Optionally, reset the keys of the filtered array
                $products = array_values($products);
                
                

                if (!empty($package_products)) {
                    foreach ($package_products as $key => $product) {
                        ?>
                        <div class="package-product" data-index="<?php echo esc_attr($key); ?>">
                            <div class="form-fields">
                                <label><?php _e('Select Product', 'woocommerce'); ?></label>
                                <select name="_package_product[<?php echo esc_attr($key); ?>][product]">
                                    <option value=""><?php _e('Select a product', 'woocommerce'); ?></option>
                                    <?php foreach ($products as $p) : ?>
                                        <option value="<?php echo esc_attr($p->get_id()); ?>"
                                            <?php selected($product['product'], $p->get_id()); ?>>
                                            <?php echo esc_html($p->get_name()); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-fields">
                                <label><?php _e('Quantity', 'woocommerce'); ?></label>
                                <input type="number" name="_package_product[<?php echo esc_attr($key); ?>][quantity]"
                                       value="<?php echo esc_attr($product['quantity']); ?>" min="1"/>
                            </div>

                            <button href="#" class="button del-btn"><?php _e('Remove Product', 'woocommerce'); ?></button>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <a href="#" id="add-package-product" class="button button-primary"><?php _e('Add Product', 'woocommerce'); ?></a>
        </div>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            let packageIndex = <?php echo !empty($package_products) ? count($package_products) : 0; ?>;

            // Add new package item
            $('#add-package-product').on('click', function (e) {
                e.preventDefault();

                let newField = `
                <div class="package-product" data-index="${packageIndex}">
                    <div class="form-fields">
                        <label><?php _e('Select Product', 'woocommerce'); ?></label>
                        <select name="_package_product[${packageIndex}][product]">
                            <option value=""><?php _e('Select a product', 'woocommerce'); ?></option>
                            <?php foreach ($products as $p) : ?>
                                <option value="<?php echo esc_attr($p->get_id()); ?>">
                                    <?php echo esc_html($p->get_name()); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-fields">
                        <label><?php _e('Quantity', 'woocommerce'); ?></label>
                        <input type="number" name="_package_product[${packageIndex}][quantity]" value="1" min="1"/>
                    </div>

                    <button href="#" class="button del-btn"><?php _e('Remove Product', 'woocommerce'); ?></button>
                </div>`;

                $('#product-package-repeater').append(newField);
                packageIndex++;
            });

            // Remove package item
            $(document).on('click', '#package_settings_product_data .del-btn', function (e) {
                e.preventDefault();
                $(this).closest('.package-product').remove();
            });
            $(document.body).on('woocommerce-product-type-change',function(event,type){
                if (type=='package') {
                    $('.general_tab').show();
                    $('.pricing').show();         
                }
            });  
        });
    </script>
    <?php
    global $product_object;
    if ( $product_object && 'package' === $product_object->get_type() ) {
        wc_enqueue_js("
            $('.general_tab').show();
            $('.pricing').show();         
        ");
    }
}
add_action('woocommerce_product_data_panels', 'add_package_product_data_panel');



function save_package_product_data($post_id) {
    if (isset($_POST['_package_product']) && is_array($_POST['_package_product'])) {
        $package_products = array();

        foreach ($_POST['_package_product'] as $product_data) {
            if (!empty($product_data['product']) && !empty($product_data['quantity'])) {
                $package_products[] = array(
                    'product'  => sanitize_text_field($product_data['product']),
                    'quantity' => intval($product_data['quantity']),
                );
            }
        }

        // Save package data
        update_post_meta($post_id, '_package_products', $package_products);
    } else {
        delete_post_meta($post_id, '_package_products');
    }
}
add_action('woocommerce_process_product_meta', 'save_package_product_data');



// Step 3: Display the package content in the product summary (frontend)
function display_package_product_summary() {
    global $post;
    // Get the saved package products and quantities
    $package_products = get_post_meta( $post->ID, '_package_products', true );
    $user_id = get_current_user_id();
    if ($package_products) {
        $args = array(
            'post_type' => 'photo_gallery',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        $photo_gallery_query = new WP_Query($args);
    
        echo '<div id="gallery-modal" style="display:none;">
        <div id="modal-content">';
        $image_ids = [];
        if ($photo_gallery_query->have_posts()) {
            while ($photo_gallery_query->have_posts()) {
                $photo_gallery_query->the_post();
                $post_title = get_the_title();
        
                $allowed_users = get_post_meta(get_the_ID(), 'axon_allowed_users', true) ?: [];
                if (in_array($user_id, $allowed_users)) {
                    $image_ids[] = [ 
                        "title" => $post_title,
                        "image_ids" => get_post_meta(get_the_ID(), 'axon_gallery_images', true)
                    ];
                }
            }
            wp_reset_postdata();
        }
        if (!empty($image_ids)) {
            foreach ($image_ids as $gallery) {
                echo '<h3>' . esc_html($gallery['title']) . '</h3>';
                if (!empty($gallery['image_ids'])) {
                    echo '<div class="gallery-images">';
                    foreach ($gallery['image_ids'] as $image_id) {
                        $image_url = wp_get_attachment_url($image_id);
                        echo '<div><input type="checkbox" class="gallery-image" value="' . esc_attr($image_id) . '">';
                        echo '<img src="' . esc_url($image_url) . '" class="zoomable-image" alt="' . esc_attr($gallery['title']) . '"></div>';
                    }
                    echo '</div>';
                }
            }
        
            echo'<button id="close-modal">Done</button>';
        }else{
            echo '<p>No galleries available login to see your allowed galleries for selection.</p>';
        }
        echo '</div></div>';
        echo '<div class="product-package-summary">';
        echo '<h3>' . __('Package Contents', 'woocommerce') . '</h3>';
        echo '<ul class="package-products-list">';

        foreach ($package_products as $product) {
            $product_id = $product['product'];
            $quantity = $product['quantity'];
            $product_obj = wc_get_product($product_id);
            $gallery_option = get_post_meta($product_id, '_gallery_option', true);
            $max_images = get_post_meta($product_id, '_max_images', true);
            
            if ($product_obj) {
                $product_thumbnail = $product_obj->get_image();
                $product_title = $product_obj->get_title();
                $product_price = wc_price($product_obj->get_price());

                echo '<li class="package-product-item">';
                echo '<div class="product-thumbnail">' . $product_thumbnail . '</div>';
                echo '<div class="product-info">';
                    echo '<span class="product-title">' . $product_title . '</span>';
                    echo '<span class="product-quantity"> x ' . $quantity . '</span>';
                    echo '<span class="product-price"> - ' . $product_price . '</span>';
                    if (empty($gallery_option) || $gallery_option == 'dont_show') {
                        // echo 'It is a dont show';
                    }
                    if ($gallery_option == 'multiple_images') {
                        for ($i=1; $i <= $quantity ; $i++) {
                            echo '<div class="image-selection-container" data-product="'.$product_id.'">
                                <input type="hidden" class="selected-image-ids" data-galleryoption="' . esc_attr($gallery_option) . '" data-max_images="' . esc_attr($max_images) . '" name="selected_image_ids[]" value="">
                                <div class="selected-images-container"></div>
                                <button class="view-gallery-btn" type="button">Select Photos (0)</button>
                            </div>';
                        }
                    }
                    if ($gallery_option == 'single_image') {
                        echo '<div class="image-selection-container" data-product="'.$product_id.'">
                            <input type="hidden" class="selected-image-ids" data-galleryoption="' . esc_attr($gallery_option) . '" data-max_images="' . esc_attr($quantity) . '" name="selected_image_ids[]" value="">
                            <div class="selected-images-container"></div>
                            <button class="view-gallery-btn" type="button">Select Photos (0)</button>
                        </div>';
                    }
                echo '</div>';
                echo '</li>';
            }
        }

        echo '</ul>';
        echo '</div>';

        echo '<div id="editor-modal" class="modal fade" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">';
        echo '    <div class="modal-dialog modal-lg" style="position: relative; margin: 50px auto; max-width: 800px; background: #fff; padding: 20px;">';
        echo '        <div class="modal-content" style="width:auto;">';
        echo '            <div class="modal-header" style="padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between;">';
        echo '                <h5 style="margin: 0; font-size: 18px;">Image Editor</h5>';
        echo '                <button id="close-editor-modal" class="close" style="background: none; border: none; font-size: 24px; cursor: pointer;">×</button>';
        echo '            </div>';
        echo '            <div class="modal-body" style="padding: 20px;">';
        echo '                <div id="tui-image-editor" style="width: 95vw; height: 500px;"></div>'; // Editor container
        echo '            </div>';
        echo '        </div>';
        echo '    </div>';
        echo '</div>';

        global $product;
        if ( ! $product->is_purchasable() ) {
            return;
        }
        
        echo wc_get_stock_html( $product );

        if ( $product->is_in_stock() ) : ?>

            <?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

            <form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
                <?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

                <?php
                do_action( 'woocommerce_before_add_to_cart_quantity' );

                woocommerce_quantity_input(
                    array(
                        'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
                        'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
                        'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
                    )
                );

                do_action( 'woocommerce_after_add_to_cart_quantity' );
                ?>

                <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

                <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
            </form>

            <?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

        <?php endif;
    }
}
add_action( 'woocommerce_single_product_summary', 'display_package_product_summary', 25 );



function add_watermark_to_image($image_url, $attachment_id) {
    return $image_url;
}
//! Hook into the 'wp_get_attachment_url' filter
// add_filter('wp_get_attachment_url', 'add_watermark_to_image', 10, 2);



function custom_woocommerce_button_text($text, $product) {
    if ((is_shop() || is_product_category()) && $product->get_type() === 'package') {
        $text = 'Get Package';
    }

    if (get_post_meta($product->get_id(), '_gallery_option', true) == 'single_image' || get_post_meta($product->get_id(), '_gallery_option', true) == 'multiple_images') {
        $text = 'View Product';
    }
    return $text;
}
add_filter('woocommerce_product_add_to_cart_text', 'custom_woocommerce_button_text', 10, 2);


function custom_woocommerce_add_to_cart_redirect($url, $product) {
    if ((is_shop() || is_product_category()) && $product->get_type() === 'package') {
        return get_permalink($product->get_id()); // Redirect to the product page
    }

    if (get_post_meta($product->get_id(), '_gallery_option', true) == 'single_image' || get_post_meta($product->get_id(), '_gallery_option', true) == 'multiple_images') {
        return get_permalink($product->get_id()); // Redirect to the product page
    }

    return $url;
}
add_filter('woocommerce_product_add_to_cart_url', 'custom_woocommerce_add_to_cart_redirect', 10, 2);
function custom_woocommerce_loop_add_to_cart_args($args, $product) {
    // Condition for shop or product category page and product type 'package'
    if ((is_shop() || is_product_category()) && $product->get_type() === 'package') {
        $args['class'] = str_replace('ajax_add_to_cart', "", $args['class']);
    }
    if (get_post_meta($product->get_id(), '_gallery_option', true) == 'single_image' || get_post_meta($product->get_id(), '_gallery_option', true) == 'multiple_images') {
        $args['class'] = str_replace('ajax_add_to_cart', "", $args['class']);
    }
    // print_r($args);

    return $args;
}
add_filter('woocommerce_loop_add_to_cart_args', 'custom_woocommerce_loop_add_to_cart_args', 10, 2);






// Step 1: Add a custom menu item to "My Account" navigation
add_filter( 'woocommerce_account_menu_items', 'add_gallery_menu_item_to_account', 10, 1 );
function add_gallery_menu_item_to_account( $menu_items ) {
    // Add a custom menu item for the photo gallery
    $menu_items['photo_gallery'] = __( 'My Galleries', 'woocommerce' );

    return $menu_items;
}
function add_custom_class_to_account_menu_item( $classes, $endpoint ) {
    // Check if the menu item is the one you want to customize (e.g., 'gallery')
    if ( 'photo_gallery' === $endpoint ) {
        $classes[] = 'custom-gallery-class'; // Add your custom class
    }
    return $classes;
}
// add_filter( 'woocommerce_account_menu_item_classes', 'add_custom_class_to_account_menu_item', 10, 2 );
function add_gallery_dashicon_to_account_menu() {
    // Ensure that Dashicons are loaded on your site
    wp_enqueue_style( 'dashicons' );

    ?>
    <style>
        /* Add the Dashicon for the Gallery */
        .woocommerce-MyAccount-navigation ul li.custom-gallery-class a::before{
            content: '\f323' !important;
            font-family: 'Dashicons' !important;
            margin-right: 8px !important;
        }
    </style>
    <?php
}
// add_action( 'wp_head', 'add_gallery_dashicon_to_account_menu' );

// Step 2: Create an endpoint to display the gallery when the menu item is clicked
add_shortcode( 'my_photo_galleries', 'display_photo_gallery_endpoint_content' );

function display_photo_gallery_endpoint_content() {
    // Get the current user ID
    $current_user_id = get_current_user_id();

    // Query the photo_gallery CPT for galleries that the current user is allowed to view
    $args = array(
        'post_type'      => 'photo_gallery',
        'posts_per_page' => -1, 
    );

    $galleries = new WP_Query( $args );
    ob_start();
    // Check if any galleries exist
    if ( $galleries->have_posts() ) {
        echo '<div class="my-account-photo-gallery">';
        echo '<h2>Your Photo Galleries</h2>';
        echo '<div class="gallery-container">';

        // Loop through each gallery post
        while ( $galleries->have_posts() ) : $galleries->the_post();
            // Get the image IDs from the gallery meta
            // print_r(get_post_meta( get_the_ID(), 'axon_allowed_users', true ));
            if (!in_array(get_current_user_id(), (array)get_post_meta( get_the_ID(), 'axon_allowed_users', true ))) continue;

            $image_ids = get_post_meta( get_the_ID(), 'axon_gallery_images', true );
            $image_count = count( $image_ids ); // Get the count of images
            $created_date = get_the_date(); // Get the created date of the gallery

            // Check if images exist
            if ( ! empty( $image_ids ) ) :
                ?>
                <div class="gallery-item">
                    <!-- Gallery Header with Title, Date, Image Count, and Show More -->
                    <div class="gallery-header">
                        <h3><?php the_title(); ?></h3>
                        <div class="meta-info">
                            <span class="date"><?php echo $created_date; ?></span>
                            <span class="image-count"><?php echo $image_count; ?> Images</span>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="show-more">See All Photos</a>
                    </div>

                    <!-- Gallery Image Preview -->
                    <div class="gallery-images">
                        <?php
                        // Show the first image in the gallery as a preview
                        // $first_image_id = $image_ids[0];
                        // $image_url = wp_get_attachment_url( $first_image_id );,
                        $image_count = 0;
                        foreach ( $image_ids as $image_id ) {
                            if ( $image_count < 3 ) { // Show only the first 3 images
                                $image_url = wp_get_attachment_url( $image_id );
                                if ( $image_url ) :
                                    ?>
                                    <div class="gallery-thumbnail" style="background-image: url('<?php echo esc_url( $image_url ); ?>');"></div>
                                <?php endif;
                                $image_count++;
                            }
                        }
                         ?>
                    </div>
                </div>
                <?php
            endif;
        endwhile;

        echo '</div>'; // Close the gallery container
        echo '</div>'; // Close the my-account-photo-gallery section
        // Reset post data after custom WP_Query
        wp_reset_postdata();
        return ob_get_clean();
    } else {
        return '<p>No galleries found in your account.</p>';
    }
}
add_action( 'woocommerce_account_photo_gallery_endpoint', function() {
    echo do_shortcode( '[my_photo_galleries]');
} );
// Step 3: Register the endpoint for the custom menu item
add_action( 'init', 'add_photo_gallery_endpoint' );
function add_photo_gallery_endpoint() {
    // Register the custom endpoint (this will be used for the menu link)
    add_rewrite_endpoint( 'photo_gallery', EP_ROOT | EP_PAGES );
}

// Step 4: Ensure that WooCommerce refreshes the rewrite rules when needed
add_action( 'woocommerce_flush_rewrite_rules', 'flush_rewrite_rules_on_gallery_endpoint' );
function flush_rewrite_rules_on_gallery_endpoint() {
    flush_rewrite_rules();
}

//by Zeeshan

function axon_add_gallery_metaboxes() {
    global $post;
    add_meta_box(
        'axon_gallery_images',
        'Gallery Images',
        'axon_gallery_metabox_callback',
        'photo_gallery',
        'normal',
        'high'
    );
    add_meta_box(
        'axon_allowed_users',
        'Allowed Users',
        'axon_allowed_users_metabox_callback',
        'photo_gallery',
        'side', // Placing it on the right side
        'high'
    );

    $gallery_option = get_post_meta($post->ID, '_gallery_option', true);

    // Conditionally add the Frame Template Settings metabox if gallery_option is 'single_image' or 'multiple_images'
    if ($gallery_option === 'single_image' || $gallery_option === 'multiple_images') {
        add_meta_box(
            'axon_frame_template_metabox',
            'Frame Template Settings',
            'axon_frame_template_metabox_callback',
            'product', // Changed to WooCommerce product post type
            'normal',
            'high'
        );
    }
    
}
add_action('add_meta_boxes', 'axon_add_gallery_metaboxes');



// Render the metabox
function axon_frame_template_metabox_callback($post) {
    // Add nonce field for security
    wp_nonce_field('axon_frame_template_metabox_nonce', 'axon_frame_template_nonce');

    // Get the saved frame template settings for this post
    $frame_template = get_post_meta($post->ID, '_axon_frame_template', true);
    if (!$frame_template) {
        $frame_template = array(
            'image_id' => 0,
            'coordinates' => array('x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 0)
        );
    }

    // Render the image selection field
    ?>
    <p>
        <label for="frame-template-image-id"><strong>Frame Template Image:</strong></label><br />
        <input type="hidden" name="axon_frame_template[image_id]" id="frame-template-image-id" value="<?php echo esc_attr($frame_template['image_id']); ?>" />
        <button type="button" class="button" id="upload-frame-template-image">Select Image</button>
        <p class="description">Select an image from the media library to use as the frame template.</p>
    </p>

    <!-- Render the coordinates field -->
    <p><strong>Top-Left (x1, y1):</strong></p>
    <input type="number" step="0.1" name="axon_frame_template[coordinates][x1]" id="coord-x1" value="<?php echo esc_attr($frame_template['coordinates']['x1']); ?>" readonly />
    <input type="number" step="0.1" name="axon_frame_template[coordinates][y1]" id="coord-y1" value="<?php echo esc_attr($frame_template['coordinates']['y1']); ?>" readonly />
    <p><strong>Bottom-Right (x2, y2):</strong></p>
    <input type="number" step="0.1" name="axon_frame_template[coordinates][x2]" id="coord-x2" value="<?php echo esc_attr($frame_template['coordinates']['x2']); ?>" readonly />
    <input type="number" step="0.1" name="axon_frame_template[coordinates][y2]" id="coord-y2" value="<?php echo esc_attr($frame_template['coordinates']['y2']); ?>" readonly />
    <p class="description">Select an area on the image preview below to set the crop coordinates.</p>
    <input type="hidden" name="axon_frame_template[coordinates][aspect_ratio]" value="0" />

    <!-- Image preview -->
    <div id="frame-template-preview">
        <h2>Preview and Select Crop Area</h2>
        <div id="image-preview-container">
            <?php
            $image_id = $frame_template['image_id'];
            if ($image_id) {
                $image_url = wp_get_attachment_url($image_id);
                if ($image_url) {
                    echo '<img id="frame-template-image" src="' . esc_url($image_url) . '" style="max-width: 100%; height: auto;" />';
                } else {
                    echo '<p>No image selected.</p>';
                }
            } else {
                echo '<p>No image selected.</p>';
            }
            ?>
        </div>
    </div>
    <?php
}

// Save the metabox data
function axon_save_frame_template_metabox($post_id) {
    // Check if nonce is set and verify it
    if (!isset($_POST['axon_frame_template_nonce']) || !wp_verify_nonce($_POST['axon_frame_template_nonce'], 'axon_frame_template_metabox_nonce')) {
        return;
    }

    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Check if the frame template data is set
    if (isset($_POST['axon_frame_template'])) {
        $frame_template = $_POST['axon_frame_template'];

        // Sanitize the input
        $sanitized_data = array();
        $sanitized_data['image_id'] = isset($frame_template['image_id']) ? absint($frame_template['image_id']) : 0;
        $sanitized_data['coordinates'] = isset($frame_template['coordinates']) ? array_map('floatval', $frame_template['coordinates']) : array(
            'x1' => 0,
            'y1' => 0,
            'x2' => 0,
            'y2' => 0
        );

        // Save the data as post meta
        update_post_meta($post_id, '_axon_frame_template', $sanitized_data);
    }
}
add_action('save_post', 'axon_save_frame_template_metabox');

function axon_enqueue_frame_template_scripts($hook) {
    // Only enqueue on the post edit screen for product post type
    global $post;
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    if (!$post || $post->post_type !== 'product') {
        return;
    }

    // Enqueue WordPress media uploader scripts
    wp_enqueue_media();

    // Enqueue Cropper.js
    wp_enqueue_style('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css', array(), '1.5.12');
    wp_enqueue_script('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js', array('jquery'), '1.5.12', true);

    // Enqueue custom script
    wp_enqueue_script(
        'axon-frame-template-script',
        AXON_PLUGIN_URL . 'assets/js/frame-template.js',
        array('jquery', 'cropper-js'),
        AXON_SCRIPTS_VERSION,
        true
    );

    // Debug: Confirm the script is enqueued
    error_log('Enqueued frame-template.js for post ID: ' . $post->ID);
}
add_action('admin_enqueue_scripts', 'axon_enqueue_frame_template_scripts');


add_action('wp_ajax_save_edited_image', 'save_edited_image_callback');
function save_edited_image_callback() {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'axon_photo_gallery_nonce')) {
        wp_send_json_error('Invalid nonce');
        wp_die();
    }

    // Check if image data is provided
    if (!isset($_POST['image_data']) || empty($_POST['image_data'])) {
        wp_send_json_error('No image data provided');
        wp_die();
    }

    // Get the base64-encoded image data
    $image_data = $_POST['image_data'];

    // Remove the data URL prefix (e.g., "data:image/png;base64,")
    $image_data = str_replace('data:image/png;base64,', '', $image_data);
    $image_data = str_replace(' ', '+', $image_data);

    // Decode the base64 string to binary data
    $decoded_image = base64_decode($image_data);
    if ($decoded_image === false) {
        wp_send_json_error('Failed to decode image data');
        wp_die();
    }

    // Generate a unique filename
    $upload_dir = wp_upload_dir();
    $filename = 'edited-image-' . uniqid() . '.png';
    $file_path = $upload_dir['path'] . '/' . $filename;

    // Save the image to the uploads directory
    $result = file_put_contents($file_path, $decoded_image);
    if ($result === false) {
        wp_send_json_error('Failed to save image to server');
        wp_die();
    }

    // Generate the URL for the saved image
    $file_url = $upload_dir['url'] . '/' . $filename;

    // Prepare the attachment data
    $filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'guid'           => $file_url,
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    // Insert the attachment into the media library
    $attachment_id = wp_insert_attachment($attachment, $file_path);
    if (is_wp_error($attachment_id)) {
        wp_send_json_error('Failed to create attachment: ' . $attachment_id->get_error_message());
        wp_die();
    }

    // Generate the attachment metadata and update the attachment
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    // Return success response with the file URL and attachment ID
    wp_send_json_success(array(
        'message'      => 'Image saved successfully',
        'file_url'     => $file_url,
        'attachment_id' => $attachment_id, // Include the attachment ID
    ));

    wp_die();
}

add_action('wp_ajax_composite_images', 'composite_images_callback');
function composite_images_callback() {
    error_log('Starting composite_images_callback');

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'axon_photo_gallery_nonce')) {
        error_log('Composite Images: Invalid nonce');
        wp_send_json_error('Invalid nonce');
        wp_die();
    }

    if (!isset($_POST['frame_image_url']) || !isset($_POST['edited_image_url']) || !isset($_POST['coordinates'])) {
        error_log('Composite Images: Missing required parameters');
        wp_send_json_error('Missing required parameters');
        wp_die();
    }

    $frame_image_url = esc_url_raw($_POST['frame_image_url']);
    $edited_image_url = esc_url_raw($_POST['edited_image_url']);
    $coordinates = array_map('floatval', $_POST['coordinates']);

    error_log('Frame Image URL: ' . $frame_image_url);
    error_log('Edited Image URL: ' . $edited_image_url);
    error_log('Coordinates: ' . print_r($coordinates, true));

    if (!isset($coordinates['x1']) || !isset($coordinates['y1']) || !isset($coordinates['x2']) || !isset($coordinates['y2'])) {
        error_log('Composite Images: Invalid coordinates');
        wp_send_json_error('Invalid coordinates');
        wp_die();
    }

    $upload_dir = wp_upload_dir();
    $frame_image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $frame_image_url);
    $edited_image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $edited_image_url);

    error_log('Frame Image Path: ' . $frame_image_path);
    error_log('Edited Image Path: ' . $edited_image_path);

    if (!file_exists($frame_image_path) || !file_exists($edited_image_path)) {
        error_log('Composite Images: One or both images not found');
        wp_send_json_error('One or both images not found');
        wp_die();
    }

    if (!function_exists('imagecreatefrompng')) {
        error_log('Composite Images: GD library is not available');
        wp_send_json_error('GD library is not available on the server');
        wp_die();
    }

    $frame_image_info = getimagesize($frame_image_path);
    $edited_image_info = getimagesize($edited_image_path);

    if (!$frame_image_info || !$edited_image_info) {
        error_log('Composite Images: Failed to get image info');
        wp_send_json_error('Failed to get image info');
        wp_die();
    }

    $frame_image_type = $frame_image_info[2];
    $edited_image_type = $edited_image_info[2];

    error_log('Frame Image Type: ' . $frame_image_type);
    error_log('Edited Image Type: ' . $edited_image_type);

    // Load the frame image
    switch ($frame_image_type) {
        case IMAGETYPE_PNG:
            $frame_image = imagecreatefrompng($frame_image_path);
            break;
        case IMAGETYPE_JPEG:
            $frame_image = imagecreatefromjpeg($frame_image_path);
            break;
        case IMAGETYPE_GIF:
            $frame_image = imagecreatefromgif($frame_image_path);
            break;
        default:
            error_log('Composite Images: Unsupported frame image type');
            wp_send_json_error('Unsupported frame image type');
            wp_die();
    }

    // Load the edited image
    switch ($edited_image_type) {
        case IMAGETYPE_PNG:
            $edited_image = imagecreatefrompng($edited_image_path);
            break;
        case IMAGETYPE_JPEG:
            $edited_image = imagecreatefromjpeg($edited_image_path);
            break;
        case IMAGETYPE_GIF:
            $edited_image = imagecreatefromgif($edited_image_path);
            break;
        default:
            error_log('Composite Images: Unsupported edited image type');
            wp_send_json_error('Unsupported edited image type');
            wp_die();
    }

    if (!$frame_image || !$edited_image) {
        error_log('Composite Images: Failed to load images');
        wp_send_json_error('Failed to load images');
        wp_die();
    }

    // Get original dimensions
    $frame_width = imagesx($frame_image);
    $frame_height = imagesy($frame_image);
    $edited_width = imagesx($edited_image);
    $edited_height = imagesy($edited_image);

    error_log('Frame Image Original Dimensions: ' . $frame_width . 'x' . $frame_height);
    error_log('Edited Image Original Dimensions: ' . $edited_width . 'x' . $edited_height);

    // Resize images to a maximum width of 1200px to improve performance
    $max_width = 1200;
    if ($frame_width > $max_width) {
        $new_frame_width = $max_width;
        $new_frame_height = (int)($frame_height * ($max_width / $frame_width));
        $resized_frame_image = imagecreatetruecolor($new_frame_width, $new_frame_height);
        imagecopyresampled(
            $resized_frame_image,
            $frame_image,
            0,
            0,
            0,
            0,
            $new_frame_width,
            $new_frame_height,
            $frame_width,
            $frame_height
        );
        imagedestroy($frame_image);
        $frame_image = $resized_frame_image;
        $frame_width = $new_frame_width;
        $frame_height = $new_frame_height;

        // Scale coordinates to the new frame dimensions
        $scale_factor = $max_width / $coordinates['frame_original_width'];
        $coordinates['x1'] *= $scale_factor;
        $coordinates['y1'] *= $scale_factor;
        $coordinates['x2'] *= $scale_factor;
        $coordinates['y2'] *= $scale_factor;

        error_log('Resized Frame Image Dimensions: ' . $frame_width . 'x' . $frame_height);
        error_log('Scaled Coordinates after Frame Resize: ' . print_r($coordinates, true));
    }

    if ($edited_width > $max_width) {
        $new_edited_width = $max_width;
        $new_edited_height = (int)($edited_height * ($max_width / $edited_width));
        $resized_edited_image = imagecreatetruecolor($new_edited_width, $new_edited_height);
        imagecopyresampled(
            $resized_edited_image,
            $edited_image,
            0,
            0,
            0,
            0,
            $new_edited_width,
            $new_edited_height,
            $edited_width,
            $edited_height
        );
        imagedestroy($edited_image);
        $edited_image = $resized_edited_image;
        $edited_width = $new_edited_width;
        $edited_height = $new_edited_height;

        error_log('Resized Edited Image Dimensions: ' . $edited_width . 'x' . $edited_height);
    }

    // Use the pre-scaled coordinates
    $x1 = (int)$coordinates['x1'];
    $y1 = (int)$coordinates['y1'];
    $x2 = (int)$coordinates['x2'];
    $y2 = (int)$coordinates['y2'];

    // Validate coordinates
    if ($x1 >= $x2 || $y1 >= $y2) {
        error_log('Composite Images: Invalid coordinates (x1 >= x2 or y1 >= y2)');
        wp_send_json_error('Invalid coordinates');
        wp_die();
    }

    // Clamp coordinates to frame image bounds
    $x1 = max(0, min($x1, $frame_width - 1));
    $y1 = max(0, min($y1, $frame_height - 1));
    $x2 = max(0, min($x2, $frame_width));
    $y2 = max(0, min($y2, $frame_height));

    error_log('Clamped Coordinates: x1=' . $x1 . ', y1=' . $y1 . ', x2=' . $x2 . ', y2=' . $y2);

    // Calculate crop dimensions
    $crop_width = $x2 - $x1;
    $crop_height = $y2 - $y1;
    error_log('Crop Dimensions: width=' . $crop_width . ', height=' . $crop_height);

    // Validate crop dimensions
    if ($crop_width <= 0 || $crop_height <= 0) {
        error_log('Composite Images: Crop dimensions are invalid (width or height <= 0)');
        wp_send_json_error('Crop dimensions are invalid');
        wp_die();
    }

    // Resize the edited image to fit the crop area
    $resized_edited_image = imagecreatetruecolor($crop_width, $crop_height);
    if (!$resized_edited_image) {
        error_log('Composite Images: Failed to create resized image resource');
        wp_send_json_error('Failed to create resized image resource');
        wp_die();
    }

    // Only enable transparency if the edited image is PNG
    if ($edited_image_type === IMAGETYPE_PNG) {
        imagealphablending($resized_edited_image, false);
        imagesavealpha($resized_edited_image, true);
        $transparent = imagecolorallocatealpha($resized_edited_image, 0, 0, 0, 127);
        imagefill($resized_edited_image, 0, 0, $transparent);
    }

    // Resize the edited image into the crop area dimensions
    $resize_result = imagecopyresampled(
        $resized_edited_image,
        $edited_image,
        0,
        0,
        0,
        0,
        $crop_width,
        $crop_height,
        $edited_width,
        $edited_height
    );
    if (!$resize_result) {
        error_log('Composite Images: Failed to resize edited image');
        wp_send_json_error('Failed to resize edited image');
        wp_die();
    }

    // Only enable transparency for the frame image if it's PNG
    if ($frame_image_type === IMAGETYPE_PNG) {
        imagealphablending($frame_image, true);
        imagesavealpha($frame_image, true);
    }

    // Composite the resized edited image onto the frame image
    $composite_result = imagecopy(
        $frame_image,
        $resized_edited_image,
        $x1,
        $y1,
        0,
        0,
        $crop_width,
        $crop_height
    );
    if (!$composite_result) {
        error_log('Composite Images: Failed to composite images at position x1=' . $x1 . ', y1=' . $y1);
        wp_send_json_error('Failed to composite images');
        wp_die();
    }

    // Save the composited image with lower quality for faster saving
    $composite_filename = 'composited-image-' . uniqid() . '.png';
    $composite_path = $upload_dir['path'] . '/' . $composite_filename;
    $save_result = imagepng($frame_image, $composite_path, 6); // Lower quality for faster saving
    if (!$save_result) {
        error_log('Composite Images: Failed to save composited image to ' . $composite_path);
        wp_send_json_error('Failed to save composited image');
        wp_die();
    }

    // Generate the URL for the composited image
    $composite_url = $upload_dir['url'] . '/' . $composite_filename;

    // Clean up
    imagedestroy($frame_image);
    imagedestroy($edited_image);
    imagedestroy($resized_edited_image);

    error_log('Composite Images: Success - Composited image URL: ' . $composite_url);

    wp_send_json_success(array(
        'message' => 'Image composited successfully',
        'composite_url' => $composite_url
    ));

    wp_die();
}


function enqueue_package_gallery_script() {
    if (!is_product()) {
        return;
    }

    $product = wc_get_product(get_the_ID());
    $is_package_product = $product && $product->get_type() === 'package';

    if ($is_package_product) {
        // Retrieve sub-product IDs from the package's meta
        $package_products = get_post_meta(get_the_ID(), '_package_products', true);
        if (!is_array($package_products)) {
            $package_products = array();
        }

        // Prepare settings for each sub-product
        $sub_product_settings = array();

        foreach ($package_products as $product_data) {
            $product_id = $product_data['product'];
            $frame_template = get_post_meta($product_id, '_axon_frame_template', true);

            $default_settings = array(
                'image_id' => 0,
                'coordinates' => array('x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 0, 'aspect_ratio' => 0),
                'frame_image_url' => ''
            );

            $settings = $frame_template ?: $default_settings;
            $settings = wp_parse_args($settings, $default_settings);

            if (!empty($settings['image_id'])) {
                $frame_image_url = wp_get_attachment_url($settings['image_id']);
                $settings['frame_image_url'] = $frame_image_url ?: '';

                $frame_image_path = get_attached_file($settings['image_id']);
                if ($frame_image_path && file_exists($frame_image_path)) {
                    $frame_image_info = getimagesize($frame_image_path);
                    if ($frame_image_info) {
                        $frame_width = $frame_image_info[0];
                        $frame_height = $frame_image_info[1];
                        error_log('Frame image dimensions for product ID ' . $product_id . ': ' . $frame_width . 'x' . $frame_height);

                        $admin_preview_width = 1200;
                        $scale_factor = $admin_preview_width / $frame_width;

                        $settings['coordinates']['x1'] = floatval($settings['coordinates']['x1']) * $scale_factor;
                        $settings['coordinates']['y1'] = floatval($settings['coordinates']['y1']) * $scale_factor;
                        $settings['coordinates']['x2'] = floatval($settings['coordinates']['x2']) * $scale_factor;
                        $settings['coordinates']['y2'] = floatval($settings['coordinates']['y2']) * $scale_factor;

                        error_log('Scaled coordinates for admin preview for product ID ' . $product_id . ': ' . print_r($settings['coordinates'], true));
                    }
                }
            } else {
                $settings['frame_image_url'] = '';
            }

            $sub_product_settings[$product_id] = $settings;
            error_log('Settings for product ID ' . $product_id . ': ' . print_r($settings, true));
        }

        // Enqueue the package script (no dependency on axon-gallery-script)
        wp_enqueue_script(
            'axon-package-gallery-script',
            plugin_dir_url(__FILE__) . 'assets/js/package-gallery-script.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Localize script with sub-product settings
        wp_localize_script(
            'axon-package-gallery-script',
            'packageAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('axon_photo_gallery_nonce'),
                'sub_product_settings' => $sub_product_settings
            )
        );

        error_log('Final sub_product_settings passed to JavaScript: ' . print_r($sub_product_settings, true));

        wp_enqueue_style(
            'axon-package-gallery-style',
            plugin_dir_url(__FILE__) . 'assets/css/package-dummy.css',
            array(),
            '1.0.0'
        );

        // Base CSS for the preview modal
        $css = '
            #preview-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 10000;
                justify-content: center;
                align-items: center;
            }
            #preview-modal-content {
                background: white;
                padding: 20px;
                border-radius: 5px;
                position: relative;
                max-width: 90%;
                max-height: 90%;
                overflow: auto;
                text-align: center;
                margin-top: 30px;
            }
            #preview-modal-close {
                position: absolute;
                top: 10px;
                right: 10px;
                cursor: pointer;
                font-size: 24px;
            }
            #preview-image {
                max-width: 100%;
                max-height: 80vh;
                height: auto;
            }
        ';

        // Add CSS to hide preset buttons for products with custom ratios
        foreach ($sub_product_settings as $product_id => $settings) {
            $coordinates = $settings['coordinates'];
            $has_frame_image = !empty($settings['frame_image_url']);
            $has_valid_coordinates = $coordinates['x1'] != 0 || $coordinates['y1'] != 0 || $coordinates['x2'] != 0 || $coordinates['y2'] != 0;
            $has_valid_aspect_ratio = floatval($coordinates['aspect_ratio']) > 0;
            $use_custom_ratios = $has_frame_image && $has_valid_coordinates && $has_valid_aspect_ratio;

            if ($use_custom_ratios) {
                $css .= '
                    /* Hide preset buttons for product ID ' . $product_id . ' */
                    div.tui-image-editor-container[data-product-id="' . $product_id . '"] ul.tui-image-editor-submenu-item li.tui-image-editor-button.preset,
                    div.tui-image-editor-container[data-product-id="' . $product_id . '"] ul.tui-image-editor-submenu-item li.tie-crop-preset-button {
                        display: none !important;
                    }
                ';
                error_log('Custom ratios are set for product ID ' . $product_id . ', hiding preset buttons via CSS');
            } else {
                error_log('No custom ratios set for product ID ' . $product_id . ', preset buttons will be visible');
            }
        }

        wp_add_inline_style('axon-package-gallery-style', $css);
        error_log('Inline CSS enqueued for axon-package-gallery-style: ' . $css);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_package_gallery_script');

function enqueue_custom_script() {
    if (!is_product()) {
        return; 
    }

    $product_id = get_the_ID();
    $product = wc_get_product($product_id);

    if ($product && $product->get_type() != 'package') {
        wp_enqueue_script(
            'axon-gallery-script',
            AXON_PLUGIN_URL . 'assets/js/gallery-script.js',
            array('jquery'),
            AXON_SCRIPTS_VERSION,
            true
        );

        wp_enqueue_style(
            'axon-gallery-style',
            AXON_PLUGIN_URL . 'assets/css/dummy.css',
            array(),
            AXON_SCRIPTS_VERSION
        );

        $frame_template_settings = get_post_meta($product_id, '_axon_frame_template', true);
        $default_settings = array(
            'image_id' => 0,
            'coordinates' => array('x1' => 0.0, 'y1' => 0.0, 'x2' => 0.0, 'y2' => 0.0, 'aspect_ratio' => 0),
            'frame_image_url' => ''
        );

        if (!$frame_template_settings || !is_array($frame_template_settings)) {
            $frame_template_settings = $default_settings;
        } else {
            $frame_template_settings = wp_parse_args($frame_template_settings, $default_settings);
            if (!empty($frame_template_settings['image_id'])) {
                $frame_image_url = wp_get_attachment_url($frame_template_settings['image_id']);
                $frame_template_settings['frame_image_url'] = $frame_image_url ?: '';

                $frame_image_path = get_attached_file($frame_template_settings['image_id']);
                if ($frame_image_path && file_exists($frame_image_path)) {
                    $frame_image_info = getimagesize($frame_image_path);
                    if ($frame_image_info) {
                        $frame_width = $frame_image_info[0];
                        $frame_height = $frame_image_info[1];
                        error_log('Frame image dimensions in enqueue_custom_script: ' . $frame_width . 'x' . $frame_height);

                        $admin_preview_width = 1200;
                        $scale_factor = $admin_preview_width / $frame_width;

                        $frame_template_settings['coordinates']['x1'] = floatval($frame_template_settings['coordinates']['x1']) * $scale_factor;
                        $frame_template_settings['coordinates']['y1'] = floatval($frame_template_settings['coordinates']['y1']) * $scale_factor;
                        $frame_template_settings['coordinates']['x2'] = floatval($frame_template_settings['coordinates']['x2']) * $scale_factor;
                        $frame_template_settings['coordinates']['y2'] = floatval($frame_template_settings['coordinates']['y2']) * $scale_factor;

                        error_log('Scaled coordinates for admin preview: ' . print_r($frame_template_settings['coordinates'], true));
                    }
                }
            } else {
                $frame_template_settings['frame_image_url'] = '';
            }
        }

        error_log('Frame template settings: ' . print_r($frame_template_settings, true));

        wp_localize_script(
            'axon-gallery-script',
            'myAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('axon_photo_gallery_nonce'),
                'frame_template' => $frame_template_settings
            )
        );

        $has_frame_image = !empty($frame_template_settings['frame_image_url']);
        $has_valid_coordinates = $frame_template_settings['coordinates']['x1'] != 0 || 
                                $frame_template_settings['coordinates']['y1'] != 0 || 
                                $frame_template_settings['coordinates']['x2'] != 0 || 
                                $frame_template_settings['coordinates']['y2'] != 0;
        $has_valid_aspect_ratio = floatval($frame_template_settings['coordinates']['aspect_ratio']) > 0;
        $use_custom_ratios = $has_frame_image && $has_valid_coordinates && $has_valid_aspect_ratio;

        $css = '
            #preview-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 10000;
                justify-content: center;
                align-items: center;
            }
            #preview-modal-content {
                background: white;
                padding: 20px;
                border-radius: 5px;
                position: relative;
                max-width: 90%;
                max-height: 90%;
                overflow: auto;
                text-align: center;
                margin-top: 30px;
            }
            #preview-modal-close {
                position: absolute;
                top: 10px;
                right: 10px;
                cursor: pointer;
                font-size: 24px;
            }
            #preview-image {
                max-width: 100%;
                max-height: 80vh;
                height: auto;
            }
        ';

        if ($use_custom_ratios) {
            $css .= '
                div.tui-image-editor-container ul.tui-image-editor-submenu-item li.tui-image-editor-button.preset,
                div.tui-image-editor-container ul.tui-image-editor-submenu-item li.tie-crop-preset-button {
                    display: none !important;
                }
            ';
            error_log('Custom ratios are set, hiding preset buttons via CSS');
        } else {
            error_log('No custom ratios set, preset buttons will be visible');
        }

        wp_add_inline_style('axon-gallery-style', $css);
        error_log('Inline CSS enqueued for axon-gallery-style: ' . $css);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_script');