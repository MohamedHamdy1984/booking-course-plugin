jQuery(document).ready(function($) {
    // Handle teacher deletion
    $('.hamdy-delete-teacher').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this teacher?')) {
            return;
        }
        
        var teacherId = $(this).data('teacher-id');
        var $button = $(this);
        
        $button.prop('disabled', true).text('Deleting...');
        
        $.ajax({
            url: hamdy_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'hamdy_delete_teacher',
                teacher_id: teacherId,
                nonce: hamdy_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to delete teacher'));
                    $button.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                alert('Error: Failed to delete teacher');
                $button.prop('disabled', false).text('Delete');
            }
        });
    });
    
    // Handle timezone change for availability display
    $('#teacher_timezone').on('change', function() {
        var selectedTimezone = $(this).val();
        if (selectedTimezone) {
            // Update availability grid display based on timezone
            // This would convert the displayed times to the selected timezone
            updateAvailabilityDisplay(selectedTimezone);
        }
    });
    
    // Auto-detect timezone on page load for new teachers
    if ($('#teacher_timezone').val() === '' && typeof Intl !== 'undefined') {
        try {
            var detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            $('#teacher_timezone').val(detectedTimezone);
        } catch (e) {
            // Fallback to UTC if detection fails
            $('#teacher_timezone').val('UTC');
        }
    }
    
    // Select all/none functionality for availability
    $('.hamdy-day-column').each(function() {
        var $dayColumn = $(this);
        var dayName = $dayColumn.find('h4').text();
        
        // Add select all/none buttons
        $dayColumn.find('h4').after(
            '<div class="hamdy-day-controls" style="margin-bottom: 10px; text-align: center;">' +
            '<button type="button" class="button button-small select-all" style="margin-right: 5px;">All</button>' +
            '<button type="button" class="button button-small select-none">None</button>' +
            '</div>'
        );
    });
    
    // Handle select all button
    $(document).on('click', '.select-all', function(e) {
        e.preventDefault();
        $(this).closest('.hamdy-day-column').find('input[type="checkbox"]').prop('checked', true);
    });
    
    // Handle select none button
    $(document).on('click', '.select-none', function(e) {
        e.preventDefault();
        $(this).closest('.hamdy-day-column').find('input[type="checkbox"]').prop('checked', false);
    });
    
    // Enhanced form validation
    $('.hamdy-teacher-form').on('submit', function(e) {
        var name = $('#teacher_name').val().trim();
        var gender = $('#teacher_gender').val();
        var hasAvailability = $(this).find('input[name^="availability"]:checked').length > 0;
        
        if (!name) {
            e.preventDefault();
            showNotice('error', 'Please enter teacher name.');
            $('#teacher_name').focus();
            return false;
        }
        
        if (!gender) {
            e.preventDefault();
            showNotice('error', 'Please select teacher gender.');
            $('#teacher_gender').focus();
            return false;
        }
        
        if (!hasAvailability) {
            e.preventDefault();
            showNotice('error', 'Please select at least one availability time slot.');
            return false;
        }
    });
    
    // Availability grid - select all day functionality
    $('.hamdy-day-column h4').on('click', function() {
        var $column = $(this).parent();
        var $checkboxes = $column.find('input[type="checkbox"]');
        var allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
        
        $checkboxes.prop('checked', !allChecked);
    });
    
    // Real-time availability preview
    $('.hamdy-availability-grid input[type="checkbox"]').on('change', function() {
        updateAvailabilityPreview();
    });
    
    function updateAvailabilityPreview() {
        var selectedSlots = {};
        
        $('.hamdy-availability-grid input[type="checkbox"]:checked').each(function() {
            var name = $(this).attr('name');
            var day = name.match(/\[([^\]]+)\]/)[1];
            var time = $(this).val();
            
            if (!selectedSlots[day]) {
                selectedSlots[day] = [];
            }
            selectedSlots[day].push(time);
        });
        
        // Update preview display if element exists
        if ($('.hamdy-availability-preview').length) {
            $('.hamdy-availability-preview').html(JSON.stringify(selectedSlots, null, 2));
        }
    }
    
    // Utility function to show notices
    function showNotice(type, message) {
        var noticeClass = 'hamdy-notice hamdy-notice-' + type;
        var $notice = $('<div class="' + noticeClass + '">' + message + '</div>');
        
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    function updateAvailabilityDisplay(timezone) {
        // This function would handle timezone conversion for display
        // For now, we'll just add a visual indicator
        $('.hamdy-availability-grid').attr('data-timezone', timezone);
        
        // Add timezone indicator
        var $indicator = $('.timezone-indicator');
        if ($indicator.length === 0) {
            $('.hamdy-availability-grid').prepend(
                '<div class="timezone-indicator" style="background: #e7f3ff; padding: 8px; margin-bottom: 15px; border-radius: 4px; font-size: 12px;">' +
                '<strong>Timezone:</strong> <span class="current-timezone">' + timezone + '</span>' +
                '</div>'
            );
        } else {
            $indicator.find('.current-timezone').text(timezone);
        }
    }
});