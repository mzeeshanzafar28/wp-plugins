<?php
/**
 * Plugin Name: Formidable Extension for FCSA Diligence Hub
 * Author: Axon Technologies
 * Author URI: https://axontech.pk
 * Description: A custom plugin to combine the features of Memberpress with Formidable Forms. Use shortocde [manage_subuser_permissions] to allow corporates' parent users to manage access of their sub accounts. Use shortcode [corporate_list_entries form_id="N"] to view all entries of the corporate.
 * Version: 1.0
 * License: GPLv2 or Later
*/

class Formidable_FCSA_DH {

     public function __construct() {
        add_action('wp_ajax_fefdh_update_permission', array($this, 'update_permission'));
        add_action('wp_ajax_nopriv_fefdh_update_permission', array($this, 'update_permission'));
        add_action('wp_enqueue_scripts', array($this,'enqueue'));
        add_shortcode('corporate_list_entries', array($this, 'entries'));
        add_shortcode('manage_subuser_permissions', array($this,'manage_subuser_permissions'));
        add_filter('frm_user_can_edit', array($this, 'check_permission'), 999, 2);
    }
    
    function enqueue() {
        wp_enqueue_script('fefdh-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), '1.0', true);
        wp_localize_script('fefdh-script', 'fefdhajaxurl', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
        // wp_enqueue_style('fefdh-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), '1.0');
    }
    
    function check_permission($edit, $args) {
        $user_id = get_current_user_id();
        $entry_user = $this->get_entry_user( $args['entry'] );
        
        $corp_of_current = $this->get_corp_of_user($user_id);
        $corp_of_current_sub = $this->get_corp_of_sub_user($user_id);
        
        $corp_of_entry_user = $this->get_merged_corporates($entry_user);
        
        if ($this->if_intersect($corp_of_entry_user, $corp_of_current)) {
            return true;
        }

        if ($this->if_intersect($corp_of_entry_user, $corp_of_current_sub)) {
            $intersection = array_intersect($corp_of_entry_user, $corp_of_current_sub);
            if (empty($intersection)) return false;
            $corp_id = reset($intersection);
            $permission = get_user_meta($user_id, $corp_id . '_fefdh_permission', true);
            
            if ($permission == 'edit' ) {
                return true;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && $permission == 'view') {
                
                      echo '<script>
                        var fefdhinterval = function() {
                            var formElements = document.querySelectorAll("[id$=diligence-pack-builder] input, [id$=diligence-pack-builder] textarea, [id$=diligence-pack-builder] select, [id$=diligence-pack-builder] button");
                    
                            formElements.forEach(function (element) {
                                element.readOnly = true;
                            });
                            
                            document.querySelectorAll("[id$=diligence-pack-builder] [type=radio], [id$=diligence-pack-builder] [type=checkbox]").forEach(function (element) {
                                element.setAttribute("onclick", "return false;");
                            });
            
                            document.querySelectorAll(".frm_final_submit").forEach(function (element) {
                                element.style.display = "none";
                            });
                           
                        }
                        document.addEventListener("DOMContentLoaded", function () {
                            setInterval(fefdhinterval, 1000);
                        });
                            </script>';
                
                return true;
            }
            
        }

        return false;
    }
  

 public function manage_subuser_permissions() {
    $user_id = get_current_user_id();
    $user_corporate_accounts = MPCA_Corporate_Account::get_all_by_user_id($user_id);

    if (!$user_corporate_accounts) {
        return;
    }

    foreach ($user_corporate_accounts as $corporate_account_rec) {
        $corporate_account = new MPCA_Corporate_Account();
        $corporate_account->load_from_array($corporate_account_rec);
        $sub = $corporate_account->get_obj();
        $status = $sub->is_active() ? __('Active', 'memberpress-corporate') : __('Inactive', 'memberpress-corporate');
        if ($status == 'Active'){
            echo '<table class="WP_List_Table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Name</th>';
            echo '<th>Permission</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            $user_objs = $corporate_account->sub_users();

            if (empty($user_objs)) {
                echo '<tr><td colspan="3"><center>No Records Found.</center></td></tr>';
            } else {
                foreach ($user_objs as $user_obj) {
                    $permission_key = $corporate_account->id . '_fefdh_permission';
                    $permission = get_user_meta($user_obj->ID, $permission_key , true) ? get_user_meta($user_obj->ID, $permission_key , true) : "none";
                    if (!$permission) {
                        add_user_meta($user_obj->ID, $permission_key , "none");
                    }
                    echo '<tr>';
                    echo '<td>' . esc_html($user_obj->ID) . '</td>';
                    echo '<td>' . esc_html($user_obj->display_name) . '</td>';
                    echo '<td>';
                    echo '<select class="permission-dropdown" data-permission-key="' .$permission_key. '" data-user-id="' . esc_attr($user_obj->ID) . '">';
                    echo '<option value="view" ' . selected($permission, 'view', false) . '>View</option>';
                    echo '<option value="edit" ' . selected($permission, 'edit', false) . '>Edit</option>';
                    echo '<option value="none" ' . selected($permission, 'none', false) . '>None</option>';
                    echo '</select>';
                    echo '</td>';
                    echo '</tr>';
                    }
                }

            echo '</tbody>';
            echo '</table>';
        }
    }
}

    public function update_permission(){
        $user_corporate_accounts = MPCA_Corporate_Account::get_all_by_user_id(get_current_user_id());
        if (!$user_corporate_accounts) {
            wp_send_json_error( '401 Unauthorized Request.' );
        }
        $sub_users = [];
        foreach ($user_corporate_accounts as $corporate_account_rec) {
            $corporate_account = new MPCA_Corporate_Account();
            $corporate_account->load_from_array($corporate_account_rec);
            $user_objs = $corporate_account->sub_users();
            foreach ($user_objs as $sub_user){
                $sub_users[] = $sub_user->ID;
            }
        }
        $permission_key = $_POST['permission_key'];
        $permission = $_POST['permission_val'];
        $user_id    = $_POST['user_id'];
        if (in_array($user_id, $sub_users)){
            update_user_meta( $user_id, $permission_key , $permission );
            wp_send_json_success( 'Permission updated successfully' );
        } else{
            wp_send_json_error( 'No Sub User Found.' );
        }
    }
  
    public function get_corp_of_sub_user($user_id){
        return get_user_meta($user_id, 'mpca_corporate_account_id', false);
    }
    
    
    public function get_corp_of_user($user_id){
        $account_ids = [];
        $corp_records = MPCA_Corporate_Account::get_all_by_user_id( $user_id );
        if (empty($corp_records)) return [];
        foreach ($corp_records as $record) {
            $corporate_account = new MPCA_Corporate_Account();
            $corporate_account->load_from_array($record);
            $sub = $corporate_account->get_obj();
            if ($sub->is_active()) {
                $account_ids[] = $corporate_account->id;
            }
        }
        return $account_ids;
    }
    
    
    public function get_merged_corporates($user_id){
        return array_merge($this->get_corp_of_sub_user($user_id), $this->get_corp_of_user($user_id));
    }
    
    
    public function if_intersect($array1, $array2) {
        return ( count(array_intersect($array1, $array2)) >= 1 );
    }
    
    
    public function get_entry_user($id) {
        $entry = FrmEntry::getOne( $id );
        return $entry->user_id ?? 0;
    }
    
    

    public function entries($params) {
        $user_id = get_current_user_id();
        $form_id = $params['form_id'];
        $corp_ids = [];
        $sub_ids = [];
        $user_corporate_accounts = MPCA_Corporate_Account::get_all_by_user_id( $user_id );
        if (!empty($user_corporate_accounts)){
            foreach( $user_corporate_accounts as $user ) {
                $ca = new MPCA_Corporate_Account();
                $ca->load_from_array($user);
                $corp_id = $ca->id;
                $sub = $ca->get_obj();
                $status = (($sub->is_active()) ? __('Active', 'memberpress-corporate') : __('Inactive', 'memberpress-corporate'));
                if ($status == 'Active') {
                    $corp_ids[] = $corp_id;
                    $sub_users = $ca->sub_users();
                    foreach ($sub_users as $sub){
                        $sub_ids[] = $sub->id;                
                    }
                    break;
                }
            }
        } else {
            $corp_id = get_user_meta($user_id, 'mpca_corporate_account_id', true);
            $all_corporate_accounts = MPCA_Corporate_Account::get_all();
            $ca = new MPCA_Corporate_Account();
            foreach ($all_corporate_accounts as $corporate) {
                if ($corporate->id == $corp_id) {
                    $ca->load_from_array($corporate);
                }
            }
            $sub = $ca->get_obj();
            $status = (($sub->is_active()) ? __('Active', 'memberpress-corporate') : __('Inactive', 'memberpress-corporate'));
            if ($status == 'Active') {
                $corp_ids[] = $corp_id;
                $sub_users = $ca->sub_users();
                foreach ($sub_users as $sub) {
                    $sub_ids[] = $sub->id;                
                }
            }
        }
          
        $allowed_ids = array_merge($corp_ids, $sub_ids);
        array_push($allowed_ids, $user_id);
        
        $entries = FrmEntry::getAll(array('it.form_id' => $form_id), ' ORDER BY it.created_at DESC');
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Name</th>';
        echo '<th>Description</th>';
        echo '<th>IP</th>';
        echo '<th>Form ID</th>';
        echo '<th>User ID</th>';
        echo '<th>Created At</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($entries as $entry) {
            if (in_array($entry->user_id , $allowed_ids)){
                echo '<tr>';
                echo '<td>' . $entry->id . '</td>';
                echo '<td>' . $entry->name . '</td>';
                echo '<td>' . json_encode($entry->description) . '</td>'; 
                echo '<td>' . $entry->ip . '</td>';
                echo '<td>' . $entry->form_id . '</td>';
                echo '<td>' . $entry->user_id . '</td>';
                echo '<td>' . $entry->created_at . '</td>';
                echo '</tr>';
            }
        }    
        
        echo '</tbody>';
        echo '</table>';

    }
    
}

$Formidable_FCSA_DH = new Formidable_FCSA_DH();