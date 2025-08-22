jQuery(document).ready(function($) {
    // Handle tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Get the audience type
        var audience = $(this).data('audience');
        
        // Get selected timezone
        var timezone = $('#soob_display_timezone').val() || 'UTC';
        
        // Load schedule for this audience
        loadScheduleForAudience(audience, timezone);
    });
    
    // Handle timezone change
    $('#soob_display_timezone').on('change', function() {
        var timezone = $(this).val();
        var activeTab = $('.nav-tab-active');
        
        if (activeTab.length) {
            var audience = activeTab.data('audience');
            loadScheduleForAudience(audience, timezone);
        }
    });
    
    // Load initial schedule (for the first tab)
    var firstTab = $('.nav-tab').first();
    if (firstTab.length) {
        firstTab.addClass('nav-tab-active');
        var audience = firstTab.data('audience');
        var timezone = $('#soob_display_timezone').val() || 'UTC';
        loadScheduleForAudience(audience, timezone);
    }
    
    function loadScheduleForAudience(audience, timezone) {
        // Show loading state
        $('#schedule-content').html('<div class="loading">Loading schedule...</div>');
        
        $.ajax({
            url: soob_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'soob_load_schedule',
                audience: audience,
                timezone: timezone,
                nonce: soob_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#schedule-content').html(response.data.html);
                } else {
                    $('#schedule-content').html('<div class="error">Error loading schedule: ' + (response.data || 'Unknown error') + '</div>');
                }
            },
            error: function() {
                $('#schedule-content').html('<div class="error">Error loading schedule. Please try again.</div>');
            }
        });
    }
});