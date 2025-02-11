
jQuery(document).ready(function($) {
    
// AJAX request to get notifications
    function getNotifications() {
        $.ajax({
            type: 'POST',
            url: axon_notification_data.ajax_url,
            data: {
                action: 'get_notifications',
                nonce: axon_notification_data.nonce
            },
            success: function(response) {
                // Update notification icon and list
                updateNotifications(response);
            }
        });
    }

    // Update notification icon and list
    function updateNotifications(data) {
        // Update notification count
        $('.notification-count').text(data.unseenCount);

        // Update notification list
        var $notificationList = $('.notifications');
        $notificationList.empty();

        $.each(data.notifications, function(index, notification) {
            var statusClass = notification.status === 'unseen' ? 'unseen' : 'seen';
            var link = notification.link ? notification.link : '#'; // Default to "#" if no link is provided

            // Create a new notification container
            var $notificationContainer = $('<li id="' + notification.id + '" class="' + statusClass + '"></li>');

            // Create a div for the notification content with a link
            var $notificationContent = $('<div class="notification-content"><a href="' + link + '" target="_blank">' + notification.content + '</a></div>');

            // Create a button for "Mark as Read" or "Mark as Unread"
            var emoji = notification.status === 'unseen' ? '🔵' : '✔️';
            var tooltipText = notification.status === 'unseen' ? 'unseen' : 'seen';
            var $markButton =  $('<span class="mark-as-read-emoji" data-tooltip="' + tooltipText +'">' + emoji + '</span>');
          
            // Append the content to the notification container
            $notificationContainer.append($notificationContent);

            // Append the button to the notification container
            $notificationContainer.append($markButton);

            // Append the container to the notification list
            $notificationList.append($notificationContainer);
            $notificationContainer.on('click', function(e) {
                var doaction = notification.status === 'unseen' ? 'mark_notification_as_seen' : '';

                if (notification.status === 'unseen') {
                  
                    e.preventDefault(); // Prevent the default link behavior for 'unseen' notifications
                    var $notificationContent = $(this).find('.notification-content');
                    markasNotification(notification.id, doaction, $notificationContent);
                    // markasNotification(notification.id, doaction);
                   
                }
            
                
                
            });

            $markButton.click(function() {
                var action = notification.status === 'unseen' ? 'mark_notification_as_seen' : 'mark_notification_as_unseen';
                markNotification(notification.id, action);
            });
        });
    }
    function  markasNotification(notificationId, doaction, $notificationContent) {
       
        $.ajax({
            type: 'POST',
            url: axon_notification_data.ajax_url,
            data: {
                action: doaction,
                nonce: axon_notification_data.nonce,
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
        
                    var $notificationContainer = $('#' + notificationId);     
                    $notificationContainer.toggleClass('unseen seen');                  
                    if (doaction === 'mark_notification_as_seen') {
                        var linkUrl = $notificationContent.find('a').attr('href');
                        window.open(linkUrl, '_blank');
                    }
                   
                } else {
                    console.log('Error updating notification status');
                }
            }
          
        });
    }

    // Function to mark a notification as seen or unseen
    function markNotification(notificationId, action) {
        $.ajax({
            type: 'POST',
            url: axon_notification_data.ajax_url,
            data: {
                action: action,
                nonce: axon_notification_data.nonce,
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    // Toggle the button text and notification status
                  
                    var $notificationContainer = $('#' + notificationId);
                  
                    $notificationContainer.toggleClass('unseen seen');
                } else {
                    console.log('Error updating notification status');
                }
            }
        });
    }

    // Toggle notification list visibility on icon click
    $('.notification-icon').click(function() {
        document.querySelector('.notification-list').style.display = "block";
    });

    // Close notification list when clicking outside
    $(document).click(function(event) {
        var $target = $(event.target);
        if (!$target.closest('.notification-icon').length && !$target.closest('.notification-list').length) {
            document.querySelector('.notification-list').style.display = "none";
        }
    });

    // Fetch notifications initially and then set interval for updates
    getNotifications();
    setInterval(getNotifications, 5000); // Update every 5 seconds
});