<?php



if (!defined('ABSPATH')) {

    die('You are not allowed to call this page directly.');

}



require_once plugin_dir_path(__FILE__) . 'axon_user_notification/axon_user_notification.php';

require_once plugin_dir_path(__FILE__) . 'auam_email.php';

class auam_UmbrellaAccessManagement

{



    private $axon_user_notification;

    private $table_name;

    private $email;



    public function __construct()

    {

        global $wpdb;

        $this->table_name = $wpdb->prefix . "axon_access_status";

        $this->axon_user_notification = new Axon_Notification();

        $this->email = new auam_email();



        // Add shortcodes

        add_shortcode('umbrella-manage-requests', array($this, 'umbrella_memberpress'));

        // Add AJAX handler for updating access status

        add_action('wp_ajax_update_access_status', array($this, 'update_access_status'));

        add_action('wp_ajax_nopriv_update_access_status', array($this, 'update_access_status'));



        add_action('save_post',[$this, 'check_post_changes'],10,3);

    }


function umbrella_memberpress()
{
    $recruiters = $this->get_recruiter_user_ids();
    $output = '';
    $umbrella_user_id = get_current_user_id();
    $requests_found = false;

    if ($this->is_umbrella($umbrella_user_id)) {
        $output .= '<table class="manageTb axonTb"><thead><tr><th>Requested by</th><th>Diligence Pack</th><th>Status</th><th>Action</th></tr></thead><tbody>';

        foreach ($recruiters as $recruiter_user_id) {
            $umbrella_user_info = get_userdata($recruiter_user_id);
            $recruiter_id = esc_attr($recruiter_user_id);
            $requests_found = true;
            $requested_pack = $this->get_requested_umbrella_packs($recruiter_user_id, $umbrella_user_id);

                foreach ($requested_pack as $index => $request_pack) {
                    $pack_id = esc_attr($request_pack->pack_id);
                    $post = get_post($pack_id); // Get the post data
                    
                    $status = $this->get_request_status($recruiter_id, $pack_id, $umbrella_user_id);

                    $output .= '<tr>';
                    $output .= '<td>' . esc_html($umbrella_user_info->display_name) . '</td>';
                    $output .= '<td>' . '<a href="' . get_permalink($pack_id) . '" target="_blank">' . esc_html($post->post_title) . '</a></td>';
                    $colors = [
                        'granted' => 'green',
                        'refused' => 'red',
                        'pending' => 'yellow',
                    ];
                    $output .= '<td style="color: '.$colors[$status].';">'.ucfirst($status).'</td>';
                    $output .= '<td>';

                    if ($status == 'granted') {
                        // For Granted status, show "Revoke" and "Set to Pending" buttons
                        $output .= '<div style="display:flex; justify-content:center; align-items: center;">';
                        $output .= '<div class="loader-wrap"><input type="button" id="request-revoke-' . $recruiter_id . '-' . $index . '" data-recruiter-id="' . $recruiter_id . '" data-pack-id="' . $pack_id . '" class="revoke-button axon-btn" value="Revoke"></div>';
                        $output .= '<div class="loader-wrap"><input type="button" id="request-set-pending-' . $recruiter_id . '-' . $index . '" data-recruiter-id="' . $recruiter_id . '" data-pack-id="' . $pack_id . '" class="set-pending-button axon-btn" value="Set to Pending"></div>';
                        $output .= '</div>';
                    } elseif ($status == 'pending') {
                        // For Pending status, show "Grant" and "Refuse" buttons
                        $output .= '<div style="display:flex; justify-content:center; align-items: center;">';
                        $output .= '<div class="loader-wrap"><input type="button" id="request-grant-' . $recruiter_id . '-' . $index . '" data-recruiter-id="' . $recruiter_id . '" data-pack-id="' . $pack_id . '" class="grant-button axon-btn" value="Grant"></div>';
                        $output .= '<div class="loader-wrap"><input type="button" id="request-refuse-' . $recruiter_id . '-' . $index . '" data-recruiter-id="' . $recruiter_id . '" data-pack-id="' . $pack_id . '" class="refuse-button axon-btn" value="Refuse"></div>';
                         $output .= '</div>';
                    } elseif ($status == 'refused') {
                        // For Refused status, show "Grant" and "Set to Pending" buttons
                        $output .= '<div style="display:flex; justify-content:center; align-items: center;">';
                        $output .= '<div class="loader-wrap"><input type="button" id="request-grant-' . $recruiter_id . '-' . $index . '" data-recruiter-id="' . $recruiter_id . '" data-pack-id="' . $pack_id . '" class="grant-button axon-btn" value="Grant"></div>';
                        $output .= '<div class="loader-wrap"><input type="button" id="request-set-pending-' . $recruiter_id . '-' . $index . '" data-recruiter-id="' . $recruiter_id . '" data-pack-id="' . $pack_id . '" class="set-pending-button axon-btn" value="Set to Pending"></div>';
                        $output .= '</div>';
                    }

                    $output .= '</td>';
                    $output .= '</tr>';
                }
        }

        $output .= '</tbody></table>';

        if (!$requests_found) {
            $output .= '<div class="no-requests-message">No Requests Found at This Time</div>';
        }
    }

    $output .= '
             <style>
                    .axon-btn{
                        margin : 5px;
                        }
            </style>
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
                     
                // JavaScript code for handling interactions

                $(document).on("click", ".grant-button", function () {
                    const recruiterId = $(this).data("recruiter-id");
                    const packId = $(this).data("pack-id");

                    // Send AJAX request to update status to "granted"
                    ajaxUpdateStatus(recruiterId, [packId], "granted");
                    showPending($(this));
                    
                });

                $(document).on("click", ".revoke-button", function () {
                    const recruiterId = $(this).data("recruiter-id");
                    const packId = $(this).data("pack-id");

                    // Send AJAX request to update status to "refused"
                    ajaxUpdateStatus(recruiterId, [packId], "refused");
                    showPending($(this));
                });

                $(document).on("click", ".set-pending-button", function () {
                    const recruiterId = $(this).data("recruiter-id");
                    const packId = $(this).data("pack-id");

                    // Send AJAX request to update status to "pending"
                    ajaxUpdateStatus(recruiterId, [packId], "pending");
                    showPending($(this));
                });

                $(document).on("click", ".refuse-button", function () {
                    const recruiterId = $(this).data("recruiter-id");
                    const packId = $(this).data("pack-id");

                    // Send AJAX request to update status to "refused"
                    ajaxUpdateStatus(recruiterId, [packId], "refused");
                    showPending($(this));
                });

                // Function to send AJAX request and handle success
                function ajaxUpdateStatus(recruiterId, selectedIds, status) {
                    var axon_class = ["requestTb", "manageTb"];
                    $.ajax({
                        type: "POST",
                        url: custom_ajax_obj.ajaxurl,
                        data: {
                            action: "update_access_status",
                            recruiterId: recruiterId,
                            selectedIds: selectedIds,
                            status: status,
                        },
                        success: function (response) {
                            $.each(axon_class, function (index, a_class) {
                                var selector = `.${a_class}`;
                                var contentToReload = $(selector);
                                contentToReload.load(location.href + " " + selector, function () {});
                            });

                           
                        },
                        error: function () {
                            alert("An error occurred.");
                        },
                    });
                }
            });
        </script>
    ';

    return $output;
}

    function update_access_status()

    {



        global $wpdb;

        if (isset($_POST['recruiterId']) && isset($_POST['selectedIds']) && isset($_POST['status'])) {



            // Get the data from the AJAX request

            $recruiter_id = $_POST['recruiterId'];

            $selected_ids = $_POST['selectedIds'];

            $status = $_POST['status'];



            $umbrella_id = get_current_user_id();

            $current_time = current_time('mysql', 1);

            $updated_count = 0; // Initialize a counter for the number of updated rows



            $total_packs = count($selected_ids);

            $user_info = get_userdata($umbrella_id);





            if ($total_packs == 1) {

                $auam_post = get_post($selected_ids[0]);

                $content = esc_html($user_info->display_name) . " " . $status . " your request for access to " . $auam_post->post_title . " pack.";

            } elseif ($total_packs == 2) {

                $auam_post = get_post($$selected_ids[0]);

                $auam_post1 = get_post($selected_ids[1]);

                $content = esc_html($user_info->display_name) . " " . $status . " your request for access to " . $auam_post->post_title . " and " . $auam_post1->post_title . " packs.";

            } else {

                $content = esc_html($user_info->display_name) . " " . $status . " your request for access to " . $total_packs . " packs.";

            }

            $link = $this->get_page_or_post_permalink_by_shortcode('[umbrella-pending-requests]');

            $this->axon_user_notification->insert_notification($recruiter_id, $content, $link);

            $this->email->send_email_request_status($umbrella_id, $recruiter_id, $selected_ids, "umbrella", $status);

            foreach ($selected_ids as $pack_id) {



                // Define the data to update

                $data = array(

                    'status' => $status,

                    'updated_at' => $current_time,

                );



                // Define the WHERE clause for the update

                $where = array(

                    'user_id' => $recruiter_id,

                    'umbrella_id' => $umbrella_id,

                    'pack_id' => $pack_id,

                );

                // Perform the update and check for errors

                $result = $wpdb->update($this->table_name, $data, $where);

                if ($result === false) {

                } else {

                    // Successful update, increment the counter

                    $updated_count++;

                }

            }


            if ($updated_count > 0) {

                // Send a success response with the number of updated rows

                echo 'Status updated to ' . $status;

            } else {

                echo "";

            }



        }


        // Always exit to avoid further processing

        wp_die();

    }



    function is_requested($recruiter_user_id, $umbrella_user_id)

    {

        global $wpdb;

        // Check if there are requests associated with the recruiter and umbrella user

        $count = $wpdb->get_var(

            $wpdb->prepare(

                "SELECT COUNT(*) FROM $this->table_name WHERE user_id = %d AND umbrella_id = %d AND status = 'pending'",

                $recruiter_user_id,

                $umbrella_user_id

            )

        );

        return $count > 0;

    }



    function is_request_granted($recruiter_user_id, $umbrella_user_id)

    {

        global $wpdb;

        // Check if there are requests associated with the recruiter and umbrella user

        $count = $wpdb->get_var(

            $wpdb->prepare(

                "SELECT COUNT(*) FROM $this->table_name WHERE user_id = %d AND umbrella_id = %d AND status = 'granted'",

                $recruiter_user_id,

                $umbrella_user_id

            )

        );

        return $count > 0;

    }
    
    function is_request_refused($recruiter_user_id, $umbrella_user_id)
    {
        global $wpdb;

        // Check if there are requests associated with the recruiter and umbrella user
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $this->table_name WHERE user_id = %d AND umbrella_id = %d AND status = 'refused'",
                $recruiter_user_id,
                $umbrella_user_id
            )
        );
        return $count > 0;
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



    function get_requested_umbrella_packs($recruiter_user_id, $umbrella_user_id)

    {

        global $wpdb;

        // Query the requests for the specified recruiter and umbrella user

        $requests = $wpdb->get_results(

            $wpdb->prepare(

                "SELECT * FROM $this->table_name WHERE user_id = %d AND umbrella_id = %d",

                $recruiter_user_id,

                $umbrella_user_id

            )

        );

        // Return the requests as an array

        return $requests;

    }

    function get_recruiter_user_ids()

    {

        $users = get_users(array('fields' => array('ID')));

        $recruiter_user_ids = array();

        foreach ($users as $user) {

            if ($this->is_recruiter($user->ID)) {

                $recruiter_user_ids[] = $user->ID;

            }

        }

        return $recruiter_user_ids;

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

    function check_post_changes($post_id, $post, $update) {

        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) return;

        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;

        if (get_post_type( $post_id ) != 'umbrella-pack') return;

            $lastupdated = get_post_meta($post_id, "auam_last_updated", time() );

            if (empty($lastupdated) || (time() - $lastupdated) > 60 ) {               

                    // Get the post author's ID
                    $umbrella_id = $post->post_author;
                    $post_title   = $post->post_title;
                     
                    // Call a function to get the recruiter IDs based on your criteria
                    $recruiter_ids = $this->get_recruiter_ids($umbrella_id, $post_id);
                    $user_info    = get_userdata($umbrella_id);
                    $link         = get_permalink($post_id);
                    $content      = esc_html($user_info->display_name) . " has updated the  " . $post_title . " Pack.";

                    if ($recruiter_ids) {
                        foreach ($recruiter_ids as $recruiter_id) {
                            // Insert a notification using the recruiter ID, content, and link
                            $this->axon_user_notification->insert_notification($recruiter_id, $content, $link);
                            
                            // Send an email to the recruiter
                             $this->email->send_email_pack_update($umbrella_id, $recruiter_id, [$post_id], 'umbrella');
                        }
                    }
            }
            update_post_meta($post_id, "auam_last_updated", time() );	
    }
    // Function to get the recruiter IDs based on your criteria
    function get_recruiter_ids($umbrella_id, $post_id) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'axon_access_status';
        $sql = $wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE umbrella_id = %d AND pack_id = %d AND status = 'granted'",
            $umbrella_id,
            $post_id
        );
    
        // Execute the SQL query and fetch results
        $recruiter_ids = $wpdb->get_col($sql);
        return $recruiter_ids;
    }







}