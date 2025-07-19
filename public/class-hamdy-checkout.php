<?php
/**
 * Checkout functionality class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Hamdy_Checkout {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize WooCommerce integration
        new Hamdy_WooCommerce();
        
        // Enqueue checkout scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_scripts'));
        
        // AJAX handlers for checkout
        add_action('wp_ajax_hamdy_get_checkout_slots', array($this, 'ajax_get_checkout_slots'));
        add_action('wp_ajax_nopriv_hamdy_get_checkout_slots', array($this, 'ajax_get_checkout_slots'));
    }
    
    /**
     * Enqueue checkout scripts
     */
    public function enqueue_checkout_scripts() {
        if (is_checkout()) {
            wp_enqueue_script('hamdy-checkout', HAMDY_PLUGIN_URL . 'assets/js/checkout.js', array('jquery'), HAMDY_PLUGIN_VERSION, true);
            wp_enqueue_style('hamdy-checkout', HAMDY_PLUGIN_URL . 'assets/css/checkout.css', array(), HAMDY_PLUGIN_VERSION);
            
            wp_localize_script('hamdy-checkout', 'hamdy_checkout_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hamdy_nonce'),
                'strings' => array(
                    'loading' => __('Loading available slots...', 'hamdy-plugin'),
                    'no_slots' => __('No available slots for this selection.', 'hamdy-plugin'),
                    'select_category' => __('Please select a category first.', 'hamdy-plugin'),
                    'select_timezone' => __('Please select your timezone first.', 'hamdy-plugin'),
                    'select_slot' => __('Please select at least one time slot.', 'hamdy-plugin'),
                    'error' => __('An error occurred. Please try again.', 'hamdy-plugin')
                )
            ));
        }
    }
    
    /**
     * AJAX: Get checkout time slots
     */
    public function ajax_get_checkout_slots() {
        check_ajax_referer('hamdy_nonce', 'nonce');
        
        $gender_age_group = sanitize_text_field($_POST['gender_age_group']);
        $timezone = sanitize_text_field($_POST['timezone']);
        
        if (empty($gender_age_group)) {
            wp_send_json_error(array('message' => __('Please select a category first.', 'hamdy-plugin')));
        }
        
        // Get next 7 days
        $days_data = array();
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $day_name = date('l', strtotime($date));
            $day_key = strtolower($day_name);
            
            // Get available slots for this day
            $available_slots = Hamdy_Teacher::get_available_slots($gender_age_group, $day_key);
            
            // Convert slots to user timezone if needed
            $converted_slots = $this->convert_slots_to_timezone($available_slots, $timezone);
            
            $days_data[] = array(
                'date' => $date,
                'day_name' => $day_name,
                'day_key' => $day_key,
                'display_date' => date('M j', strtotime($date)),
                'slots' => $converted_slots,
                'has_slots' => !empty($converted_slots)
            );
        }
        
        wp_send_json_success(array(
            'days' => $days_data,
            'timezone' => $timezone
        ));
    }
    
    /**
     * Convert time slots to user timezone
     */
    private function convert_slots_to_timezone($slots, $user_timezone) {
        if (empty($slots) || empty($user_timezone)) {
            return $slots;
        }
        
        $converted_slots = array();
        
        try {
            $server_tz = new DateTimeZone('UTC');
            $user_tz = new DateTimeZone($user_timezone);
            
            foreach ($slots as $slot) {
                $datetime = new DateTime($slot, $server_tz);
                $datetime->setTimezone($user_tz);
                
                $converted_slots[] = array(
                    'original' => $slot,
                    'converted' => $datetime->format('H:i'),
                    'display' => $datetime->format('g:i A'),
                    'timezone' => $user_timezone
                );
            }
        } catch (Exception $e) {
            // If timezone conversion fails, return original slots
            foreach ($slots as $slot) {
                $converted_slots[] = array(
                    'original' => $slot,
                    'converted' => $slot,
                    'display' => date('g:i A', strtotime($slot)),
                    'timezone' => 'UTC'
                );
            }
        }
        
        return $converted_slots;
    }
    
    /**
     * Generate time slots HTML for checkout
     */
    public function generate_time_slots_html($days_data) {
        if (empty($days_data)) {
            return '<p class="hamdy-no-slots">' . __('No available time slots found.', 'hamdy-plugin') . '</p>';
        }
        
        $html = '<div class="hamdy-time-slots-container">';
        
        // Days tabs
        $html .= '<div class="hamdy-days-tabs">';
        foreach ($days_data as $index => $day) {
            $active_class = $index === 0 ? ' active' : '';
            $disabled_class = !$day['has_slots'] ? ' disabled' : '';
            
            $html .= '<button type="button" class="hamdy-day-tab' . $active_class . $disabled_class . '" data-day="' . $day['day_key'] . '" data-date="' . $day['date'] . '">';
            $html .= '<span class="day-name">' . $day['day_name'] . '</span>';
            $html .= '<span class="day-date">' . $day['display_date'] . '</span>';
            $html .= '</button>';
        }
        $html .= '</div>';
        
        // Time slots content
        $html .= '<div class="hamdy-slots-content">';
        foreach ($days_data as $index => $day) {
            $active_class = $index === 0 ? ' active' : '';
            
            $html .= '<div class="hamdy-day-slots' . $active_class . '" data-day="' . $day['day_key'] . '">';
            
            if ($day['has_slots']) {
                $html .= '<div class="hamdy-slots-grid">';
                foreach ($day['slots'] as $slot) {
                    $html .= '<label class="hamdy-slot-option">';
                    $html .= '<input type="checkbox" name="hamdy_time_slots[]" value="' . esc_attr(json_encode(array(
                        'day' => $day['day_name'],
                        'date' => $day['date'],
                        'time' => $slot['original'],
                        'display_time' => $slot['display'],
                        'timezone' => $slot['timezone']
                    ))) . '">';
                    $html .= '<span class="slot-time">' . $slot['display'] . '</span>';
                    $html .= '</label>';
                }
                $html .= '</div>';
            } else {
                $html .= '<p class="hamdy-no-slots">' . __('No available slots for this day.', 'hamdy-plugin') . '</p>';
            }
            
            $html .= '</div>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
}