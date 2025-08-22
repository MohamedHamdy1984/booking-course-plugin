jQuery(document).ready(function($) {
    'use strict';
    
    var $timezoneField = $('#soob_timezone');
    var $genderField = $('#soob_gender');
    var $slotsWrapper = $('#soob_time_slots_wrapper');
    var $slotsContainer = $('#soob_time_slots_container');
    
    // Initialize checkout functionality
    function initCheckout() {
        // Auto-detect and set user timezone
        autoDetectTimezone();
        
        // Handle timezone and category changes
        $timezoneField.add($genderField).on('change', loadTimeSlots);
        
        // Handle day tab clicks
        $(document).on('click', '.soob-day-tab:not(.disabled)', function() {
            var dayKey = $(this).data('day');
            switchToDay(dayKey);
        });
        
        // Handle slot selection
        $(document).on('change', 'input[name="soob_time_slots[]"]', function() {
            updateSelectedSlots();
        });
    }
    
    // Auto-detect user's timezone and set as default
    function autoDetectTimezone() {
        if ($timezoneField.length && !$timezoneField.val()) {
            try {
                var userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                
                // Check if detected timezone exists in dropdown options
                var $matchingOption = $timezoneField.find('option[value="' + userTimezone + '"]');
                if ($matchingOption.length) {
                    $timezoneField.val(userTimezone).trigger('change');
                } else {
                    // Try to find a close match or fallback
                    var fallbackTimezone = getFallbackTimezone(userTimezone);
                    if (fallbackTimezone) {
                        $timezoneField.val(fallbackTimezone).trigger('change');
                    }
                }
            } catch (e) {
                console.log('Timezone auto-detection not supported in this browser');
            }
        }
    }
    
    // Get fallback timezone for common cases
    function getFallbackTimezone(detectedTimezone) {
        var fallbackMap = {
            // Americas
            'America/New_York': 'America/New_York',
            'America/Chicago': 'America/Chicago',
            'America/Denver': 'America/Denver',
            'America/Los_Angeles': 'America/Los_Angeles',
            'America/Sao_Paulo': 'America/Sao_Paulo',
            'America/Argentina/Buenos_Aires': 'America/Argentina/Buenos_Aires',
            
            // Europe
            'Europe/London': 'Europe/London',
            'Europe/Paris': 'Europe/Paris',
            'Europe/Berlin': 'Europe/Berlin',
            'Europe/Moscow': 'Europe/Moscow',
            'Europe/Istanbul': 'Europe/Istanbul',
            
            // Asia
            'Asia/Dubai': 'Asia/Dubai',
            'Asia/Riyadh': 'Asia/Riyadh',
            'Asia/Tehran': 'Asia/Tehran',
            'Asia/Karachi': 'Asia/Karachi',
            'Asia/Kolkata': 'Asia/Kolkata',
            'Asia/Dhaka': 'Asia/Dhaka',
            'Asia/Bangkok': 'Asia/Bangkok',
            'Asia/Shanghai': 'Asia/Shanghai',
            'Asia/Tokyo': 'Asia/Tokyo',
            'Asia/Seoul': 'Asia/Seoul',
            
            // Africa
            'Africa/Cairo': 'Africa/Cairo',
            'Africa/Johannesburg': 'Africa/Johannesburg',
            'Africa/Lagos': 'Africa/Lagos',
            'Africa/Casablanca': 'Africa/Casablanca',
            
            // Australia & Oceania
            'Australia/Sydney': 'Australia/Sydney',
            'Australia/Perth': 'Australia/Perth',
            'Pacific/Auckland': 'Pacific/Auckland'
        };
        
        // Direct match
        if (fallbackMap[detectedTimezone]) {
            return fallbackMap[detectedTimezone];
        }
        
        // Regional fallbacks
        if (detectedTimezone.startsWith('America/')) {
            return 'America/New_York'; // Default to Eastern
        } else if (detectedTimezone.startsWith('Europe/')) {
            return 'Europe/London'; // Default to GMT
        } else if (detectedTimezone.startsWith('Asia/')) {
            return 'Asia/Dubai'; // Default to Gulf
        } else if (detectedTimezone.startsWith('Africa/')) {
            return 'Africa/Cairo'; // Default to Cairo
        } else if (detectedTimezone.startsWith('Australia/') || detectedTimezone.startsWith('Pacific/')) {
            return 'Australia/Sydney'; // Default to Sydney
        }
        
        return null; // No fallback found
    }
    
    // Load available time slots
    function loadTimeSlots() {
        var timezone = $timezoneField.val();
        var gender = $genderField.val();
        
        if (!gender) {
            $slotsWrapper.html('<p>' + soob_checkout_ajax.strings.select_gender + '</p>');
            return;
        }
        if (!timezone) {
            $slotsWrapper.html('<p>' + soob_checkout_ajax.strings.select_timezone + '</p>');
            return;
        }
        
        $slotsWrapper.html('<p>' + soob_checkout_ajax.strings.loading + '</p>');
        
        $.ajax({
            url: soob_checkout_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'soob_get_checkout_slots',
                nonce: soob_checkout_ajax.nonce,
                gender: gender,
                timezone: timezone
            },
            success: function(response) {
                if (response.success) {
                    renderTimeSlots(response.data.days);
                } else {
                    $slotsWrapper.html('<p class="error">' + (response.data.message || soob_checkout_ajax.strings.error) + '</p>');
                }
            },
            error: function() {
                $slotsWrapper.html('<p class="error">' + soob_checkout_ajax.strings.error + '</p>');
            }
        });
    }
    
    // Render time slots HTML
    function renderTimeSlots(days) {
        if (!days || days.length === 0) {
            $slotsWrapper.html('<p>' + soob_checkout_ajax.strings.no_slots + '</p>');
            return;
        }
        
        var html = '<div class="soob-time-slots-container">';
        
        // Days tabs
        html += '<div class="soob-days-tabs">';
        $.each(days, function(index, day) {
            var activeClass = index === 0 ? ' active' : '';
            var disabledClass = !day.has_slots ? ' disabled' : '';
            
            html += '<button type="button" class="soob-day-tab' + activeClass + disabledClass + '" data-day="' + day.day_key + '">';
            html += '<span class="day-name">' + day.day_name + '</span>';
            html += '</button>';
        });
        html += '</div>';
        
        // Time slots content
        html += '<div class="soob-slots-content">';
        $.each(days, function(index, day) {
            var activeClass = index === 0 ? ' active' : '';
            
            html += '<div class="soob-day-slots' + activeClass + '" data-day="' + day.day_key + '">';
            
            if (day.has_slots) {
                html += '<div class="soob-slots-grid">';
                $.each(day.slots, function(slotIndex, slot) {
                    var slotData = {
                        day: day.day_name,
                        day_key: day.day_key,
                        time: slot.original,
                        display_time: slot.display,
                        timezone: slot.timezone
                    };
                    
                    html += '<label class="soob-slot-option">';
                    html += '<input type="checkbox" name="soob_time_slots[]" value="' + JSON.stringify(slotData).replace(/"/g, '&quot;') + '">';
                    html += '<span class="slot-time">' + slot.display + '</span>';
                    html += '</label>';
                });
                html += '</div>';
            } else {
                html += '<p class="soob-no-slots">No available slots for this day.</p>';
            }
            
            html += '</div>';
        });
        html += '</div>';
        
        html += '</div>';
        
        $slotsWrapper.html(html);
    }
    
    // Switch to specific day
    function switchToDay(dayKey) {
        $('.soob-day-tab').removeClass('active');
        $('.soob-day-tab[data-day="' + dayKey + '"]').addClass('active');
        
        $('.soob-day-slots').removeClass('active');
        $('.soob-day-slots[data-day="' + dayKey + '"]').addClass('active');
    }
    
    // Update selected slots for form submission
    function updateSelectedSlots() {
        var selectedSlots = [];
        $('input[name="soob_time_slots[]"]:checked').each(function() {
            try {
                var slotData = JSON.parse($(this).val());
                selectedSlots.push(slotData);
            } catch (e) {
                console.error('Error parsing slot data:', e);
            }
        });
        
        // Create or update hidden field for form submission
        var $hiddenField = $('input[name="soob_selected_slots"]');
        if ($hiddenField.length === 0) {
            $hiddenField = $('<input type="hidden" name="soob_selected_slots">');
            $slotsContainer.append($hiddenField);
        }
        
        $hiddenField.val(JSON.stringify(selectedSlots));
    }
    
    // Initialize when page loads
    initCheckout();
});