jQuery(document).ready(function($) {
    var modal = $('#wc-order-notification-modal');
    var content = $('#wc-order-notification-content');
    var ignoreButton = $('#wc-order-notification-ignore');
    var closeButton = $('#wc-order-notification-close');

    // Function to build the modal content from localized data
    function buildModalContent(data) {
        if (!data || data.length === 0) {
            return;
        }

        var html = '';

        data.forEach(function(item) {
            html += '<div class="border border-gray-300 rounded p-3">';
            html += '<p class="font-semibold mb-2">Product: ' + item.product_name + '</p>';
            html += '<ul class="list-disc list-inside space-y-1">';

            item.orders.forEach(function(order) {
                html += '<li>';
                html += '<a href="' + order.order_url + '" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">';
                html += 'Order #' + order.order_id + ' (' + order.status + ')';
                html += '</a>';
                html += '</li>';
            });

            html += '</ul>';
            html += '</div>';
        });

        content.html(html);
    }

    // Show modal if duplicate data exists
    if (typeof wcOrderNotificationData !== 'undefined' && wcOrderNotificationData.length > 0) {
        buildModalContent(wcOrderNotificationData);
        modal.removeClass('hidden');

        // Prevent form submission while modal is visible
        $('form.checkout').on('submit', function(e) {
            if (!modal.hasClass('hidden')) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Ignore button click handler
    ignoreButton.on('click', function() {
        // Add a hidden input to the checkout form to indicate ignoring duplicates
        if ($('input[name="wc_order_notification_ignore"]').length === 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'wc_order_notification_ignore',
                value: 'yes'
            }).appendTo('form.checkout');
        }

        modal.addClass('hidden');
        // Allow form submission now
        $('form.checkout').off('submit');
    });

    // Close button click handler (same as ignore)
    closeButton.on('click', function() {
        ignoreButton.trigger('click');
    });
});
