jQuery(document).ready(function($) {
    // Handle provider deletion
    $('.soob-delete-provider').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this provider?')) {
            return;
        }
        
        var providerId = $(this).data('provider-id');
        var $button = $(this);
        
        $button.prop('disabled', true).text('Deleting...');
        
        $.ajax({
            url: soob_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'soob_delete_provider',
                provider_id: providerId,
                nonce: soob_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to delete provider'));
                    $button.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                alert('Error: Failed to delete provider');
                $button.prop('disabled', false).text('Delete');
            }
        });
    });
    
    // Handle timezone change for availability display
    $('#provider_timezone').on('change', function() {
        var selectedTimezone = $(this).val();
        if (selectedTimezone) {
            // Update availability grid display based on timezone
            // This would convert the displayed times to the selected timezone
            updateAvailabilityDisplay(selectedTimezone);
        }
    });
    
    // Enhanced auto-fetch timezone with Cairo-specific fixes
    // This handles both new providers and existing providers with missing timezone
    function autoFetchProviderTimezone() {
        var $timezoneField = $('#provider_timezone');
        var currentValue = $timezoneField.val();
        
        console.log('Starting timezone auto-detection...');
        console.log('Current timezone field value:', currentValue);
        
        // Enhanced logic: Also override if current value is UTC but browser detects something more specific
        var shouldDetect = false;
        
        if (!currentValue || currentValue === '') {
            shouldDetect = true;
            console.log('Field is empty, will auto-detect');
        } else if (currentValue === 'UTC') {
            // If current value is UTC but browser can detect a more specific timezone, override it
            if (typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
                try {
                    var browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    if (browserTimezone && browserTimezone !== 'UTC') {
                        shouldDetect = true;
                        console.log('Current value is UTC but browser detected more specific timezone:', browserTimezone);
                    }
                } catch (e) {
                    console.log('Could not detect browser timezone for UTC override check');
                }
            }
        }
        
        if (shouldDetect) {
            var detectedTimezone = null;
            var detectedOffset = null;
            
            // First try: Browser timezone detection using Intl API
            if (typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
                try {
                    detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    console.log('Browser detected timezone:', detectedTimezone);
                    
                    // Get the current offset for this timezone
                    var now = new Date();
                    var offsetMinutes = -now.getTimezoneOffset();
                    detectedOffset = offsetMinutes / 60;
                    console.log('Current timezone offset (hours):', detectedOffset);
                    console.log('Expected for Cairo (Africa/Cairo): +3 hours');
                    
                } catch (e) {
                    console.warn('Browser timezone detection failed:', e.message);
                }
            } else {
                console.warn('Intl API not available');
            }
            
            // Second try: JavaScript Date object timezone offset (enhanced for Cairo)
            if (!detectedTimezone) {
                try {
                    var offset = -new Date().getTimezoneOffset() / 60;
                    console.log('Fallback: Detected offset from Date object:', offset);
                    
                    // Enhanced mapping for common Middle East/Africa timezones
                    var offsetToTimezone = {
                        '3': 'Africa/Cairo',      // Egypt Standard Time (UTC+3) - CAIRO FIX
                        '2': 'Europe/Berlin',     // Central European Time
                        '1': 'Europe/London',     // British Summer Time / Central European Time
                        '0': 'UTC',               // UTC
                        '-5': 'America/New_York', // Eastern Time
                        '-8': 'America/Los_Angeles' // Pacific Time
                    };
                    
                    if (offsetToTimezone[offset.toString()]) {
                        detectedTimezone = offsetToTimezone[offset.toString()];
                        console.log('Mapped offset ' + offset + ' to timezone:', detectedTimezone);
                    } else {
                        console.log('No mapping found for offset:', offset, 'using UTC');
                        detectedTimezone = 'UTC';
                    }
                } catch (e) {
                    console.warn('Offset timezone detection failed:', e.message);
                }
            }
            
            // Final fallback: UTC
            if (!detectedTimezone) {
                detectedTimezone = 'UTC';
                console.log('Using final fallback timezone: UTC');
            }
            
            // Debug: Show all available options in dropdown
            console.log('Available timezone options in dropdown:');
            $timezoneField.find('option').each(function(index, option) {
                if ($(option).val().includes('Cairo') || $(option).val().includes('Africa') || $(option).val() === detectedTimezone) {
                    console.log('  - ' + $(option).val() + ' (' + $(option).text() + ')');
                }
            });
            
            // Validate that the detected timezone exists in the dropdown options
            var $option = $timezoneField.find('option[value="' + detectedTimezone + '"]');
            if ($option.length > 0) {
                $timezoneField.val(detectedTimezone);
                console.log('✓ Successfully set provider timezone to:', detectedTimezone);
                
                // Trigger change event to update availability display if needed
                $timezoneField.trigger('change');
                
                // Show success message to user
                showTimezoneDetectionMessage('success', 'Timezone auto-detected: ' + detectedTimezone);
            } else {
                console.warn('✗ Detected timezone "' + detectedTimezone + '" not found in dropdown options');
                
                // Try to find Cairo specifically as a fallback for Middle East users
                var $cairoOption = $timezoneField.find('option[value*="Cairo"], option[value*="africa/cairo"], option[value*="Africa/Cairo"]');
                if ($cairoOption.length > 0) {
                    var cairoValue = $cairoOption.first().val();
                    $timezoneField.val(cairoValue);
                    console.log('✓ Using Cairo timezone fallback:', cairoValue);
                    showTimezoneDetectionMessage('success', 'Using Cairo timezone: ' + cairoValue);
                } else {
                    // Final fallback to UTC
                    $timezoneField.val('UTC');
                    console.warn('✗ No suitable timezone found. Using UTC fallback.');
                    showTimezoneDetectionMessage('warning', 'Could not detect your timezone. Please select manually.');
                }
            }
        } else {
            console.log('Provider timezone already set and not overriding:', currentValue);
            
            // Still show debug info even when not overriding
            if (typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
                try {
                    var browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    var offset = -new Date().getTimezoneOffset() / 60;
                    console.log('For reference - Browser detected:', browserTimezone, 'with offset UTC' + (offset >= 0 ? '+' : '') + offset);
                } catch (e) {
                    console.log('Could not get browser timezone for reference');
                }
            }
        }
    }
    
    // Show timezone detection message to user
    function showTimezoneDetectionMessage(type, message) {
        var messageClass = 'notice-' + type;
        var $message = $('<div class="notice ' + messageClass + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($message);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $message.fadeOut();
        }, 5000);
    }
    
    // Execute auto-fetch on page load
    autoFetchProviderTimezone();
    
    // Select all/none functionality for availability
    $('.soob-day-column').each(function() {
        var $dayColumn = $(this);
        var dayName = $dayColumn.find('h4').text();
        
        // Add select all/none buttons
        $dayColumn.find('h4').after(
            '<div class="soob-day-controls" style="margin-bottom: 10px; text-align: center;">' +
            '<button type="button" class="button button-small select-all" style="margin-right: 5px;">All</button>' +
            '<button type="button" class="button button-small select-none">None</button>' +
            '</div>'
        );
    });
    
    // Handle select all button
    $(document).on('click', '.select-all', function(e) {
        e.preventDefault();
        $(this).closest('.soob-day-column').find('input[type="checkbox"]').prop('checked', true);
    });
    
    // Handle select none button
    $(document).on('click', '.select-none', function(e) {
        e.preventDefault();
        $(this).closest('.soob-day-column').find('input[type="checkbox"]').prop('checked', false);
    });
    
    // Enhanced form validation
    $('.soob-provider-form').on('submit', function(e) {
        var name = $('#provider_name').val().trim();
        var gender = $('#provider_gender').val();
        var hasAvailability = $(this).find('input[name^="availability"]:checked').length > 0;
        
        if (!name) {
            e.preventDefault();
            showNotice('error', 'Please enter provider name.');
            $('#provider_name').focus();
            return false;
        }
        
        if (!gender) {
            e.preventDefault();
            showNotice('error', 'Please select provider gender.');
            $('#provider_gender').focus();
            return false;
        }
        
        if (!hasAvailability) {
            e.preventDefault();
            showNotice('error', 'Please select at least one availability time slot.');
            return false;
        }
    });
    
    // Availability grid - select all day functionality
    $('.soob-day-column h4').on('click', function() {
        var $column = $(this).parent();
        var $checkboxes = $column.find('input[type="checkbox"]');
        var allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
        
        $checkboxes.prop('checked', !allChecked);
    });
    
    // Real-time availability preview
    $('.soob-availability-grid input[type="checkbox"]').on('change', function() {
        updateAvailabilityPreview();
    });
    
    function updateAvailabilityPreview() {
        var selectedSlots = {};
        
        $('.soob-availability-grid input[type="checkbox"]:checked').each(function() {
            var name = $(this).attr('name');
            var day = name.match(/\[([^\]]+)\]/)[1];
            var time = $(this).val();
            
            if (!selectedSlots[day]) {
                selectedSlots[day] = [];
            }
            selectedSlots[day].push(time);
        });
        
        // Update preview display if element exists
        if ($('.soob-availability-preview').length) {
            $('.soob-availability-preview').html(JSON.stringify(selectedSlots, null, 2));
        }
    }
    
    // Utility function to show notices
    function showNotice(type, message) {
        var noticeClass = 'soob-notice soob-notice-' + type;
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
        $('.soob-availability-grid').attr('data-timezone', timezone);
        
        // Add timezone indicator
        var $indicator = $('.timezone-indicator');
        if ($indicator.length === 0) {
            $('.soob-availability-grid').prepend(
                '<div class="timezone-indicator" style="background: #e7f3ff; padding: 8px; margin-bottom: 15px; border-radius: 4px; font-size: 12px;">' +
                '<strong>Timezone:</strong> <span class="current-timezone">' + timezone + '</span>' +
                '</div>'
            );
        } else {
            $indicator.find('.current-timezone').text(timezone);
        }
    }
});