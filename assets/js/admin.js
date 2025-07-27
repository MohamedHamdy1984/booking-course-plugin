/**
 * Hamdy Plugin Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Schedule overview - time slot tooltips
    $('.hamdy-time-slot').on('mouseenter', function() {
        var hour = $(this).data('hour');
        var day = $(this).closest('.hamdy-day-row').data('day');
        var available = $(this).hasClass('available');
        
        var tooltip = available ? 
            'Available at ' + hour + ':00 on ' + day : 
            'Not available at ' + hour + ':00 on ' + day;
        
        $(this).attr('title', tooltip);
    });
    
    // Auto-save functionality for forms
    var autoSaveTimeout;
    $('.hamdy-teacher-form input, .hamdy-teacher-form select, .hamdy-teacher-form textarea').on('input change', function() {
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
    
    // Search functionality
    $('#teacher-search-input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.wp-list-table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Statistics refresh
    $('.hamdy-stat-card').on('click', function() {
        refreshStatistics();
    });
    
    function refreshStatistics() {
        $.ajax({
            url: hamdy_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'hamdy_refresh_stats',
                nonce: hamdy_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.hamdy-stat-number').each(function(index) {
                        $(this).text(response.data.stats[index]);
                    });
                }
            }
        });
    }
    
    // Media uploader for teacher photos
    $('.hamdy-upload-photo').on('click', function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: 'Select Teacher Photo',
            button: {
                text: 'Use this photo'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#teacher_photo').val(attachment.url);
            $('.hamdy-current-photo img').attr('src', attachment.url);
        });
        
        mediaUploader.open();
    });
    
    // Sortable functionality for tables
    if ($.fn.sortable) {
        $('.wp-list-table tbody').sortable({
            handle: '.hamdy-sort-handle',
            update: function(event, ui) {
                var order = $(this).sortable('toArray', {attribute: 'data-id'});
                
                $.ajax({
                    url: hamdy_admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'hamdy_update_order',
                        order: order,
                        nonce: hamdy_admin_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice('success', 'Order updated successfully');
                        }
                    }
                });
            }
        });
    }
    
    // General form field dependencies can be added here if needed
    
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
    
    // Initialize tooltips if available
    if ($.fn.tooltip) {
        $('[data-tooltip]').tooltip();
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            $('.hamdy-teacher-form').submit();
        }
        
        // Escape to close modals
        if (e.which === 27) {
            $('.hamdy-modal').hide();
        }
    });
    
    // Initialize page
    initializePage();
    
    function initializePage() {
        // Set focus on first input
        $('.hamdy-teacher-form input:first').focus();
        
        // Initialize any third-party plugins
        if ($.fn.datepicker) {
            $('.hamdy-date-picker').datepicker({
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
    $('.hamdy-delete-booking').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var bookingId = $button.data('booking-id');
        var $row = $button.closest('tr');
        
        // Show confirmation dialog
        if (confirm(hamdy_admin_ajax.strings.confirm_delete)) {
            // Add loading state
            $row.addClass('deleting');
            $button.prop('disabled', true).text(hamdy_admin_ajax.strings.loading);
            
            // Send AJAX request
            $.ajax({
                url: hamdy_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hamdy_delete_booking',
                    booking_id: bookingId,
                    nonce: hamdy_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove row with animation
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Show success message
                            showNotice('success', response.data);
                            
                            // Check if table is empty
                            if ($('.hamdy-bookings-table tbody tr').length === 0) {
                                $('.hamdy-bookings-list').html('<p>' + 'No bookings found.' + '</p>');
                            }
                        });
                    } else {
                        // Remove loading state and show error
                        $row.removeClass('deleting');
                        $button.prop('disabled', false).text('Delete');
                        showNotice('error', response.data || hamdy_admin_ajax.strings.error);
                    }
                },
                error: function() {
                    // Remove loading state and show error
                    $row.removeClass('deleting');
                    $button.prop('disabled', false).text('Delete');
                    showNotice('error', hamdy_admin_ajax.strings.error);
                }
            });
        }
    });
    
    // Tab switching functionality for bookings - removed to allow natural navigation
    // The tabs will work through normal page navigation with URL parameters
    
    // Highlight expiring bookings on page load
    highlightExpiringBookings();
    
    function highlightExpiringBookings() {
        $('.hamdy-bookings-table tr.expiring').each(function() {
            var $row = $(this);
            
            // Add a subtle animation to draw attention
            setTimeout(function() {
                $row.addClass('highlight-animation');
            }, 500);
        });
    }
    
    // Enhanced search functionality for bookings
    $('#booking-search-input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.hamdy-bookings-table tbody tr').filter(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(value) > -1);
        });
    });
    
    // Bulk actions for bookings (future enhancement)
    $('.hamdy-bookings-table #cb-select-all-1').on('change', function() {
        var checked = $(this).prop('checked');
        $('.hamdy-bookings-table tbody input[type="checkbox"]').prop('checked', checked);
    });
    
    // Add confirmation dialog for critical actions
    function showConfirmDialog(title, message, onConfirm, onCancel) {
        var $overlay = $('<div class="hamdy-dialog-overlay"></div>');
        var $dialog = $('<div class="hamdy-confirm-dialog"></div>');
        
        $dialog.html(
            '<h3>' + title + '</h3>' +
            '<p>' + message + '</p>' +
            '<div class="button-group">' +
                '<button class="button button-secondary hamdy-cancel-btn">Cancel</button>' +
                '<button class="button button-primary hamdy-confirm-btn">Confirm</button>' +
            '</div>'
        );
        
        $('body').append($overlay).append($dialog);
        
        // Handle confirm
        $dialog.find('.hamdy-confirm-btn').on('click', function() {
            $overlay.remove();
            $dialog.remove();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });
        
        // Handle cancel
        $dialog.find('.hamdy-cancel-btn, .hamdy-dialog-overlay').on('click', function() {
            $overlay.remove();
            $dialog.remove();
            if (typeof onCancel === 'function') {
                onCancel();
            }
        });
        
        // Close on escape
        $(document).on('keydown.hamdy-dialog', function(e) {
            if (e.which === 27) {
                $overlay.remove();
                $dialog.remove();
                $(document).off('keydown.hamdy-dialog');
                if (typeof onCancel === 'function') {
                    onCancel();
                }
            }
        });
    }
    
    // Enhanced table row hover effects
    $('.hamdy-bookings-table tbody tr').hover(
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
            if (window.location.href.indexOf('hamdy-bookings') > -1 &&
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
    if (window.location.href.indexOf('hamdy-bookings') > -1) {
        startAutoRefresh();
    }
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        stopAutoRefresh();
    });
});