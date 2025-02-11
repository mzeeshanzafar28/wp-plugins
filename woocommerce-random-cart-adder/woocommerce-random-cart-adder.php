<?php
/*
Plugin Name: WooCommerce Random Cart Adder
Description: Adds 100 random WooCommerce products with random variations and quantities to the cart.
Version: 1.0
Author: M. Zeeshan Zafar
*/


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Random_Cart_Adder {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_post_add_random_products', [$this, 'add_random_products_to_cart']);
    }

    public function add_plugin_page() {
        add_menu_page(
            'Random Cart Adder',
            'Random Cart Adder',
            'manage_options',
            'random-cart-adder',
            [$this, 'settings_page'],
            'dashicons-cart',
            56
        );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>WooCommerce Random Cart Adder</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="add_random_products">
                <button type="submit" class="button button-primary">Add Random Products to Cart</button>
            </form>
        </div>
        <?php
    }

    public function add_random_products_to_cart() {
        // Verify the current user's permissions.
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Load WooCommerce cart if not already initialized.
        if (!WC()->cart) {
            wc_load_cart();
        }

        $product_ids = wc_get_products(['status' => 'publish', 'limit' => -1, 'return' => 'ids']);
        if (empty($product_ids)) {
            wp_die(__('No products found.'));
        }

        // Select up to 100 random products.
        $random_products = array_rand($product_ids, min(100, count($product_ids)));

        // Add selected products to the cart.
        foreach ((array)$random_products as $key) {
            $product_id = $product_ids[$key];
            $product = wc_get_product($product_id);

            if ($product->is_in_stock()) {
                if ($product->is_type('variable')) {
                    $variations = $product->get_available_variations();
                    if (!empty($variations)) {
                        $variation = $variations[array_rand($variations)];
                        $variation_id = $variation['variation_id'];
                        $available_stock = $variation['max_qty'] ?: 10; // Use max_qty for variation stock.
                        if ($available_stock > 0) {
                            $quantity = rand(1, $available_stock);
                            WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation['attributes']);
                        }
                    }
                } else {
                    $available_stock = $product->get_stock_quantity();
                    if ($available_stock > 0) {
                        $quantity = rand(1, $available_stock);
                        WC()->cart->add_to_cart($product_id, $quantity);
                    }
                }
            }
        }

        // Redirect to the cart page after adding products.
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }
}

new WC_Random_Cart_Adder();
