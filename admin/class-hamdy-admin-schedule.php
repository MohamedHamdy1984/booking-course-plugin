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
    }
    
    /**
     * Display schedule page
     */
    public function display_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Schedule Overview', 'hamdy-plugin'); ?></h1>
            
            <div class="hamdy-schedule-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#men" class="nav-tab nav-tab-active" data-tab="men"><?php _e('Men', 'hamdy-plugin'); ?></a>
                    <a href="#women" class="nav-tab" data-tab="women"><?php _e('Women', 'hamdy-plugin'); ?></a>
                    <a href="#children" class="nav-tab" data-tab="children"><?php _e('Children', 'hamdy-plugin'); ?></a>
                </nav>
                
                <div class="hamdy-tab-content">
                    <div id="men-tab" class="hamdy-tab-panel active">
                        <?php $this->display_schedule_grid('man'); ?>
                    </div>
                    
                    <div id="women-tab" class="hamdy-tab-panel">
                        <?php $this->display_schedule_grid('woman'); ?>
                    </div>
                    
                    <div id="children-tab" class="hamdy-tab-panel">
                        <?php $this->display_schedule_grid('children'); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .hamdy-schedule-tabs {
            margin-top: 20px;
        }
        
        .hamdy-tab-panel {
            display: none;
            padding: 20px 0;
        }
        
        .hamdy-tab-panel.active {
            display: block;
        }
        
        .hamdy-schedule-grid {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 1px;
            background: #ddd;
            border: 1px solid #ddd;
            max-width: 100%;
            overflow-x: auto;
        }
        
        .hamdy-day-header {
            background: #f1f1f1;
            padding: 10px;
            font-weight: bold;
            text-align: center;
            border-right: 1px solid #ddd;
        }
        
        .hamdy-day-slots {
            display: grid;
            grid-template-columns: repeat(24, 1fr);
            gap: 1px;
            background: #ddd;
        }
        
        .hamdy-time-slot {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            cursor: pointer;
            position: relative;
        }
        
        .hamdy-time-slot.available {
            background: #4CAF50;
            color: white;
        }
        
        .hamdy-time-slot.unavailable {
            background: #f5f5f5;
            color: #999;
        }
        
        .hamdy-time-slot:hover {
            opacity: 0.8;
        }
        
        .hamdy-legend {
            margin: 20px 0;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .hamdy-legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .hamdy-legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
        }
        
        .hamdy-hours-header {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 1px;
            margin-bottom: 1px;
        }
        
        .hamdy-hours-labels {
            display: grid;
            grid-template-columns: repeat(24, 1fr);
            gap: 1px;
            background: #ddd;
        }
        
        .hamdy-hour-label {
            width: 30px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            background: #f9f9f9;
            color: #666;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
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
            
            // Tooltip for time slots
            $('.hamdy-time-slot').on('mouseenter', function() {
                var hour = $(this).data('hour');
                var day = $(this).closest('.hamdy-day-row').data('day');
                var available = $(this).hasClass('available');
                
                var tooltip = available ? 'Available at ' + hour + ':00' : 'Not available at ' + hour + ':00';
                $(this).attr('title', tooltip);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display schedule grid for specific audience
     */
    private function display_schedule_grid($audience) {
        $days = array(
            'sunday' => __('Sunday', 'hamdy-plugin'),
            'monday' => __('Monday', 'hamdy-plugin'),
            'tuesday' => __('Tuesday', 'hamdy-plugin'),
            'wednesday' => __('Wednesday', 'hamdy-plugin'),
            'thursday' => __('Thursday', 'hamdy-plugin'),
            'friday' => __('Friday', 'hamdy-plugin'),
            'saturday' => __('Saturday', 'hamdy-plugin')
        );
        
        // Get availability data for this audience
        $availability_data = $this->get_availability_for_audience($audience);
        
        echo '<div class="hamdy-legend">';
        echo '<div class="hamdy-legend-item">';
        echo '<div class="hamdy-legend-color" style="background: #4CAF50;"></div>';
        echo '<span>' . __('Available', 'hamdy-plugin') . '</span>';
        echo '</div>';
        echo '<div class="hamdy-legend-item">';
        echo '<div class="hamdy-legend-color" style="background: #f5f5f5;"></div>';
        echo '<span>' . __('Unavailable', 'hamdy-plugin') . '</span>';
        echo '</div>';
        echo '</div>';
        
        // Hours header
        echo '<div class="hamdy-hours-header">';
        echo '<div></div>'; // Empty cell for day column
        echo '<div class="hamdy-hours-labels">';
        for ($hour = 0; $hour < 24; $hour++) {
            echo '<div class="hamdy-hour-label">' . sprintf('%02d', $hour) . '</div>';
        }
        echo '</div>';
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
     * Get availability data for specific audience
     */
    private function get_availability_for_audience($audience) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_teachers';
        
        // Determine gender and age group filters
        $gender_filter = '';
        $age_group_filter = '';
        
        switch ($audience) {
            case 'man':
                $gender_filter = "gender = 'man'";
                $age_group_filter = "age_group = 'adults'";
                break;
            case 'woman':
                $gender_filter = "gender = 'woman'";
                $age_group_filter = "age_group = 'adults'";
                break;
            case 'children':
                $gender_filter = "(gender = 'man' OR gender = 'woman')";
                $age_group_filter = "age_group = 'children'";
                break;
        }
        
        $query = "SELECT availability FROM $table WHERE status = 'active' AND $gender_filter AND $age_group_filter";
        $teachers = $wpdb->get_results($query);
        
        $combined_availability = array();
        
        foreach ($teachers as $teacher) {
            $availability = json_decode($teacher->availability, true);
            if (is_array($availability)) {
                foreach ($availability as $day => $slots) {
                    if (!isset($combined_availability[$day])) {
                        $combined_availability[$day] = array();
                    }
                    $combined_availability[$day] = array_merge($combined_availability[$day], $slots);
                }
            }
        }
        
        // Remove duplicates and sort
        foreach ($combined_availability as $day => $slots) {
            $combined_availability[$day] = array_unique($slots);
            sort($combined_availability[$day]);
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
        $availability_data = $this->get_availability_for_audience($audience);
        
        wp_send_json_success($availability_data);
    }
}