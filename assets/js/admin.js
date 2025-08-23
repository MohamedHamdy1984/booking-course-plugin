/**
 * Soob Plugin Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Utility: simple debounce
    function debounce(fn, wait) {
        var timer;
        return function() {
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() { fn.apply(ctx, args); }, wait);
        };
    }

    // i18n helper: read from soob_admin_ajax.strings with fallback
    function t(key, fallback) {
        try {
            if (window.soob_admin_ajax && window.soob_admin_ajax.strings && window.soob_admin_ajax.strings[key]) {
                return window.soob_admin_ajax.strings[key];
            }
        } catch (e) {}
        return fallback;
    }
    
    // Schedule overview - time slot tooltips
    $('.soob-time-slot').on('mouseenter', function() {
        var hour = $(this).data('hour');
        var day = $(this).closest('.soob-day-row').data('day');
        var available = $(this).hasClass('available');
        
        var tooltip = available ? 
            'Available at ' + hour + ':00 on ' + day : 
            'Not available at ' + hour + ':00 on ' + day;
        
        $(this).attr('title', tooltip);
    });
    
    // Auto-save functionality for forms
    var autoSaveTimeout;
    $('.soob-provider-form input, .soob-provider-form select, .soob-provider-form textarea').on('input change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            // Auto-save logic can be implemented here
            console.log('Auto-save triggered');
        }, 2000);
    });
    
    // Bulk actions for tables
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        var checked = $(this).prop('checked');
        $('tbody input[type="checkbox"]').prop('checked', checked);
        updateBulkActions();
    });
    
    $('tbody input[type="checkbox"]').on('change', function() {
        updateBulkActions();
    });
    
    function updateBulkActions() {
        var checkedCount = $('tbody input[type="checkbox"]:checked').length;
        $('.bulkactions').toggle(checkedCount > 0);
    }
    
    // Search functionality (debounced)
    $('#provider-search-input').on('keyup', debounce(function() {
        var value = $(this).val().toLowerCase();
        $('.wp-list-table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    }, 250));
    
    // Statistics refresh
    $('.soob-stat-card').on('click', function() {
        refreshStatistics();
    });
    
    function refreshStatistics() {
        $.ajax({
            url: soob_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'soob_refresh_stats',
                nonce: soob_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.soob-stat-number').each(function(index) {
                        $(this).text(response.data.stats[index]);
                    });
                }
            }
        });
    }
    
    // Media uploader for provider photos
    $('.soob-upload-photo').on('click', function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: t('select_photo', 'Select Provider Photo'),
            button: {
                text: t('use_photo', 'Use this photo')
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#provider_photo').val(attachment.url);
            $('.soob-current-photo img').attr('src', attachment.url);
        });
        
        mediaUploader.open();
    });
    
    // Sortable functionality for tables
    if ($.fn.sortable) {
        $('.wp-list-table tbody').sortable({
            handle: '.soob-sort-handle',
            update: function(event, ui) {
                var order = $(this).sortable('toArray', {attribute: 'data-id'});
                
                $.ajax({
                    url: soob_admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'soob_update_order',
                        order: order,
                        nonce: soob_admin_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice('success', t('saved', 'Order updated successfully'));
                        }
                    }
                });
            }
        });
    }
    
    // General form field dependencies can be added here if needed
    
    // Utility function to show notices
    function showNotice(type, message) {
        var noticeClass = 'soob-notice soob-notice-' + type;
        var $notice = $('<div class="' + noticeClass + '" role="alert" aria-live="polite">' + message + '</div>');
        
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Initialize tooltips if available
    if ($.fn.tooltip) {
        $('[data-tooltip]').tooltip();
    }
    
    // Keyboard shortcuts
    $(document).on('keydown.soob-global', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            $('.soob-provider-form').submit();
        }
        
        // Escape to close modals
        if (e.which === 27) {
            $('.soob-modal').hide();
        }
    });
    
    // Initialize page
    initializePage();
    
    function initializePage() {
        // Set focus on first input
        $('.soob-provider-form input:first').focus();
        
        // Initialize any third-party plugins
        if ($.fn.datepicker) {
            $('.soob-date-picker').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        }
        
        // Load initial data if needed
        if (typeof loadInitialData === 'function') {
            loadInitialData();
        }
    }
    
    // Booking Management Functionality
    
    // Delete booking confirmation and AJAX
    $('.soob-delete-booking').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var bookingId = $button.data('booking-id');
        var $row = $button.closest('tr');
        
        // Show confirmation dialog
        if (confirm(soob_admin_ajax.strings.confirm_delete)) {
            // Add loading state
            $row.addClass('deleting');
            $button.prop('disabled', true).text(soob_admin_ajax.strings.loading);
            
            // Send AJAX request
            $.ajax({
                url: soob_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'soob_delete_booking',
                    booking_id: bookingId,
                    nonce: soob_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove row with animation
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Show success message
                            showNotice('success', response.data);
                            
                            // Check if table is empty
                            if ($('.soob-bookings-table tbody tr').length === 0) {
                                $('.soob-bookings-list').html('<p>' + t('no_bookings', 'No bookings found.') + '</p>');
                            }
                        });
                    } else {
                        // Remove loading state and show error
                        $row.removeClass('deleting');
                        $button.prop('disabled', false).text(t('delete', 'Delete'));
                        showNotice('error', response.data || soob_admin_ajax.strings.error);
                    }
                },
                error: function() {
                    // Remove loading state and show error
                    $row.removeClass('deleting');
                    $button.prop('disabled', false).text(t('delete', 'Delete'));
                    showNotice('error', soob_admin_ajax.strings.error);
                }
            });
        }
    });
    
    // Tab switching functionality for bookings - removed to allow natural navigation
    // The tabs will work through normal page navigation with URL parameters
    
    // Highlight expiring bookings on page load
    highlightExpiringBookings();
    
    function highlightExpiringBookings() {
        $('.soob-bookings-table tr.expiring').each(function() {
            var $row = $(this);
            
            // Add a subtle animation to draw attention
            setTimeout(function() {
                $row.addClass('highlight-animation');
            }, 500);
        });
    }
    
    // Enhanced search functionality for bookings (debounced)
    $('#booking-search-input').on('keyup', debounce(function() {
        var value = $(this).val().toLowerCase();
        $('.soob-bookings-table tbody tr').filter(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(value) > -1);
        });
    }, 250));
    
    // Bulk actions for bookings (future enhancement)
    $('.soob-bookings-table #cb-select-all-1').on('change', function() {
        var checked = $(this).prop('checked');
        $('.soob-bookings-table tbody input[type="checkbox"]').prop('checked', checked);
    });
    
    // Add confirmation dialog for critical actions
    function showConfirmDialog(title, message, onConfirm, onCancel) {
        var $overlay = $('<div class="soob-dialog-overlay"></div>');
        var $dialog = $('<div class="soob-confirm-dialog"></div>');
        
        $dialog.html(
            '<h3>' + title + '</h3>' +
            '<p>' + message + '</p>' +
            '<div class="button-group">' +
                '<button class="button button-secondary soob-cancel-btn">' + t('cancel', 'Cancel') + '</button>' +
                '<button class="button button-primary soob-confirm-btn">' + t('confirm', 'Confirm') + '</button>' +
            '</div>'
        );
        
        $('body').append($overlay).append($dialog);
        
        // Handle confirm
        $dialog.find('.soob-confirm-btn').on('click', function() {
            $overlay.remove();
            $dialog.remove();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });
        
        // Handle cancel
        $dialog.find('.soob-cancel-btn, .soob-dialog-overlay').on('click', function() {
            $overlay.remove();
            $dialog.remove();
            if (typeof onCancel === 'function') {
                onCancel();
            }
        });
        
        // Close on escape
        $(document).on('keydown.soob-dialog', function(e) {
            if (e.which === 27) {
                $overlay.remove();
                $dialog.remove();
                $(document).off('keydown.soob-dialog');
                if (typeof onCancel === 'function') {
                    onCancel();
                }
            }
        });
    }
    
    // Enhanced table row hover effects
    $('.soob-bookings-table tbody tr').hover(
        function() {
            $(this).addClass('hover-highlight');
        },
        function() {
            $(this).removeClass('hover-highlight');
        }
    );
    
    // Auto-refresh functionality (optional)
    var autoRefreshInterval;
    
    function startAutoRefresh() {
        autoRefreshInterval = setInterval(function() {
            // Only refresh if user is on bookings page and not interacting
            if (window.location.href.indexOf('soob-bookings') > -1 &&
                !$('body').hasClass('user-interacting')) {
                
                // Subtle refresh without full page reload
                refreshBookingsTable();
            }
        }, 30000); // Refresh every 30 seconds
    }
    
    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
    }
    
    function refreshBookingsTable() {
        // This would be implemented with AJAX to refresh just the table
        // For now, we'll skip this to avoid complexity
    }
    
    // Track user interaction
    $(document).on('mousedown keydown', function() {
        $('body').addClass('user-interacting');
        setTimeout(function() {
            $('body').removeClass('user-interacting');
        }, 5000);
    });
    
    // Initialize auto-refresh if on bookings page
    if (window.location.href.indexOf('soob-bookings') > -1) {
        startAutoRefresh();
    }
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        // Unbind namespaced keyboard shortcuts
        $(document).off('keydown.soob-global keydown.soob-editor');
        stopAutoRefresh();
    });
    
    // Booking Editor Functionality
    
    // Add new session functionality
    $(document).on('click', '#soob-add-session', function(e) {
        e.preventDefault();
        
        var $container = $('#soob-sessions-container');
        var $template = $('#soob-session-template');
        var sessionCount = $('.soob-session-row').length;
        var newIndex = sessionCount;
        
        // Remove "no sessions" message if it exists
        $('.soob-no-sessions').remove();
        
        // Get template HTML and replace placeholder
        var templateHtml = $template.html();
        templateHtml = templateHtml.replace(/\{\{INDEX\}\}/g, newIndex);
        
        // Create new session row
        var $newSession = $(templateHtml);
        $newSession.hide();
        
        // Add to container
        $container.append($newSession);
        
        // Show with animation
        $newSession.slideDown(300);
        
        // Focus on first field
        $newSession.find('.soob-session-day').focus();
    });
    
    // Remove session functionality
    $(document).on('click', '.soob-remove-session', function(e) {
        e.preventDefault();
        
        var $sessionRow = $(this).closest('.soob-session-row');
        var $container = $('#soob-sessions-container');
        
        // Add removing class for animation
        $sessionRow.addClass('soob-session-removing');
        
        // Remove after animation
        setTimeout(function() {
            $sessionRow.remove();
            
            // Re-index remaining sessions
            reindexSessions();
            
            // Show "no sessions" message if no sessions left
            if ($('.soob-session-row').length === 0) {
                $container.append('<p class="soob-no-sessions">' + t('no_sessions', 'No sessions scheduled yet.') + '</p>');
            }
        }, 300);
    });
    
    // Re-index sessions after removal
    function reindexSessions() {
        $('.soob-session-row').each(function(index) {
            var $row = $(this);
            $row.attr('data-index', index);
            
            // Update field names
            $row.find('select, input').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                if (name) {
                    // Replace the index in the name attribute
                    var newName = name.replace(/sessions\[\d+\]/, 'sessions[' + index + ']');
                    $field.attr('name', newName);
                }
            });
        });
    }
    
    // Form validation for booking editor
    $('.soob-booking-edit-form').on('submit', function(e) {
        var hasErrors = false;
        var $form = $(this);
        
        // Clear previous errors
        $('.soob-field-error').removeClass('soob-field-error');
        $('.soob-error-message').remove();
        
        // Validate client name
        var $clientName = $('#client_name');
        if ($clientName.val().trim() === '') {
            showFieldError($clientName, t('client_name_required', 'Client name is required.'));
            hasErrors = true;
        }
        
        // Validate age
        var $age = $('#customer_age');
        var age = parseInt($age.val());
        if (isNaN(age) || age < 1 || age > 100) {
            showFieldError($age, t('age_invalid', 'Please enter a valid age between 1 and 100.'));
            hasErrors = true;
        }
        
        // Validate sessions
        var sessionErrors = validateSessions();
        if (sessionErrors.length > 0) {
            hasErrors = true;
        }
        
        if (hasErrors) {
            e.preventDefault();
            
            // Scroll to first error
            var $firstError = $('.soob-field-error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
            
            showNotice('error', t('fix_errors', 'Please correct the errors below and try again.'));
        }
    });
    
    // Show field error
    function showFieldError($field, message) {
        $field.addClass('soob-field-error');
        $field.after('<span class="soob-error-message">' + message + '</span>');
    }
    
    // Validate sessions
    function validateSessions() {
        var errors = [];
        
        $('.soob-session-row').each(function(index) {
            var $row = $(this);
            var day = $row.find('.soob-session-day').val();
            var startTime = $row.find('.soob-session-start-time').val();
            var endTime = $row.find('.soob-session-end-time').val();
            
            if (!day || !startTime || !endTime) {
                $row.find('select, input').addClass('soob-field-error');
                errors.push('Session ' + (index + 1) + ': All fields are required.');
            } else if (startTime >= endTime) {
                $row.find('.soob-session-start-time, .soob-session-end-time').addClass('soob-field-error');
                errors.push('Session ' + (index + 1) + ': End time must be after start time.');
            }
        });
        
        return errors;
    }
    
    // Real-time validation
    $(document).on('blur', '.soob-editable-field', function() {
        var $field = $(this);
        $field.removeClass('soob-field-error');
        $field.siblings('.soob-error-message').remove();
        
        // Validate specific fields
        if ($field.attr('id') === 'client_name' && $field.val().trim() === '') {
            showFieldError($field, t('client_name_required', 'Client name is required.'));
        } else if ($field.attr('id') === 'customer_age') {
            var age = parseInt($field.val());
            if (isNaN(age) || age < 1 || age > 100) {
                showFieldError($field, t('age_invalid', 'Please enter a valid age between 1 and 100.'));
            }
        }
    });
    
    // Session time validation
    $(document).on('change', '.soob-session-start-time, .soob-session-end-time', function() {
        var $row = $(this).closest('.soob-session-row');
        var startTime = $row.find('.soob-session-start-time').val();
        var endTime = $row.find('.soob-session-end-time').val();
        
        // Clear previous errors
        $row.find('.soob-session-start-time, .soob-session-end-time').removeClass('soob-field-error');
        $row.find('.soob-error-message').remove();
        
        if (startTime && endTime && startTime >= endTime) {
            $row.find('.soob-session-start-time, .soob-session-end-time').addClass('soob-field-error');
            $row.find('.soob-session-end-time').after('<span class="soob-error-message">' + t('end_after_start', 'End time must be after start time.') + '</span>');
        }
    });
    
    // Auto-save draft functionality (optional)
    var autoSaveTimer;
    $(document).on('input change', '.soob-editable-field', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Could implement auto-save to localStorage here
            console.log('Auto-save triggered');
        }, 2000);
    });
    
    // Keyboard shortcuts for booking editor
    $(document).on('keydown.soob-editor', function(e) {
        // Only on booking edit page
        if (!$('.soob-edit-booking-page').length) return;
        
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            $('.soob-booking-edit-form').submit();
        }
        
        // Ctrl/Cmd + N to add new session
        if ((e.ctrlKey || e.metaKey) && e.which === 78) {
            e.preventDefault();
            $('#soob-add-session').click();
        }
    });
    
    // Initialize booking editor if on edit page
    if ($('.soob-edit-booking-page').length) {
        initializeBookingEditor();
    }
    
    function initializeBookingEditor() {
        // Focus on first editable field
        $('.soob-editable-field').first().focus();
        
        // Initialize date picker if available
        if ($.fn.datepicker) {
            $('.soob-date-picker').datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0 // Don't allow past dates
            });
        }
        
        // Add tooltips to form fields
        $('.soob-editable-field').each(function() {
            var $field = $(this);
            var description = $field.siblings('.description').text();
            if (description) {
                $field.attr('title', description);
            }
        });
        
        // Show confirmation before leaving with unsaved changes
        var formChanged = false;
        $('.soob-editable-field').on('change', function() {
            formChanged = true;
        });
        
        $(window).on('beforeunload', function() {
            if (formChanged) {
                return t('unsaved_changes', 'You have unsaved changes. Are you sure you want to leave?');
            }
        });
        
        // Reset form changed flag on successful submit
        $('.soob-booking-edit-form').on('submit', function() {
            formChanged = false;
        });
    }
});