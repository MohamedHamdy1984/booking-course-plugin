jQuery(document).ready(function($) {
    'use strict';
    
    var $timezoneField = $('#hamdy_timezone');
    var $categoryField = $('#hamdy_gender_age_group');
    var $slotsWrapper = $('#hamdy_time_slots_wrapper');
    var $slotsContainer = $('#hamdy_time_slots_container');
    
    // Initialize checkout functionality
    function initCheckout() {
        // Handle timezone and category changes
        $timezoneField.add($categoryField).on('change', loadTimeSlots);
        
        // Handle day tab clicks
        $(document).on('click', '.hamdy-day-tab:not(.disabled)', function() {
            var dayKey = $(this).data('day');
            switchToDay(dayKey);
        });
        
        // Handle slot selection
        $(document).on('change', 'input[name="hamdy_time_slots[]"]', function() {
            updateSelectedSlots();
        });
    }
    
    // Load available time slots
    function loadTimeSlots() {
        var timezone = $timezoneField.val();
        var category = $categoryField.val();
        
        if (!category) {
            $slotsWrapper.html('<p>' + hamdy_checkout_ajax.strings.select_category + '</p>');
            return;
        }
        if (!timezone) {
            $slotsWrapper.html('<p>' + hamdy_checkout_ajax.strings.select_timezone + '</p>');
            return;
        }
        
        $slotsWrapper.html('<p>' + hamdy_checkout_ajax.strings.loading + '</p>');
        
        $.ajax({
            url: hamdy_checkout_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'hamdy_get_checkout_slots',
                nonce: hamdy_checkout_ajax.nonce,
                gender_age_group: category,
                timezone: timezone
            },
            success: function(response) {
                if (response.success) {
                    renderTimeSlots(response.data.days);
                } else {
                    $slotsWrapper.html('<p class="error">' + (response.data.message || hamdy_checkout_ajax.strings.error) + '</p>');
                }
            },
            error: function() {
                $slotsWrapper.html('<p class="error">' + hamdy_checkout_ajax.strings.error + '</p>');
            }
        });
    }
    
    // Render time slots HTML
    function renderTimeSlots(days) {
        if (!days || days.length === 0) {
            $slotsWrapper.html('<p>' + hamdy_checkout_ajax.strings.no_slots + '</p>');
            return;
        }
        
        var html = '<div class="hamdy-time-slots-container">';
        
        // Days tabs
        html += '<div class="hamdy-days-tabs">';
        $.each(days, function(index, day) {
            var activeClass = index === 0 ? ' active' : '';
            var disabledClass = !day.has_slots ? ' disabled' : '';
            
            html += '<button type="button" class="hamdy-day-tab' + activeClass + disabledClass + '" data-day="' + day.day_key + '" data-date="' + day.date + '">';
            html += '<span class="day-name">' + day.day_name + '</span>';
            html += '<span class="day-date">' + day.display_date + '</span>';
            html += '</button>';
        });
        html += '</div>';
        
        // Time slots content
        html += '<div class="hamdy-slots-content">';
        $.each(days, function(index, day) {
            var activeClass = index === 0 ? ' active' : '';
            
            html += '<div class="hamdy-day-slots' + activeClass + '" data-day="' + day.day_key + '">';
            
            if (day.has_slots) {
                html += '<div class="hamdy-slots-grid">';
                $.each(day.slots, function(slotIndex, slot) {
                    var slotData = {
                        day: day.day_name,
                        date: day.date,
                        time: slot.original,
                        display_time: slot.display,
                        timezone: slot.timezone
                    };
                    
                    html += '<label class="hamdy-slot-option">';
                    html += '<input type="checkbox" name="hamdy_time_slots[]" value="' + JSON.stringify(slotData).replace(/"/g, '&quot;') + '">';
                    html += '<span class="slot-time">' + slot.display + '</span>';
                    html += '</label>';
                });
                html += '</div>';
            } else {
                html += '<p class="hamdy-no-slots">No available slots for this day.</p>';
            }
            
            html += '</div>';
        });
        html += '</div>';
        
        html += '</div>';
        
        $slotsWrapper.html(html);
    }
    
    // Switch to specific day
    function switchToDay(dayKey) {
        $('.hamdy-day-tab').removeClass('active');
        $('.hamdy-day-tab[data-day="' + dayKey + '"]').addClass('active');
        
        $('.hamdy-day-slots').removeClass('active');
        $('.hamdy-day-slots[data-day="' + dayKey + '"]').addClass('active');
    }
    
    // Update selected slots for form submission
    function updateSelectedSlots() {
        var selectedSlots = [];
        $('input[name="hamdy_time_slots[]"]:checked').each(function() {
            try {
                var slotData = JSON.parse($(this).val());
                selectedSlots.push(slotData);
            } catch (e) {
                console.error('Error parsing slot data:', e);
            }
        });
        
        // Create or update hidden field for form submission
        var $hiddenField = $('input[name="hamdy_selected_slots"]');
        if ($hiddenField.length === 0) {
            $hiddenField = $('<input type="hidden" name="hamdy_selected_slots">');
            $slotsContainer.append($hiddenField);
        }
        
        $hiddenField.val(JSON.stringify(selectedSlots));
    }
    
    // Initialize when page loads
    initCheckout();
});