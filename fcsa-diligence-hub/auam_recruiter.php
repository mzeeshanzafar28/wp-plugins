<?php



if (!defined('ABSPATH')) {

    die('You are not allowed to call this page directly.');

}



require_once plugin_dir_path(__FILE__) . 'axon_user_notification/axon_user_notification.php';

require_once plugin_dir_path(__FILE__) . 'auam_email.php';



class auam_RecruiterAccessManagement

{



    private $axon_user_notification;

    private $table_name;

    private $email;



    public function __construct()

    {

        global $wpdb;



        $this->email = new auam_email();



        $this->table_name = $wpdb->prefix . 'axon_access_status';



        $this->axon_user_notification = new Axon_Notification();



        // Add shortcode

        add_shortcode('recruiter-request-access', array($this, 'recruiter_memberpress'));



        // AJAX action hook

        add_action('wp_ajax_process_request', array($this, 'process_request'));

        add_action('wp_ajax_nopriv_process_request', array($this, 'process_request'));

    }



 function recruiter_memberpress()
{
    $umbrella_user_ids = $this->get_umbrella_user_ids();
    $output = '';

    $user_id = get_current_user_id();

    if ($this->is_recruiter($user_id)) {
        $output .= '<table class="requestTb axonTb"><thead><tr><th>Parent Company</th><th>Diligence Pack For</th><th>Request Status</th></tr></thead><tbody>';

        foreach ($umbrella_user_ids as $umbrella_user_id) {
            $umbrella_user_info = get_userdata($umbrella_user_id);

            $args = array(
                'author' => $umbrella_user_id,
                'post_type' => 'umbrella-pack',
                'posts_per_page' => -1,
            );

            $posts = get_posts($args);

            foreach ($posts as $post) {
                $output .= '<tr>';
                $pack_id = esc_attr($post->ID);
                $request_status = $this->get_request_status($user_id, $pack_id, $umbrella_user_id);
                $output .= '<td>' . esc_html($umbrella_user_info->display_name) . '</td>';
                $output .= '<td>' . ($post->post_title ? esc_html($post->post_title) : '') . '</td>';
                $output .= '<td>';

                if ($request_status === 'pending') {
                    $output .= '<div style="color: yellow; display: flex;  align-items: center;">';
                    $output .= '<label style="margin-right: 10px;"><i class="fas fa-stopwatch"></i></label>';
                    $output .= '<div style="margin-left: 10px;">Pending</div>';
                    $output .= '</div>';
                } elseif ($request_status === 'granted') {
                    $output .= '<label style="color: green; margin-right: 10px;"><i class="far fa-check-circle"></i></label>';
                    $output .= '<a style="color:green;" href="' . esc_url(get_permalink($post->ID)) . '">' . esc_html($post->post_title) . ' Granted: View</a><br>';
                } elseif ($request_status === 'refused') {
                    $output .= '<div style="color: red; display: flex; align-items: center;">';
                    $output .= '<label style="margin-right: 10px;"><i class="far fa-times-circle"></i></label>';
                    $output .= '<div style="margin-left: 10px;">Refused</div>';
                    $output .= '</div>';
                } else {
                    $output .= '<div  class="request-button" style="cursor: pointer;" data-umbrella-id="' . $umbrella_user_id . '" data-pack-id="' . $pack_id . '"><span class="fas fa-plus-circle"></span> Send access request</div>';
                }

                $output .= '</td>';
                $output .= '</tr>';
            }
        }

        $output .= '</tbody></table>';
        $output .= '
       
            <script>
                jQuery(document).ready(function ($) {
                   if ( ! DataTable.isDataTable( ".axonTb" ) ) {
                        $(".axonTb").DataTable({
                            "paging": true,
                            "searching": true,
                            "ordering": true
                        });
                    }
                    
                    if (typeof showPending != "function") { 
                        function showPending(obj)
                        {
                            $(obj).css("opacity", "0.5");
                            $(obj).prop("disabled", true);
                        }
                    }

                    // Use event delegation for "Request" button click event
                    $("body").on("click", \'.request-button\', function () {
                        showPending(this);
                        var userId = $(this).data("umbrella-id");
                        var notification = $("#notification-" + userId);
                        var packId = $(this).data("pack-id");

                        if (packId) {
                            var data = {
                                action: "process_request",
                                umbrella_id: userId,
                                post_ids: [packId],
                                nonce: custom_ajax_obj.nonce
                            };

                            $.post(custom_ajax_obj.ajaxurl, data, function (response) {
                                if (response.trim() !== "") {
                                    // Reload only the content within the specific div
                                    var contentToReload = $(".content-to-reload[data-user-id=\'" + userId + "\']");
                                    contentToReload.load(location.href + \' .content-to-reload[data-user-id="\' + userId + \'"]\', function () {
                                        // Callback function after the content is loaded
                                        // You can perform any additional actions here
                                    });

                                    // Replace the "Request" button with the status
                                    var requestButton = $(\'[data-umbrella-id="\' + userId + \'"][data-pack-id="\' + packId + \'"]\');
                                    requestButton.replaceWith(response);

                                    setTimeout(function () {
                                        notification.css("display", "none");
                                    }, 5000);
                                }
                            });
                        } else {
                            notification.css("display", "block");
                            notification.html("Please select a pack to request.");
                            setTimeout(function () {
                                notification.css("display", "none");
                            }, 5000);
                        }
                    });
                });
            </script>
        ';

        return $output;
    }
}





    function get_request_status($user_id, $pack_id, $umbrella_user_id)

    {

        global $wpdb;

        $status = $wpdb->get_var(

            $wpdb->prepare(

                "SELECT status FROM $this->table_name WHERE user_id = %d AND umbrella_id = %d AND pack_id = %d ORDER BY id DESC LIMIT 1",

                $user_id,

                $umbrella_user_id,

                $pack_id

            )

        );

        return $status;



    }



    function is_recruiter($user_id = 0)

    {

        if (!$user_id)
            $user_id = get_current_user_id();

        if (!$user_id)
            return false;
		
		$user = new WP_User( $user_id );
		if ( empty( $user->roles ) || !is_array( $user->roles ) )
			return false;

        return in_array('recruiter', array_map(function($r) { return strtolower(trim($r)); }, $user->roles ));

    }



    function get_umbrella_user_ids()

    {

        $users = get_users(array('fields' => array('ID')));

        $umbrella_user_ids = array();

        foreach ($users as $user) {

            if ($this->is_umbrella($user->ID)) {

                $umbrella_user_ids[] = $user->ID;

            }

        }

        return $umbrella_user_ids;

    }



    function is_umbrella($user_id = 0)

    {

        if (!$user_id)
            $user_id = get_current_user_id();

        if (!$user_id)
            return false;
		
		$user = new WP_User( $user_id );
		if ( empty( $user->roles ) || !is_array( $user->roles ) )
			return false;

        return in_array('umbrella', array_map(function($r) { return strtolower(trim($r)); }, $user->roles ));

    }



    // Add this function to your theme's functions.php or a custom plugin

    function process_request()

    {

        global $wpdb;

        if (isset($_POST["umbrella_id"]) && isset($_POST["post_ids"])) {

            $user_id = get_current_user_id();

            $umbrella_id = intval($_POST["umbrella_id"]);

            $pack_ids = array_map('intval', $_POST["post_ids"]);

            $total_packs = count($pack_ids);

            $user_info = get_userdata($user_id);



            if ($total_packs == 1) {

                $auam_post = get_post($pack_ids[0]);

                $content = esc_html($user_info->display_name) . " Asked For Access to " . $auam_post->post_title . " Pack.";

            } elseif ($total_packs == 2) {

                $auam_post = get_post($pack_ids[0]);

                $auam_post1 = get_post($pack_ids[1]);

                $content = esc_html($user_info->display_name) . " Asked For Access to " . $auam_post->post_title . " and " . $auam_post1->post_title . " Packs.";

            } else {

                $content = esc_html($user_info->display_name) . " Asked For Access to " . $total_packs . " Packs.";

            }

            $link = $this->get_page_or_post_permalink_by_shortcode('[recruiter-request-access]');

            $this->axon_user_notification->insert_notification($umbrella_id, $content, $link);

            $this->email->send_email_request($user_id, $umbrella_id , $pack_ids, "recruiter" );

            $counter = 0; // Initialize a counter

            foreach ($pack_ids as $pack_id) {
                // Check if a request with the same user_id, umbrella_id, and pack_id and status != 'refused' already exists

                $table = $this->table_name;
                $existing_request = $wpdb->get_var(

                    $wpdb->prepare(

                        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND umbrella_id = %d AND pack_id = %d AND status != %s",

                        $user_id,

                        $umbrella_id,

                        $pack_id,

                        'refused'
                    )
                );

                if ($existing_request === '0') {

                    $result = $wpdb->insert(

                        $table,

                        array(

                            'user_id' => $user_id,

                            'umbrella_id' => $umbrella_id,

                            'pack_id' => $pack_id,

                            'status' => 'pending',

                            'created_at' => current_time('mysql', 1),

                        ),

                        array('%d', '%d', '%d', '%s', '%s')

                    );



                    if ($result !== false) {

                        $counter++; // Increase the counter on successful insertion

                    }

                }

            }



            if ($counter > 0) {

                echo '<div style="color:#a7a746; display: flex;  align-items: center;"><label style="margin-right: 10px;"><i class="fas fa-stopwatch"></i></label><div style="margin-left: 10px;">Pending</div></div>';

            } else {

                echo ''; // Send an empty response when no requests were processed

            }

        } else {

            echo "Invalid request data.";

        }
        die();
    }

    function get_page_or_post_permalink_by_shortcode($shortcode_name)

    {

        $args = array(

            'post_type' => array('page', 'post'),

            // Include both pages and posts

            'posts_per_page' => -1,

            // Retrieve all pages and posts

            's' => $shortcode_name,

            'post_status' => 'publish',

        );



        $pages_and_posts_with_shortcode = get_posts($args);

        if (!empty($pages_and_posts_with_shortcode)) {

            return get_permalink($pages_and_posts_with_shortcode[0]);

        }

        return false; // Return false if shortcode is not found on any page or post

    }







}