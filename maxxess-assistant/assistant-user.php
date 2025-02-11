<?php
if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly');
}

class AssistantUser {
    public function __construct(){
        // short Code to Display category Data
        add_shortcode('show_data', array($this, 'category_shortcode_callback'));

        // shortcode to display the input field for Search
        add_shortcode('search_bar', array($this, 'search_bar_shortcode'));
        add_filter('posts_search_columns', array($this, 'add_search_index'));

        //Call back for ajax request  
        add_action('wp_ajax_search_products', array($this, 'search_products'));
        add_action('wp_ajax_nopriv_search_products', array($this, 'search_products'));
    }

    function category_shortcode_callback($atts) {
        $category_slug = $atts['slug'];
        $category = get_term_by('slug', $category_slug, 'assistant_category');
        $category_name = $category->name;

        $args = array(
            'post_type' => 'assistant', 
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'assistant_category',
                    'field' => 'slug',
                    'terms' => $category_slug,
                ),
            ),
        );
        $query = new WP_Query($args);
        ob_start();
        $output = '';
        $otstandard =  '<table class="table-1"><thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Date</th><th>Download</th></tr></thead>';
        $otvideo =  '<table><thead></thead>';
        static $spt = 0;
        static $vpt = 0;
        static $ctAdded = false;
        static $addedCat = '';
    
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $title = get_the_title();
                $video_title = '';

                // $category = get_the_terms(get_the_ID(), 'assistant_category');
                // if ($category && !is_wp_error($category)) {
                //     foreach ($category as $cat) {
                //         $parent_cat = get_term($cat->parent, 'assistant_category');
                //         if ($addedCat != $cat->name) {
                //             $ctAdded = false;
                //         }
                //         else{
                //             $ctAdded = true;
                //         }
                //         if ($parent_cat && !is_wp_error($parent_cat) && $parent_cat->name === 'Training Videos' && $ctAdded == false) {
                //             $video_title .= '<h1 style="display:inline;">'.$cat->name.'</h1>';
                //             $addedCat = $cat->name;
                //             $ctAdded = true;
                //             break; 
                //         }
                //     }
                // }

                $video_url = get_post_meta(get_the_ID(), '_video_url', true);
                $video_title .= '<tr><td style="text-align: center;"><b>'.$title.'</b><td></tr>';
                $video = '<video width="100%" height="100%" controls><source src="' . esc_url($video_url) . '" ></video>';
                $video_description = get_post_meta(get_the_ID(), '_video_description', true);
                $standard_id = get_post_meta(get_the_ID(), '_standard_id', true);
                $standard_type = get_post_meta(get_the_ID(), '_standard_type', true);
                $standard_date = get_post_meta(get_the_ID(), '_standard_date', true);
                $standard_download_url_text = get_post_meta(get_the_ID(), '_standard_download_url_text', true);
                $standard_download_url = get_post_meta(get_the_ID(), '_standard_download_url', true);

                if (!empty($standard_type) && !empty($standard_date) && !empty($standard_download_url_text) && !empty($standard_download_url)) {
                    if ($spt == 0)
                    {
                        $output .= $otstandard;
                    }
                    $spt = 1;
                    $output .= '<tr>';
                    $output .= '<td>' . esc_html($standard_id) . '</td>';
                    $output .= '<td>' . esc_html($title) . '</td>';
                    $output .= '<td>' . esc_html($standard_type) . '</td>';
                    $output .= '<td>' . esc_html($standard_date) . '</td>';
                    $output .= '<td><a target="_blank" href="' . esc_url($standard_download_url) . '">' . esc_html($standard_download_url_text) . '</a></td>';
                    $output .= '</tr>';
                }
                else if (!empty($video_url)) {
                    if ($vpt == 0)
                    {
                        $output .= $otvideo;
                    }
                    $vpt = 1;
                    $output .= $video_title;
                    $output .= '<tr>';
                    $output .= '<td width="40%" >' . $video . '</td>';
                    $output .= '<td width="60%" style="line-height:normal;padding: 0 10px;">' . wp_kses_post($video_description) . '</td>';
                    $output .= '</tr>';
                }
            }
        }
        $output .= '</table>';
        wp_reset_postdata();
        ob_get_clean();
        $spt = 0;
         $vpt = 0;
         $ctAdded = false;
         $addedCat = '';
        return $output;
    }

    function add_search_index($index_columns)
    {
        $index_columns['post_title'] = 'post_title(255)';
        $index_columns['post_content'] = 'post_content(255)';
        return $index_columns;
    }

    public function search_products(){
        $search_term = sanitize_text_field($_GET['search_term']);
        // Check if search term has at least 3 characters
        if (mb_strlen($search_term, 'UTF-8') < 3) {
            wp_send_json_error('Search term should be at least 3 characters long.');
        }
    
        global $wpdb;
        $assistants = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE post_type = 'assistant' AND post_status = 'publish' AND 
                ((post_title LIKE '%%%s%%' OR post_content LIKE '%%%s%%')
                OR (ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_standard_id' AND meta_value LIKE '%%%s%%'))
                OR (ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_video_description' AND meta_value LIKE '%%%s%%')))",
                $search_term,
                $search_term,
                $search_term,
                $search_term
            )
        );
    
        if ($assistants) {
            $result = array();
            foreach ($assistants as $assistant) {
                $assistant_id = $assistant->ID;
                $video_url = esc_url(get_post_meta($assistant_id, '_video_url', true)); // Get video URL
                $video_description = get_post_meta($assistant_id, '_video_description', true); // Get video description
    
                // Fetch data from the standard metabox
                $standard_id = get_post_meta($assistant_id, '_standard_id', true);
                $standard_type = get_post_meta($assistant_id, '_standard_type', true);
                $standard_date = get_post_meta($assistant_id, '_standard_date', true);
                $standard_download_url_text = get_post_meta($assistant_id, '_standard_download_url_text', true);
                $standard_download_url = esc_url(get_post_meta($assistant_id, '_standard_download_url', true));
    
                $result[] = array(
                    'id' => $assistant_id,
                    'title' => get_the_title($assistant_id),
                    'permalink' => get_permalink($assistant_id),
                    'video_url' => $video_url,
                    'video_description' => $video_description,
                    'standard_id' => $standard_id,
                    'standard_type' => $standard_type,
                    'standard_date' => $standard_date,
                    'standard_download_url_text' => $standard_download_url_text,
                    'standard_download_url' => $standard_download_url
                );
            }
    
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => 'No assistants found'));
        }
    }
    

    
    public function search_bar_shortcode($atts)
    {
        ob_start();
        ?>
        <div class="search-bar-container" style=" max-width: 95vw;">
            <input type="text" id="search-bar" placeholder="Search here...">
            <div id="search-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }


}