<?php
/*
Plugin Name: Content Partner WP
Description: This plugin connects ContentPartner.ai to your WordPress website. Get your ContentPartner.ai articles inside your WordPress website.
Version: 1.1
Author: Axon Technologies
*/


// Register submenu pages
add_action('admin_menu', 'content_partner_wp_register_submenu_pages');

function content_partner_wp_register_submenu_pages() {
    add_menu_page('Content Partner', 'Content Partner', 'manage_options', 'content-partner', 'content_partner_wp_all_articles_page', 'dashicons-admin-page', 6);
    add_submenu_page('content-partner', 'All Articles', 'All Articles', 'manage_options', 'content-partner', 'content_partner_wp_all_articles_page');
    add_submenu_page('content-partner', 'New Article', 'New Article', 'manage_options', 'content-partner-new-article', 'content_partner_wp_new_article_page');
    add_submenu_page('content-partner', 'Settings', 'Settings', 'manage_options', 'content-partner-settings', 'content_partner_wp_settings_page');
}


// New Article page
function content_partner_wp_new_article_page() {
    content_partner_wp_style()
    ?>
    <div class="wrap">
        <h1>Generate New Article - Content Partner</h1>
        <div id="content-partner-new-article">
            <form method="post" action="admin-post.php">
                <?php
                settings_fields('content_partner_wp_new_article_group');
                do_settings_sections('content-partner-new-article');
                ?>
                <input type="hidden" name="action" value="content_partner_wp_generate_article">
                <button type="submit" class="button button-primary"><svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.9 0.499976C13.9 0.279062 13.7209 0.0999756 13.5 0.0999756C13.2791 0.0999756 13.1 0.279062 13.1 0.499976V1.09998H12.5C12.2791 1.09998 12.1 1.27906 12.1 1.49998C12.1 1.72089 12.2791 1.89998 12.5 1.89998H13.1V2.49998C13.1 2.72089 13.2791 2.89998 13.5 2.89998C13.7209 2.89998 13.9 2.72089 13.9 2.49998V1.89998H14.5C14.7209 1.89998 14.9 1.72089 14.9 1.49998C14.9 1.27906 14.7209 1.09998 14.5 1.09998H13.9V0.499976ZM11.8536 3.14642C12.0488 3.34168 12.0488 3.65826 11.8536 3.85353L10.8536 4.85353C10.6583 5.04879 10.3417 5.04879 10.1465 4.85353C9.9512 4.65827 9.9512 4.34169 10.1465 4.14642L11.1464 3.14643C11.3417 2.95116 11.6583 2.95116 11.8536 3.14642ZM9.85357 5.14642C10.0488 5.34168 10.0488 5.65827 9.85357 5.85353L2.85355 12.8535C2.65829 13.0488 2.34171 13.0488 2.14645 12.8535C1.95118 12.6583 1.95118 12.3417 2.14645 12.1464L9.14646 5.14642C9.34172 4.95116 9.65831 4.95116 9.85357 5.14642ZM13.5 5.09998C13.7209 5.09998 13.9 5.27906 13.9 5.49998V6.09998H14.5C14.7209 6.09998 14.9 6.27906 14.9 6.49998C14.9 6.72089 14.7209 6.89998 14.5 6.89998H13.9V7.49998C13.9 7.72089 13.7209 7.89998 13.5 7.89998C13.2791 7.89998 13.1 7.72089 13.1 7.49998V6.89998H12.5C12.2791 6.89998 12.1 6.72089 12.1 6.49998C12.1 6.27906 12.2791 6.09998 12.5 6.09998H13.1V5.49998C13.1 5.27906 13.2791 5.09998 13.5 5.09998ZM8.90002 0.499976C8.90002 0.279062 8.72093 0.0999756 8.50002 0.0999756C8.2791 0.0999756 8.10002 0.279062 8.10002 0.499976V1.09998H7.50002C7.2791 1.09998 7.10002 1.27906 7.10002 1.49998C7.10002 1.72089 7.2791 1.89998 7.50002 1.89998H8.10002V2.49998C8.10002 2.72089 8.2791 2.89998 8.50002 2.89998C8.72093 2.89998 8.90002 2.72089 8.90002 2.49998V1.89998H9.50002C9.72093 1.89998 9.90002 1.72089 9.90002 1.49998C9.90002 1.27906 9.72093 1.09998 9.50002 1.09998H8.90002V0.499976Z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path></svg> Generate Article</button>
            </form>
            <div class="mf-overlay">
                <div class="mf-loader"></div>
            </div>
        </div>
    </div>
    <?php
}

