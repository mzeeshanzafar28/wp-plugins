<?php
/**
 * Plugin Name: FCSA Diligence Hub
 * Description: Custom plugin to allow recruiters to request access to Umbrella packs and umbrella to recruiter packs. You can use shortcode [umbrella-request-access] ,and [umbrella-manage-requests] for umbrella and [recruiter-request-access] ,and [recruiter-manage-requests] for recruiters on any page of your website. This plugin also provides a shortcode [notification_icon] to show notification icon.
 * Version: 1.5
 * Author: Axon Tech
*/

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once plugin_dir_path(__FILE__) . 'axon_user_notification/axon_user_notification.php';
require_once plugin_dir_path(__FILE__) . 'auam_umbrella.php';
require_once plugin_dir_path(__FILE__) . 'auam_umbrella_vv.php';
require_once plugin_dir_path(__FILE__) . 'auam_recruiter.php';
require_once plugin_dir_path(__FILE__) . 'auam_recruiter_vv.php';
require_once plugin_dir_path(__FILE__) . 'auam_email.php';

class AxonUmbrellaAccessManagement
{

    private $axon_user_notification;
    private $recruiter;
    private $umbrella;
    private $recruitervv;
    private $umbrellavv;
    private $email;

    public function __construct()
    {
        $this->axon_user_notification = new Axon_Notification();
        $this->recruiter = new auam_RecruiterAccessManagement();
        $this->umbrella = new auam_UmbrellaAccessManagement();
        $this->recruitervv = new auam_RecruiterAccessManagementVV();
        $this->umbrellavv = new auam_UmbrellaAccessManagementVV();
        $this->email = new auam_email();
        register_activation_hook(__FILE__, array($this, 'create_custom_tables'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_custom_scripts'));
        add_action('admin_menu', array($this, 'add_plugin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp', [$this, 'proflie_access']);
    }

    function enqueue_custom_scripts()
    {
        //DataTables lib icludes
        wp_enqueue_script('axon-datatables-script', 'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js', array('jquery'), '1.0', true);
        wp_enqueue_style('axon-datatables-styles', 'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css');
        
        // Enqueue scripts and localize variables
        wp_enqueue_script('axon-notification-script', plugin_dir_url(__FILE__) . 'js/notification-script.js', array('jquery'), '1.0', true);
        wp_enqueue_style('axon-notification-styles', plugin_dir_url(__FILE__) . 'css/styles.css');

        wp_localize_script('axon-notification-script', 'axon_notification_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('axon-notification-nonce'),
        ));
        
        // Localize the script to include the AJAX URL
        wp_localize_script(
            'axon-notification-script',
            'custom_ajax_obj',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('custom-ajax-nonce')
            )
        );
    }

    function create_custom_tables()
    {
        global $wpdb;
        $table_name1 = $wpdb->prefix . 'axon_access_status'; // Replace 'your_custom_table_name' with your desired table name
        $table_name1vv = $wpdb->prefix . 'axon_access_status_vv'; // Replace 'your_custom_table_name' with your desired table name
        $charset_collate = $wpdb->get_charset_collate();
        $table_name2 = $wpdb->prefix . 'axon_user_notification';

        $sql1 = "CREATE TABLE IF NOT EXISTS $table_name1 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            umbrella_id mediumint(9) NOT NULL,
            pack_id mediumint(9) NOT NULL,
            status varchar(255) NOT NULL DEFAULT 'pending',
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);

        $sql1vv = "CREATE TABLE IF NOT EXISTS $table_name1vv (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            recruiter_id mediumint(9) NOT NULL,
            pack_id mediumint(9) NOT NULL,
            status varchar(255) NOT NULL DEFAULT 'pending',
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1vv);

