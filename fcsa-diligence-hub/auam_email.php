<?php 
if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}
class auam_email {
    public function __construct() {}

    // Function to send an email with variable replacement
    public function send_email_request($author_id, $requesters_id , $packs_id, $from ) {
        if($from == "recruiter"){
            $template        = get_option('send_request_content_vv');
            $content         = $this->auam_find_replace($author_id, $requesters_id, $packs_id, $template ,$from);
            $umbrella_data   = get_userdata($requesters_id);
            $email_to        = $umbrella_data->user_email;
            $subject         = "Pack Access Request";
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($email_to, $subject, $content ,$headers);
        }
        if($from == "umbrella"){
            $template        = get_option('send_request_content');
            $content         = $this->auam_find_replace($author_id, $requesters_id, $packs_id, $template ,$from);
            $recruiter_data   = get_userdata($requesters_id);
            $email_to        = $recruiter_data->user_email;
            $subject         = "Pack Access Request";
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($email_to, $subject, $content ,$headers);
        }
       
    }
    public function send_email_pack_update($author_id, $requesters_id, $packs_id, $from) {
        if($from == "recruiter"){
            $template        = get_option('auam_update_content_vv');
            $content         = $this->auam_find_replace($author_id, $requesters_id, $packs_id, $template ,$from);
            $umbrella_data    = get_userdata($requesters_id);
            $email_to         = $umbrella_data->user_email;
            $subject          = "Pack Update Notice";
            $headers          = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($email_to, $subject, $content ,$headers);
        }
        if($from == "umbrella"){
            $template        = get_option('auam_update_content');
            $content         = $this->auam_find_replace($author_id, $requesters_id, $packs_id, $template ,$from);
            $recruiter_data    = get_userdata($requesters_id);
            $email_to         = $recruiter_data->user_email;
            $subject          = "Pack Update Notice";
            $headers          = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($email_to, $subject, $content ,$headers);
         }
    }

    public function send_email_request_status($author_id, $requesters_id, $packs_id, $from, $status  ) {
        if($from == "recruiter"){
            $template = get_option('request_status_content_vv');
            $content  = $this->auam_find_replace($author_id, $requesters_id, $packs_id, $template ,$from , $status);
            $umbrella_data   = get_userdata($requesters_id);
            $email_to        = $umbrella_data->user_email;
            $subject         = "Pack Access Request Status Update";
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($email_to, $subject, $content ,$headers);
        }
        if($from == "umbrella"){
            $template = get_option('request_status_content');
            $content  = $this->auam_find_replace($author_id, $requesters_id, $packs_id, $template ,$from , $status);
            $recruiter_data   = get_userdata($requesters_id);
            $email_to        = $recruiter_data->user_email;
            $subject         = "Pack Access Request Status Update";
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($email_to, $subject, $content ,$headers);
        }
        
    }
    public function auam_find_replace($author_id, $requesters_id, $packs_id, $template , $from , $status = 'Pending') {
        // Calculate $total_req_prof
        $total_req_prof = count($packs_id);

        // Get user data and user meta for $recruiter_id and $umbrella_id
        if($from == "recruiter"){
            $recruiter_data     = get_userdata($author_id);
            $umbrella_data      = get_userdata($requesters_id);
            $recruiter_meta     = get_user_meta($author_id);
            $umbrella_meta      = get_user_meta($requesters_id);
        }
        if($from == "umbrella"){
            $recruiter_data     = get_userdata($requesters_id);
            $umbrella_data      = get_userdata($author_id);
            $recruiter_meta     = get_user_meta( $requesters_id);
            $umbrella_meta      = get_user_meta($author_id);
        }
        $packs_name      = [];
        $pack_permalink  = get_permalink($packs_id[0]);
        foreach($packs_id as $pack_id){
            $post = get_post($pack_id);
            $packs_name[] =  esc_html($post->post_title);            
        }
        
        $pack_list = '<ul>';
        foreach( $packs_name as $name ){
            $pack_list .= '<li>' . $name . '</li>';
        }
        $pack_list .= '</ul>';
        // Define an array of placeholders and their corresponding values
        $placeholders = array(
            '{{requested_packs}}' => $pack_list,
            '{{requested_packs_count}}' => $total_req_prof,
            '{{request_status}}' => $status,
            '{{pack_link}}' => $pack_permalink,
            '{{pack_id}}' => $packs_id[0],
            '{{pack_title}}' => isset($packs_name[0]) ? $packs_name[0] : '',
        );

        // Replace template variables based on the placeholders array
        foreach ($placeholders as $placeholder => $value) {
            if (strpos($template, $placeholder) !== false) {
                $template = str_replace($placeholder, $value, $template);
            }
        }

        // Replace user and user-meta variables in the template
        foreach ($recruiter_data->data as $key => $value) {
            $placeholder = '{{recruiter_' . $key . '}}';
            if (strpos($template, $placeholder) !== false) {
                $template = str_replace($placeholder, $value, $template);
            }
        }

        foreach ($umbrella_data->data as $key => $value) {
            $placeholder = '{{umbrella_' . $key . '}}';
            if (strpos($template, $placeholder) !== false) {
                $template = str_replace($placeholder, $value, $template);
            }
        }

        foreach ($recruiter_meta as $key => $meta) {
            $placeholder = '{{recruiter_meta_' . $key . '}}';
            if (strpos($template, $placeholder) !== false) {
                $template = str_replace($placeholder, $meta[0], $template);
            }
        }

        foreach ($umbrella_meta as $key => $meta) {
            $placeholder = '{{umbrella_meta_' . $key . '}}';
            if (strpos($template, $placeholder) !== false) {
                $template = str_replace($placeholder, $meta[0], $template);
            }
        }

        return $template;
    }
}

