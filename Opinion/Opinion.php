<?php
/**
 * Plugin Name: Opinion
 * Plugin URI: https://zee.axonteam.pk/
 * Description: A plugin to get user reviews and opinions about your site
 * Version: 1.0
 * Author: AXON Technologies
 * Author URI: https://zee.axonteam.pk/
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    die("Something went wrong");
}

register_activation_hook(__FILE__, 'activation_function'  );
register_deactivation_hook(__FILE__, 'deactivation_function'  );
register_uninstall_hook( __FILE__, 'uninstall_function'  );
function activation_function()
{
    global $wpdb,$table_prefix;
    $table =  $table_prefix.'opinion';
    $q = "CREATE TABLE `$table` (`name` VARCHAR(255) NOT NULL , `email` VARCHAR(255) NOT NULL , `city` VARCHAR(255) NOT NULL , `rating` INT(11) NOT NULL , `comments` VARCHAR(255) NOT NULL ) ENGINE = InnoDB;";
    $wpdb->query($q);
}

function deactivation_function()
{
    echo '<script>alert("Opinion plugin deactivated successfully")</script>';
}


function uninstall_function()
{
    global $wpdb,$table_prefix;
    $table =  $table_prefix.'opinion';
    $q = "DROP TABLE `<?$table?>`;";
    $wpdb->query($q);
}


add_action('admin_menu', 'custom_function1');
add_action('admin_enqueue_scripts', 'custom_function3');
add_action('wp_body_open', 'custom_function4');
add_action('wp_enqueue_scripts', 'custom_function5');

function custom_function1()
{
    add_menu_page('Opinion', 'Opinion', 'manage_options', 'opinion', 'custom_function2', plugins_url('/assets/icons/opinion.png', __FILE__));
}

function custom_function2()
{
    include_once('settings_page.php');
}

function custom_function3()
{
    wp_enqueue_style('stylesheet', plugins_url('/assets/css/style.css', __FILE__));
}

function custom_function4()
{
    include_once('user_form.php');
}

function custom_function5()
{
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js', [], '', true);
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css', [] );
    wp_enqueue_script('jquery-ui', 'https://code.jquery.com/ui/1.13.1/jquery-ui.min.js', ['jquery'], '', true);
}


function validate_POST($data)
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    else{
        die('You should not temper with the form method.');
    }
}

add_action( 'init', 'save_data' );

function save_data()
{
if (isset($_POST["btn_submit"])) 
{
    $name = validate_POST($_POST["name"]);
    $email = validate_POST($_POST["email"]);
    $city = validate_POST($_POST["city"]);
    $rating = validate_POST($_POST["rating"]);
    $comments = validate_POST($_POST["comments"]);

if (!isset($name) || !isset($email) || !isset($city) || empty($rating) || !isset($comments))
{
    die('Please set the values completely.'); 
}
global $wpdb,$table_prefix;
$table = $table_prefix."opinion";
$q = "INSERT INTO `$table` VALUES ('$name', '$email', '$city', '$rating', '$comments')";
$wpdb->query($q);
wp_redirect(home_url());
echo '<div class="notice notice-success is-dismissible">
<p>Thank you for your feedback.</p>
</div>';

// echo '<script>alert("Thank you for your feedback")</script>';
exit;
   
}else{
    return;
}

}
