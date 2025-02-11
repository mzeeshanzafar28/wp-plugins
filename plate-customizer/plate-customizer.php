<?php
/**
 * Plugin Name: Plate Customizer
 * Description: A custom plugin to allow customers of your store to customize their number plates to order. Use shortcode [plate-customizer] anywhere in your website to show the customizer.
 * Author:M Zeeshan Zafar
 * Author URI: https://github.com/mzeeshanzafar28
 * License: GPLv2 or later
 */

defined( 'ABSPATH' ) or die( 'Unauthorized' );

class PlateCustomizer {
    public static $plateCustomizerDB = 'plate_customizer';

    public function __construct() {
        register_activation_hook( __FILE__, [ $this, 'initiater' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_menu', [ $this, 'add_page' ] );
        add_shortcode( 'plate-customizer', [ $this, 'site_html' ] );
        add_action( 'init', [ $this, 'save_font' ] );
        add_action( 'admin_post_plate_customizer_handle_form', [ $this, 'handle_form_submission' ] );

        add_action( 'wp_ajax_save_new_value', [ $this, 'save_new_value' ] );
        add_action( 'wp_ajax_nopriv_save_new_value', [ $this, 'save_new_value' ] );

        add_action( 'wp_ajax_remove_value', [ $this, 'remove_value' ] );
        add_action( 'wp_ajax_nopriv_remove_value', [ $this, 'remove_value' ] );
        
        add_action('wp_ajax_update_sort_order', [ $this ,'update_sort_order_callback']);
        add_action('wp_ajax_nopriv_update_sort_order', [ $this ,'update_sort_order_callback']);

        add_action( 'wp_ajax_fetch_data_for_plate', [ $this, 'fetch_data_for_plate' ] );
        add_action( 'wp_ajax_nopriv_fetch_data_for_plate', [ $this, 'fetch_data_for_plate' ] );

        add_action( 'wp_ajax_fetch_plate_customizer_allowances', [ $this, 'fetch_plate_customizer_allowances' ] );
        add_action( 'wp_ajax_nopriv_fetch_plate_customizer_allowances', [ $this, 'fetch_plate_customizer_allowances' ] );

        add_action('wp_head' , [$this , 'manage_font_faces']);

        add_action( 'wp_ajax_processOrder', [ $this, 'processOrder' ] );
        add_action( 'wp_ajax_nopriv_processOrder', [ $this, 'processOrder' ] );

        add_action('admin_init', array($this, 'register_settings'));

        add_action('woocommerce_before_calculate_totals', [$this, 'modify_cart_item_prices'], 10, 1); // change price
        add_filter('woocommerce_get_item_data', array($this, 'display_custom_data_in_cart'), 10, 2); // meta in cart
        add_filter('kses_allowed_protocols', [$this, 'allow_data_uri_scheme']); // allow data uri scheme
        add_filter( 'woocommerce_is_sold_individually', [$this, 'remove_qty'], 10, 2 ); // remove qty in cart

        add_filter('manage_edit-shop_order_columns', [$this,'add_custom_data_column']); // add custom data column
        add_action( 'woocommerce_checkout_create_order_line_item', [$this,'wk_checkout_create_order_line_item'], 10, 4 ); // store order meta

        add_action('manage_shop_order_posts_custom_column', [$this,'display_custom_data_column_content'], 10, 2);
        add_action('admin_head', [$this,'custom_data_column_width']);

        add_action('template_redirect', [$this,'exclude_selected_product'],10);
        
        add_filter('woocommerce_get_price_html', [$this,'hide_price_and_add_to_cart_for_specific_product'], 10, 2);

        // add_filter('woocommerce_is_purchasable', [$this,'hide_price_and_add_to_cart_for_specific_product'], 10, 2);

        // add_action('switch_theme', [$this,'exclude_selected_product'],10);
    }

    function hide_price_and_add_to_cart_for_specific_product($html, $product) {
        $selected_product_id = get_option('selected_product_id'); 
        if ($product->get_id() == $selected_product_id) {
            return '';
        }
        return $html;
    }

    function exclude_selected_product() {
        $selected_product_id = get_option('selected_product_id'); 
        if (is_product()) {
    
            if ($selected_product_id) {
                $product = wc_get_product();
                $id = $product->get_id();
                if ($selected_product_id == $id)
                {
                
                    add_action( 'woocommerce_after_single_product_summary' , function(){
                    // echo "<h1>HERE</h1>";
                    echo do_shortcode( '[plate-customizer]' );
                    } );

                    add_action('woocommerce_before_single_product',[$this, 'hide_all_product_data']);

                }
            }
        }

        
    }

    function hide_all_product_data() {

        remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
    
        remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);

        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );

        remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);

        add_filter( 'woocommerce_variable_sale_price_html', 'woocommerce_remove_prices', 10, 2 );

        add_filter( 'woocommerce_variable_price_html', 'woocommerce_remove_prices', 10, 2 );

        add_filter( 'woocommerce_get_price_html', 'woocommerce_remove_prices', 10, 2 );

        add_filter('woocommerce_sale_flash', 'plate_customizer_hide_sale_flash'); 


        function woocommerce_remove_prices( $price, $product ) {
            echo '<style>.woocommerce-Price-amount {display:none;}</style>';
            $price = '';
            return $price;
        }

        function plate_customizer_hide_sale_flash() {
            echo '<style>.woocommerce span.onsale{ display:none; }</style>';
            return false;
        }

    }

    function display_custom_data_column_content($column, $post_id) {
        if ($column == 'custom_data') {
            $order = wc_get_order($post_id);

            $custom_data = [];
            $first = true;

            foreach ($order->get_items() as $item_id => $item) {
                $item_custom_data = $item->get_meta('custom_data', true);
                if (!empty($item_custom_data) && is_array($item_custom_data)) {
                    if (!$first) echo '<hr>';
                    $first = false;
                    foreach ($item_custom_data as $key => $data) {
                        if ( $key == 'rear_plate_preview' || $key == 'front_plate_qty' || $key == 'rear_plate_qty' ){
                            echo '<p>';
                            echo ucwords(str_replace('_', ' ', $key)) . ': ';
                            echo $data;
                            echo '</p>';
                        }
                    }
                }
            }
        }
    }

    function custom_data_column_width() {
        echo '<style type="text/css">';
        echo '.column-custom_data { width: 120px !important;}';
        echo '</style>';
    }

    function add_custom_data_column($columns) {
        $columns['custom_data'] = __('Custom Data', 'plate-customizer');
        return $columns;
    }

    function wk_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
        if ( isset( $values['custom_data'] ) ) {
            $data = $values['custom_data'];
            $item->add_meta_data(
            'custom_data',
                $values['custom_data'],
            );

            foreach ($data as $key => $value) {
                $item->add_meta_data(
                    ucwords(str_replace('_', ' ', $key)),
                    $value,
                );
            }
        }
    }

    public function modify_cart_item_prices($cart_object) {
        if (is_admin() && ! defined('DOING_AJAX')) return;

        $selected_product_id = get_option('selected_product_id');
        $data = get_option('plate_customizer_data');
        foreach ($cart_object->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] == $selected_product_id) {
            
                $cart_item['data']->set_price($cart_item['price']);
                unset($cart_item['quantity']);
                $cart_item['data']->add_meta_data('custom_data', $data);
                $cart_item['price_mod'] = true;

            }
        }
    }

    function display_custom_data_in_cart($item_data, $cart_item) {
        if (isset($cart_item['custom_data'])) {
            $custom_data = $cart_item['custom_data'];

            $excluded_keys = array('badge_image_url', 'plate_font', 'badge_type', 'border', 'bottom_text_color', 'bottom_text_font', 'bottom_text_size', 'total_price');

            foreach ($custom_data as $key => $value) {
                if (!in_array($key, $excluded_keys)) {
                    $item_data[] = array(
                        'key' => ucwords(str_replace('_', ' ', $key)),
                        'value' => $value,
                    );
                }
            }
        }
        return $item_data;
    }

    function allow_data_uri_scheme($protocols) {
        $protocols[] = 'data';
        return $protocols;
    }

    public function remove_qty($is_sold_individually, $product) {
        $pid = get_option('selected_product_id');

        if ($pid == $product->get_id()) {
            return true;
        } else {
            return false;
        }
    }
