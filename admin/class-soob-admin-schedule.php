<?php
/**
 * Schedule overview admin class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SOOB_Admin_Schedule {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_soob_get_schedule_data', array($this, 'ajax_get_schedule_data'));
        add_action('wp_ajax_soob_load_schedule', array($this, 'ajax_load_schedule'));
    }
    
    /**
     * Enqueue scripts and styles
     * called from SOOB_Admin class
     */
    public function enqueue_scripts() {
        wp_enqueue_style('soob-admin-schedule', SOOB_PLUGIN_URL . 'assets/css/admin-schedule.css', array(), SOOB_PLUGIN_VERSION);
        wp_enqueue_script('soob-admin-schedule', SOOB_PLUGIN_URL . 'assets/js/admin-schedule.js', array('jquery'), SOOB_PLUGIN_VERSION, true);
        
        // Localize script for AJAX with unique variable name
        wp_localize_script('soob-admin-schedule', 'soob_schedule_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('soob_admin_nonce'),
            'strings' => array(
                'loading' => __('Loading schedule...', 'soob-plugin'),
                'no_data' => __('No schedule data available.', 'soob-plugin'),
                'error' => __('An error occurred while loading the schedule.', 'soob-plugin'),
            )
        ));
    }
    
    /**
     * Display schedule page
     */
    public function display_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Schedule Overview', 'soob-plugin'); ?></h1>
            
            <!-- Timezone selector for admin -->
            <div class="soob-timezone-selector" style="margin-bottom: 20px;">
                <label for="soob_display_timezone"><?php _e('Display times in timezone:', 'soob-plugin'); ?></label>
                <select id="soob_display_timezone" name="soob_display_timezone">
                    <?php
                    $woocommerce = new SOOB_WooCommerce();
                    $timezones = $woocommerce->get_timezone_options();
                    foreach ($timezones as $value => $label) {
                        echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Times are stored in UTC and will be converted to the selected timezone for display.', 'soob-plugin'); ?></p>
            </div>
            
            <div class="soob-schedule-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#male" class="nav-tab" data-audience="male"><?php _e('Male', 'soob-plugin'); ?></a>
                    <a href="#female" class="nav-tab" data-audience="female"><?php _e('Female', 'soob-plugin'); ?></a>
                </nav>
                
                <div id="schedule-content" class="soob-tab-content">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
        

 
        <?php
    }
    
    /**
     * Display schedule grid for specific audience
     */
    private function display_schedule_grid($audience, $display_timezone = 'UTC') {
        $days = array(
            'sunday' => __('Sunday', 'soob-plugin'),
            'monday' => __('Monday', 'soob-plugin'),
            'tuesday' => __('Tuesday', 'soob-plugin'),
            'wednesday' => __('Wednesday', 'soob-plugin'),
            'thursday' => __('Thursday', 'soob-plugin'),
            'friday' => __('Friday', 'soob-plugin'),
            'saturday' => __('Saturday', 'soob-plugin')
        );
        
        // Get availability data for this audience with timezone conversion
        $availability_data = $this->get_availability_for_audience($audience, $display_timezone);
        
        // Display timezone indicator
        echo '<div class="soob-timezone-indicator">';
        echo '<strong>' . __('Displaying times in:', 'soob-plugin') . '</strong> ' . esc_html($display_timezone);
        echo '</div>';
        
        // Check if there's any availability data
        if (empty($availability_data)) {
            echo '<div class="soob-no-data">';
            echo '<h3>' . __('No teachers available', 'soob-plugin') . '</h3>';
            echo '<p>' . sprintf(__('There are currently no active teachers available for %s.', 'soob-plugin'), $this->get_audience_label($audience)) . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=soob-teachers&action=add') . '" class="button button-primary">' . __('Add Teacher', 'soob-plugin') . '</a></p>';
            echo '</div>';
            return;
        }
        
        // Display legend
        echo '<div class="soob-legend">';
        echo '<div class="soob-legend-item">';
        echo '<div class="soob-legend-color soob-legend-available"></div>';
        echo '<span>' . __('Available', 'soob-plugin') . '</span>';
        echo '</div>';
        echo '<div class="soob-legend-item">';
        echo '<div class="soob-legend-color soob-legend-unavailable"></div>';
        echo '<span>' . __('Unavailable', 'soob-plugin') . '</span>';
        echo '</div>';
        echo '</div>';
        
        // Timezone notice header
        echo '<div class="soob-timezone-notice">';
        echo '<p><strong>' . __('All times are shown in:', 'soob-plugin') . '</strong> ';
        
        // Get timezone name and UTC offset
        try {
            $tz = new DateTimeZone($display_timezone);
            $now = new DateTime('now', $tz);
            $offset = $now->getOffset();
            $offset_hours = intval($offset / 3600);
            $offset_minutes = abs(($offset % 3600) / 60);
            
            $offset_string = sprintf('UTC%+d', $offset_hours);
            if ($offset_minutes > 0) {
                $offset_string .= ':' . sprintf('%02d', $offset_minutes);
            }
            
            echo esc_html($display_timezone . ' (' . $offset_string . ')');
        } catch (Exception $e) {
            echo esc_html($display_timezone);
        }
        
        echo '</p>';
        echo '</div>';
        
        echo '<div class="soob-schedule-grid">';
        
        foreach ($days as $day_key => $day_name) {
            echo '<div class="soob-day-header">' . $day_name . '</div>';
            echo '<div class="soob-day-slots soob-day-row" data-day="' . $day_key . '">';
            
            for ($hour = 0; $hour < 24; $hour++) {
                $time_slot = sprintf('%02d:00', $hour);
                $is_available = isset($availability_data[$day_key]) && in_array($time_slot, $availability_data[$day_key]);
                $class = $is_available ? 'available' : 'unavailable';
                
                echo '<div class="soob-time-slot ' . $class . '" data-hour="' . sprintf('%02d', $hour) . '" data-day="' . $day_key . '" data-time="' . $time_slot . '">';
                echo sprintf('%02d', $hour);
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Get availability data for specific audience with timezone conversion
     */
    private function get_availability_for_audience($audience, $display_timezone = 'UTC') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soob_teachers';
        
        // Validate audience
        if (!in_array($audience, ['male', 'female'])) {
            return array();
        }
        
        // Prepare and execute query safely
        $query = $wpdb->prepare(
            "SELECT availability FROM $table WHERE status = %s AND gender = %s",
            'active',
            $audience
        );
        
        $teachers = $wpdb->get_results($query);
        
        // Handle database errors
        if ($wpdb->last_error) {
            error_log('Database error in get_availability_for_audience: ' . $wpdb->last_error);
            return array();
        }
        
        // If no teachers found, return empty array
        if (empty($teachers)) {
            return array();
        }
        
        $combined_availability = array();
        
        foreach ($teachers as $teacher) {
            if (empty($teacher->availability)) {
                continue;
            }
            
            $availability = json_decode($teacher->availability, true);
            
            // Skip if JSON decode failed or not an array
            if (!is_array($availability)) {
                continue;
            }
            
            foreach ($availability as $day => $slots) {
                // Validate day and slots
                if (!is_string($day) || !is_array($slots)) {
                    continue;
                }
                
                if (!isset($combined_availability[$day])) {
                    $combined_availability[$day] = array();
                }
                
                // Filter out invalid time slots
                $valid_slots = array_filter($slots, function($slot) {
                    return is_string($slot) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $slot);
                });
                
                $combined_availability[$day] = array_merge($combined_availability[$day], $valid_slots);
            }
        }
        
        // Remove duplicates and sort
        foreach ($combined_availability as $day => $slots) {
            $combined_availability[$day] = array_unique($slots);
            sort($combined_availability[$day]);
        }
        
        // Convert from UTC to display timezone (always normalize format)
        if (!empty($combined_availability)) {
            if ($display_timezone !== 'UTC') {
                $combined_availability = $this->convert_availability_from_utc($combined_availability, $display_timezone);
            } else {
                // Even for UTC, normalize the format from H:i:s to H:i
                $combined_availability = $this->normalize_utc_format($combined_availability);
            }
        }
        
        return $combined_availability;
    }
    
    /**
     * AJAX: Get schedule data
     */
    public function ajax_get_schedule_data() {
        check_ajax_referer('soob_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'soob-plugin'));
        }
        
        $audience = sanitize_text_field($_POST['audience']);
        $display_timezone = isset($_POST['timezone']) ? sanitize_text_field($_POST['timezone']) : 'UTC';
        $availability_data = $this->get_availability_for_audience($audience, $display_timezone);
        
        wp_send_json_success($availability_data);
    }
    
    /**
     * AJAX: Load schedule (returns HTML for display)
     */
    public function ajax_load_schedule() {
        check_ajax_referer('soob_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'soob-plugin'));
        }
        
        $audience = sanitize_text_field($_POST['audience']);
        $display_timezone = isset($_POST['timezone']) ? sanitize_text_field($_POST['timezone']) : 'UTC';
        
        // Generate HTML for the schedule grid
        ob_start();
        $this->display_schedule_grid($audience, $display_timezone);
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Convert availability times from UTC to display timezone
     */
    private function convert_availability_from_utc($availability, $display_timezone) {
        if (empty($availability) || empty($display_timezone)) {
            return $availability;
        }
        
        $display_availability = array();
        
        try {
            $utc_tz = new DateTimeZone('UTC');
            $display_tz = new DateTimeZone($display_timezone);
            
            foreach ($availability as $day => $slots) {
                $display_availability[$day] = array();
                
                foreach ($slots as $slot) {
                    // Create datetime in UTC
                    $datetime = new DateTime($slot, $utc_tz);
                    
                    // Convert to display timezone
                    $datetime->setTimezone($display_tz);
                    
                    $display_availability[$day][] = $datetime->format('H:i');
                }
            }
        } catch (Exception $e) {
            // If conversion fails, return original availability
            error_log('Timezone conversion error: ' . $e->getMessage());
            return $availability;
        }
        
        return $display_availability;
    }
    
    /**
     * Normalize UTC format from H:i:s to H:i for display consistency
     */
    private function normalize_utc_format($availability) {
        if (empty($availability)) {
            return $availability;
        }
        
        $normalized_availability = array();
        
        foreach ($availability as $day => $slots) {
            $normalized_availability[$day] = array();
            
            foreach ($slots as $slot) {
                // Convert H:i:s format to H:i format
                if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $slot, $matches)) {
                    $normalized_availability[$day][] = $matches[1];
                } else {
                    // Already in H:i format or invalid, keep as is
                    $normalized_availability[$day][] = $slot;
                }
            }
        }
        
        return $normalized_availability;
    }
    
    /**
     * Get human-readable label for audience type
     */
    private function get_audience_label($audience) {
        switch ($audience) {
            case 'male':
                return __('male teachers', 'soob-plugin');
            case 'female':
                return __('female teachers', 'soob-plugin');
            default:
                return __('this audience', 'soob-plugin');
        }
    }
}