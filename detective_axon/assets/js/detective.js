jQuery(document).ready(function($) {
    $('#custom_link_form').submit(function(e) {
        // e.preventDefault(); // Prevent the form submission to allow AJAX update

        setTimeout(function() {
            alert('hehe');
            var link = $('#new_link').val();
            var new_link = '<p><strong>Custom Link: </strong>' + link + '</p>';
            $('#invite-links-section').append(new_link);
        }, 1500); // Delay the code execution by 1500 milliseconds (1.5 seconds)
    });
});
