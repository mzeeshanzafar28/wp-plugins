<?php
/*
 Plugin Name: SearchMan
 * Description: An efficient plugin to search for products on your WooCommerce site.
 * Version: 1.0
 * Author: M Zeeshan Zafar
 Author URI: https://github.com/mzeeshanzafar28
 * License: GPL2
 */

class SearchMan
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_search_products', array($this, 'search_products'));
        add_action('wp_ajax_nopriv_search_products', array($this, 'search_products'));
        add_shortcode('search_bar', array($this, 'search_bar_shortcode'));
        add_filter('posts_search_columns', array($this, 'add_search_index'));
    }

    function add_search_index($index_columns)
    {
        $index_columns['post_title'] = 'post_title(255)';
        $index_columns['post_content'] = 'post_content(255)';
        return $index_columns;
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('searchman-rateyo', 'https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.js', array('jquery'), '2.3.2', true);
        wp_enqueue_style('searchman-rateyo-style', 'https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.css');
        
        wp_enqueue_script('searchman-script', plugin_dir_url(__FILE__) . 'searchman.js', array('jquery', 'searchman-rateyo'), '1.0', true);
        wp_localize_script('searchman-script', 'searchman_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }
    

    public function search_products()
    {
        $search_term = sanitize_text_field($_GET['search_term']);
        // Check if search term has at least 3 characters
        if (mb_strlen($search_term, 'UTF-8') < 3) {
            wp_send_json_error('Search term should be at least 3 characters long.');
        }
    
        global $wpdb;
        $products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_price' AND post_id IN 
                (SELECT ID FROM $wpdb->posts WHERE post_type = 'product' AND post_status = 'publish' AND 
                (post_title LIKE '%%%s%%' OR post_content LIKE '%%%s%%'))",
                $search_term,
                $search_term
            )
        );
    
        if ($products) {
            $result = array();
            foreach ($products as $product) {
                $product_id = $product->post_id;
                $price = wc_price($product->meta_value); // Format the price using WooCommerce function
                $rating = get_post_meta($product_id, '_wc_average_rating', true); // Get product rating
    
                $result[] = array(
                    'id' => $product_id,
                    'title' => get_the_title($product_id),
                    'permalink' => get_permalink($product_id),
                    'price' => $price,
                    'image' => get_the_post_thumbnail_url($product_id, 'thumbnail'), // Get product image URL
                    'rating' => $rating, // Add product rating to the response
                );
            }
    
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => 'No products found'));
        }
    }
    

    

    public function search_bar_shortcode($atts)
    {
        ob_start();
        ?>
        <div class="search-container">
            <input type="text" id="search-bar" placeholder="Search products...">
            <div id="search-results"></div>
        </div>

        <style>

        #search-results{
            width: 85vw;
            box-shadow: 1px 2px 30px 1px rgba(0, 0, 0, 0.2);
            border-radius:10px;
            text-align: center;
        }

        .search-res{
            padding: 5px;
            border-radius:10px;
            box-shadow: 1px 2px 30px 1px rgba(0, 0, 0, 0.2);
            margin-bottom: 8px;
            margin-top: 0px;
        }

        .res-ancher h2{
            margin-left: 1vw;
        }

            #search-bar {
                margin-top:5px;
                width: 85vw;
                padding: 10px;
                border-radius: 20px;
                border: 1px solid #ccc;
                margin-bottom: 5px;
            }

        .res-ancher{
            display: flex;
            justify-items: self-start;
            justify-content: start;
            margin-bottom: -18px;
            }

        .res-ancher img{
            border-radius: 50%;
            height: 10vh;
            width: auto;
        }

        .search-res h5, .search-res p{
            padding:0;
            margin:0;
        }

        .name-n-price{
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: start;
            margin-left: 1vw;
        }

        </style>

        <?php
        return ob_get_clean();
    }
}

new SearchMan();
