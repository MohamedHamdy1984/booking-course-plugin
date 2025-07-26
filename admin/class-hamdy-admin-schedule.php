<?php
/**
 * Schedule overview admin class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Hamdy_Admin_Schedule {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_hamdy_get_schedule_data', array($this, 'ajax_get_schedule_data'));
        add_action('wp_ajax_hamdy_load_schedule', array($this, 'ajax_load_schedule'));
    }
    
    /**
     * Enqueue scripts and styles
     * called from Hamdy_Admin class
     */
    public function enqueue_scripts() {
        wp_enqueue_style('hamdy-admin-schedule', HAMDY_PLUGIN_URL . 'assets/css/admin-schedule.css', array(), HAMDY_PLUGIN_VERSION);
        wp_enqueue_script('hamdy-admin-schedule', HAMDY_PLUGIN_URL . 'assets/js/admin-schedule.js', array('jquery'), HAMDY_PLUGIN_VERSION, true);
        
        // Localize script for AJAX with unique variable name
        wp_localize_script('hamdy-admin-schedule', 'hamdy_schedule_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hamdy_admin_nonce'),
            'strings' => array(
                'loading' => __('Loading schedule...', 'hamdy-plugin'),
                'no_data' => __('No schedule data available.', 'hamdy-plugin'),
                'error' => __('An error occurred while loading the schedule.', 'hamdy-plugin'),
            )
        ));
    }
    
    /**
     * Display schedule page
     */
    public function display_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Schedule Overview', 'hamdy-plugin'); ?></h1>
            
            <!-- Timezone selector for admin -->
            <div class="hamdy-timezone-selector" style="margin-bottom: 20px;">
                <label for="hamdy_display_timezone"><?php _e('Display times in timezone:', 'hamdy-plugin'); ?></label>
                <select id="hamdy_display_timezone" name="hamdy_display_timezone">
                    <?php
                    $woocommerce = new Hamdy_WooCommerce();
                    $timezones = $woocommerce->get_timezone_options();
                    foreach ($timezones as $value => $label) {
                        echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Times are stored in UTC and will be converted to the selected timezone for display.', 'hamdy-plugin'); ?></p>
            </div>
            
            <div class="hamdy-schedule-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#male" class="nav-tab" data-audience="male"><?php _e('Male', 'hamdy-plugin'); ?></a>
                    <a href="#female" class="nav-tab" data-audience="female"><?php _e('Female', 'hamdy-plugin'); ?></a>
                </nav>
                
                <div id="schedule-content" class="hamdy-tab-content">
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
            'sunday' => __('Sunday', 'hamdy-plugin'),
            'monday' => __('Monday', 'hamdy-plugin'),
            'tuesday' => __('Tuesday', 'hamdy-plugin'),
            'wednesday' => __('Wednesday', 'hamdy-plugin'),
            'thursday' => __('Thursday', 'hamdy-plugin'),
            'friday' => __('Friday', 'hamdy-plugin'),
            'saturday' => __('Saturday', 'hamdy-plugin')
        );
        
        // Get availability data for this audience with timezone conversion
        $availability_data = $this->get_availability_for_audience($audience, $display_timezone);
        
        // Display timezone indicator
        echo '<div class="hamdy-timezone-indicator">';
        echo '<strong>' . __('Displaying times in:', 'hamdy-plugin') . '</strong> ' . esc_html($display_timezone);
        echo '</div>';
        
        // Check if there's any availability data
        if (empty($availability_data)) {
            echo '<div class="hamdy-no-data">';
            echo '<h3>' . __('No teachers available', 'hamdy-plugin') . '</h3>';
            echo '<p>' . sprintf(__('There are currently no active teachers available for %s.', 'hamdy-plugin'), $this->get_audience_label($audience)) . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=hamdy-teachers&action=add') . '" class="button button-primary">' . __('Add Teacher', 'hamdy-plugin') . '</a></p>';
            echo '</div>';
            return;
        }
        
        // Display legend
        echo '<div class="hamdy-legend">';
        echo '<div class="hamdy-legend-item">';
        echo '<div class="hamdy-legend-color hamdy-legend-available"></div>';
        echo '<span>' . __('Available', 'hamdy-plugin') . '</span>';
        echo '</div>';
        echo '<div class="hamdy-legend-item">';
        echo '<div class="hamdy-legend-color hamdy-legend-unavailable"></div>';
        echo '<span>' . __('Unavailable', 'hamdy-plugin') . '</span>';
        echo '</div>';
        echo '</div>';
        
        // Timezone notice header
        echo '<div class="hamdy-timezone-notice">';
        echo '<p><strong>' . __('All times are shown in:', 'hamdy-plugin') . '</strong> ';
        
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
        
        echo '<div class="hamdy-schedule-grid">';
        
        foreach ($days as $day_key => $day_name) {
            echo '<div class="hamdy-day-header">' . $day_name . '</div>';
            echo '<div class="hamdy-day-slots hamdy-day-row" data-day="' . $day_key . '">';
            
            for ($hour = 0; $hour < 24; $hour++) {
                $time_slot = sprintf('%02d:00', $hour);
                $is_available = isset($availability_data[$day_key]) && in_array($time_slot, $availability_data[$day_key]);
                $class = $is_available ? 'available' : 'unavailable';
                
                echo '<div class="hamdy-time-slot ' . $class . '" data-hour="' . sprintf('%02d', $hour) . '" data-day="' . $day_key . '" data-time="' . $time_slot . '">';
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
        
        $table = $wpdb->prefix . 'hamdy_teachers';
        
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
        check_ajax_referer('hamdy_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'hamdy-plugin'));
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
        check_ajax_referer('hamdy_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'hamdy-plugin'));
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
                return __('male teachers', 'hamdy-plugin');
            case 'female':
                return __('female teachers', 'hamdy-plugin');
            default:
                return __('this audience', 'hamdy-plugin');
        }
    }
}