        $sql2 = "CREATE TABLE IF NOT EXISTS $table_name2 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            content text NOT NULL,
            link varchar(255),
            meta text,
            status varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            seen_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql2);   
    }
    public function add_plugin_menu() {
        add_menu_page(
            'Umbrella Access Management',
            'Umbrella Access',
            'manage_options',
            'umbrella-access-management',
            array($this, 'plugin_settings_page')
        );
        add_submenu_page(
            'umbrella-access-management',
            'Recruiter Access Management',
            'Recruiter Access',
            'manage_options',
            'umbrella-access-management-2',
            array($this, 'plugin_settings_page_2')
        );
    }

        // Function to render the plugin settings page
        public function plugin_settings_page() {
            // Display the settings page
            ?>
            <div class="wrap">
                <h2>Umbrella Access Management</h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('umbrella-access-settings');
                    do_settings_sections('umbrella-access-settings');
                    ?>
                    <h3 for="send_request_content">Email for Send request :</h3>
                    <?php
                    wp_editor(get_option('send_request_content'), 'send_request_content', array('textarea_rows' => 10));?>
                     <br><h3 for="request_status_content">Email for Request Status:</h3>
                    <?php
                    wp_editor(get_option('request_status_content'), 'request_status_content', array('textarea_rows' => 10));?>
                    <br><h3 for="auam_update_content">Email for Update Pack :</h3>
                    <?php
                    wp_editor(get_option('auam_update_content'), 'auam_update_content', array('textarea_rows' => 10));
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        public function plugin_settings_page_2() {
            // Display the settings page for section 2
            ?>
            <div class="wrap">
                <h2>Recruiter Access Management </h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('umbrella-access-settings_2');
                    do_settings_sections('umbrella-access-settings_2');
                    ?>
                    <h3 for="send_request_content_vv">Email for Send request :</h3>
                    <?php
                    wp_editor(get_option('send_request_content_vv'), 'send_request_content_vv', array('textarea_rows' => 10));?>
                    <br><h3 for="request_status_content_vv">Email for Request Status:</h3>
                    <?php
                    wp_editor(get_option('request_status_content_vv'), 'request_status_content_vv', array('textarea_rows' => 10));?>
                     <br><h3 for="auam_update_content_vv">Update  Pack :</h3>
                    <?php
                    wp_editor(get_option('auam_update_content_vv'), 'auam_update_content_vv', array('textarea_rows' => 10));
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
    // Function to register plugin settings
    public function register_settings() {
        register_setting('umbrella-access-settings', 'request_status_content');
        register_setting('umbrella-access-settings', 'send_request_content');
        register_setting('umbrella-access-settings', 'auam_update_content');

        register_setting('umbrella-access-settings_2', 'request_status_content_vv');
        register_setting('umbrella-access-settings_2', 'send_request_content_vv');
        register_setting('umbrella-access-settings_2', 'auam_update_content_vv');
    }

    public function proflie_access() {
        if ( is_singular( 'umbrella-pack' ) ) {
            if (current_user_can('administrator')) return;
            $pack_id = get_the_ID();
            $umbrella_user_id = get_post_field( 'post_author', $pack_id );
            $user_id = get_current_user_id();
            if ($this->umbrella->is_umbrella($user_id)) { return; }
            if ($this->recruiter->is_recruiter($user_id)) {
                $status = $this->recruiter->get_request_status($user_id, $pack_id, $umbrella_user_id);
                if ($status == "granted") {
                    return;
                }
            }
            wp_redirect(home_url()."/unauthorised");
            exit();
        }
        if ( is_singular( 'recruiter-pack' ) ) {
            if (current_user_can('administrator')) return;
            $pack_id = get_the_ID();
            $recruiter_user_id = get_post_field( 'post_author', $pack_id );
            $user_id = get_current_user_id();
            if ($this->recruiter->is_recruiter($user_id)) { return; }
            if ($this->umbrella->is_umbrella($user_id)) {
                $status = $this->umbrellavv->get_request_status($user_id, $pack_id, $recruiter_user_id);
                if ($status == "granted") {
                    return;
                }
            }
            wp_redirect(home_url()."/unauthorised");
            exit();
        }
    }
}

$AxonUmbrellaAccessManagement = new AxonUmbrellaAccessManagement();