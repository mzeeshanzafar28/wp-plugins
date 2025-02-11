<?php
/*
Plugin Name: Detective Axon
Author: M Zeeshan Zafar
Author URI: https://www.linkedin.com/in/m-zeeshan-zafar-9205a1248/
Description: SEO plugin for your woocommerce store, counts the traffic on your site, generates custom invite links, keeps track of users coming from all social platforms such as Facebook, Instagram and Organic traffic etc.
Version: 1.0.0
License: GPLv2 or later
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class DetectiveAxon {
    private $utm_param_name = 'utm_source';
    private $platforms = [ 'facebook', 'twitter', 'linkedin', 'instagram', 'whatsapp', 'tiktok', 'snapchat', 'pinterest', 'youtube', 'reddit', 'tumblr', 'medium', 'vimeo', 'twitch', 'spotify' ];
    private $user_tracking_table_name = 'detective_user_tracking';
    private $links_table_name = 'detective_links';

    public function __construct() {
        register_activation_hook( __FILE__, [ $this, 'activation_action' ] );
        add_action( 'admin_menu', [ $this, 'settings_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
        add_action( 'init', [ $this, 'handle_source' ] );
        add_action( 'woocommerce_thankyou', [ $this, 'store_source' ] );
        add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_order_column' ] );
        add_action( 'manage_shop_order_posts_custom_column', [ $this, 'display_order_column_data' ], 10, 2 );

        add_action( 'init', [ $this, 'schedule_visits_update' ] );
        add_action( 'daily_visits_update', [ $this, 'update_visits' ] );

        add_action( 'wp_loaded', [ $this, 'schedule_weekly_reset' ] );
        add_action( 'wp_loaded', [ $this, 'schedule_monthly_reset' ] );
        add_action( 'wp_loaded', [ $this, 'schedule_yearly_reset' ] );

        add_action( 'weekly_visits_reset', [ $this, 'weekly_visits_reset' ] );
        add_action( 'monthly_visits_reset', [ $this, 'monthly_visits_reset' ] );
        add_action( 'yearly_visits_reset', [ $this, 'yearly_visits_reset' ] );
    }

    function activation_action() {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->user_tracking_table_name;

        $q = "CREATE TABLE IF NOT EXISTS `$table_name` (
            `order_id` INT(11) NOT NULL,
            `source` VARCHAR(255) NOT NULL,
            `visit_time` TIMESTAMP NOT NULL,
            PRIMARY KEY (`order_id`)
        ) ENGINE = InnoDB;";

    $wpdb->query( $q );

    $table_name = $wpdb->prefix . $this->links_table_name;
    $q = "CREATE TABLE IF NOT EXISTS `$table_name` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `links` VARCHAR(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB;";

    $wpdb->query( $q );


    foreach ( $this->platforms as $pt ) {
    $invite_link = add_query_arg( [ $this->utm_param_name => $pt ], home_url() );
    $wpdb->insert(
        $table_name,
        array(
            'links' => $invite_link,
        )
    );


}

        add_option( 'detective_site_visits', 0 );
        add_option( 'detective_site_visits_this_week', 0 );
        add_option( 'detective_site_visits_this_month', 0 );
        add_option( 'detective_site_visits_this_year', 0 );

    }

    function settings_page() {
        add_menu_page(
            'Detective Axon',
            'Detective Axon',
            'manage_options',
            'detective_axon',
            [ $this, 'settings_page_html' ],
            plugins_url( 'assets/images/detective.png', __FILE__ )
        );
    }

    function settings_page_html() {
        echo '<h1 class="center">Detective Axon</h1>';

        echo '<div class="detective-container">';

        echo '<div class="custom-link-section">';
        echo '<h2>Generate Custom Link</h2>';
        echo '<form method="post" action="" name="custom_link_form" id="custom_link_form">';
        echo '<label for="platform">Select Platform:</label>';
        echo '<select name="platform" id="platform">';
        foreach ( $this->platforms as $pt ) {
            echo '<option value="' . $pt . '">' . ucwords( $pt ) . '</option>';
        }
        echo '</select><br><br>';
        echo '<label for="parameter">Enter Parameter:</label>';
        echo '<input type="text" name="parameter" id="parameter"><br>';
        echo '<p style="color:black;">e.g. <strong>axon=0123</strong></p>';
        echo '<input type="submit" name="generate_link" value="Generate">';
        echo '</form>';
        echo '</div>';

        if ( isset( $_POST[ 'generate_link' ] ) ) {
            $platform = sanitize_text_field( $_POST[ 'platform' ] );
            $parameter = sanitize_text_field( $_POST[ 'parameter' ] );
            $custom_link = add_query_arg( [ $this->utm_param_name => $platform ], home_url() ) . '-' . $parameter;
        
            global $wpdb;
            $table_name = $wpdb->prefix . $this->links_table_name;
            $wpdb->insert(
                $table_name,
                array(
                    'links' => sanitize_text_field( $custom_link ),
                )
            );
        }
        

        global $wpdb;
        $table_name = $wpdb->prefix . $this->links_table_name;
        $results = $wpdb->get_results("SELECT links FROM $table_name");
        $links = array_column($results, 'links');
        
        echo '<div class="invite-links-section">';
        echo '<h2>Invite Links</h2>';
        $i = 0;
        
        foreach ($links as $link) {
            if (isset($this->platforms[$i])) {
                $platform = $this->platforms[$i];
            } else {
                $platform = 'Custom ';
            }
        
            echo '<p><strong>' . ucwords($platform) . ' Invite Link:</strong> ';
            echo '<a href="' . $link . '">' . $link . '</a>';
        
            echo '</p>';
            $i++;
        }
        
        echo '</div>';
        
        
        

        echo '<div class="user-tracking-section">';
        echo '<h2>User Tracking</h2>';
        $tracked_data_grouped = $this->get_tracked_user_data_grouped();
        if ( ! empty( $tracked_data_grouped ) ) {
            echo '<div class="tracking-data-table">';
            echo '<table>';
            echo '<tr><th>Source Platform</th><th>Number of Orders</th></tr>';
            foreach ( $tracked_data_grouped as $source => $order_count ) {
                echo '<tr><td>' . ucwords( $source ) . '</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . esc_html( $order_count ) . '</td></tr>';
            }
            echo '</table>';
            echo '</div>';
        } else {
            echo '<p>No user tracking data available.</p>';
        }
        echo '</div>';

        $visits_today = intval( get_option( 'detective_site_visits' ) );
        $visits_this_week = intval( get_option( 'detective_site_visits_this_week' ) );
        $visits_this_month = intval( get_option( 'detective_site_visits_this_month' ) );
        $visits_this_year = intval( get_option( 'detective_site_visits_this_year' ) );
        echo '<div class="site-visits-section">';
        echo '<h2>Site Visits</h2>';
        echo '<table>';
        echo '<tr><th>Today</th><th>This Week</th><th>This Month</th><th>This Year</th></tr>';
        echo '<tr><td>' . $visits_today . '</td><td>'.$visits_this_week.'</td><td>'.$visits_this_month.'</td><td>'.$visits_this_year.'</td></tr>';
        echo '</table>';
        echo '</div>';

        echo '</div>';
    }

    function admin_scripts() {
        wp_enqueue_style( 'detective-stylesheet', plugins_url( 'assets/css/detective.css', __FILE__ ) );
        wp_enqueue_script( 'detective-script', plugins_url( 'assets/js/detective.js', __FILE__ ) );
    }

    function handle_source() {
        if ( isset( $_GET[ $this->utm_param_name ] ) && ! empty( $_GET[ $this->utm_param_name ] ) ) {
            $source = sanitize_text_field( $_GET[ $this->utm_param_name ] );
            WC()->session->set( 'source', $source );
            $this->new_visit();
        }
    }

    function new_visit()
 {
        $visit = intval( get_option( 'detective_site_visits' ) );
        $visit++;
        update_option( 'detective_site_visits', $visit );
    }

    function schedule_visits_update()
 {
        $next_midnight = strtotime( 'tomorrow midnight' );
        wp_schedule_single_event( $next_midnight, 'daily_visits_update' );
    }

    function update_visits()
 {

        $visits_today = intval( get_option( 'detective_site_visits' ) );
        $visits_this_week = intval( get_option( 'detective_site_visits_this_week' ) );
        $visits_this_month = intval( get_option( 'detective_site_visits_this_month' ) );
        $visits_this_year = intval( get_option( 'detective_site_visits_this_year' ) );
        $visits_this_week += $visits_today;
        $visits_this_month += $visits_today;
        $visits_this_year += $visits_today;
        update_option( 'detective_site_visits', 0 );
        update_option( 'detective_site_visits_this_week', $visits_this_week );
        update_option( 'detective_site_visits_this_month', $visits_this_month );
        update_option( 'detective_site_visits_this_year', $visits_this_year );

    }

    function schedule_weekly_reset() {
        wp_schedule_event( strtotime( 'next Monday midnight' ), 'weekly', 'weekly_visits_reset' );
    }

    function schedule_monthly_reset() {
        wp_schedule_event( strtotime( 'first day of next month midnight' ), 'monthly', 'monthly_visits_reset' );
    }

    function schedule_yearly_reset() {
        wp_schedule_event( strtotime( 'January 1 next year midnight' ), 'yearly', 'yearly_visits_reset' );
    }

    function weekly_visits_reset() {
        update_option( 'detective_site_visits_this_week', 0 );
    }

    function monthly_visits_reset() {
        update_option( 'detective_site_visits_this_month', 0 );
    }

    function yearly_visits_reset() {
        update_option( 'detective_site_visits_this_year', 0 );
    }

    function add_order_column( $columns ) {
        $columns[ 'detective_source' ] = __( 'Source', 'detective-axon' );
        return $columns;
    }

    function store_source( $order_id )
 {
        $source = WC()->session->get( 'source' );
        if ( ! $source || empty( $source ) ) {
            $source = 'Organic';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . $this->user_tracking_table_name;
        $wpdb->insert(
            $table_name,
            array(
                'source' => $source,
                'order_id' => $order_id,
                'visit_time' => current_time( 'mysql' ),
            )
        );
        WC()->session->__unset( 'source' );
    }

    function get_tracked_user_data_grouped() {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->user_tracking_table_name;
        $results = $wpdb->get_results( "SELECT source, COUNT(*) as order_count FROM $table_name GROUP BY source" );

        $tracked_data_grouped = array();
        foreach ( $results as $data ) {
            $tracked_data_grouped[ $data->source ] = $data->order_count;
        }

        return $tracked_data_grouped;
    }

    function display_order_column_data( $column, $order_id ) {
        if ( $column === 'detective_source' ) {
            $source = $this->get_user_source( $order_id );
            echo $source ? ucwords( $source ) : 'N/A';
        }
    }

    function get_user_source( $order_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->user_tracking_table_name;

        $source = $wpdb->get_var( $wpdb->prepare( "SELECT source FROM $table_name WHERE order_id = %d", $order_id ) );

        return $source ? $source : '';
    }

}

$obj = new DetectiveAxon();