// All Articles page
function content_partner_wp_all_articles_page() {
    $user_id = get_option('content_partner_wp_user_id');
    $token_id = get_option('content_partner_wp_token_id');
    $api_url = "https://api-prod.contentpartner.ai/user-history/getUserHistory/$user_id";
    $headers = array(
        'Authorization' => 'Bearer ' . $token_id
    );

    $response = wp_remote_post($api_url, array('headers' => $headers, 'timeout'=> 300));

    if (is_wp_error($response)) {
        wp_die('Error fetching articles: ' . $response->get_error_message());
    }

    $articles = json_decode(wp_remote_retrieve_body($response), true);
    content_partner_wp_style();
    ?>
    <link rel="stylesheet" href="https://pagination.js.org/dist/2.6.0/pagination.css">
    <div class="wrap">
        <h1>All Articles - Content Partner</h1>
        <br>
        <table id="content-partner-articles-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th style="width: 20%; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article) : ?>
                    <tr>
                        <td><?php echo esc_html($article['dataTitle']); ?></td>
                        <td style="text-align: center;">
                            <?php
                            // Query for posts with meta key '_content_partner_article_id' equal to current article's _id
                            $query_args = array(
                                'meta_key' => '_content_partner_article_id',
                                'meta_value' => $article['_id'],
                                'post_type' => 'post',
                                'post_status' => 'any',
                                'fields' => 'ids'
                            );
                            $posts_with_meta = get_posts($query_args);
                            $is_published = !empty($posts_with_meta);
                
                            if (!$is_published) : ?>
                                <form method="post" action="admin-post.php" style="display: inline;">
                                    <input type="hidden" name="action" value="content_partner_wp_publish_article">
                                    <input type="hidden" name="article_id" value="<?php echo esc_attr($article['_id']); ?>">
                                    <button type="submit" class="button button-primary">Publish</button>
                                </form>
                            <?php else :
                                $post_id = $posts_with_meta[0];
                                ?>
                                <a href="<?php echo get_edit_post_link($post_id); ?>" class="button">Edit</a>
                                <a href="<?php echo get_permalink($post_id); ?>" class="button">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <div id="content-partner-pagination"></div>
    </div>
    <?php
}

