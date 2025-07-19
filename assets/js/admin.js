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
    
    // Delete teacher functionality
    $('.hamdy-delete-teacher').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(hamdy_admin_ajax.strings.confirm_delete)) {
            return;
        }
        
        var teacherId = $(this).data('teacher-id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: hamdy_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'hamdy_delete_teacher',
                teacher_id: teacherId,
                nonce: hamdy_admin_ajax.nonce
            },
            beforeSend: function() {
                $row.addClass('hamdy-loading');
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showNotice('success', response.data.message);
                } else {
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                showNotice('error', hamdy_admin_ajax.strings.error);
            },
            complete: function() {
                $row.removeClass('hamdy-loading');
            }
        });
    });
    
    // Teacher form validation
    $('.hamdy-teacher-form').on('submit', function(e) {
        var name = $('#teacher_name').val().trim();
        var gender = $('#teacher_gender').val();
        var ageGroup = $('#teacher_age_group').val();
        
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
        
        if (!ageGroup) {
            e.preventDefault();
            showNotice('error', 'Please select age group.');
            $('#teacher_age_group').focus();
            return false;
        }
        
        // Check if at least one availability slot is selected
        var hasAvailability = $('.hamdy-availability-grid input[type="checkbox"]:checked').length > 0;
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
    
    // Form field dependencies
    $('#teacher_gender').on('change', function() {
        var gender = $(this).val();
        var $ageGroup = $('#teacher_age_group');
        
        // Reset age group selection
        $ageGroup.val('');
        
        // You can add logic here to show/hide age group options based on gender
        // For example, if certain genders can only teach certain age groups
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
        
        // Update preview display
        $('.hamdy-availability-preview').html(JSON.stringify(selectedSlots, null, 2));
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