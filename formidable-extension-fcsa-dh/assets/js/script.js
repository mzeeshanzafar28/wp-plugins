
jQuery(document).ready(function($) {
    $('.permission-dropdown').change(function() {
        var user_id = $(this).data('user-id');
        var permission_key = $(this).data('permission-key');
        var permission = $(this).val();
        updatePermission(user_id, permission_key ,  permission, this);
    });

    function updatePermission(user_id, permission_key ,permission, element) {
        jQuery(element).css("opacity", 0.5);
        jQuery(element).css("pointer-events", 'none');

        jQuery.ajax({
            url: fefdhajaxurl.ajaxurl,
            type: "POST",
            data: {
                action: "fefdh_update_permission",
                user_id: user_id,
                permission_key:permission_key,
                permission_val: permission,
            },
            success: function (response) {
                jQuery(element).css("opacity", 1);
                jQuery(element).css("pointer-events", 'initial');
            },
            error: function (error) {
                alert("Error updating permission");
            }
        });
    }
});