// Settings page
function content_partner_wp_settings_page() {
    ?>
    <div class="wrap">
        <h1>Settings - Content Partner</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('content_partner_wp_settings_group');
            do_settings_sections('content-partner-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'content_partner_wp_register_settings');

function content_partner_wp_register_settings() {
    // New Article settings
    add_settings_section('content_partner_wp_new_article_section', '', null, 'content-partner-new-article');
    add_settings_field('content_partner_wp_topic', 'Topic', 'content_partner_wp_topic_callback', 'content-partner-new-article', 'content_partner_wp_new_article_section');
    add_settings_field('content_partner_wp_tone', 'Tone', 'content_partner_wp_tone_callback', 'content-partner-new-article', 'content_partner_wp_new_article_section');
    add_settings_field('content_partner_wp_keywords', 'Enter SEO Keywords (Max 10)', 'content_partner_wp_keywords_callback', 'content-partner-new-article', 'content_partner_wp_new_article_section');
    add_settings_field('content_partner_wp_language', 'Choose Language', 'content_partner_wp_language_callback', 'content-partner-new-article', 'content_partner_wp_new_article_section');
    add_settings_field('content_partner_wp_template', 'Choose Template', 'content_partner_wp_template_callback', 'content-partner-new-article', 'content_partner_wp_new_article_section');
    add_settings_field('content_partner_wp_writing_style', 'Choose Writing Style', 'content_partner_wp_writing_style_callback', 'content-partner-new-article', 'content_partner_wp_new_article_section');
    add_settings_field('content_partner_wp_writing_voice', 'Choose Writing Voice', 'content_partner_wp_writing_voice_callback', 'content-partner-new-article', 'content_partner_wp_new_article_section');
    add_settings_field('content_partner_wp_citation', 'Add Auto Citations', 'content_partner_wp_citation_callback', 'content-partner-new-article', 'content_partner_wp_new_article_section');
    add_settings_field('content_partner_wp_no_of_citations', 'Number of Citations', 'content_partner_wp_no_of_citations_callback', 'content-partner-new-article', 'content_partner_wp_new_article_section');
    add_settings_field('content_partner_wp_faqs', 'Add FAQs based on the article', 'content_partner_wp_faqs_callback', 'content-partner-new-article', 'content_partner_wp_new_article_section');

    register_setting('content_partner_wp_new_article_group', 'content_partner_wp_topic');
    register_setting('content_partner_wp_new_article_group', 'content_partner_wp_tone');
    register_setting('content_partner_wp_new_article_group', 'content_partner_wp_keywords', 'content_partner_wp_keywords_sanitize');
    register_setting('content_partner_wp_new_article_group', 'content_partner_wp_language');
    register_setting('content_partner_wp_new_article_group', 'content_partner_wp_template');
    register_setting('content_partner_wp_new_article_group', 'content_partner_wp_writing_style');
    register_setting('content_partner_wp_new_article_group', 'content_partner_wp_writing_voice');
    register_setting('content_partner_wp_new_article_group', 'content_partner_wp_citation');
    register_setting('content_partner_wp_new_article_group', 'content_partner_wp_no_of_citations');
    register_setting('content_partner_wp_new_article_group', 'content_partner_wp_faqs');
    // Settings page settings
    add_settings_section('content_partner_wp_settings_section', '', null, 'content-partner-settings');
    add_settings_field('content_partner_wp_token_id', 'Token ID', 'content_partner_wp_token_id_callback', 'content-partner-settings', 'content_partner_wp_settings_section');
    add_settings_field('content_partner_wp_user_id', 'User ID', 'content_partner_wp_user_id_callback', 'content-partner-settings', 'content_partner_wp_settings_section');
    register_setting('content_partner_wp_settings_group', 'content_partner_wp_token_id');
    register_setting('content_partner_wp_settings_group', 'content_partner_wp_user_id');
}

// Sanitize keywords input
function content_partner_wp_keywords_sanitize($input) {
    return array_slice(array_map('sanitize_text_field', $input), 0, 10);
}

// Callback functions for the fields
function content_partner_wp_topic_callback() {
    $topic = get_option('content_partner_wp_topic');
    echo '<input type="text" name="content_partner_wp_topic" value="' . esc_attr($topic) . '" required />';
}

function content_partner_wp_tone_callback() {
    $tone = get_option('content_partner_wp_tone');
    ?>
    <select name="content_partner_wp_tone">
        <option value="Neutral" <?php selected($tone, 'Neutral'); ?>>Neutral</option>
        <option value="Friendly" <?php selected($tone, 'Friendly'); ?>>Friendly</option>
        <option value="Professional" <?php selected($tone, 'Professional'); ?>>Professional</option>
    </select>
    <?php
}

function content_partner_wp_keywords_callback() {
    $keywords = get_option('content_partner_wp_keywords');
    echo '<select name="content_partner_wp_keywords[]" class="select2" multiple="multiple" style="width: 50%;" required>';
    if (!empty($keywords)) {
        foreach ($keywords as $keyword) {
            echo '<option value="' . esc_attr($keyword) . '" selected>' . esc_html($keyword) . '</option>';
        }
    }
    echo '</select>';
}

function content_partner_wp_language_callback() {
    $language = get_option('content_partner_wp_language');
    ?>
    <select name="content_partner_wp_language">
        <option value="English" <?php selected($language, 'English'); ?>>English</option>
        <option value="French" <?php selected($language, 'French'); ?>>French</option>
        <option value="Spanish" <?php selected($language, 'Spanish'); ?>>Spanish</option>
    </select>
    <?php
}

function content_partner_wp_template_callback() {
    $template = get_option('content_partner_wp_template');
    ?>
    <select name="content_partner_wp_template">
        <option value="Short Form" <?php selected($template, 'Short Form'); ?>>Short Form</option>
        <option value="Long Form" <?php selected($template, 'Long Form'); ?>>Long Form</option>
        <option value="Long Form Pro" <?php selected($template, 'Long Form Pro'); ?>>Long Form Pro</option>
    </select>
    <?php
}

function content_partner_wp_writing_style_callback() {
    $writing_style = get_option('content_partner_wp_writing_style');
    ?>
    <select name="content_partner_wp_writing_style">
        <option value="Casual" <?php selected($writing_style, 'Casual'); ?>>Casual</option>
        <option value="Formal" <?php selected($writing_style, 'Formal'); ?>>Formal</option>
        <option value="Neutral" <?php selected($writing_style, 'Neutral'); ?>>Neutral</option>
    </select>
    <?php
}

function content_partner_wp_writing_voice_callback() {
    $writing_voice = get_option('content_partner_wp_writing_voice');
    ?>
    <select name="content_partner_wp_writing_voice">
        <option value="Active" <?php selected($writing_voice, 'Active'); ?>>Active</option>
        <option value="Passive" <?php selected($writing_voice, 'Passive'); ?>>Passive</option>
    </select>
    <?php
}

function content_partner_wp_citation_callback() {
echo '<input id="content_partner_wp_citation" type="checkbox" style="width:15px !important;" name="content_partner_wp_citation" value="1" />
<script>
    jQuery(document).ready(function($){
        var citation = $("#content_partner_wp_citation");

        function updateCheckboxValue() {
            citation.val(citation.is(":checked") ? "1" : "0");
        }

        updateCheckboxValue();

        citation.on("click", function(){
            updateCheckboxValue();
        });
    });
</script>';


    
}
function content_partner_wp_no_of_citations_callback() {
    $no_of_citations = get_option('content_partner_wp_no_of_citations');
    echo '<input style="display:none;"id="content_partner_wp_no_of_citations" min="0" max="9" type="number" name="content_partner_wp_no_of_citations" value="3" />';
}

function content_partner_wp_faqs_callback() {
echo '<input id="content_partner_wp_faqs" type="checkbox" style="width:15px !important;" name="content_partner_wp_faqs" value="1" />
<script>
    jQuery(document).ready(function($){
        var faqs = $("#content_partner_wp_faqs");

        function updateCheckboxValue() {
            faqs.val(faqs.is(":checked") ? "1" : "0");
        }

        updateCheckboxValue();

        faqs.on("click", function(){
            updateCheckboxValue();
        });
    });
</script>';

    
}


function content_partner_wp_token_id_callback() {
    $token_id = get_option('content_partner_wp_token_id');
    echo '<input type="text" name="content_partner_wp_token_id" value="' . esc_attr($token_id) . '" style="width: 100%; max-width: 400px;" />';
}

function content_partner_wp_user_id_callback() {
    $user_id = get_option('content_partner_wp_user_id');
    echo '<input type="text" name="content_partner_wp_user_id" value="' . esc_attr($user_id) . '" style="width: 100%; max-width: 400px;" />';
}

add_action('admin_footer', 'show_hide_no_of_citations');
function show_hide_no_of_citations() {
    echo '<script>
        jQuery(document).ready(function($){
            // Check the initial state of the checkbox
            if ($("#content_partner_wp_citation").is(":checked")) {
                $("#content_partner_wp_no_of_citations").show();
            } else {
                $("#content_partner_wp_no_of_citations").hide();
            }

            // Handle checkbox click event
            $("#content_partner_wp_citation").on("change", function() {
                if ($(this).is(":checked")) {
                    $("#content_partner_wp_no_of_citations").show();
                } else {
                    $("#content_partner_wp_no_of_citations").hide();
                }
            });
        });
    </script>';
}


// Handle form submission to generate article
add_action('admin_post_content_partner_wp_generate_article', 'content_partner_wp_generate_article');

function content_partner_wp_generate_article() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    // Fetch necessary data from settings
    $token_id = get_option('content_partner_wp_token_id');
    $user_id = get_option('content_partner_wp_user_id');
    
    $keywords = isset($_POST['content_partner_wp_keywords']) ? array_map('sanitize_text_field', $_POST['content_partner_wp_keywords']) : array();
    $topic = isset($_POST['content_partner_wp_topic']) ? sanitize_text_field($_POST['content_partner_wp_topic']) : '';
    
    $citation = isset($_POST['content_partner_wp_citation']) ? sanitize_text_field($_POST['content_partner_wp_citation']) : '';
    $citation = intval($citation) == 1 ? 'true' : 'false';
    $noOfCitations = '';
    if ($citation == 'true'){
        $noOfCitations = isset($_POST['content_partner_wp_no_of_citations']) ? sanitize_text_field($_POST['content_partner_wp_no_of_citations']) : '';
    }
    
    $faqs = isset($_POST['content_partner_wp_faqs']) ? sanitize_text_field($_POST['content_partner_wp_faqs']) : '';
    $faqs = intval($faqs) == 1 ? 'true' : 'false';
    
        // Request 1 - Generate Fancy Title
    $api_url = 'https://api-prod.contentpartner.ai/open-ai/generateFancyHeadline';

    $title = isset($_POST['content_partner_wp_topic']) ? sanitize_text_field($_POST['content_partner_wp_topic']) : '';
    $language = isset($_POST['content_partner_wp_language']) ? sanitize_text_field($_POST['content_partner_wp_language']) : 'English';
    $request_body = array(
        'topic' => $title,
        'language' => $language
    );

    // Prepare headers
    $headers = array(
        'Authorization' => 'Bearer ' . $token_id,
        'Content-Type' => 'application/json'
    );

    // Send POST request to generate headline
    $response = wp_remote_post($api_url, array(
        'headers' => $headers,
        'body' => json_encode($request_body),
        'timeout'=> 300
    ));
    
    if (is_wp_error($response)) {
        wp_die('Error generating headline: ' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    $generated_title = isset($data['headlines']) ? $data['headlines'][0] : '';
    
    // Request 2 - Get Article Outline
    $outline_api_url = "https://api-prod.contentpartner.ai/open-ai/generateQuestions";
    
    $outline_request_body = array(
        'topic' => $generated_title,
        'keywords' => $keywords,
        'language' => $language
    );

    $outline_response = wp_remote_post($outline_api_url, array(
        'headers' => $headers,
        'body' => json_encode($outline_request_body),
        'timeout'=> 300
    ));
    
    if (is_wp_error($outline_response)) {
        wp_die('Error generating outline: ' . $outline_response->get_error_message());
    }

    $outline_body = wp_remote_retrieve_body($outline_response);
    $outline_data = json_decode($outline_body, true);
    $outline = isset($outline_data['outline']) ? $outline_data['outline'] : '';

    // Request 3 - Generate the Article
    
    // Based on the chosen template, call respective API to generate article
    $template = isset($_POST['content_partner_wp_template']) ? sanitize_text_field($_POST['content_partner_wp_template']) : '';
    $article_type = '';
    $api_endpoint = '';

    switch ($template) {
        case 'Short Form':
            $article_type = 'short';
            $api_endpoint = 'https://api-prod.contentpartner.ai/open-ai/generateArticle';
            break;
        case 'Long Form':
            $article_type = 'long';
            $api_endpoint = 'https://api-prod.contentpartner.ai/open-ai/generateLongArticle';
            break;
        case 'Long Form Pro':
            $article_type = 'long_pro';
            $api_endpoint = 'https://api-prod.contentpartner.ai/open-ai/generateLongArticle';
            break;
        default:
            wp_die('Invalid template selected.');
    }

    
    // Prepare request body for article generation
    $request_body = array(
        'outline' => $outline,
        'headline' => $generated_title,
        'topic' => $topic,
        'keywords' => $keywords,
        'userId' => $user_id,
        'language' => $language,
        'articleType' => $article_type,
        'addCitations' => $citation,
        'numberOfCitations' => intval($noOfCitations),
        'addFaq' => $faqs
        
    );
    
    
    // Send POST request to generate article 
    $response = wp_remote_post($api_endpoint, array(
        'headers' => $headers,
        'body' => json_encode($request_body),
        'timeout'=> 300
    ));
    
    
    if (is_wp_error($response)) {
        wp_die('Error generating article: ' . $response->get_error_message());
    }

    // Process response and handle accordingly
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    $citationsContent = '';
    foreach ($data['article']['citations'] as $citation) {
        $citationsContent .= "Title: {$citation['title']}\n";
        $citationsContent .= "Link: {$citation['link']}\n";
        $citationsContent .= "Authors: {$citation['authors']}\n";
        $citationsContent .= "Takeaways: {$citation['takeaways']}\n\n";
    }
    

    // Request 4 - Save the article
    // Prepare request body to save article data
    $save_api_url = 'https://api-prod.contentpartner.ai/user-history/saveData';
   $save_request_body = array(
    'data' => $data['article']['introduction'] . "\n\n" . 
              $data['article']['explainedHeadings'] . "\n\n" . 
              $data['article']['conclusion'] . "\n\n" . 
              $data['article']['faqs'] . "\n\n" . 
              $citationsContent,
    'metaDescription' => isset($data['article']['metaDescription']) ? $data['article']['metaDescription'] : '',
    'dataType' => 'Article',
    'dataTitle' => $generated_title,
    'createdAt' => current_time('mysql'),
    'updatedAt' => current_time('mysql')
);

    // Send POST request to save article data
    $save_response = wp_remote_post($save_api_url, array(
        'headers' => $headers,
        'body' => json_encode($save_request_body),
        'timeout' => 300
    ));
    
    if (is_wp_error($save_response)) {
        wp_die('Error saving article data: ' . $save_response->get_error_message());
    }
    
    $save_body = wp_remote_retrieve_body($save_response);
    $save_data = json_decode($save_body, true);

    // Request 5 - Save Article to User History

    // Prepare request body to save user history
    $history_api_url = 'https://api-prod.contentpartner.ai/user-history/saveUserHistory';
    $history_request_body = array(
        'articleType' => $article_type,
        'keywords' => $keywords,
        'dataTitle' => $generated_title,
        'dataId' => $save_data['_id'],
        'userId' => $user_id
    );
    
    // Send POST request to save user history
    $history_response = wp_remote_post($history_api_url, array(
        'headers' => $headers,
        'body' => json_encode($history_request_body),
        'timeout' => 300
    ));
    
    if (is_wp_error($history_response)) {
        wp_die('Error saving user history: ' . $history_response->get_error_message());
    }
    
    wp_redirect(admin_url('admin.php?page=content-partner'));
    exit;
}

// Handle form submission to publish article
add_action('admin_post_content_partner_wp_publish_article', 'content_partner_wp_publish_article');

function content_partner_wp_publish_article() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    $article_id = isset($_POST['article_id']) ? sanitize_text_field($_POST['article_id']) : '';

    // Get articles data from user history API
    $user_id = get_option('content_partner_wp_user_id');
    $token_id = get_option('content_partner_wp_token_id');
    $api_url = "https://api-prod.contentpartner.ai/user-history/getUserHistory/$user_id";
    $headers = array(
        'Authorization' => 'Bearer ' . $token_id
    );

    $response = wp_remote_post($api_url, array('headers' => $headers, 'timeout'=> 300));

    if (is_wp_error($response)) {
        wp_die('Error fetching articles: ' . $response->get_error_message());
    }

    $articles = json_decode(wp_remote_retrieve_body($response), true);
    // Find the article by ID
    $article_data = array_filter($articles, function ($article) use ($article_id) {
        return isset($article['_id']) && $article['_id'] === $article_id;
    });
    
    $found_data = wp_remote_post('https://api-prod.contentpartner.ai/user-history/getDataById/' . $article_data[0]['dataId'], array('headers' => $headers, 'timeout'=> 300));
    $found_data_body = json_decode(wp_remote_retrieve_body($found_data), true);
    $article_body = $found_data_body['data'];

    if (empty($article_data)) {
        wp_die('Article not found.');
    }

    $article_data = reset($article_data); // Get the first item in the array

    // Insert the article as a new WordPress post
    $post_data = array(
        'post_title'    => sanitize_text_field($article_data['dataTitle']),
        'post_content'  => $article_body, 
        'post_status'   => 'publish',
        'post_author'   => get_current_user_id(),
        'post_type'     => 'post'
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_die('Error publishing article as WordPress post: ' . $post_id->get_error_message());
    }

    // Update meta data to indicate article has been published
    update_post_meta($post_id, '_content_partner_wp_published', true);
    update_post_meta($post_id, '_content_partner_article_id', $article_id);

    // Redirect back to All Articles page after publishing
    wp_redirect(admin_url('admin.php?page=content-partner'));
    exit;
}

add_action('admin_enqueue_scripts', 'content_partner_wp_enqueue_scripts');

function content_partner_wp_enqueue_scripts() {
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
    wp_add_inline_script('select2', '
        jQuery(document).ready(function($) {
            $(".select2").select2({
                tags: true,
                tokenSeparators: [","],
                maximumSelectionLength: 10
            });
            $("#content-partner-new-article form").on("submit", function() {
                $("#content-partner-new-article .mf-overlay").css("display", "flex");
            });
        });
    ');
    wp_enqueue_script('paginationjs', 'https://pagination.js.org/dist/2.1.4/pagination.min.js', array('jquery'), null, true);
    wp_add_inline_script('paginationjs', '
        jQuery(document).ready(function($) {
            let rows = [];
            $("#content-partner-articles-table tbody tr").each(function(i, row) {
            	return rows.push(row);
            });
            
            $("#content-partner-pagination").pagination({
                dataSource: rows,
                pageSize: 10,
                callback: function(data, pagination) {
                    $("tbody").html(data);
                }
            })
        });
    ');
}

function content_partner_wp_style() {
    ?><style>
    .wrap h1 { text-align: center; margin-top: 30px; font-weight: 600; }
    #content-partner-new-article { position: relative; background: white; width: 100%; max-width: 800px; margin: 30px auto; padding: 30px 50px; border-radius: 50px; box-shadow: 0 0 20px 1px rgba(100, 100, 100, 0.1); }
    #content-partner-new-article table tr th { font-size: 18px; }
    #content-partner-new-article table tr td input, #content-partner-new-article table tr td select, #content-partner-new-article table tr td .select2-container { width: 100% !important; max-width: 100%; padding: 6px 12px; border: 1px solid #ddd; border-radius: 5px; }
    #content-partner-new-article table tr td .select2-container .selection .select2-selection { border: none !important; }
    #content-partner-new-article table tr td .select2-container .dropdown-wrapper, body.content-partner_page_content-partner-new-article .select2-container .select2-dropdown { display: none !important; }
    #content-partner-new-article .button.button-primary { font-size: 18px; background: #16a34a; border-radius: 30px; padding: 3px 20px; margin: 30px auto 10px auto; display: block; }
    .mf-overlay { display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.8); z-index: 10; justify-content: center; align-items: center; }
    .mf-loader { border: 4px solid #f3f3f3; border-top: 4px solid #16a34a; border-radius: 50%; width: 60px; height: 60px; animation: spin 2s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    #content-partner-pagination .paginationjs { justify-content: center; }
    #content-partner-articles-table tr th { font-weight: bold; }
    </style><?php
}
?>
