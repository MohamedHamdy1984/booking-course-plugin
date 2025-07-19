/**
 * Hamdy Plugin Public JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Time slots selection functionality
    var selectedSlots = [];
    
    // Handle category and timezone changes
    $('#hamdy_gender_age_group, #hamdy_timezone').on('change', function() {
        loadAvailableSlots();
    });
    
    // Day tab switching
    $(document).on('click', '.hamdy-day-tab', function(e) {
        e.preventDefault();
        
        if ($(this).hasClass('disabled')) {
            return;
        }
        
        var day = $(this).data('day');
        
        // Update active tab
        $('.hamdy-day-tab').removeClass('active');
        $(this).addClass('active');
        
        // Show corresponding slots
        $('.hamdy-day-slots').removeClass('active');
        $('.hamdy-day-slots[data-day="' + day + '"]').addClass('active');
    });
    
    // Time slot selection
    $(document).on('change', 'input[name="hamdy_time_slots[]"]', function() {
        updateSelectedSlots();
    });
    
    // Load available slots based on selection
    function loadAvailableSlots() {
        var genderAgeGroup = $('#hamdy_gender_age_group').val();
        var timezone = $('#hamdy_timezone').val();
        
        if (!genderAgeGroup) {
            $('#hamdy_time_slots_wrapper').html('<p class="hamdy-error">' + hamdy_ajax.strings.select_category + '</p>');
            return;
        }
        
        if (!timezone) {
            $('#hamdy_time_slots_wrapper').html('<p class="hamdy-error">' + hamdy_ajax.strings.select_timezone + '</p>');
            return;
        }
        
        var $wrapper = $('#hamdy_time_slots_wrapper');
        $wrapper.addClass('hamdy-loading').html('<p>' + hamdy_ajax.strings.loading + '</p>');
        
        $.ajax({
            url: hamdy_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'hamdy_get_checkout_slots',
                gender_age_group: genderAgeGroup,
                timezone: timezone,
                nonce: hamdy_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayTimeSlots(response.data.days);
                } else {
                    $wrapper.html('<p class="hamdy-error">' + (response.data.message || hamdy_ajax.strings.error) + '</p>');
                }
            },
            error: function() {
                $wrapper.html('<p class="hamdy-error">' + hamdy_ajax.strings.error + '</p>');
            },
            complete: function() {
                $wrapper.removeClass('hamdy-loading');
            }
        });
    }
    
    // Display time slots
    function displayTimeSlots(daysData) {
        if (!daysData || daysData.length === 0) {
            $('#hamdy_time_slots_wrapper').html('<p class="hamdy-no-slots">' + hamdy_ajax.strings.no_slots + '</p>');
            return;
        }
        
        var html = '<div class="hamdy-time-slots-container">';
        
        // Days tabs
        html += '<div class="hamdy-days-tabs">';
        $.each(daysData, function(index, day) {
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
        $.each(daysData, function(index, day) {
            var activeClass = index === 0 ? ' active' : '';
            
            html += '<div class="hamdy-day-slots' + activeClass + '" data-day="' + day.day_key + '">';
            
            if (day.has_slots && day.slots.length > 0) {
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
                    html += '<input type="checkbox" name="hamdy_time_slots[]" value="' + escapeHtml(JSON.stringify(slotData)) + '">';
                    html += '<span class="slot-time">' + slot.display + '</span>';
                    html += '</label>';
                });
                html += '</div>';
            } else {
                html += '<p class="hamdy-no-slots">' + hamdy_ajax.strings.no_slots + '</p>';
            }
            
            html += '</div>';
        });
        html += '</div>';
        
        html += '</div>';
        
        $('#hamdy_time_slots_wrapper').html(html);
        
        // Initialize first tab
        $('.hamdy-day-tab:first:not(.disabled)').trigger('click');
    }
    
    // Update selected slots
    function updateSelectedSlots() {
        selectedSlots = [];
        
        $('input[name="hamdy_time_slots[]"]:checked').each(function() {
            try {
                var slotData = JSON.parse($(this).val());
                selectedSlots.push(slotData);
            } catch (e) {
                console.error('Error parsing slot data:', e);
            }
        });
        
        // Update hidden field for form submission
        updateHiddenField();
        
        // Update selection summary
        updateSelectionSummary();
    }
    
    // Update hidden field with selected slots
    function updateHiddenField() {
        var existingField = $('input[name="hamdy_selected_slots"]');
        if (existingField.length) {
            existingField.remove();
        }
        
        if (selectedSlots.length > 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'hamdy_selected_slots',
                value: JSON.stringify(selectedSlots)
            }).appendTo('#hamdy_booking_fields');
        }
    }
    
    // Update selection summary
    function updateSelectionSummary() {
        var $summary = $('.hamdy-selection-summary');
        
        if (selectedSlots.length === 0) {
            $summary.hide();
            return;
        }
        
        if ($summary.length === 0) {
            $summary = $('<div class="hamdy-selection-summary"></div>');
            $('#hamdy_time_slots_container').append($summary);
        }
        
        var html = '<h4>Selected Time Slots:</h4><ul>';
        $.each(selectedSlots, function(index, slot) {
            html += '<li>' + slot.day + ', ' + slot.display_time + '</li>';
        });
        html += '</ul>';
        
        $summary.html(html).show();
    }
    
    // Booking button functionality
    $('.hamdy-booking-button').on('click', function(e) {
        var $button = $(this);
        
        // Add loading state
        $button.addClass('hamdy-loading').prop('disabled', true);
        
        // Remove loading state after navigation
        setTimeout(function() {
            $button.removeClass('hamdy-loading').prop('disabled', false);
        }, 2000);
    });
    
    // Form validation before checkout submission
    $('form.checkout').on('submit', function(e) {
        // Check if booking fields are present
        if ($('#hamdy_booking_fields').length === 0) {
            return true; // No booking fields, proceed normally
        }
        
        var genderAgeGroup = $('#hamdy_gender_age_group').val();
        var timezone = $('#hamdy_timezone').val();
        var selectedSlotsCount = $('input[name="hamdy_time_slots[]"]:checked').length;
        
        if (!genderAgeGroup) {
            showError(hamdy_ajax.strings.select_category);
            $('#hamdy_gender_age_group').focus();
            return false;
        }
        
        if (!timezone) {
            showError(hamdy_ajax.strings.select_timezone);
            $('#hamdy_timezone').focus();
            return false;
        }
        
        if (selectedSlotsCount === 0) {
            showError(hamdy_ajax.strings.select_slot);
            $('.hamdy-time-slots-container').get(0).scrollIntoView();
            return false;
        }
        
        return true;
    });
    
    // Utility functions
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function showError(message) {
        // Remove existing errors
        $('.hamdy-error').remove();
        
        // Add new error
        var $error = $('<div class="hamdy-error">' + message + '</div>');
        $('#hamdy_booking_fields').prepend($error);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $error.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    function showSuccess(message) {
        // Remove existing messages
        $('.hamdy-success').remove();
        
        // Add success message
        var $success = $('<div class="hamdy-success">' + message + '</div>');
        $('#hamdy_booking_fields').prepend($success);
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            $success.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Accessibility improvements
    $('.hamdy-slot-option input[type="checkbox"]').on('focus', function() {
        $(this).closest('.hamdy-slot-option').addClass('focused');
    }).on('blur', function() {
        $(this).closest('.hamdy-slot-option').removeClass('focused');
    });
    
    // Keyboard navigation for day tabs
    $('.hamdy-days-tabs').on('keydown', '.hamdy-day-tab', function(e) {
        var $tabs = $('.hamdy-day-tab:not(.disabled)');
        var currentIndex = $tabs.index(this);
        var $target;
        
        switch(e.which) {
            case 37: // Left arrow
                $target = $tabs.eq(currentIndex - 1);
                break;
            case 39: // Right arrow
                $target = $tabs.eq(currentIndex + 1);
                break;
            default:
                return;
        }
        
        if ($target.length) {
            e.preventDefault();
            $target.focus().trigger('click');
        }
    });
    
    // Initialize on page load
    if ($('#hamdy_booking_fields').length > 0) {
        // Auto-load slots if both fields are pre-selected
        var genderAgeGroup = $('#hamdy_gender_age_group').val();
        var timezone = $('#hamdy_timezone').val();
        
        if (genderAgeGroup && timezone) {
            loadAvailableSlots();
        }
    }
    
    // Handle browser back/forward buttons
    $(window).on('popstate', function() {
        // Reload slots if needed
        if ($('#hamdy_booking_fields').length > 0) {
            loadAvailableSlots();
        }
    });
});