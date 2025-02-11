<?php
/**
 * Plugin Name: ReviewMan
 * Description: A plugin to add reviews to WooCommerce products from an admin options page.
 * Version: 1.1
 * Author: Axon Technologies
 * Text Domain: reviewman
 */

if (!defined('ABSPATH')) {
    exit; 
}

add_action('admin_menu', 'reviewman_admin_menu');

add_action('admin_enqueue_scripts', 'reviewman_enqueue_custom_css');
add_action('wp_enqueue_scripts', 'reviewman_enqueue_frontend_css_js');

function reviewman_admin_menu() {
    add_menu_page(
        'ReviewMan Options', 
        'ReviewMan', 
        'manage_options', 
        'reviewman', 
        'reviewman_options_page'
    );
}

function reviewman_enqueue_custom_css($hook) {
    if ($hook !== 'toplevel_page_reviewman') {
        return;
    }
    wp_enqueue_style('reviewman_custom_css', plugins_url('reviewman.css', __FILE__));
}

function reviewman_enqueue_frontend_css_js() {
    wp_enqueue_style('reviewman_frontend_css', plugins_url('reviewman-frontend.css', __FILE__));
    wp_enqueue_script('reviewman_frontend_js', plugins_url('reviewman-frontend.js', __FILE__), array('jquery'), null, true);

    wp_localize_script('reviewman_frontend_js', 'reviewman_vars', array(
        'tick_image_url' => plugins_url('images/verified-tick.png', __FILE__)
    ));
}

function reviewman_options_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_POST['reviewman_submit'])) {
        reviewman_handle_form_submission();
    }

    $products = get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => -1
    ));
    ?>

    <div class="wrap reviewman-background">
        <h1>ReviewMan</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="reviewman_customer_name">Customer Name</label></th>
                    <td><input type="text" id="reviewman_customer_name" name="reviewman_customer_name" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="reviewman_products">Products</label></th>
                    <td>
                        <?php foreach ($products as $product): ?>
                            <label style="color:green; font-weight:bold;">
                                <input type="checkbox" name="reviewman_products[]" value="<?php echo esc_attr($product->ID); ?>">
                                <?php echo esc_html($product->post_title); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="reviewman_stars">Stars</label></th>
                    <td>
                        <select id="reviewman_stars" name="reviewman_stars">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="reviewman_verified">Verified Review</label></th>
                    <td><input type="checkbox" id="reviewman_verified" name="reviewman_verified" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="reviewman_review_text">Review Text</label></th>
                    <td><textarea id="reviewman_review_text" name="reviewman_review_text" rows="5" cols="50"></textarea></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="reviewman_submit" id="submit" class="button button-primary" value="Submit Review">
            </p>
        </form>
    </div>
    <?php
}

function reviewman_handle_form_submission() {
    if (!isset($_POST['reviewman_customer_name']) || !isset($_POST['reviewman_products']) || !isset($_POST['reviewman_stars']) || !isset($_POST['reviewman_review_text'])) {
        return;
    }

    $customer_name = sanitize_text_field($_POST['reviewman_customer_name']);
    $products = array_map('intval', $_POST['reviewman_products']);
    $stars = intval($_POST['reviewman_stars']);
    $review_text = sanitize_textarea_field($_POST['reviewman_review_text']);
    $is_verified = isset($_POST['reviewman_verified']) ? 'yes' : 'no';

    foreach ($products as $product_id) {
        $commentdata = array(
            'comment_post_ID' => $product_id,
            'comment_author' => $customer_name,
            'comment_content' => $review_text,
            'comment_type' => 'review',
            'comment_approved' => 1,
        );

        $comment_id = wp_insert_comment($commentdata);

        if ($comment_id) {
            update_comment_meta($comment_id, 'rating', $stars);
            update_comment_meta($comment_id, 'verified', $is_verified);
        }
    }

    echo '<div id="message" class="updated notice is-dismissible"><p>Review(s) added successfully!</p></div>';
}

add_filter('woocommerce_product_review_list_args', 'reviewman_custom_review_list_args');

function reviewman_custom_review_list_args($args) {
    $args['callback'] = 'reviewman_custom_reviews_callback';
    return $args;
}

function reviewman_custom_reviews_callback($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment;
    ?>
    <li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
        <div id="comment-<?php comment_ID(); ?>" class="comment_container">
            <?php echo get_avatar($comment, apply_filters('woocommerce_review_gravatar_size', '60'), ''); ?>
            <div class="comment-text">
                <?php if ( $comment->comment_approved == '0' ) : ?>
                    <p class="meta"><em><?php esc_html_e('Your comment is awaiting approval', 'woocommerce'); ?></em></p>
                <?php else : ?>
                    <p class="meta">
                        <strong class="woocommerce-review__author"><?php comment_author(); ?></strong>
                        <?php if ( 'yes' === get_comment_meta( $comment->comment_ID, 'verified', true ) ) : ?>
                            <span class="woocommerce-review__verified verified"><span class="verified-tooltip">✔ Verified Owner</span></span>
                        <?php endif; ?>
                        <span class="woocommerce-review__dash">&ndash;</span> <time class="woocommerce-review__published-date" datetime="<?php echo get_comment_date('c'); ?>"><?php echo get_comment_date( wc_date_format() ); ?></time>
                    </p>
                <?php endif; ?>

                <?php if ($rating = intval(get_comment_meta($comment->comment_ID, 'rating', true))) : ?>
                    <div class="star-rating" title="<?php echo sprintf(esc_attr__('Rated %d out of 5', 'woocommerce'), $rating); ?>">
                        <span style="width:<?php echo (esc_attr($rating) / 5) * 100; ?>%"><strong itemprop="ratingValue"><?php echo esc_html($rating); ?></strong> <?php esc_html_e('out of 5', 'woocommerce'); ?></span>
                    </div>
                <?php endif; ?>

                <div class="description">
                    <?php comment_text(); ?>
                </div>
            </div>
        </div>
    </li>
    <?php
}
?>
