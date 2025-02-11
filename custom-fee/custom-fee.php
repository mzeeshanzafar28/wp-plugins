<?php
/*
Plugin Name: Custom Fee
Description: Adds a custom fee based on an input values on the checkouts page.
Version: 1.0
Author: M.Zeeshan Zafar
License: GPLv2 or later
Author URI: https://www.linkedin.com/in/m-zeeshan-zafar-9205a1248/
*/

if (!defined('ABSPATH')) {
    die("Something went wrong");
}

function custom_fee_enqueue_script() {
    if (is_checkout()) {
        wp_enqueue_script('custom-fee-script', plugin_dir_url(__FILE__) . 'js/custom-fee-script.js', array('jquery'), '1.0', true);
        wp_localize_script('custom-fee-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    } 
}
add_action('wp_enqueue_scripts', 'custom_fee_enqueue_script');

add_action('woocommerce_review_order_before_payment', 'custom_field_function');
function custom_field_function() {
    echo '<div name = "clown"><div id="0" name="count">
    <p class="form-row form-row-wide" id="custom-fee-input-p" data-priority="110"><label id="lab">Custom Fee&nbsp;<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><input type="text" class="input-text " name="custom-fee-input" maxlength="10" id="custom-fee-input"></span></p></div></div>';
}

add_action('wp_ajax_update_custom_fee', 'custom_fee_update_custom_fee');
add_action('wp_ajax_nopriv_update_custom_fee', 'custom_fee_update_custom_fee');
function custom_fee_update_custom_fee() {
    if (isset($_POST['custom_fee'])) {
        $custom_fee = intval($_POST['custom_fee']);
            WC()->session->set('custom_fee', $custom_fee);
    }
    wp_die();
}

add_action('woocommerce_cart_calculate_fees', 'custom_fee_checkout_input_fee', 20, 1);
function custom_fee_checkout_input_fee($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    $custom_fee = WC()->session->get('custom_fee');
    
    if ($custom_fee && $custom_fee != 0 && $custom_fee != null){
        $fee = 10 * $custom_fee;
        $cart->add_fee('Custom Fee', $fee);
}

}


// add_action('wp_footer', 'store_for_reload');

function store_for_reload()
{
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
//             if (localStorage.getItem("countElements")) {
//                 var storedElements = JSON.parse(localStorage.getItem("countElements"));
//                 var countDiv = document.getElementsByName("count")[0];
//                 countDiv.innerHTML = storedElements.html;
//                 restoreElementValues(storedElements.children);
//             }
//         });

//         function restoreElementValues(children) {
//             for (var i = 0; i < children.length; i++) {
//                 var child = children[i];
//                 if (child.nodeType === Node.ELEMENT_NODE) {
//                     var storedValue = child.getAttribute("data-stored-value");
//                     if (storedValue !== null) {
//                         child.value = storedValue;
//                     }
//                 }
//             }
//         }

//         function storeElements() {
//             var countDiv = document.getElementsByName("count")[0];
//             var storedElements = {
//                 html: countDiv.innerHTML,
//                 children: collectElementValues(countDiv.childNodes)
//             };
//             localStorage.setItem("countElements", JSON.stringify(storedElements));
//         }

//         function collectElementValues(children) {
//             var values = [];
//             for (var i = 0; i < children.length; i++) {
//                 var child = children[i];
//                 if (child.nodeType === Node.ELEMENT_NODE) {
//                     var storedValue = child.value;
//                     if (storedValue !== "") {
//                         child.setAttribute("data-stored-value", storedValue);
//                     }
//                     values.push(child);
//                 }
//             }
//             return values;
        }

//         window.addEventListener("beforeunload", storeElements);
</script>
<?php
}
