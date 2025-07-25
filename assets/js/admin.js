/**
 * Hamdy Plugin Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Tab switching functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var tab = $(this).data('tab');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show corresponding panel
        $('.hamdy-tab-panel').removeClass('active');
        $('#' + tab + '-tab').addClass('active');
    });
    
    // General admin functionality only - teacher-specific code moved to admin-teachers.js
    
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
});