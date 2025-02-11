<?php
if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}
class Axon_Notification
{

    public function __construct()
    {
        add_shortcode('notification_icon', array($this, 'notification_icon_shortcode'));
        add_shortcode('store_notification', array($this, 'store_notification_shortcode'));

        add_action('wp_ajax_get_notifications', array($this, 'get_notifications'));
        add_action('wp_ajax_mark_notification_as_seen', array($this, 'mark_notification_as_seen'));

        add_action('wp_ajax_mark_notification_as_unseen', array($this, 'mark_notification_as_unseen'));

    }


    // Notification Icon Shortcode
    public function notification_icon_shortcode()
    {
        if (!is_user_logged_in()) return '';
        ob_start();

        // Display notification icon
        echo '<div class="notification-icon">';
        echo '<span class="icon">🔔</span>'; // Replace with your icon
        echo '<span class="notification-count">0</span>'; // This will be updated dynamically
        echo '<div class="notification-list">';
        echo '<ul class="notifications"></ul>';
        echo '</div>';
        echo '</div>';

        $output = ob_get_clean();
        return $output;
    }

    // Store Notification Shortcode
    public function store_notification_shortcode()
    {
        ob_start();

        if (isset($_POST['notification_content'])) {
            $notification_content = sanitize_text_field($_POST['notification_content']);
            $notification_user_id = sanitize_text_field($_POST['user_id']);
            $link = $_POST['link'];

            if ($this->insert_notification($notification_user_id, $notification_content, $link)) {
                echo '<p class="success-message">Notification submitted successfully.</p>';
            } else {
                echo '<p class="error-message">Failed to submit notification.</p>';
            }
        }

        // Display store notification form
        echo '<div class="store-notification">';
        echo '<form method="post">';
        echo '<input type="text" name="user_id" placeholder="Enter User id"/><br><br>';
        echo '<input type="text" name="link" placeholder="Enter Link"/><br><br>';
        echo '<textarea name="notification_content" placeholder="Enter your notification"></textarea><br><br>';
        echo '<input type="submit" value="Submit Notification">';
        echo '</form>';
        echo '</div>';


        $output = ob_get_clean();
        return $output;
    }

    // Insert notification into the database
    public function insert_notification($user_id, $content,$link)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'axon_user_notification';

        $insert_data = array(
            'user_id' => $user_id,
            'content' => $content,
            'link' => $link,
            'status' => 'unseen',
            'created_at' => current_time('mysql', 1)
        );

        return $wpdb->insert($table_name, $insert_data, array('%d', '%s', '%s', '%s', '%s'));
    }


    // Ajax Callback: Get Notifications
    public function get_notifications()
    {
        check_ajax_referer('axon-notification-nonce', 'nonce');

        // Get current logged-in user ID
        $user_id = get_current_user_id();


        // Fetch all notifications for the user
        $all_notifications = $this->fetch_all_notifications($user_id);

        // Count unseen notifications
        $unseen_count = 0;
        foreach ($all_notifications as $notification) {
            if ($notification['status'] === 'unseen') {
                $unseen_count++;
            }
        }

        // Prepare response
        $response = array(
            'unseenCount' => $unseen_count,
            'notifications' => $all_notifications
        );

        wp_send_json($response);
    }

    // Fetch all notifications for a user
    private function fetch_all_notifications($user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'axon_user_notification';

        $query = $wpdb->prepare(
            "SELECT *  FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    public function mark_notification_as_seen()
    {
        check_ajax_referer('axon-notification-nonce', 'nonce');

        $notification_id = intval($_POST['notification_id']);
        $user_id = get_current_user_id();


        global $wpdb;
        $table_name = $wpdb->prefix . 'axon_user_notification';

        // Retrieve the user ID associated with the notification ID from the database
        $query_user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM $table_name WHERE id = %d",
                $notification_id
            )
        );


        if ($user_id == $query_user_id) {
            // Update the seen_at timestamp
            $rslt = $this->update_notification_status($notification_id, 'seen');

            if ($rslt) {
                // Send back the updated timestamp in the response
                wp_send_json(array('success' => true, 'seen_at' => current_time('mysql', 1)));
            } else {
                wp_send_json(array('success' => false));
            }
        } else {
            wp_send_json(array('success' => false));
        }
    }


    // Update notification seen_at timestamp
    private function update_notification_status($notification_id, $status)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'axon_user_notification';
        if ($status == 'seen') {
            $update_data = array(
                'status' => $status,
                // Update status to "seen"
                'seen_at' => current_time('mysql', 1) // Update the timestamp
            );
        } else {
            $update_data = array(
                'status' => $status // Update status to either "seen" or "unseen"
            );

        }
        $where = array('id' => $notification_id);

        $q = $wpdb->update($table_name, $update_data, $where);
        return $q;
    }
    // Ajax Callback: Mark Notification as Unseen
// Ajax Callback: Mark Notification as Unseen (Mark as Unread)
    public function mark_notification_as_unseen()
    {
        check_ajax_referer('axon-notification-nonce', 'nonce');

        $notification_id = intval($_POST['notification_id']);
        $user_id = get_current_user_id();

        global $wpdb;
        $table_name = $wpdb->prefix . 'axon_user_notification';

        // Check if the user is the owner of the notification
        $query_user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM $table_name WHERE id = %d",
                $notification_id
            )
        );

        if ($user_id == $query_user_id) {
            // Update the status to "unseen" (mark as unread)
            $rslt = $this->update_notification_status($notification_id, 'unseen');

            if ($rslt) {
                // Send back a success response
                wp_send_json(array('success' => true, 'status' => 'unseen'));
            } else {
                wp_send_json(array('success' => false));
            }
        } else {
            wp_send_json(array('success' => false));
        }
    }
}