<?php
/**
 * Plugin Name: Axon Photo Gallery
 * Description: Plugin to create Photo Galleries for clients, Easy Digital downloading, Integrated with woocommerce for gallery Products [my_photo_galleries].
 * Version: 3.6
 * Author: Axon Technologies
 * Author URI: https://axontech.pk
 * License: GPL2
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

define('AXON_PLUGIN_VERSION', '3.6.0');
define('AXON_SCRIPTS_VERSION', '3.6.0.0');
define('AXON_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AXON_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Axon Photo Gallery requires WooCommerce to be installed and activated for certain features. Please install and activate WooCommerce.</p></div>';
    });
} else {
    if (is_plugin_active('woocommerce/woocommerce.php')) {
            add_action('init', 'register_package_product_type');
    }
}

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
        'rewrite'            => array('slug' => 'photo-gallery'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'supports'           => array('title', 'thumbnail'),
        'menu_icon'          => 'dashicons-format-gallery',
    );

    register_post_type('photo_gallery', $args);
}
add_action('init', 'create_photo_gallery_cpt');

function axon_enqueue_admin_scripts($hook) {
    wp_enqueue_style('gallery-style', plugin_dir_url(__FILE__) . '/assets/css/gallery-style.css');
    if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], null, true);
    wp_enqueue_style('select2-style', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
}
add_action('admin_enqueue_scripts', 'axon_enqueue_admin_scripts');

function my_plugin_enqueue_styles() {
    wp_enqueue_style('gallery-style', plugin_dir_url(__FILE__) . '/assets/css/gallery-style.css', [], AXON_SCRIPTS_VERSION);

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
}
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_styles');

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
                    tokenSeparators: [","],
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
                        <button class="button del-btn remove-gallery-image">🗑</button>
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
                        multiple: true
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
                                    <button class="button del-btn remove-gallery-image">🗑</button>
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
            } elseif (filter_var($user, FILTER_VALIDATE_EMAIL)) {
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

function axon_gallery_shortcode($atts) {
    $atts = shortcode_atts(['id' => ''], $atts, 'axon_gallery');
    if (empty($atts['id'])) return 'Invalid Photo Gallery';

    $allowed_users = get_post_meta($atts['id'], 'axon_allowed_users', true) ?: [];
    if (!in_array(get_current_user_id(), $allowed_users)) return 'You are not allowed to access this Photo Gallery.';

    $gallery_images = get_post_meta($atts['id'], 'axon_gallery_images', true) ?: [];
    if (empty($gallery_images)) return 'There are no Images in this Photo Gallery.';

    $output = '<div class="image-mosaic">';

    foreach ($gallery_images as $index => $image_id) {
        $image_url = wp_get_attachment_url($image_id);
        $image_sizes = wp_get_attachment_metadata($image_id)['sizes'];

        $card_class = 'card';
        if ($index % 3 == 0) {
            $card_class .= ' card-wide';
        }
        if ($index % 4 == 0) {
            $card_class .= ' card-tall';
        }

        $output .= '<div class="' . esc_attr($card_class) . '" style="background-image: url(' . esc_url($image_url) . ')">';
        $output .= '<input type="checkbox" class="axon-image-checkbox" data-image-id="' . esc_attr($image_id) . '" style=" top: 10px; right: 10px;" />';
        $output .= '</div>';
    }

    $output .= '</div>';

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

function axon_download_image() {
    if (isset($_GET['image_id']) && isset($_GET['size'])) {
        $image_id = intval($_GET['image_id']);
        $size = sanitize_text_field($_GET['size']);

        $image_path = get_attached_file($image_id);
        $metadata = wp_get_attachment_metadata($image_id);

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
    $product = wc_get_product($post->ID);
    $product_type = $product->get_type();
    if (is_product() && $product_type != "package") {
        $user_id = get_current_user_id();

        $gallery_option = get_post_meta($post->ID, '_gallery_option', true);
        $max_images = get_post_meta($post->ID, '_max_images', true);

        if (empty($gallery_option) || $gallery_option === 'dont_show') {
            return;
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

            echo '<button id="close-modal">Done</button>';
        } else {
            echo '<p>No galleries available login to see your allowed galleries for selection.</p>';
        }
        echo '</div></div>';
        echo '<div class="image-selection-container" data-product="' . $post->ID . '">
            <input type="hidden" class="selected-image-ids" data-galleryoption="' . esc_attr($gallery_option) . '" data-max_images="' . esc_attr($max_images) . '" name="selected_image_ids[]" value="">
            <div class="selected-images-container"></div>
            <button class="view-gallery-btn" type="button">Select Photos (0)</button>
        </div>';

        echo '<div id="editor-modal" class="modal fade" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">
            <div class="modal-dialog modal-lg" style="position: relative; margin: 50px auto; max-width: 800px; background: #fff; padding: 20px;">
                <div class="modal-content" style="width:auto;">
                    <div class="modal-header" style="padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between;">
                        <h5 style="margin: 0; font-size: 18px;">Image Editor</h5>
                        <button id="close-editor-modal" class="close" style="background: none; border: none; font-size: 24px; cursor: pointer;">×</button>
                    </div>
                    <div class="modal-body" style="padding: 20px;">
                        <div id="tui-image-editor" style="width: 95vw; height: 500px;"></div>
                    </div>
                </div>
            </div>
        </div>';

        // Add composite image preview
        echo '<div class="product-composite-preview-container">';
        echo '<img id="product-composite-preview" src="" style="max-width: 100%; height: auto; display: none;" />';
        echo '</div>';
    }
}
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
                } else {
                    jQuery('div[data-block-name="woocommerce/add-to-cart-form"] .quantity').val(jQuery('div[data-block-name="woocommerce/add-to-cart-form"] .quantity').val()-1);
                }
                console.log("changes in normal single");
            });
        </script>
        <?php
    }
}
add_action('woocommerce_after_add_to_cart_quantity', 'hide_quantity');

function custom_woocommerce_quantity_input_args($args, $product) {
    $gallery_option = get_post_meta($product->get_id(), '_gallery_option', true);
    if (!empty($gallery_option) && $gallery_option === 'single_image') {
        $args['readonly'] = true;
        ?>
        <script>
            jQuery(document).ready(function($) {
                $(document).on('singleQtyChange', function(e, count) {
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
    return $args;
}
add_filter('woocommerce_quantity_input_args', 'custom_woocommerce_quantity_input_args', 10, 2);

function add_gallery_metabox() {
    add_meta_box(
        'gallery_metabox',
        'Gallery Settings',
        'gallery_metabox_callback',
        'product',
        'side',
        'high'
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

    if (isset($_POST['max_images'])) {
        if ($_POST['gallery_option'] == "single_image") {
            $_POST['max_images'] = -1;
        }
        update_post_meta($post_id, '_max_images', intval($_POST['max_images']));
    }

    if ('product' === get_post_type($post_id)) {
        $product = wc_get_product($post_id);
        if ($product->is_type('variable')) {
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
add_action('save_post', 'save_gallery_metabox_data');

function save_selected_gallery_images_in_cart($cart_item_data, $product_id) {
    if (isset($_POST['selected_gallery_images'])) {
        $selected_images = json_decode(stripslashes($_POST['selected_gallery_images']), true);
        if (!empty($selected_images)) {
            $cart_item_data['selected_gallery_images'] = $selected_images;
        }
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'save_selected_gallery_images_in_cart', 10, 2);

function save_selected_gallery_images_in_order_meta($item, $cart_item_key, $values, $order) {
    $cart_item = $values;
    if (isset($cart_item['selected_gallery_images'])) {
        $selected_images = $cart_item['selected_gallery_images'];
        $data = "";
        if ($cart_item["data"]->get_type() == "package") {
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
                    $product_price = wc_price($product_obj->get_price() * (int)$quantity);

                    $data .= '<li class="package-product-item" style="text-align: left;">';
                    $data .= '<span class="product-title">' . $product_title . '</span>';
                    $data .= '<span class="product-quantity"> x ' . $quantity . '</span>';
                    $data .= '<span class="product-price"> - ' . $product_price . '</span><ul style="margin: 0; padding: 0;list-style: none !important;" type="none">';
                    foreach ($selected_images[$product_id] as $group) {
                        $data .= "<li style='padding: 2px 0;' type='none'><div class='cart-images' style='display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 5px;width: 100%;'>";
                        foreach ($group as $image_id) {
                            $image_url = wp_get_attachment_image_src($image_id, 'thumbnail')[0];
                            $data .= '<img src="' . esc_url($image_url) . '" alt="Selected Image" style="width: 70px; height: 70px; object-fit: cover;" />';
                        }
                        $data .= "</div></li>";
                    }
                    $data .= '</ul></li>';
                }
            }
            $data .= '</ol>';
        } else {
            foreach ($selected_images[$cart_item["product_id"]] as $group) {
                $data .= "<div class='cart-images' style='display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 5px;width: 100%;float: left;'>";
                foreach ($group as $image_id) {
                    $image_url = wp_get_attachment_image_src($image_id, 'thumbnail')[0];
                    $data .= '<img src="' . esc_url($image_url) . '" alt="Selected Image" style="width: 70px; height: 70px; object-fit: cover;" />';
                }
                $data .= "</div>";
            }
        }
        $item->add_meta_data('Selected Images', $data, true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'save_selected_gallery_images_in_order_meta', 10, 4);

function display_selected_gallery_images_on_cart($item_data, $cart_item) {
    $cart_item = $cart_item;
    if (isset($cart_item['selected_gallery_images'])) {
        $selected_images = $cart_item['selected_gallery_images'];
        if ($cart_item["data"]->get_type() == "package") {
            $package_products = $cart_item["data"]->get_meta("_package_products");
            echo '<ol class="package-products-list" style="margin-left: 1em; padding:0;width: 100%;float: left;">';
            foreach ($package_products as $index => $product) {
                $product_id = $product['product'];
                $quantity = $product['quantity'];
                $product_obj = wc_get_product($product_id);

                if ($product_obj) {
                    $product_thumbnail = $product_obj->get_image();
                    $product_title = $product_obj->get_title();
                    $product_price = wc_price($product_obj->get_price() * (int)$quantity);

                    echo '<li class="package-product-item" style="text-align: left;">';
                    echo '<span class="product-title">' . $product_title . '</span>';
                    echo '<span class="product-quantity"> x ' . $quantity . '</span>';
                    echo '<span class="product-price"> - ' . $product_price . '</span><ul style="margin: 0; padding: 0;" type="none">';
                    foreach ($selected_images[$product_id] as $group) {
                        echo "<li style='padding: 2px 0;' type='none'><div class='cart-images' style='display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 5px;width: 100%;'>";
                        foreach ($group as $image_id) {
                            $image_url = wp_get_attachment_image_src($image_id, 'thumbnail')[0];
                            echo '<img src="' . esc_url($image_url) . '" alt="Selected Image" style="width: 70px; height: 70px; object-fit: cover;" />';
                        }
                        echo "</div></li>";
                    }
                    echo '</ul></li>';
                }
            }
            echo '</ol>';
        } else {
            $value = "";
            foreach ($selected_images[$cart_item["product_id"]] as $group) {
                $value .= "<div class='cart-images' style='display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 5px;width: 100%;float: left;'>";
                foreach ($group as $image_id) {
                    $image_url = wp_get_attachment_image_src($image_id, 'thumbnail')[0];
                    $value .= '<img src="' . esc_url($image_url) . '" alt="Selected Image" style="width: 70px; height: 70px; object-fit: cover;" />';
                }
                $value .= "</div>";
            }
            $item_data[] = array(
                'key'   => 'Selected Image',
                'value' => $value,
                'display' => '',
            );
        }
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_selected_gallery_images_on_cart', 10, 2);

function check_selected_images_add_to_cart_validation($passed, $product_id, $quantity, $variation_id = null) {
    $gallery_option = get_post_meta($product_id, '_gallery_option', true);
    $product = wc_get_product($product_id);
    $product_type = $product->get_type();
    $_POST['selected_gallery_images'] = stripslashes($_POST['selected_gallery_images']);
    if ($product_type == "package") {
        foreach (json_decode($_POST['selected_gallery_images']) as $images) {
            foreach ($images as $sets) {
                if (is_array($sets) && empty($sets)) {
                    $passed = false;
                    wc_add_notice('All Galleries Should Have Atleast 1 Images Selected.', 'error');
                    break 2;
                }
            }
        }
    }
    if (empty($gallery_option) || $gallery_option === 'dont_show') {
        return $passed;
    }
    foreach (json_decode($_POST['selected_gallery_images']) as $images) {
        foreach ($images as $sets) {
            if (is_array($sets) && empty($sets)) {
                $passed = false;
                wc_add_notice('Atleast 1 Images is required to be selected.', 'error');
                break 2;
            }
        }
    }
    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'check_selected_images_add_to_cart_validation', 10, 4);

function register_package_product_type() {
    #[AllowDynamicProperties]
    class WC_Product_Package extends WC_Product {
        public function __construct($product) {
            $this->product_type = 'package';
            parent::__construct($product);
        }
        public function get_type() {
            return 'package';
        }
    }
}
function add_package_product_type($types) {
    $types['package'] = 'Package';
    return $types;
}
add_filter('product_type_selector', 'add_package_product_type');
add_filter('woocommerce_product_class', 'bbloomer_woocommerce_product_class', 10, 2);

function bbloomer_woocommerce_product_class($classname, $product_type) {
    if ($product_type == 'package') {
        $classname = 'WC_Product_Package';
    }
    return $classname;
}

function add_package_product_data_tab($tabs) {
    $tabs['package_settings'] = array(
        'label'    => __('Package Settings', 'woocommerce'),
        'target'   => 'package_settings_product_data',
        'class'    => array('show_if_package'),
        'priority' => 21,
    );

    $tabs['linked_product']['class'][] = 'hide_if_package';
    $tabs['attribute']['class'][] = 'hide_if_package';
    $tabs['advanced']['class'][] = 'hide_if_package';
    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'add_package_product_data_tab', 10, 1);

function add_package_product_data_panel() {
    global $post;

    ?>
    <div id="package_settings_product_data" class="panel woocommerce_options_panel">
        <div class="options_group">
            <h3><?php _e('Package Products', 'woocommerce'); ?></h3>
            <div id="product-package-repeater" class="product-package-repeater">
                <?php
                $package_products = get_post_meta($post->ID, '_package_products', true);
                $products = wc_get_products(array(
                    'status' => 'publish',
                    'limit' => -1,
                    'type' => array('simple', 'grouped', 'external', 'variation'),
                ));
                $products = array_filter($products, function($product) {
                    return !$product->is_type('variable');
                });
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
    if ($product_object && 'package' === $product_object->get_type()) {
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

        update_post_meta($post_id, '_package_products', $package_products);
    } else {
        delete_post_meta($post_id, '_package_products');
    }
}
add_action('woocommerce_process_product_meta', 'save_package_product_data');

function display_package_product_summary() {
    global $post;
    $package_products = get_post_meta($post->ID, '_package_products', true);
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

            echo '<button id="close-modal">Done</button>';
        } else {
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
                    for ($i = 1; $i <= $quantity; $i++) {
                        echo '<div class="image-selection-container" data-product="' . $product_id . '">
                            <input type="hidden" class="selected-image-ids" data-galleryoption="' . esc_attr($gallery_option) . '" data-max_images="' . esc_attr($max_images) . '" name="selected_image_ids[]" value="">
                            <div class="selected-images-container"></div>
                            <button class="view-gallery-btn" type="button">Select Photos (0)</button>
                        </div>';
                    }
                }
                if ($gallery_option == 'single_image') {
                    echo '<div class="image-selection-container" data-product="' . $product_id . '">
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

        echo '<div id="editor-modal" class="modal fade" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000;">
            <div class="modal-dialog modal-lg" style="position: relative; margin: 50px auto; max-width: 800px; background: #fff; padding: 20px;">
                <div class="modal-content" style="width:auto;">
                    <div class="modal-header" style="padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between;">
                        <h5 style="margin: 0; font-size: 18px;">Image Editor</h5>
                        <button id="close-editor-modal" class="close" style="background: none; border: none; font-size: 24px; cursor: pointer;">×</button>
                    </div>
                    <div class="modal-body" style="padding: 20px;">
                        <div id="tui-image-editor" style="width: 95vw; height: 500px;"></div>
                    </div>
                </div>
            </div>
        </div>';

        global $product;
        if (!$product->is_purchasable()) {
            return;
        }

        echo wc_get_stock_html($product);

        if ($product->is_in_stock()) : ?>

            <?php do_action('woocommerce_before_add_to_cart_form'); ?>

            <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
                <?php do_action('woocommerce_before_add_to_cart_button'); ?>

                <?php
                do_action('woocommerce_before_add_to_cart_quantity');

                woocommerce_quantity_input(
                    array(
                        'min_value'   => apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product),
                        'max_value'   => apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product),
                        'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(),
                    )
                );

                do_action('woocommerce_after_add_to_cart_quantity');
                ?>

                <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>

                <?php do_action('woocommerce_after_add_to_cart_button'); ?>
            </form>

            <?php do_action('woocommerce_after_add_to_cart_form'); ?>

        <?php endif;
    }
}
add_action('woocommerce_single_product_summary', 'display_package_product_summary', 25);

function add_watermark_to_image($image_url, $attachment_id) {
    return $image_url;
}

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
        return get_permalink($product->get_id());
    }

    if (get_post_meta($product->get_id(), '_gallery_option', true) == 'single_image' || get_post_meta($product->get_id(), '_gallery_option', true) == 'multiple_images') {
        return get_permalink($product->get_id());
    }

    return $url;
}
add_filter('woocommerce_product_add_to_cart_url', 'custom_woocommerce_add_to_cart_redirect', 10, 2);

function custom_woocommerce_loop_add_to_cart_args($args, $product) {
    if ((is_shop() || is_product_category()) && $product->get_type() === 'package') {
        $args['class'] = str_replace('ajax_add_to_cart', "", $args['class']);
    }
    if (get_post_meta($product->get_id(), '_gallery_option', true) == 'single_image' || get_post_meta($product->get_id(), '_gallery_option', true) == 'multiple_images') {
        $args['class'] = str_replace('ajax_add_to_cart', "", $args['class']);
    }
    return $args;
}
add_filter('woocommerce_loop_add_to_cart_args', 'custom_woocommerce_loop_add_to_cart_args', 10, 2);

add_filter('woocommerce_account_menu_items', 'add_gallery_menu_item_to_account', 10, 1);
function add_gallery_menu_item_to_account($menu_items) {
    $menu_items['photo_gallery'] = __('My Galleries', 'woocommerce');
    return $menu_items;
}

add_shortcode('my_photo_galleries', 'display_photo_gallery_endpoint_content');

function display_photo_gallery_endpoint_content() {
    $current_user_id = get_current_user_id();

    $args = array(
        'post_type'      => 'photo_gallery',
        'posts_per_page' => -1,
    );

    $galleries = new WP_Query($args);
    ob_start();
    if ($galleries->have_posts()) {
        echo '<div class="my-account-photo-gallery">';
        echo '<h2>Your Photo Galleries</h2>';
        echo '<div class="gallery-container">';

        while ($galleries->have_posts()) : $galleries->the_post();
            if (!in_array(get_current_user_id(), (array)get_post_meta(get_the_ID(), 'axon_allowed_users', true))) continue;

            $image_ids = get_post_meta(get_the_ID(), 'axon_gallery_images', true);
            $image_count = count($image_ids);
            $created_date = get_the_date();

            if (!empty($image_ids)) :
                ?>
                <div class="gallery-item">
                    <div class="gallery-header">
                        <h3><?php the_title(); ?></h3>
                        <div class="meta-info">
                            <span class="date"><?php echo $created_date; ?></span>
                            <span class="image-count"><?php echo $image_count; ?> Images</span>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="show-more">See All Photos</a>
                    </div>

                    <div class="gallery-images">
                        <?php
                        $image_count = 0;
                        foreach ($image_ids as $image_id) {
                            if ($image_count < 3) {
                                $image_url = wp_get_attachment_url($image_id);
                                if ($image_url) :
                                    ?>
                                    <div class="gallery-thumbnail" style="background-image: url('<?php echo esc_url($image_url); ?>');"></div>
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

        echo '</div>';
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    } else {
        return '<p>No galleries found in your account.</p>';
    }
}
add_action('woocommerce_account_photo_gallery_endpoint', function() {
    echo do_shortcode('[my_photo_galleries]');
});

add_action('init', 'add_photo_gallery_endpoint');
function add_photo_gallery_endpoint() {
    add_rewrite_endpoint('photo_gallery', EP_ROOT | EP_PAGES);
}

add_action('woocommerce_flush_rewrite_rules', 'flush_rewrite_rules_on_gallery_endpoint');
function flush_rewrite_rules_on_gallery_endpoint() {
    flush_rewrite_rules();
}

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
        'side',
        'high'
    );

    $gallery_option = get_post_meta($post->ID, '_gallery_option', true);

    if ($gallery_option === 'single_image' || $gallery_option === 'multiple_images') {
        add_meta_box(
            'axon_frame_template_metabox',
            'Frame Template Settings',
            'axon_frame_template_metabox_callback',
            'product',
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'axon_add_gallery_metaboxes');

function axon_frame_template_metabox_callback($post) {
    wp_nonce_field('axon_frame_template_metabox_nonce', 'axon_frame_template_nonce');
    $frame_template = get_post_meta($post->ID, '_axon_frame_template', true);
    if (!$frame_template || !is_array($frame_template)) {
        $frame_template = array(
            'image_id' => 0,
            'coordinates' => array()
        );
    }

    $gallery_option = get_post_meta($post->ID, '_gallery_option', true);
    echo '<input type="hidden" id="gallery-option" value="' . esc_attr($gallery_option) . '" />';

    ?>
    <p>
        <label for="frame-template-image-id"><strong>Frame Template Image:</strong></label><br />
        <input type="hidden" name="axon_frame_template[image_id]" id="frame-template-image-id" value="<?php echo esc_attr($frame_template['image_id']); ?>" />
        <button type="button" class="button" id="upload-frame-template-image">Select Image</button>
        <p class="description">Select an image from the media library to use as the frame template.</p>
    </p>

    <div id="frame-template-preview">
        <h2>Preview and Select Crop Area(s)</h2>
        <div id="image-preview-container">
            <?php
            $image_id = $frame_template['image_id'];
            if ($image_id) {
                $image_url = wp_get_attachment_url($image_id);
                echo $image_url ? '<img id="frame-template-image" src="' . esc_url($image_url) . '" style="max-width: 100%; height: auto;" />' : '<p>No image selected.</p>';
            } else {
                echo '<p>No image selected.</p>';
            }
            ?>
        </div>
    </div>

    <?php
    if ($gallery_option === 'multiple_images') {
        $coordinates_list = isset($frame_template['coordinates']) ? array_values($frame_template['coordinates']) : array();
        ?>
        <div id="crop-areas-container">
            <?php
            foreach ($coordinates_list as $index => $coords) {
                if (!is_array($coords) || !isset($coords['x1'], $coords['y1'], $coords['x2'], $coords['y2'], $coords['aspect_ratio'])) {
                    continue;
                }
                ?>
                <div class="crop-area" data-index="<?php echo esc_attr($index); ?>">
                    <p><strong>Crop Area <?php echo intval($index + 1); ?></strong></p>
                    <input type="hidden" name="axon_frame_template[coordinates][<?php echo $index; ?>][x1]" value="<?php echo esc_attr($coords['x1']); ?>" />
                    <input type="hidden" name="axon_frame_template[coordinates][<?php echo $index; ?>][y1]" value="<?php echo esc_attr($coords['y1']); ?>" />
                    <input type="hidden" name="axon_frame_template[coordinates][<?php echo $index; ?>][x2]" value="<?php echo esc_attr($coords['x2']); ?>" />
                    <input type="hidden" name="axon_frame_template[coordinates][<?php echo $index; ?>][y2]" value="<?php echo esc_attr($coords['y2']); ?>" />
                    <input type="hidden" name="axon_frame_template[coordinates][<?php echo $index; ?>][aspect_ratio]" value="<?php echo esc_attr($coords['aspect_ratio']); ?>" />
                    <button type="button" class="remove-crop-area">Remove</button>
                </div>
                <?php
            }
            ?>
        </div>
        <button type="button" id="add-crop-area">Add Crop Area</button>
        <button type="button" id="save-crop-area" style="display:none;">Save Crop Area</button>
        <p class="description">Click "Add Crop Area" to define a new crop area on the image. Save each area to add it to the list.</p>
        <?php
    } else {
        $coordinates = !empty($frame_template['coordinates']) && is_array($frame_template['coordinates']) ? $frame_template['coordinates'][0] : array('x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 0, 'aspect_ratio' => 0);
        ?>
        <p><strong>Top-Left (x1, y1):</strong></p>
        <input type="number" step="0.1" name="axon_frame_template[coordinates][0][x1]" id="coord-x1" value="<?php echo esc_attr($coordinates['x1']); ?>" readonly />
        <input type="number" step="0.1" name="axon_frame_template[coordinates][0][y1]" id="coord-y1" value="<?php echo esc_attr($coordinates['y1']); ?>" readonly />
        <p><strong>Bottom-Right (x2, y2):</strong></p>
        <input type="number" step="0.1" name="axon_frame_template[coordinates][0][x2]" id="coord-x2" value="<?php echo esc_attr($coordinates['x2']); ?>" readonly />
        <input type="number" step="0.1" name="axon_frame_template[coordinates][0][y2]" id="coord-y2" value="<?php echo esc_attr($coordinates['y2']); ?>" readonly />
        <input type="hidden" name="axon_frame_template[coordinates][0][aspect_ratio]" id="coord-aspect-ratio" value="<?php echo esc_attr($coordinates['aspect_ratio']); ?>" />
        <p class="description">Select an area on the image preview to set the crop coordinates.</p>
        <?php
    }
}

function axon_save_frame_template_metabox($post_id) {
    if (!isset($_POST['axon_frame_template_nonce']) || !wp_verify_nonce($_POST['axon_frame_template_nonce'], 'axon_frame_template_metabox_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['axon_frame_template'])) {
        $frame_template = $_POST['axon_frame_template'];
        $sanitized_data = array();
        $sanitized_data['image_id'] = isset($frame_template['image_id']) ? absint($frame_template['image_id']) : 0;

        if (isset($frame_template['coordinates']) && is_array($frame_template['coordinates'])) {
            $sanitized_coordinates = array();
            foreach ($frame_template['coordinates'] as $coord_set) {
                if (is_array($coord_set) && isset($coord_set['x1'], $coord_set['y1'], $coord_set['x2'], $coord_set['y2'], $coord_set['aspect_ratio'])) {
                    $sanitized_coordinates[] = array(
                        'x1' => floatval($coord_set['x1']),
                        'y1' => floatval($coord_set['y1']),
                        'x2' => floatval($coord_set['x2']),
                        'y2' => floatval($coord_set['y2']),
                        'aspect_ratio' => floatval($coord_set['aspect_ratio'])
                    );
                }
            }
            $sanitized_data['coordinates'] = $sanitized_coordinates;
        } else {
            $sanitized_data['coordinates'] = array();
        }

        update_post_meta($post_id, '_axon_frame_template', $sanitized_data);
    }
}
add_action('save_post', 'axon_save_frame_template_metabox');

function axon_enqueue_frame_template_scripts($hook) {
    global $post;
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    if (!$post || $post->post_type !== 'product') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css', array(), '1.5.12');
    wp_enqueue_script('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js', array('jquery'), '1.5.12', true);

    wp_enqueue_script(
        'axon-frame-template-script',
        AXON_PLUGIN_URL . 'assets/js/frame-template.js',
        array('jquery', 'cropper-js'),
        AXON_SCRIPTS_VERSION,
        true
    );

    error_log('Enqueued frame-template.js for post ID: ' . $post->ID);
}
add_action('admin_enqueue_scripts', 'axon_enqueue_frame_template_scripts');

// AJAX handler to composite images
function composite_images_callback() {
    check_ajax_referer('axon_photo_gallery_nonce', 'nonce');

    $response = array('success' => false);

    try {
        // Handle both single and multiple image cases
        $edited_image_url = isset($_POST['edited_image_url']) ? esc_url_raw($_POST['edited_image_url']) : '';
        $edited_image_urls = isset($_POST['edited_image_urls']) && is_array($_POST['edited_image_urls']) ? array_map('esc_url_raw', $_POST['edited_image_urls']) : ($edited_image_url ? [$edited_image_url] : []);
        $coordinates = isset($_POST['coordinates']) ? (array) $_POST['coordinates'] : [];
        $frame_image_url = isset($_POST['frame_image_url']) ? esc_url_raw($_POST['frame_image_url']) : '';

        if (empty($edited_image_urls) || empty($coordinates) || empty($frame_image_url)) {
            $response['data'] = 'Missing required parameters: edited_image_urls=' . print_r($edited_image_urls, true) . ', coordinates=' . print_r($coordinates, true) . ', frame_image_url=' . $frame_image_url;
            wp_send_json_error($response);
            wp_die();
        }

        $upload_dir = wp_upload_dir();
        $frame_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $frame_image_url);
        if (!file_exists($frame_path)) {
            $response['data'] = 'Frame image not found: ' . $frame_path;
            wp_send_json_error($response);
            wp_die();
        }

        // Determine frame image type and load
        $frame_type = exif_imagetype($frame_path);
        $frame = false;
        switch ($frame_type) {
            case IMAGETYPE_JPEG:
                $frame = imagecreatefromjpeg($frame_path);
                break;
            case IMAGETYPE_PNG:
                $frame = imagecreatefrompng($frame_path);
                break;
            default:
                $response['data'] = 'Unsupported frame image format: ' . $frame_path;
                wp_send_json_error($response);
                wp_die();
        }
        if ($frame === false) {
            $response['data'] = 'Failed to load frame image: ' . $frame_path;
            wp_send_json_error($response);
            wp_die();
        }

        $frame_width = imagesx($frame);
        $frame_height = imagesy($frame);

        // Process each edited image
        $edited_images = [];
        foreach ($edited_image_urls as $index => $url) {
            $edited_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
            if (!file_exists($edited_path)) {
                error_log('Edited image not found: ' . $edited_path);
                continue;
            }

            $edited_type = exif_imagetype($edited_path);
            $edited = false;
            switch ($edited_type) {
                case IMAGETYPE_PNG:
                    $edited = imagecreatefrompng($edited_path);
                    break;
                case IMAGETYPE_JPEG:
                    $edited = imagecreatefromjpeg($edited_path);
                    break;
                default:
                    error_log('Unsupported edited image format: ' . $edited_path);
                    continue 2;
            }
            if ($edited === false) {
                error_log('Failed to load edited image: ' . $edited_path);
                continue;
            }

            $edited_width = imagesx($edited);
            $edited_height = imagesy($edited);

            // Validate and normalize coordinates
            $coord = isset($coordinates[$index]) && is_array($coordinates[$index]) ? $coordinates[$index] : (is_array($coordinates) && !isset($coordinates[0]) ? $coordinates : []);
            $coord = array_merge(['x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 0, 'aspect_ratio' => $edited_width / $edited_height, 'frame_original_width' => $frame_width, 'frame_original_height' => $frame_height], $coord);
            $x = floatval($coord['x1']);
            $y = floatval($coord['y1']);
            $width = floatval($coord['x2'] - $coord['x1']);
            $height = floatval($coord['y2'] - $coord['y1']);

            // Calculate scaling factor based on frame's original width
            $frame_original_width = floatval($coord['frame_original_width']);
            $scale_factor = $frame_original_width > 0 ? $frame_width / $frame_original_width : 1;
            $x *= $scale_factor;
            $y *= $scale_factor;
            $width *= $scale_factor;
            $height *= $scale_factor;

            // Handle invalid dimensions by using aspect ratio to compute a centered area
            if ($width <= 0 || $height <= 0) {
                error_log('Invalid coordinates for image ' . $index . ': width=' . $width . ', height=' . $height . '. Computing fallback dimensions.');
                $aspect_ratio = floatval($coord['aspect_ratio']);
                if ($aspect_ratio <= 0) $aspect_ratio = $edited_width / $edited_height;

                // Use 80% of frame dimensions as a fallback
                if ($frame_width / $frame_height > $aspect_ratio) {
                    $height = $frame_height * 0.8;
                    $width = $height * $aspect_ratio;
                } else {
                    $width = $frame_width * 0.8;
                    $height = $width / $aspect_ratio;
                }

                $x = ($frame_width - $width) / 2;
                $y = ($frame_height - $height) / 2;
            }

            // Clamp to frame boundaries
            $x = max(0, min($x, $frame_width - $width));
            $y = max(0, min($y, $frame_height - $height));
            $width = min($width, $frame_width - $x);
            $height = min($height, $frame_height - $y);

            // Ensure the edited image fits within the coordinates while maintaining aspect ratio
            $target_aspect_ratio = $width / $height;
            $edited_aspect_ratio = $edited_width / $edited_height;
            $resized_width = $width;
            $resized_height = $height;

            if ($edited_aspect_ratio != $target_aspect_ratio) {
                if ($edited_aspect_ratio > $target_aspect_ratio) {
                    // Edited image is wider: fit to height, adjust width
                    $resized_width = $height * $edited_aspect_ratio;
                    $x += ($width - $resized_width) / 2; // Center horizontally
                } else {
                    // Edited image is taller: fit to width, adjust height
                    $resized_height = $width / $edited_aspect_ratio;
                    $y += ($height - $resized_height) / 2; // Center vertically
                }
            }

            // Resize edited image to fit coordinates
            $resized = imagecreatetruecolor($resized_width, $resized_height);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $edited, 0, 0, 0, 0, $resized_width, $resized_height, $edited_width, $edited_height);
            imagedestroy($edited);

            // Composite the image onto the frame at the exact coordinates
            imagecopy($frame, $resized, $x, $y, 0, 0, $resized_width, $resized_height);
            imagedestroy($resized);

            $edited_images[] = $edited_path;
            error_log('Composited image ' . $index . ': x=' . $x . ', y=' . $y . ', width=' . $resized_width . ', height=' . $resized_height);
        }

        // Save the composite image
        $composite_filename = 'composited-image-' . wp_generate_uuid4() . '.png';
        $composite_path = $upload_dir['path'] . '/' . $composite_filename;
        imagepng($frame, $composite_path);
        imagedestroy($frame);

        $composite_url = $upload_dir['url'] . '/' . $composite_filename;
        if ($composite_url) {
            wp_send_json([
                'success' => true,
                'composite_url' => $composite_url
            ]);
        } else {
            wp_send_json([
                'success' => false,
                'message' => 'Failed to composite images'
            ]);
        }

        error_log('Composite created with frame: ' . $frame_path . ', edited images: ' . print_r($edited_images, true) . ', coordinates: ' . print_r($coordinates, true));
    } catch (Exception $e) {
        error_log('Error in package_composite_images_callback: ' . $e->getMessage());
        $response['data'] = 'Critical error: ' . $e->getMessage();
        wp_send_json_error($response);
    }

    wp_die();
}

add_action('wp_ajax_composite_images', 'composite_images_callback');
add_action('wp_ajax_nopriv_composite_images', 'composite_images_callback');

function save_edited_image_callback() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
        wp_die();
    }

    $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';
    if (empty($image_data) || !preg_match('/^data:image\/(png|jpeg);base64,/', $image_data)) {
        error_log('Invalid image data in save_edited_image_callback');
        wp_send_json_error('Invalid image data');
        wp_die();
    }

    $image_data = str_replace('data:image/png;base64,', '', $image_data);
    $image_data = str_replace('data:image/jpeg;base64,', '', $image_data);
    $image_data = str_replace(' ', '+', $image_data);
    $decoded_image = base64_decode($image_data);

    if ($decoded_image === false) {
        error_log('Failed to decode base64 image in save_edited_image_callback');
        wp_send_json_error('Failed to decode image');
        wp_die();
    }

    $upload_dir = wp_upload_dir();
    $image_filename = 'edited-image-' . uniqid() . '.png';
    $image_path = $upload_dir['path'] . '/' . $image_filename;

    if (!file_put_contents($image_path, $decoded_image)) {
        error_log('Failed to save edited image to: ' . $image_path);
        wp_send_json_error('Failed to save image');
        wp_die();
    }

    $image_url = $upload_dir['url'] . '/' . $image_filename;
    $attachment = wp_insert_attachment([
        'post_mime_type' => 'image/png',
        'post_title' => 'Edited Image',
        'post_content' => '',
        'post_status' => 'inherit',
    ], $image_path);

    if (!is_wp_error($attachment)) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment, $image_path);
        wp_update_attachment_metadata($attachment, $attachment_data);
    }

    wp_send_json_success(['file_url' => $image_url, 'attachment_id' => $attachment]);
    wp_die();
}
add_action('wp_ajax_save_edited_image', 'save_edited_image_callback');
add_action('wp_ajax_nopriv_save_edited_image', 'save_edited_image_callback');



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

        $frame_template_settings = get_post_meta($product_id, '_axon_frame_template', true) ?: [
            'image_id' => 0,
            'coordinates' => [],
            'frame_image_url' => ''
        ];

        if (!is_array($frame_template_settings['coordinates'])) {
            $frame_template_settings['coordinates'] = [];
        }
        $gallery_option = get_post_meta($product_id, '_gallery_option', true);
        if ($gallery_option === 'single_image' && !empty($frame_template_settings['coordinates'])) {
            $frame_template_settings['coordinates'] = $frame_template_settings['coordinates'][0] ?: ['x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 0, 'aspect_ratio' => 0];
        } elseif ($gallery_option === 'multiple_images' && !empty($frame_template_settings['coordinates'])) {
            foreach ($frame_template_settings['coordinates'] as &$coords) {
                if (!is_array($coords)) {
                    $coords = ['x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 0, 'aspect_ratio' => 0];
                }
            }
            unset($coords);
        }

        if (!empty($frame_template_settings['image_id'])) {
            $frame_image_url = wp_get_attachment_url($frame_template_settings['image_id']) ?: '';
            $frame_image_path = get_attached_file($frame_template_settings['image_id']);
            if ($frame_image_path && file_exists($frame_image_path)) {
                $frame_image_info = getimagesize($frame_image_path);
                if ($frame_image_info) {
                    $frame_width = $frame_image_info[0];
                    $frame_height = $frame_image_info[1];
                    $admin_preview_width = 1200;
                    $scale_factor = $admin_preview_width / $frame_width;
                    if ($gallery_option === 'single_image' && !empty($frame_template_settings['coordinates'])) {
                        $coords = &$frame_template_settings['coordinates'];
                        $coords['x1'] = floatval($coords['x1']) * $scale_factor;
                        $coords['y1'] = floatval($coords['y1']) * $scale_factor;
                        $coords['x2'] = floatval($coords['x2']) * $scale_factor;
                        $coords['y2'] = floatval($coords['y2']) * $scale_factor;
                        $coords['aspect_ratio'] = floatval($coords['aspect_ratio']);
                    } elseif ($gallery_option === 'multiple_images') {
                        foreach ($frame_template_settings['coordinates'] as &$coords) {
                            $coords['x1'] = floatval($coords['x1']) * $scale_factor;
                            $coords['y1'] = floatval($coords['y1']) * $scale_factor;
                            $coords['x2'] = floatval($coords['x2']) * $scale_factor;
                            $coords['y2'] = floatval($coords['y2']) * $scale_factor;
                            $coords['aspect_ratio'] = floatval($coords['aspect_ratio']);
                        }
                        unset($coords);
                    }
                }
            }
            $frame_template_settings['frame_image_url'] = $frame_image_url;
        }

        wp_localize_script(
            'axon-gallery-script',
            'myAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('axon_photo_gallery_nonce'),
                'frame_template' => $frame_template_settings
            )
        );

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

        $coordinates = $frame_template_settings['coordinates'];
        $has_frame_image = !empty($frame_template_settings['frame_image_url']);
        $has_valid_coordinates = (isset($coordinates['x1']) ? $coordinates['x1'] : 0) != 0 || (isset($coordinates['y1']) ? $coordinates['y1'] : 0) != 0 || (isset($coordinates['x2']) ? $coordinates['x2'] : 0) != 0 || (isset($coordinates['y2']) ? $coordinates['y2'] : 0) != 0;
        $has_valid_aspect_ratio = floatval(isset($coordinates['aspect_ratio']) ? $coordinates['aspect_ratio'] : 0) > 0;
        if ($gallery_option === 'single_image' && $has_frame_image && $has_valid_coordinates && $has_valid_aspect_ratio) {
            $css .= '
                div.tui-image-editor-container ul.tui-image-editor-submenu-item li.tui-image-editor-button.preset,
                div.tui-image-editor-container ul.tui-image-editor-submenu-item li.tie-crop-preset-button {
                    display: none !important;
                }
            ';
        }

        wp_add_inline_style('axon-gallery-style', $css);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_script');

function update_frame_template_coordinates() {
    
    check_ajax_referer('axon_photo_gallery_nonce', 'nonce');

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $coordinates = isset($_POST['coordinates']) ? (array) $_POST['coordinates'] : [];

    if (!$product_id || empty($coordinates)) {
        wp_send_json_error(['message' => 'Invalid product ID or coordinates']);
        return;
    }

    $frame_template_settings = get_post_meta($product_id, '_axon_frame_template', true) ?: [
        'image_id' => 0,
        'coordinates' => [],
        'frame_image_url' => ''
    ];
    $frame_template_settings['coordinates'] = $coordinates;
    update_post_meta($product_id, '_axon_frame_template', $frame_template_settings);

    wp_send_json_success(['message' => 'Coordinates updated successfully']);
}
add_action('wp_ajax_update_frame_template_coordinates', 'update_frame_template_coordinates');


function enqueue_package_gallery_script() {
    static $enqueued = false;
    if ($enqueued || !is_product()) {
        return;
    }

    $product = wc_get_product(get_the_ID());
    $is_package_product = $product && $product->get_type() === 'package';

    if ($is_package_product) {
        $package_products = get_post_meta(get_the_ID(), '_package_products', true) ?: [];
        $sub_product_settings = [];

        foreach ($package_products as $product_data) {
            $product_id = $product_data['product'];
            $gallery_option = get_post_meta($product_id, '_gallery_option', true) ?: 'single_image';
            $frame_template_raw = get_post_meta($product_id, '_axon_frame_template', true);
            error_log("Raw frame template for product ID $product_id: " . print_r($frame_template_raw, true));

            $frame_template = [];
            if (empty($frame_template_raw) || !is_array($frame_template_raw)) {
                $frame_template = [
                    'image_id' => 0,
                    'coordinates' => $gallery_option === 'multiple_images' ? [] : ['x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 0, 'aspect_ratio' => 1],
                    'frame_image_url' => '',
                    'gallery_option' => $gallery_option
                ];
                error_log("Frame template not set or invalid for product ID $product_id. Using default empty template.");
            } else {
                $frame_template = $frame_template_raw;
                $frame_template['gallery_option'] = $gallery_option;

                if ($gallery_option === 'single_image') {
                    if (!isset($frame_template['coordinates']) || !is_array($frame_template['coordinates']) || empty($frame_template['coordinates'])) {
                        $frame_template['coordinates'] = ['x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 0, 'aspect_ratio' => 1];
                        error_log("Single image product ID $product_id has invalid coordinates. Set to default.");
                    }
                } elseif ($gallery_option === 'multiple_images') {
                    if (!isset($frame_template['coordinates']) || !is_array($frame_template['coordinates'])) {
                        $frame_template['coordinates'] = [];
                        error_log("Multiple image product ID $product_id has invalid coordinates. Set to empty array.");
                    }
                }
            }

            if (!empty($frame_template['image_id'])) {
                $frame_image_url = wp_get_attachment_url($frame_template['image_id']) ?: '';
                $frame_image_path = get_attached_file($frame_template['image_id']);
                $frame_image_info = false;

                if ($frame_image_path && file_exists($frame_image_path)) {
                    $frame_image_info = getimagesize($frame_image_path);
                } else {
                    error_log("Frame image not found for product ID $product_id, image ID {$frame_template['image_id']}. Path: " . ($frame_image_path ?: 'none'));
                }

                if ($frame_image_info) {
                    $frame_width = $frame_image_info[0];
                    $frame_height = $frame_image_info[1];
                    $admin_preview_width = 1200;
                    $scale_factor = $frame_width > 0 ? $admin_preview_width / $frame_width : 1;
                    $default_aspect_ratio = $frame_height > 0 ? $frame_width / $frame_height : 1;

                    if ($gallery_option === 'single_image') {
                        $coords = (is_array($frame_template['coordinates']) && !empty($frame_template['coordinates'])) 
                            ? $frame_template['coordinates'][0] 
                            : [];
                        $coords = wp_parse_args($coords, [
                            'x1' => 0,
                            'y1' => 0,
                            'x2' => 0,
                            'y2' => 0,
                            'aspect_ratio' => $default_aspect_ratio
                        ]);

                        $aspect_ratio = floatval($coords['aspect_ratio'] ?: $default_aspect_ratio);
                        $width = floatval($coords['x2']) - floatval($coords['x1']);
                        $height = floatval($coords['y2']) - floatval($coords['y1']);
                        $is_all_zeros = $coords['x1'] == 0 && $coords['x2'] == 0 && $coords['y1'] == 0 && $coords['y2'] == 0;

                        if ($is_all_zeros || $width <= 0 || $height <= 0) {
                            $expected_width = 249.64824120603015; // Default from JS log
                            $expected_height = 360; // Default from JS log
                            $width = $expected_width;
                            $height = $expected_height;

                            $x1 = ($frame_width - $width) / 2;
                            $y1 = ($frame_height - $height) / 2;
                            $x2 = $x1 + $width;
                            $y2 = $y1 + $height;

                            error_log("Invalid coordinates for product ID $product_id. Centering image with expected dimensions: x1=$x1, y1=$y1, x2=$x2, y2=$y2");
                        } else {
                            $x1 = floatval($coords['x1']);
                            $y1 = floatval($coords['y1']);
                            $x2 = floatval($coords['x2']);
                            $y2 = floatval($coords['y2']);
                            error_log("Using raw coordinates for product ID $product_id: x1=$x1, y1=$y1, x2=$x2, y2=$y2");
                        }

                        $frame_template['coordinates'] = [
                            'x1' => $x1,
                            'y1' => $y1,
                            'x2' => $x2,
                            'y2' => $y2,
                            'aspect_ratio' => $aspect_ratio,
                            'frame_original_width' => $frame_width,
                            'frame_original_height' => $frame_height,
                            'admin_preview_width' => $admin_preview_width
                        ];
                    } elseif ($gallery_option === 'multiple_images') {
                        $frame_template['coordinates'] = array_map(function ($coords) use ($scale_factor, $default_aspect_ratio, $frame_width, $frame_height, $admin_preview_width) {
                            $coords = wp_parse_args($coords, [
                                'x1' => 0,
                                'y1' => 0,
                                'x2' => 0,
                                'y2' => 0,
                                'aspect_ratio' => $default_aspect_ratio
                            ]);
                            return [
                                'x1' => floatval($coords['x1']) * $scale_factor,
                                'y1' => floatval($coords['y1']) * $scale_factor,
                                'x2' => floatval($coords['x2']) * $scale_factor,
                                'y2' => floatval($coords['y2']) * $scale_factor,
                                'aspect_ratio' => floatval($coords['aspect_ratio'] ?: $default_aspect_ratio),
                                'frame_original_width' => $frame_width,
                                'frame_original_height' => $frame_height,
                                'admin_preview_width' => $admin_preview_width
                            ];
                        }, $frame_template['coordinates'] ?: []);
                    }

                    $frame_template['frame_width'] = $frame_width;
                    $frame_template['frame_height'] = $frame_height;
                } else {
                    $default_aspect_ratio = 1;
                    if ($gallery_option === 'single_image') {
                        $coords = wp_parse_args($frame_template['coordinates'], [
                            'x1' => 0,
                            'y1' => 0,
                            'x2' => 0,
                            'y2' => 0,
                            'aspect_ratio' => $default_aspect_ratio
                        ]);
                        $frame_template['coordinates'] = [
                            'x1' => floatval($coords['x1']),
                            'y1' => floatval($coords['y1']),
                            'x2' => floatval($coords['x2']),
                            'y2' => floatval($coords['y2']),
                            'aspect_ratio' => floatval($coords['aspect_ratio'] ?: $default_aspect_ratio),
                            'frame_original_width' => 0,
                            'frame_original_height' => 0,
                            'admin_preview_width' => $admin_preview_width
                        ];
                    } elseif ($gallery_option === 'multiple_images') {
                        $frame_template['coordinates'] = array_map(function ($coords) use ($default_aspect_ratio) {
                            $coords = wp_parse_args($coords, [
                                'x1' => 0,
                                'y1' => 0,
                                'x2' => 0,
                                'y2' => 0,
                                'aspect_ratio' => $default_aspect_ratio
                            ]);
                            return [
                                'x1' => floatval($coords['x1']),
                                'y1' => floatval($coords['y1']),
                                'x2' => floatval($coords['x2']),
                                'y2' => floatval($coords['y2']),
                                'aspect_ratio' => floatval($coords['aspect_ratio'] ?: $default_aspect_ratio),
                                'frame_original_width' => 0,
                                'frame_original_height' => 0,
                                'admin_preview_width' => $admin_preview_width
                            ];
                        }, $frame_template['coordinates'] ?: []);
                    }
                }

                $frame_template['frame_image_url'] = $frame_image_url;
            } else {
                error_log("No frame image ID set for product ID $product_id. Frame image URL will be empty.");
                if ($gallery_option === 'single_image') {
                    $coords = wp_parse_args($frame_template['coordinates'], [
                        'x1' => 0,
                        'y1' => 0,
                        'x2' => 0,
                        'y2' => 0,
                        'aspect_ratio' => 1
                    ]);
                    $frame_template['coordinates'] = [
                        'x1' => floatval($coords['x1']),
                        'y1' => floatval($coords['y1']),
                        'x2' => floatval($coords['x2']),
                        'y2' => floatval($coords['y2']),
                        'aspect_ratio' => floatval($coords['aspect_ratio'] > 0 ? $coords['aspect_ratio'] : 1),
                        'frame_original_width' => 0,
                        'frame_original_height' => 0,
                        'admin_preview_width' => $admin_preview_width
                    ];
                } elseif ($gallery_option === 'multiple_images') {
                    $frame_template['coordinates'] = array_map(function ($coords) {
                        $coords = wp_parse_args($coords, [
                            'x1' => 0,
                            'y1' => 0,
                            'x2' => 0,
                            'y2' => 0,
                            'aspect_ratio' => 1
                        ]);
                        return [
                            'x1' => floatval($coords['x1']),
                            'y1' => floatval($coords['y1']),
                            'x2' => floatval($coords['x2']),
                            'y2' => floatval($coords['y2']),
                            'aspect_ratio' => floatval($coords['aspect_ratio'] ?? 1),
                            'frame_original_width' => 0,
                            'frame_original_height' => 0,
                            'admin_preview_width' => $admin_preview_width
                        ];
                    }, $frame_template['coordinates'] ?: []);
                }
                $frame_template['frame_image_url'] = '';
            }

            $sub_product_settings[$product_id] = $frame_template;
            error_log("Final Product ID: $product_id, Gallery Option: $gallery_option, Frame Template: " . print_r($frame_template, true));
        }

        wp_enqueue_script(
            'axon-package-gallery-script',
            plugin_dir_url(__FILE__) . 'assets/js/package-gallery-script.js',
            array('jquery'),
            '1.0.0.9',
            true
        );

        wp_localize_script(
            'axon-package-gallery-script',
            'packageAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('axon_photo_gallery_nonce'),
                'sub_product_settings' => $sub_product_settings
            )
        );

        wp_enqueue_style(
            'axon-package-gallery-style',
            plugin_dir_url(__FILE__) . 'assets/css/package-dummy.css',
            array(),
            '1.0.0'
        );

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

        foreach ($sub_product_settings as $product_id => $settings) {
            $coordinates = $settings['coordinates'];
            $has_frame_image = !empty($settings['frame_image_url']);
            $has_valid_coordinates = $settings['gallery_option'] === 'single_image'
                ? (is_array($coordinates) && (
                    (isset($coordinates['x1']) ? $coordinates['x1'] : 0) != 0 ||
                    (isset($coordinates['y1']) ? $coordinates['y1'] : 0) != 0 ||
                    (isset($coordinates['x2']) ? $coordinates['x2'] : 0) != 0 ||
                    (isset($coordinates['y2']) ? $coordinates['y2'] : 0) != 0
                ))
                : (is_array($coordinates) && count($coordinates) > 0 && array_reduce($coordinates, function ($carry, $coord) {
                    return $carry || ($coord['x1'] != $coord['x2'] && $coord['y1'] != $coord['y2']) && ($coord['x1'] < $coord['x2'] && $coord['y1'] < $coord['y2']);
                }, false));
            $has_valid_aspect_ratio = $settings['gallery_option'] === 'single_image'
                ? (is_array($coordinates) && floatval(isset($coordinates['aspect_ratio']) ? $coordinates['aspect_ratio'] : 0) > 0)
                : (is_array($coordinates) && array_reduce($coordinates, function ($carry, $coord) {
                    return $carry && floatval($coord['aspect_ratio']) > 0;
                }, true));
            if ($has_frame_image && $has_valid_coordinates && $has_valid_aspect_ratio) {
                $css .= '
                    div.tui-image-editor-container[data-product-id="' . $product_id . '"] ul.tui-image-editor-submenu-item li.tui-image-editor-button.preset,
                    div.tui-image-editor-container[data-product-id="' . $product_id . '"] ul.tui-image-editor-submenu-item li.tie-crop-preset-button {
                        display: none !important;
                    }
                ';
            }
        }

        wp_add_inline_style('axon-package-gallery-style', $css);
        $enqueued = true;
    }
}
add_action('wp_enqueue_scripts', 'enqueue_package_gallery_script');