//*DONE
    public function register_settings() {
        register_setting('plugin_settings', 'selected_product_id', 'intval');
        register_setting('plugin_settings', 'front_plate_bg_color');
        register_setting('plugin_settings', 'front_plate_text_color');
        register_setting('plugin_settings', 'rear_plate_bg_color');
        register_setting('plugin_settings', 'rear_plate_text_color');
        register_setting('plugin_settings', 'display_order');

    }

    public function initiater(){
        global $wpdb;
        $table_name = $wpdb->prefix . self::$plateCustomizerDB;

        global $wpdb;
        $table_name = $wpdb->prefix . self::$plateCustomizerDB;
        
        $badge_types = [
            'UK LEGAL',
            'CARBON FIBRE',
            'EURO',
            'FOOTBALL',
            'ANIMALS',
            'CARTOON',
            'ELECTRIC CAR',
            'Alfa Romeo',
            'Audi',
            'BMW',
            'Citroen',
            'Fiat',
            'Ford',
            'Honda',
            'Hyundai',
            'Infiniti',
            'Jaguar',
            'Kia',
            'Land Rover',
            'Lexus',
            'MG',
            'Mazda',
            'Mercedes',
            'Mini',
            'Mitsubishi',
            'Nissan',
            'Peugeot',
            'Renault',
            'SURF n SKATE',
            'Saab',
            'Skoda',
            'Smart',
            'Subaru',
            'Suzuki',
            'Tesla',
            'Toyota',
            'VARIOUS',
            'Vauxhall',
            'Volkswagen',
            'Volvo',
            'WELSH'
        ];
        $default_bottom_text_sizes = [
            '8mm',
            '9mm',
            '10mm'
        ];

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name){
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                type varchar(255) NOT NULL,
                value varchar(255) NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);


            $default_plate_styles = [
                'Standard Black Plates',
                'Colour Badged Plates',
                '3D Gel Plates',
                '4D Plates - 3mm',
                '4D Plates - 5mm',
                '4D+Gel Plates'
            ];
            
            $default_plate_fonts = [
                'STANDARD',
                '3D FONT',
                'HILINE',
                'CARBON',
                'CARBON 3D',
                'HILINE CARBON',
                '3D GEL',
                '4D 3MM',
                '4D 5MM',
                '4D GEL'
            ];
            
            $default_borders = [
                'None',
                'Black',
                'Red',
                'Grey',
                'Pink'
            ];
            
            $default_bottom_text_colors = [
                'Black',
                'Red',
                'Grey',
                'Pink'
            ];
            
            $default_bottom_text_fonts = [
                'Arial',
                'Arial Bold',
                'Helvetica',
                'Regular',
                'Standard',
                'Carbon 3D',
                'Hiline',
                'Hiline Carbon'
            ];
            
            
            
            
            // Insert Plate Styles
            foreach ($default_plate_styles as $style) {
                $insert_data = array(
                    'type' => 'plate_style',
                    'value' => $style
                );
                $wpdb->insert($table_name, $insert_data);
            }
            
            // Insert Plate Fonts
            foreach ($default_plate_fonts as $font) {
                $insert_data = array(
                    'type' => 'plate_font',
                    'value' => $font
                );
                $wpdb->insert($table_name, $insert_data);
            }
            
            // Insert Borders
            foreach ($default_borders as $border) {
                $insert_data = array(
                    'type' => 'border',
                    'value' => $border
                );
                $wpdb->insert($table_name, $insert_data);
            }
            
            // Insert Bottom Text Colors
            foreach ($default_bottom_text_colors as $color) {
                $insert_data = array(
                    'type' => 'bottom_text_color',
                    'value' => $color
                );
                $wpdb->insert($table_name, $insert_data);
            }
            
            // Insert Bottom Text Fonts
            foreach ($default_bottom_text_fonts as $font) {
                $insert_data = array(
                    'type' => 'bottom_text_font',
                    'value' => $font
                );
                $wpdb->insert($table_name, $insert_data);
            }
            
            // Insert Bottom Text Sizes
            foreach ($default_bottom_text_sizes as $size) {
                $insert_data = array(
                    'type' => 'bottom_text_size',
                    'value' => $size
                );
                $wpdb->insert($table_name, $insert_data);
            }
            
            // Insert Badge Types
            foreach ($badge_types as $badge) {
                $insert_data = array(
                    'type' => 'badge_type',
                    'value' => $badge
                );
                $wpdb->insert($table_name, $insert_data);
            }
            
        }
        //CODE FOR ALLOWANCE TABLE
        $table_name = $wpdb->prefix . 'plate_customizer_allowances';

        $sql = "CREATE TABLE $table_name (
                id INT NOT NULL AUTO_INCREMENT,
                plate_name VARCHAR(255) NOT NULL,
                allows LONGTEXT NOT NULL,
                PRIMARY KEY (id)
            )";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        $plate_style_badge_images = [];
        foreach ($badge_types as $key => $value) {
            $plate_style_badge_images[$key] = site_url() . '/wp-content/plugins/plate-customizer/assets/images/badge_images/' . strtolower(str_replace(' ', '_', $value));
        }

        $data_to_insert = [
            'standard_black_plates' => [
                'Standard Black Plates',
                23,
                [ plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/standard_plates.jpg' ] ,
                [ 'STANDARD' ] ,
                [ 'NONE', 'BLACK' ] ,
                [ 'BLACK' ] ,
                [ 'Arial', 'Arial Bold', 'Helvetica' ] ,
                $default_bottom_text_sizes ,
                [] ,
                [] ,
                11.50,
                11.50,
            ],

            'colour_badged_plates' => [
                'Colour Badged Plates',
                25,
                [ plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/colour_badged_plates_1.jpg', plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/colour_badged_plates_2.jpg' ] ,
                [ 'STANDARD', '3D FONT', 'CARBON', 'HILINE', 'CARBON 3D', 'HILINE CARBON' ] ,
                [ 'NONE', 'BLACK', 'RED', 'GREY', 'PINK' ] ,
                [ 'BLACK', 'RED', 'GREY', 'PINK' ] ,
                [ 'Arial', 'Arial Bold', 'Helvetica' ] ,
                $default_bottom_text_sizes ,
                $badge_types ,
                $plate_style_badge_images,
                12.50,
                12.50,
            ],

            '3d_gel_plates' => [
                '3D Gel Plates',
                40,
                [ plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/3d_gel_plates_1.jpg', plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/3d_gel_plates_2.jpg', plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/3d_gel_plates_3.jpg' ] ,
                [ '3D GEL' ] ,
                [ 'NONE', 'BLACK', 'RED', 'GREY', 'PINK' ] ,
                [ 'BLACK', 'RED', 'GREY', 'PINK' ] ,
                [ 'Arial', 'Arial Bold', 'Helvetica' ] ,
                $default_bottom_text_sizes ,
                $badge_types ,
                $plate_style_badge_images,
                20,
                20,
            ],

            '4d_plates_-_3mm' => [
                '4D Plates - 3mm',
                45,
                [ plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/4d_plates_3mm.jpg' ] ,
                [ '4D 3MM' ] ,
                [ 'NONE', 'BLACK', 'RED', 'GREY', 'PINK' ] ,
                [ 'BLACK', 'RED', 'GREY', 'PINK' ] ,
                [ 'Arial', 'Arial Bold', 'Helvetica' ] ,
                $default_bottom_text_sizes ,
                $badge_types ,
                $plate_style_badge_images,
                22.50,
                22.50,
            ],

            '4d_plates_-_5mm' => [
                '4D Plates - 5mm',
                55,
                [ plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/4d_plates_5mm_1.jpg', plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/4d_plates_5mm_2.jpg' ] ,
                [ '4D 5MM' ] ,
                [ 'NONE', 'BLACK', 'RED', 'GREY', 'PINK' ] ,
                [ 'BLACK', 'RED', 'GREY', 'PINK' ] ,
                [ 'Arial', 'Arial Bold', 'Helvetica' ] ,
                $default_bottom_text_sizes ,
                $badge_types ,
                $plate_style_badge_images,
                27.50,
                27.50,
            ],

            '4d+gel_plates' => [
                '4D+GEL Plates',
                60,
                [ plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/4d+gel_plates_0.jpg', plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/4d+gel_plates_1.jpg', plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/4d+gel_plates_2.jpg' ] ,
                [ '4D GEL' ] ,
                [ 'NONE', 'BLACK', 'RED', 'GREY', 'PINK' ] ,
                [ 'BLACK', 'RED', 'GREY', 'PINK' ] ,
                [ 'Arial', 'Arial Bold', 'Helvetica' ] ,
                $default_bottom_text_sizes ,
                $badge_types ,
                $plate_style_badge_images,
                30,
                30,
            ],
        ];

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $data_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        if ( $data_count == 0 ) {
        foreach ( $data_to_insert as $plate_key => $plate_data ) {
            $wpdb->insert(
                $table_name,
                [
                    'plate_name' => $plate_key,
                    'allows' =>  json_encode($plate_data) ,
                ]
            );
        }
        }

    }
    

    public function processOrder(){
        if (isset($_POST["data"])) {

            $data = $_POST["data"];
            $plateNumber = $data["plate_number"];
            $plateStyle = $data["plate_style"];
            $plateFont = $data['plate_font'];
            $badgeType = $data["badge_type"];
            $badgeImageUrl = $data["badge_image_url"];
            $border = $data["border"];
            $bottomText = $data["bottom_text"];
            $bottomTextColor = $data["bottom_text_color"];
            $bottomTextFont = $data["bottom_text_font"];
            $bottomTextSize = $data["bottom_text_size"];
            $frontPlateQty = $data["front_plate_qty"];
            $rearPlateQty = $data["rear_plate_qty"];
            $TotalPrice = $data["total_price"];
            $frontPlatePreview =  '<img style="height:50%; width:50%;" src="' . $data["front_plate_preview"] . '">';
            $rearPlatePreview =  '<img style="height:50%; width:50%;" src="' . $data["rear_plate_preview"] . '">';
            $data['front_plate_preview'] = $frontPlatePreview;
            $data['rear_plate_preview'] = $rearPlatePreview;


            global $wpdb;
            $table_name = $wpdb->prefix . "plate_customizer_allowances";
            $plate = str_replace(' ', '_', strtolower($plateStyle)); 
            $plateAllowances = $wpdb->get_results("SELECT * FROM $table_name WHERE plate_name = '$plate'");
            $allows = [];
            if (!empty($plateAllowances)) {
                $allows = $plateAllowances[0]->allows;
            } else {
                $allows = 'No Allows Man!';
            }
            $allows = json_decode($allows);
            $priceFromDB = $allows[1];
            $frontPlatePriceFromDB = $allows[10];
            $rearPlatePriceFromDB = $allows[11];

            $priceForFront = $frontPlatePriceFromDB * $frontPlateQty;
            $priceForRear = $rearPlatePriceFromDB * $rearPlateQty;
            $TotalPriceAccurate = $priceForFront + $priceForRear;

            // if ($TotalPriceAccurate != $TotalPrice) {
            //     echo "<script>alert('Do not try to cheat nigga!')</script>";
            //     }

            $data['total_price'] = $TotalPriceAccurate;
            update_option('plate_customizer_data', $data);
            $_POST['data'] = $data;

            $selected_product_id = get_option('selected_product_id');

            if (!$selected_product_id) {
                echo 'Please select a product from the settings page.';
                return;
            }
            if (WC()->cart->add_to_cart($selected_product_id, 1, 0, array(), array('custom_data' => $data , 'price_mod' => false , 'price' => $TotalPriceAccurate)))
            echo "success";
            else{
                 echo "error adding to cart";
                }

        }
        else{
            echo 'No Data Recieved';
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'plate-customizer-site-css', plugin_dir_url( __FILE__ ) . 'assets/css/site-css.css' );

        wp_enqueue_script( 'plate-customizer-site-script', plugin_dir_url( __FILE__ ) . 'assets/js/site-script.js', [ 'jquery' ], '1.0', true );

        wp_localize_script( 'plate-customizer-site-script', 'plate_customizer_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ), ] );

            wp_enqueue_script( 'owl-carousel-js' , 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js' );

            wp_enqueue_style( 'owl-carousel-css' , 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.css' );

            wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Black+Ops+One&family=Kanit:wght@300;400&family=Share+Tech+Mono&display=swap' );

            wp_enqueue_script( 'select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js' );

            wp_enqueue_style( 'select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' );

            wp_enqueue_script( 'html2canvas-script' , plugin_dir_url( __FILE__ ) . 'assets/js/html2canvas.js' , [ 'jquery' ] , '1.0' , true );

    }

    public function admin_scripts() {
        wp_enqueue_style( 'plate-customizer-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/admin-css.css' );

        wp_enqueue_script( 'plate-customizer-admin-script', plugin_dir_url( __FILE__ ) . 'assets/js/admin-script.js', [ 'jquery', 'jquery-form' ], '1.0', true );

        if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'plate-customizer' ){
            wp_enqueue_style( 'bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' );

            wp_enqueue_script( 'bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js' );
        }

        wp_localize_script( 'plate-customizer-admin-script', 'plate_customizer_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ), ] );

            wp_enqueue_script( 'select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js' );
            wp_enqueue_script( 'jquery-ui-js', 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js' );

            wp_enqueue_style( 'select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' );

    }
//*DONE
    public function add_page() {
        add_menu_page( 'Plate Customizer', 'Plate Customizer', 'manage_options', 'plate-customizer', [ $this, 'menu_page_html' ], 'dashicons-nametag' );
    }

    public function manage_font_faces(){
        echo '<style>';
        global $wpdb;
        $table_name = $wpdb->prefix . self::$plateCustomizerDB;

        $plate_fonts = $wpdb->get_col( "SELECT plate_font FROM $table_name WHERE plate_font IS NOT NULL AND plate_font != ''" );

        foreach ($plate_fonts as $font)
        {
            $f = get_option($font) ? get_option($font) : '';
            if ($f and !empty($f))
            {
                echo $f;
            }
        }
        echo '</style>';
    }

    public function menu_page_html() {
        
        global $wpdb;
        $table_name = $wpdb->prefix . self::$plateCustomizerDB;

        $plate_styles_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='plate_style'");
        $plate_styles = [];
        foreach ($plate_styles_results as $pt) {
            $plate_styles[] = $pt->value;
        }
    
        $plate_fonts_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='plate_font'");
        $plate_fonts = [];
        foreach ($plate_fonts_results as $pt) {
            $plate_fonts[] = $pt->value;
        }
    
        $borders_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='border'");
        $borders = [];
        foreach ($borders_results as $pt) {
            $borders[] = $pt->value;
        }
    
        $bottom_text_colors_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='bottom_text_color'");
        $bottom_text_colors = [];
        foreach ($bottom_text_colors_results as $pt) {
            $bottom_text_colors[] = $pt->value;
        }
    
        $bottom_text_fonts_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='bottom_text_font'");
        $bottom_text_fonts = [];
        foreach ($bottom_text_fonts_results as $pt) {
            $bottom_text_fonts[] = $pt->value;
        }
    
        $bottom_text_sizes_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='bottom_text_size'");
        $bottom_text_sizes = [];
        foreach ($bottom_text_sizes_results as $pt) {
            $bottom_text_sizes[] = $pt->value;
        }
    
        $badge_types_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='badge_type'");
        $badge_types = [];
        foreach ($badge_types_results as $pt) {
            $badge_types[] = $pt->value;
        }

        $plate_style_images = [];

        $categories = [
            'Plate Style' => 'plate_style',
            'Plate Font' => 'plate_font',
            'Border' => 'border',
            'Bottom Text Color' => 'bottom_text_color',
            'Bottom Text Font' => 'bottom_text_font',
            'Bottom Text Size' => 'bottom_text_size',
            'Badge Type' => 'badge_type',
        ];

        echo '<hr><h1>Plate Customizer Settings</h1><hr>';


        echo '<br><div class="wrap">
        <form method="post" action="options.php">';
        settings_fields('plugin_settings');
        do_settings_sections('plugin-settings');
        echo '<h3>Plate Customizer Settings:</h3>
        <label>WooCommerce Product: </label>
        <select name="selected_product_id" style="width:10%; margin-right:10px; margin-bottom:5px;" required><option value="">-- SELECT A PRODUCT --</option>';

        $products = wc_get_products(array('post_type' => 'product'));
        $selected_product_id = get_option('selected_product_id');

        foreach ($products as $product) {
            $product_id = esc_attr($product->get_id());
            $product_name = esc_html($product->get_name());
            $selected = selected($product_id, $selected_product_id, false);
            echo "<option value='$product_id' $selected>$product_name</option>";
        }

        echo '</select>';

        $front_plate_bg_color = get_option('front_plate_bg_color');
        echo '<br><label for="front_plate_bg_color">Front Plate BG Color:</label>
        <input type="color" id="front_plate_bg_color" name="front_plate_bg_color" class="color-picker" value="' . esc_attr($front_plate_bg_color) . '" data-default-color="#ffffff" />';

        $front_plate_text_color = get_option('front_plate_text_color');
        echo '<br><label for="front_plate_text_color">Front Plate Text Color:</label>
        <input type="color" id="front_plate_text_color" name="front_plate_text_color" class="color-picker" value="' . esc_attr($front_plate_text_color) . '" data-default-color="#000000" />';

        $rear_plate_bg_color = get_option('rear_plate_bg_color');
        echo '<br><label for="rear_plate_bg_color">Rear Plate BG Color:</label>
        <input type="color" id="rear_plate_bg_color" name="rear_plate_bg_color" class="color-picker" value="' . esc_attr($rear_plate_bg_color) . '" data-default-color="#ffffff" />';

        $rear_plate_text_color = get_option('rear_plate_text_color');
        echo '<br><label for="rear_plate_text_color">Rear Plate Text Color:</label>
        <input type="color" id="rear_plate_text_color" name="rear_plate_text_color" class="color-picker" value="' . esc_attr($rear_plate_text_color) . '" data-default-color="#000000" />';

        echo submit_button('Save Settings') . '</form></div><br>';


        //Sorting Selectbox asc/dsc
        // echo '<form method="POST" action="options.php" id="sorting_form">';
        // settings_fields('plugin_settings');
        // do_settings_sections('plugin-settings');
        // echo '<select name="display_order" id="display_order_id" style="width:10%; margin-right:10px; margin-bottom:5px;">';
        // $display_order = get_option('display_order');
        // $selectedasc = selected("asc", $display_order, false);
        // $selecteddsc = selected("dsc", $display_order, false);
        // echo "<option value='asc' $selectedasc>Ascending Order</option>";
        // echo "<option value='dsc' $selecteddsc>Descending Order</option>";
        // echo '</select></form>';



        echo'<div class="modal" id="myModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h4 class="modal-title">Add New Plate Style</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                
                        <!-- Modal body -->
                        <div class="modal-body">
                            <form method="POST" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">
                                <input type="hidden" name="action" value="plate_customizer_handle_form">
                                <div class="form-group">
                                    <label for="plate_style">Plate Style</label>
                                    <input id="plate-customizer-plate-style" required type="text" name="plate_style" class="form-control">
                                </div>
                                <input id="plate-customizer-old-name" type="hidden" name="old_name" class="form-control">
                                <div class="form-group">
                                    <label for="plate_style_price">Price</label>
                                    <input id="plate-customizer-plate-price-set" required type="number" name="plate_style_price" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="plate_style_front_plate_price">Front Plate Price</label>
                                    <input id="plate-customizer-front-plate-price-set" required readonly type="number" name="plate_style_front_plate_price" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="plate_style_rear_plate_price">Rear Plate Price</label>
                                    <input id="plate-customizer-rear-plate-price-set" required readonly type="number" name="plate_style_rear_plate_price" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="plate_style_images">Plate Style Images</label>
                                    <input type="file" style="width:50%;" multiple="multiple" id="plate_style_imgs" name="plate_style_images[]"  class="form-control">
                                    <select name="plate_style_images2[]" multiple="multiple" class="form-control plate_style_imgs">
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="plate_style_fonts">Allowed Fonts</label>
                                    <select name="plate_style_fonts[]" multiple="multiple" class="form-control plate_style_fonts">
                                    ';
                                    
                                    foreach ( $plate_fonts as $font ) {
                                        echo '<option value="'. $font .'">'. $font .'</option>';
                                    }
                                    
                                    echo '</select>
                                </div>
                                <div class="form-group">
                                    <label for="plate_style_badge_type">Allowed Badge Type</label>
                                    <select name="plate_style_badge_type[]" multiple="multiple" class="form-control plate_style_badge_type">
                                    ';
                                    foreach ( $badge_types  as $btype ) {
                                        echo '<option value="'. $btype .'">'. $btype .'</option>';
                                    }
                                
                                    echo '</select>
                                </div>
                                <div class="form-group">
                                    <label for="plate_style_border">Allowed Border</label>
                                    <select name="plate_style_border[]" multiple="multiple" class="form-control plate_style_border">
                                    ';
                                    foreach ( $borders as $border ) {
                                        echo '<option value="'. $border .'">'. $border .'</option>';
                                    }

                                    echo '</select>
                                </div>
                                <div class="form-group">
                                    <label for="plate_style_bottom_text_color">Allowed Bottom Text Color</label>
                                    <select name="plate_style_bottom_text_color[]" multiple="multiple" class="form-control plate_style_bottom_text_color">
                                    ';
                                    foreach ( $bottom_text_colors as $btc ) {
                                        echo '<option value="'. $btc .'">'. $btc .'</option>';
                                    }
                                    echo '</select>
                                </div>
                                <div class="form-group">
                                    <label for="plate_style_bottom_text_font">Allowed Bottom Text Font</label>
                                    <select name="plate_style_bottom_text_font[]" multiple="multiple" class="form-control plate_style_bottom_text_font">
                                    ';
                                    foreach ( $bottom_text_fonts  as $btf ) {
                                        echo '<option value="'. $btf .'">'. $btf .'</option>';
                                    }
                                    echo '</select>
                                </div>
                                <div class="form-group">
                                    <label for="plate_style_bottom_text_size">Allowed Bottom Text Size</label>
                                    <select name="plate_style_bottom_text_size[]" multiple="multiple" class="form-control plate_style_bottom_text_size">
                                    ';
                                    foreach ( $bottom_text_sizes  as $bts ) {
                                        echo '<option value="'. $bts .'">'. $bts .'</option>';
                                    }

                                    echo '</select>
                                </div>

                                <br>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </form>
                        </div>
                
                    </div>
                </div>
            </div>';
        
        foreach ($categories as $categoryName => $columnName) {
            $values = [];
            $vals = $wpdb->get_results("SELECT value FROM $table_name WHERE type='$columnName'");
            foreach ($vals as $val) {
                $values[] = $val->value;
            }
        
            if ($categoryName == 'Plate Style') {
                echo '<div style="display: flex;"><h2 class="category-title">' . $categoryName . '</h2> &nbsp;&nbsp; <button class="btn btn-primary add-plate-style" data-bs-toggle="modal" data-bs-target="#myModal">&nbsp;+&nbsp; Add New</button></div>';
            } else if ($categoryName == 'Badge Type') {
                echo '<div style="display: flex;"><h2 class="category-title">' . $categoryName . '</h2>  &nbsp;&nbsp;  <button class="btn btn-success add-prop">&nbsp;+&nbsp; Add New</button> </div><p style="color: dodgerblue; font-family: monospace; font-size: 12px; margin-top: 20px;">&#9432; Badge images have to be uploaded manually</p>';
            } else {
                echo '<div style="display: flex;"><h2 class="category-title">' . $categoryName . '</h2>  &nbsp;&nbsp;  <button class="btn btn-success add-prop">&nbsp;+&nbsp; Add New</button> </div>';
            }
        
            echo '<table class="wp-list-table table fixed properties-table">';
            echo '<thead><tr><th style="width:2%;"></th><th>Value</th><th>Action</th></tr></thead>';
            echo '<tbody class="sortable" data-column-name="' . esc_attr($columnName) . '">';
        
            foreach ($values as $value) {
                if ($categoryName == "Plate Style") {
                    echo '<tr data-value="' . esc_attr($value) . '">
                            <td class="handle">&#9776;</td>
                            <td>' . esc_html($value) . '</td>
                            <td>
                                <button class="btn btn-warning edit-this">&nbsp;&nbsp;Edit&nbsp;&nbsp;</button>
                                &nbsp;&nbsp;<button class="btn btn-danger drop-this">Remove</button>
                            </td>
                        </tr>';
                } else {
                    echo '<tr data-value="' . esc_attr($value) . '">
                            <td class="handle">&#9776;</td>
                            <td>' . esc_html($value) . '</td>
                            <td>
                                <button class="btn btn-danger drop-this">Remove</button>
                            </td>
                        </tr>';
                }
            }
        
            echo '</tbody>';
            echo '</table>';
        }
        
        //Works with asc/dsc ordering
        // foreach ($categories as $categoryName => $columnName) {
        //     $values = [];
        //     $display_order = get_option('display_order');
        //     if ($display_order == "dsc") {
        //         $vals = $wpdb->get_results("SELECT value FROM $table_name WHERE type='$columnName' ORDER BY value DESC");
        //     } else {
        //         $vals = $wpdb->get_results("SELECT value FROM $table_name WHERE type='$columnName' ORDER BY value ASC");
        //     }
        //     foreach ($vals as $val) {
        //         $values[] = $val->value;
        //     }
        
        //     if ($categoryName == 'Plate Style') {
        //         echo '<div style="display: flex;"><h2 class="category-title">' . $categoryName . '</h2> &nbsp;&nbsp; <button class="btn btn-primary add-plate-style" data-bs-toggle="modal" data-bs-target="#myModal">&nbsp;+&nbsp; Add New</button></div>';
        //     } else if ($categoryName == 'Badge Type') {
        //         echo '<div style="display: flex;"><h2 class="category-title">' . $categoryName . '</h2>  &nbsp;&nbsp;  <button class="btn btn-success add-prop">&nbsp;+&nbsp; Add New</button> </div><p style="color: dodgerblue; font-family: monospace; font-size: 12px; margin-top: 20px;">&#9432; Badge images have to be uploaded manually</p>';
        //     } else {
        //         echo '<div style="display: flex;"><h2 class="category-title">' . $categoryName . '</h2>  &nbsp;&nbsp;  <button class="btn btn-success add-prop">&nbsp;+&nbsp; Add New</button> </div>';
        //     }
        
        //     echo '<table class="wp-list-table table fixed properties-table">';
        //     echo '<thead><tr><th>Value</th><th>Action</th></tr></thead>';
        //     echo '<tbody class="sortable">';
        
        //     foreach ($values as $value) {
        //         if ($categoryName == "Plate Style") {
        //             echo '<tr><td>' . esc_html($value) . '</td><td><button class="btn btn-warning edit-this">&nbsp;&nbsp;Edit&nbsp;&nbsp;</button>&nbsp;&nbsp;<button class="btn btn-danger drop-this">Remove</button></td></tr>';
        //         } else {
        //             echo '<tr><td>' . esc_html($value) . '</td><td><button class="btn btn-danger drop-this">Remove</button></td></tr>';
        //         }
        //     }
        
        //     echo '</tbody>';
        //     echo '</table>';
        // }
            
    }

    public function handle_form_submission(){

        global $wpdb;
        $table_name = $wpdb->prefix . 'plate_customizer_allowances';

        $plate_style = isset( $_POST[ 'plate_style' ] ) ? sanitize_text_field( $_POST[ 'plate_style' ] ) : '';
        $old_name = isset( $_POST[ 'old_name' ] ) ? sanitize_text_field( $_POST[ 'old_name' ] ) : '';
        $plate_style_price = isset( $_POST[ 'plate_style_price' ] ) ? intval( $_POST[ 'plate_style_price' ] ) : 0;
        $plate_style_fonts = isset( $_POST[ 'plate_style_fonts' ] ) ? $_POST[ 'plate_style_fonts' ] : [];
        $plate_style_border = isset( $_POST[ 'plate_style_border' ] ) ? $_POST[ 'plate_style_border' ] : [];
        $plate_style_bottom_text_color = isset( $_POST[ 'plate_style_bottom_text_color' ] ) ? $_POST[ 'plate_style_bottom_text_color' ] : [];
        $plate_style_images2 = isset( $_POST[ 'plate_style_images2' ] ) ? $_POST[ 'plate_style_images2' ] : [];
        $plate_style_bottom_text_font = isset( $_POST[ 'plate_style_bottom_text_font' ] ) ? $_POST[ 'plate_style_bottom_text_font' ] : [];
        $plate_style_bottom_text_size = isset( $_POST[ 'plate_style_bottom_text_size' ] ) ? $_POST[ 'plate_style_bottom_text_size' ] : [];
        $plate_style_badge_type = isset( $_POST[ 'plate_style_badge_type' ] ) ? $_POST[ 'plate_style_badge_type' ] : [];
        $plate_style_front_plate_price = isset( $_POST[ 'plate_style_front_plate_price' ] ) ? intval( $_POST[ 'plate_style_front_plate_price' ] ) : 0;
        $plate_style_rear_plate_price = isset( $_POST[ 'plate_style_rear_plate_price' ] ) ? intval( $_POST[ 'plate_style_rear_plate_price' ] ) : 0;

        $plate_style_badge_images = [];
        foreach ($plate_style_badge_type as $key => $value) {
            $plate_style_badge_images[$key] = site_url() . '/wp-content/plugins/plate-customizer/assets/images/badge_images/' . strtolower(str_replace(' ', '_', $value));
        }

        $plate_style_images_array = [];
        if ( !empty( $_FILES[ 'plate_style_images' ][ 'name' ][ 0 ] ) ) {
            $upload_dir = wp_upload_dir();

            foreach ( $_FILES[ 'plate_style_images' ][ 'name' ] as $key => $name ) {
                $tmp_name = $_FILES[ 'plate_style_images' ][ 'tmp_name' ][ $key ];
                $file_name = sanitize_file_name( $name );

                $path_to_save = site_url() . '/wp-content/plugins/plate-customizer/assets/images/plate_images/' . $file_name;
                $file_path = ABSPATH . '/wp-content/plugins/plate-customizer/assets/images/plate_images/' . $file_name;

                move_uploaded_file( $tmp_name, $file_path );

                $plate_style_images_array[] = $path_to_save;

            }
        }

        $plate_key = str_replace( ' ', '_', strtolower( $plate_style ) );
        // $old_name = str_replace( ' ', '_', strtolower( $old_name ) );

        $existing_plate = $wpdb->get_var( $wpdb->prepare( "SELECT plate_name FROM $table_name WHERE plate_name = %s", str_replace( ' ', '_', strtolower( $old_name ))  ) );

        $existing_plate_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE plate_name = %s",str_replace( ' ', '_', strtolower( $old_name ) ) ) );

        if ($existing_plate_id) {
            // $existing_plate_images = $wpdb->get_var( $wpdb->prepare( "SELECT allows FROM $table_name WHERE plate_name = %s", str_replace( ' ', '_', strtolower( $old_name ))  ) );
            // $existing_plate_images = json_decode($existing_plate_images)[2];
            $plate_style_images_array = array_merge($plate_style_images2, $plate_style_images_array);

        }

    

            $plate_data = [
                $plate_style,
                $plate_style_price,
                $plate_style_images_array,
                $plate_style_fonts,
                $plate_style_border,
                $plate_style_bottom_text_color,
                $plate_style_bottom_text_font,
                $plate_style_bottom_text_size,
                $plate_style_badge_type,
                $plate_style_badge_images,
                $plate_style_front_plate_price,
                $plate_style_rear_plate_price,
            ];

            if ($existing_plate == $plate_key)
            {
                $wpdb->update(
                    $table_name,
                    [
                        'allows' =>  json_encode( $plate_data ) ,
                    ],
                    [
                        'plate_name' => $plate_key,
                    ]
                );
            
            }
            else if ($existing_plate && !empty($existing_plate))
            {
                $wpdb->update(
                    $table_name,
                    [
                        'plate_name' => $plate_key,
                        'allows' =>  json_encode( $plate_data ) ,
                    ],
                    [
                        'id' => $existing_plate_id
                    ]
                );


                $table_name = $wpdb->prefix . self::$plateCustomizerDB;
                $prepared_query = $wpdb->prepare("SELECT id FROM $table_name WHERE type = %s AND value = %s", "plate_style", $old_name);
                $id = $wpdb->get_var($prepared_query);
                
                $wpdb->update(
                    $table_name,
                    [
                        'value' => $plate_style,
                    ],
                    [
                        'type' => 'plate_style',
                        'id' => $id
                    ]
                );

            }
            else {
                $wpdb->insert(
                    $table_name,
                    [
                        'plate_name' => $plate_key,
                        'allows' =>  json_encode( $plate_data ) ,
                    ]
                );

                $_POST[ 'property' ] = 'plate_style';
                $_POST[ 'value' ] = sanitize_text_field( $_POST[ 'plate_style' ] );
                $this->save_new_value();
            }


            wp_redirect( admin_url( 'admin.php?page=plate-customizer' ) );
            exit;
    }

    public function fetch_data_for_plate(){
        if (!isset($_POST['plate_name'] )) {
            echo "No plate name specified";
            return;
        }
        $plate_name = sanitize_text_field($_POST['plate_name']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'plate_customizer_allowances';

        $query = $wpdb->prepare( "SELECT * FROM $table_name WHERE plate_name = %s", $plate_name );
        $result = $wpdb->get_results( $query );

        if ( !empty( $result ) ) {
            $data = $result[ 0 ]->allows;
            unset($_POST['plate_name']);
            print_r($data);
            return;
        } else {
            return 'No data found for ' . $plate_name;
        }

    }

    public function save_font(){
        if (isset($_FILES['plate_customizer_font_file'])) {
            $upload_dir = wp_upload_dir();
            $file_name = $_FILES['plate_customizer_font_file']['name'];
            $temp_file_name = $_FILES['plate_customizer_font_file']['tmp_name'];
            $folder = trailingslashit($upload_dir['path']);
            $destination = $folder . $file_name;
    
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

            $allowed_extensions = ['otf', 'ttf', 'woff', 'woff2'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                if (move_uploaded_file($temp_file_name, $destination)) {
                    $font_folder = trailingslashit(get_template_directory()) . 'fonts/';
                    
                    if (!file_exists($font_folder)) {
                        wp_mkdir_p($font_folder);
                    }
        
                    $font_name = isset($_POST['font-name']) ? sanitize_text_field($_POST['font-name']) : pathinfo($file_name, PATHINFO_FILENAME);
        
                    $upload_dir = wp_upload_dir();
                    $upload_url = $upload_dir['baseurl'];
                    $month = strval(date('n'));
                    if (strlen($month) == 1) {
                        $month = '0' . $month;
                        }

                    $destination = $upload_url . '/' .date( "Y" ) . '/' . $month . '/' . $file_name;



                    $font_face = "@font-face {
                        font-family: '$font_name';
                        src: url('$destination');
                    }       
                    ";
        

                    update_option($font_name , $font_face);
        
                    echo '<script>alert("Font-face created successfully.");</script>';
                } else {
                    echo '<script>alert("Font file upload failed.");</script>';
                }
            } else {
                echo '<script>alert("Invalid file type. Only OTF, TTF, WOFF, and WOFF2 files are allowed.");</script>';
            }
        }
    }

    public function save_new_value(){
        if ( isset( $_POST[ 'property' ] ) && isset( $_POST[ 'value' ] ) ) {
            $property = sanitize_text_field( $_POST[ 'property' ] );
            $value = sanitize_text_field( $_POST[ 'value' ] );
    
            if ( $property && $value ) {
                global $wpdb;
                $table_name = $wpdb->prefix . self::$plateCustomizerDB;
    
                $existing_value = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT value FROM $table_name WHERE type = %s AND value = %s",
                        $property,
                        $value
                    )
                );
    
                if ( $existing_value === $value ) {
                    echo 'Value already exists in the database.';
                } else {
                    $insert_result = $wpdb->insert(
                        $table_name,
                        [
                            'type' => $property,
                            'value' => $value,
                        ]
                    );
    
                    if ( $insert_result ) {
                        echo 'success';
                    } else {
                        echo 'Failed to insert into the database.';
                    }
                }
            } else {
                echo 'Property or value is empty.';
            }
        } else {
            echo 'Missing property or value in the POST request.';
        }
    
        if ( $property == 'plate_style' ) {
            wp_redirect( admin_url( 'admin.php?page=plate-customizer' ) );
        }
        exit;
    }

    public function remove_value(){
        if ( isset( $_POST[ 'property' ] ) && isset( $_POST[ 'value' ] ) ) {
            $property = sanitize_text_field( $_POST[ 'property' ] );
            $property = str_replace( ' ', '_', strtolower( $property ) );
            $value = sanitize_text_field( $_POST[ 'value' ] );

            if ( $property && $value ) {
                global $wpdb;
                $table_name = $wpdb->prefix . self::$plateCustomizerDB;

                $delete_result = $wpdb->delete(
                    $table_name,
                    [
                        'type' => $property,
                        'value' => $value,
                    ]
                );

                if ( $delete_result ) {
                    echo 'success';
                } else {
                    echo 'Failed to remove the value from the database.';
                }
            } else {
                echo 'Property or value is empty.';
            }
        } else {
            echo 'Missing property or value in the POST request.';
        }

        if ( $property == 'plate_style' ){

            $value = str_replace( ' ', '_', strtolower( $value ) );
            global $wpdb;
            $table_name = $wpdb->prefix . 'plate_customizer_allowances';

            $wpdb->delete(
                $table_name,
                array( 'plate_name' => $value ),
                array( '%s' )
            );

        }

        wp_die();

    }

    public function fetchAllPlateCustomizerAllowances(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'plate_customizer_allowances';
        $sql = "SELECT * FROM $table_name";
        $results = $wpdb->get_results( $sql, ARRAY_A );
        return $results;
    }

    public function fetch_plate_customizer_allowances(){
        $plateCustomizerAllowances = $this->fetchAllPlateCustomizerAllowances();
        foreach ( $plateCustomizerAllowances as $key => $value ) {
            $plateCustomizerAllowances[ $key ]['allows'] = json_decode( $value['allows'], true );
        }
        $_POST = [];
        wp_send_json( $plateCustomizerAllowances);
    }

    public function site_html(){
        global $wpdb;
        $table_name = $wpdb->prefix . self::$plateCustomizerDB;
        ob_start();

        $plate_styles_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='plate_style'");
        $plate_styles = [];
        foreach ($plate_styles_results as $pt) {
            $plate_styles[] = $pt->value;
        }
    
        $plate_fonts_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='plate_font'");
        $plate_fonts = [];
        foreach ($plate_fonts_results as $pt) {
            $plate_fonts[] = $pt->value;
        }
    
        $borders_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='border'");
        $borders = [];
        foreach ($borders_results as $pt) {
            $borders[] = $pt->value;
        }
    
        $bottom_text_colors_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='bottom_text_color'");
        $bottom_text_colors = [];
        foreach ($bottom_text_colors_results as $pt) {
            $bottom_text_colors[] = $pt->value;
        }
    
        $bottom_text_fonts_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='bottom_text_font'");
        $bottom_text_fonts = [];
        foreach ($bottom_text_fonts_results as $pt) {
            $bottom_text_fonts[] = $pt->value;
        }
    
        $bottom_text_sizes_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='bottom_text_size'");
        $bottom_text_sizes = [];
        foreach ($bottom_text_sizes_results as $pt) {
            $bottom_text_sizes[] = $pt->value;
        }
    
        $badge_types_results = $wpdb->get_results("SELECT value FROM $table_name WHERE type='badge_type'");
        $badge_types = [];
        foreach ($badge_types_results as $pt) {
            $badge_types[] = $pt->value;
        }


        echo '<div id="wrapper-div">';
        echo '<div class="card w-100" id="card-for-plate">
        <div class="card-body">
        <h4 class="card-title">Let`s Make a Plate</h4>
        <p class="card-text">
        <form name="order_form" id="plate_form" action="" method="POST" enctype="multipart/form-data">

        <input oninput="this.value = this.value.toUpperCase()" type="text" minlength="1" maxlength="9" id="plate_number_input" class="form-control" placeholder="Enter Plate Number...">

        <br>
        <h6 id="plate_style_heading">Step 1- Plate Style</h6>        
        <select id="plate_style_select" class="plate-customizer-select">';
        foreach ( $plate_styles as $style ) {
            $styleId = str_replace( ' ', '_', strtolower( $style ) );
            echo '<option id="' . $styleId . '" value="' . $styleId . '">' . esc_html( $style ) . '</option>';
        }
        echo '</select>';

        $plate_images = scandir( plugin_dir_path( __FILE__ ) . 'assets/images/plate_images' );

        echo '<br><br>';
        


        echo "<div id='plate-customizer-carousl'>";
        echo '<div class="owl-carousel owl-theme">';
        foreach ( $plate_images as $image ) {
            if ( $image == '.' || $image == '..' ) {
                continue;
            }
            else{
            echo '<div class="carousel-item" style="display:block;">';
            echo '<img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/plate_images/' . $image . '" class="d-block w-100">';
            echo '</div>';
            }
        }
        echo '</div>';
        echo '</div>';




        echo '
        <h6 id="fonts_heading">Fonts</h6>
        <select id="fonts_select"  class="plate-customizer-select">';
        foreach ( $plate_fonts as $font ) {
            $fontId = str_replace( ' ', '_', strtolower( $font ) );
            echo '<option id="' . $fontId . '" value="' . $fontId . '">' . esc_html( $font ) . '</option>';
        }
        echo '</select>';

        

        echo '
        <h6 id="badge_type_heading">Badge Type</h6>
        <select id="badge_type_select"  class="plate-customizer-select">';
        foreach ( $badge_types as $badge_type ) {
            $badgeTypeId = str_replace( ' ', '_', strtolower( $badge_type ) );
            echo '<option id="badge_type_' . $badgeTypeId . '" value="badge_type_' . $badgeTypeId . '">' . esc_html( $badge_type ) . '</option>';
        }
        echo '</select>';

        
        $plugin_dir = plugin_dir_path(__FILE__);
        $badge_images_dir = $plugin_dir . 'assets/images/badge_images';
        $badge_folders = array_filter(glob($badge_images_dir . '/*'), 'is_dir');

        echo '<h6 id="badge_image_heading">Badge Image</h6>';
        echo '<select id="badge_image_select" style="width: 200px;"  class="plate-customizer-select">';

        foreach ($badge_folders as $folder) {
            $folder_name = strtoupper(str_replace('_', ' ', basename($folder)));
            $images_in_folder = glob($folder . '/*.{jpg,png,gif}', GLOB_BRACE);

            foreach ($images_in_folder as $image_url) {
                $img_src = plugin_dir_url(__FILE__) . 'assets/images/badge_images/' . basename($folder) . '/' . basename($image_url);
                echo '<option value="' . $img_src . '"  data-img_src="' . $img_src . '">'  . '</option>';
            }
        }

        echo '</select>';

        echo '
        <h6 id="border_heading">Border</h6>
        <select id="border_select"  class="plate-customizer-select">';
        foreach ( $borders as $border ) {
            $borderId = str_replace( ' ', '_', strtolower( $border ) );
            echo '<option id="border_' . $borderId . '" value="border_' . $borderId . '">' . esc_html( $border ) . '</option>';
        }
        echo '</select>';

        echo '
        <h6 id="bottom_text_heading">Bottom Text</h6>
        <input id="bottom_text_input" oninput="this.value = this.value.toUpperCase()" minlength="0" maxlength="40" type="text" class="form-control" placeholder="Enter Bottom Text">';


        echo '
        <h6 id="bottom_text_color_heading">Bottom Text Color</h6>
        <select id="bottom_text_color_select"  class="plate-customizer-select">';
        foreach ( $bottom_text_colors as $color ) {
            $colorId = str_replace( ' ', '_', strtolower( $color ) );
            echo '<option id="bottom_text_color_' . $colorId . '" value="bottom_text_color_' . $colorId . '">' . esc_html( $color ) . '</option>';
        }
        echo '</select>';

        echo '
        <h6 id="bottom_text_font_heading">Bottom Text Font</h6>
        <select id="bottom_text_font_select"  class="plate-customizer-select">';
        foreach ( $bottom_text_fonts as $font ) {
            $fontId = str_replace( ' ', '_', strtolower( $font ) );
            echo '<option id="bottom_text_font_' . $fontId . '" value="bottom_text_font_' . $fontId . '">' . esc_html( $font ) . '</option>';
        }
        echo '</select>';

        echo '
        <h6 id="bottom_text_size_heading">Bottom Text Size</h6>
        <select id="bottom_text_size_select"  class="plate-customizer-select">';
        foreach ( $bottom_text_sizes as $size ) {
            $sizeId = str_replace( ' ', '_', strtolower( $size ) );
            echo '<option id="bottom_text_size_' . $sizeId . '" value="bottom_text_size_' . $sizeId . '">' . esc_html( $size ) . '</option>';
        }
        echo '</select>';

        echo '
        <div class="plate-btns" style="margin-top: 25px;">

                <div>
                <h5>Front Plates</h5>
                </div>

                <div>
                <button id="minus-front">-</button>
                <div id="front-plate-quantity" style="display:inline;">1</div>
                <button id="plus-front">+</button>
                </div>

                </div>
                ';

                echo '
                <div class="plate-btns">

                <div>
                <h5>Rear Plates</h5>
                </div>

                <div>
                <button id="minus-rear">-</button>
                <div id="rear-plate-quantity" style="display:inline;">1</div>
                <button id="plus-rear">+</button>
                </div>

                </div>
                ';


                echo '<input type="hidden" id="sumit_order" name="submit_order" href="#" class="btn btn-primary">
            </form>
            </div>
        </div>';

    

        echo '<div id="wrap-2">
        <div class="card preview-card" style="width: 18rem;">
        <div class="card-body preview-card-body" id="front-plate-preview" style="background-color:' .  get_option('front_plate_bg_color') . '">
        
        
        
        <div class="preview-div">
        <h1 class="preview-div-text" style="color: ' . get_option('front_plate_text_color') . '">Preview</h1>
        <p class="bottom-text-p"  style="background-color: '. get_option('front_plate_bg_color') . '"></p>
        </div>
        
        </div>
        </div>


        <br><br>

        <div class="card preview-card" style="width: 18rem;">
        <div class="card-body preview-card-body" id="rear-plate-preview" style="background-color:' .  get_option('rear_plate_bg_color') . '">
        
        
        
        <div class="preview-div">
        <h1 class="preview-div-text" style="color: ' . get_option('rear_plate_text_color') . '">Preview</h1>
        <p class="bottom-text-p" style="background-color: '. get_option('rear_plate_bg_color') . '"></p>
        </div>
        
        </div>
        </div>

        <div style="margin-top:70px;">
        <div>


        <div id="finCustomizer" >
        <h5 style="display:inline;">Plate Style: </h5><h5 id="finalStyle" style="display:inline;"> </h5> <br>
        <h5 style="display:inline;">Qty: </h5> <h5 id="finalQty" style="display:inline;">1 Front 1 rear</h5> <br>
        <h5 style="display:inline;">Price: <p id="priceWalaP">'; echo get_woocommerce_currency_symbol(); echo ' </p></h5><h5 id="total_price" style="display:inline;"></h5> <br>
        <button id="addToCart" class="btn btn-primary">Add to Cart</button>
        </div>
        
        </div>
        </div>


        </div>';

        

        echo '</div><style>.sidebar { display: none; }</style>';
            
            return ob_get_clean();
    }
    
    public function update_sort_order_callback() {
        $property = $_POST['column'];
        $order = $_POST['order'];

        global $wpdb;
        $table_name = $wpdb->prefix . self::$plateCustomizerDB;

        $order_values = explode(',', $order);
        foreach ($order_values as $index => $value) {
            $delete_result = $wpdb->delete(
                $table_name,
                [
                    'type' => $property,
                    'value' => $value,
                ]
            );
    
            if ( $delete_result ) {
                echo 'success';
            } else {
                echo 'Failed to remove the value from the database.';
            }
        }

        foreach ($order_values as $index => $value) {
            $insert_result = $wpdb->insert(
                $table_name,
                [
                    'type' => $property,
                    'value' => $value,
                ]
            );

            if ( $insert_result ) {
                echo 'success';
            } else {
                echo 'Failed to insert into the database.';
            }
        }

        wp_send_json_success("Sort order updated successfully.");
    }


}

new PlateCustomizer();