<?php
if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}
class AssistantAdmin {
    public function __construct(){
        //To add Custom Post Type 
        add_action('init', array($this, 'add_assistant_cpt'));
        add_action('init', array($this, 'add_assistant_taxonomy'));

        //To add Custom Meta Box based on Category (Video post and Standard Post) 
        add_action('add_meta_boxes', array($this, 'add_assistant_metaboxes'));
        add_action('save_post', array($this, 'save_assistant_metaboxes'));
        add_action('save_post_assistant', array($this, 'save_assistant_metaboxes'));
    }

    function add_assistant_cpt() {
        $supports = array(
            'title', 
        );
        $labels = array(
            'name' => __('Assistant Posts', 'plural'),
            'singular_name' => __('Assistant', 'singular'),
            'menu_name' => __('Assistant', 'admin menu'),
            'name_admin_bar' => __('Assistant', 'admin bar'),
            'add_new' => __('Add New', 'add new'),
            'add_new_item' => __('Add New Assistant Post'),
            'new_item' => __('New Post'),
            'edit_item' => __('Edit Post'),
            'view_item' => __('View Post'),
            'all_items' => __('All Assistant Posts'),
            'search_items' => __('Search Post'),
            'not_found' => __('No Post found.'),
        );
        $args = array(
            'supports' => $supports,
            'labels' => $labels,
            'public' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'assistant'),
            'has_archive' => true,
            'hierarchical' => false,
            // 'show_in_rest' => true, // Enabling Gutenberg editor
        );
        register_post_type('assistant', $args);
    }
    function add_assistant_taxonomy() {
        $labels = array(
            'name' => _x('Categories', 'taxonomy general name'),
            'singular_name' => _x('Category', 'taxonomy singular name'),
            'search_items' => __('Search Categories'),
            'all_items' => __('All Categories'),
            'parent_item' => __('Parent Category'),
            'parent_item_colon' => __('Parent Category:'),
            'edit_item' => __('Edit Category'),
            'update_item' => __('Update Category'),
            'add_new_item' => __('Add New Category'),
            'new_item_name' => __('New Category Name'),
            'menu_name' => __('Categories'),
        );
        register_taxonomy('assistant_category', 'assistant', array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'assistant-category'),
            'show_in_rest' => true, // Enabling Gutenberg editor
        ));
    }

    function add_assistant_metaboxes($post_type) {
        if ($post_type === 'assistant') {
            // add_meta_box('post_type_selector', 'Post Type', array($this, 'post_type_selector_callback'), 'assistant', 'side', 'high');
            add_meta_box('video_metabox', 'Video Post', array($this, 'video_metabox_callback'), 'assistant', 'normal', 'high');
            add_meta_box('standard_metabox', 'Standard Post', array($this, 'standard_metabox_callback'), 'assistant', 'normal', 'high');
        }
    }
   function post_type_selector_callback($post) {
      $post_type = get_post_meta($post->ID, '_post_type', true);
        ?>
        <label><input type="radio" name="post_type_checker" value="video" <?php checked($post_type, 'video'); ?>> Video</label>
        <label><input type="radio" name="post_type_checker" value="standard" <?php checked($post_type, 'standard'); ?>> Standard</label>
        <?php
   }
    function video_metabox_callback($post) {
        $video_url = get_post_meta($post->ID, '_video_url', true);
        $description = get_post_meta($post->ID, '_video_description', true);
        ?>
        <label for="video_url">Video URL:</label>
        <input type="text" id="video_url" name="video_url" value="<?php echo esc_attr($video_url); ?>" />
        <input type="button" class="button" id="video_media_button" value="Select Video from Library"><br />
        <label for="video_description">Video Description:</label>
        <?php
        wp_editor($description, 'video_description', array(
            'textarea_name' => 'video_description',
            'textarea_rows' => 5,
        ));
        ?>
        <?php
    }   

    function standard_metabox_callback($post) {
        $id = get_post_meta($post->ID, '_standard_id', true);
        $type = get_post_meta($post->ID, '_standard_type', true);
        $date = get_post_meta($post->ID, '_standard_date', true);
        $download_url_text = get_post_meta($post->ID, '_standard_download_url_text', true);
        $download_url = get_post_meta($post->ID, '_standard_download_url', true);
        ?>
        <table>
            <tr>
                <td><label for="standard_id">ID:</label></td>
                <td><input type="text" id="standard_id" name="standard_id" value="<?php echo esc_attr($id); ?>" /></td>
            </tr>
            <tr>
                <td><label for="standard_type">Type:</label></td>
                <td><input type="text" id="standard_type" name="standard_type" value="<?php echo esc_attr($type); ?>" /></td>
            </tr>
            <tr>
                <td><label for="standard_date">Date:</label></td>
                <td><input type="text" id="standard_date" name="standard_date" value="<?php echo esc_attr($date); ?>" /></td>
            </tr>
            <tr>
                <td><label for="standard_download_url_text">Download URL Text:</label></td>
                <td><input type="text" id="standard_download_url_text" name="standard_download_url_text" value="<?php echo esc_attr($download_url_text); ?>" /></td>
            </tr>
            <tr>
                <td><label for="standard_download_url">Download URL:</label></td>
                <td><input type="text" id="standard_download_url" name="standard_download_url" value="<?php echo esc_url($download_url); ?>" /></td>
            </tr>
        </table>
        <small style="color: #999; font-size: 12px;">Title will serve as Name</small>
     <?php
    }
    function save_assistant_metaboxes($post_id) {
        // Check if the post type is 'assistant'
        if ('assistant' !== get_post_type($post_id)) {
            return $post_id;
        }
        // Check if the user has permission to save the post
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
        // Avoid autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }   
       // Check if the data is set in the request
       if (isset($_POST['post_type'])) {
            update_post_meta($post_id, '_post_type', sanitize_text_field($_POST['post_type']));
        }    

        if (isset($_POST['video_url'])) {
           update_post_meta($post_id, '_video_url', esc_url_raw($_POST['video_url']));
        }
        if (isset($_POST['standard_id'])) {
            update_post_meta($post_id, '_standard_id', sanitize_text_field($_POST['standard_id']));
        }
        if (isset($_POST['standard_type'])) {
                   update_post_meta($post_id, '_standard_type', sanitize_text_field($_POST['standard_type']));
        }
        if (isset($_POST['standard_date'])) {
            update_post_meta($post_id, '_standard_date', sanitize_text_field($_POST['standard_date']));
        }
        if (isset($_POST['standard_download_url_text'])) {
            update_post_meta($post_id, '_standard_download_url_text', sanitize_text_field($_POST['standard_download_url_text']));
        }
        if (isset($_POST['standard_download_url'])) {
            update_post_meta($post_id, '_standard_download_url', esc_url_raw($_POST['standard_download_url']));
        }
        if (isset($_POST['video_description'])) {
            update_post_meta($post_id, '_video_description', wp_kses_post($_POST['video_description']));
        }
        return $post_id;
    }
}