<?php
/**
 * Public functionality class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Hamdy_Public {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize checkout functionality
        // new Hamdy_Checkout();
        
        // AJAX handlers for public
        add_action('wp_ajax_hamdy_get_available_slots', array($this, 'ajax_get_available_slots'));
        add_action('wp_ajax_nopriv_hamdy_get_available_slots', array($this, 'ajax_get_available_slots'));
        
        // Shortcodes
        add_shortcode('hamdy_booking_button', array($this, 'booking_button_shortcode'));
        add_shortcode('hamdy_time_slots', array($this, 'time_slots_shortcode'));
    }
    
    /**
     * AJAX: Get available time slots
     */
    public function ajax_get_available_slots() {
        check_ajax_referer('hamdy_nonce', 'nonce');
        
        $gender_age_group = sanitize_text_field($_POST['gender_age_group']);
        $selected_date = sanitize_text_field($_POST['selected_date']);
        
        if (empty($gender_age_group)) {
            wp_send_json_error(array('message' => __('Please select a category.', 'hamdy-plugin')));
        }
        
        
        // Get day of week from selected date
        $day_of_week = strtolower(date('l', strtotime($selected_date)));
        
        // Get available slots for this criteria
        $available_slots = Hamdy_Teacher::get_available_slots($gender_age_group, $day_of_week);
        
        // Format slots for display
        $formatted_slots = array();
        foreach ($available_slots as $slot) {
            $formatted_slots[] = array(
                'time' => $slot,
                'display' => date('g:i A', strtotime($slot)),
                'day' => $day_of_week,
                'date' => $selected_date
            );
        }
        
        wp_send_json_success(array(
            'slots' => $formatted_slots,
            'day' => $day_of_week,
            'date' => $selected_date
        ));
    }
    
    /**
     * Booking button shortcode
     */
    public function booking_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => '',
            'text' => __('Book Now', 'hamdy-plugin'),
            'class' => 'hamdy-booking-button'
        ), $atts);
        
        if (empty($atts['product_id'])) {
            return '<p>' . __('Product ID is required for booking button.', 'hamdy-plugin') . '</p>';
        }
        
        $product = wc_get_product($atts['product_id']);
        if (!$product) {
            return '<p>' . __('Product not found.', 'hamdy-plugin') . '</p>';
        }
        
        // Check if product is bookable
        $is_bookable = get_post_meta($atts['product_id'], '_hamdy_bookable', true);
        if ($is_bookable !== 'yes') {
            return '<p>' . __('This product is not bookable.', 'hamdy-plugin') . '</p>';
        }
        
        $checkout_url = wc_get_checkout_url() . '?add-to-cart=' . $atts['product_id'];
        
        return '<a href="' . esc_url($checkout_url) . '" class="' . esc_attr($atts['class']) . '">' . esc_html($atts['text']) . '</a>';
    }
    
    /**
     * Time slots shortcode
     */
    public function time_slots_shortcode($atts) {
        $atts = shortcode_atts(array(
            'gender_age_group' => '',
            'date' => date('Y-m-d')
        ), $atts);
        
        if (empty($atts['gender_age_group'])) {
            return '<p>' . __('Gender/age group is required.', 'hamdy-plugin') . '</p>';
        }
        
        $day_of_week = strtolower(date('l', strtotime($atts['date'])));
        $available_slots = Hamdy_Teacher::get_available_slots($atts['gender_age_group'], $day_of_week);
        
        if (empty($available_slots)) {
            return '<p>' . __('No available time slots for this date.', 'hamdy-plugin') . '</p>';
        }
        
        $output = '<div class="hamdy-time-slots-display">';
        $output .= '<h3>' . sprintf(__('Available slots for %s', 'hamdy-plugin'), date('F j, Y', strtotime($atts['date']))) . '</h3>';
        $output .= '<div class="hamdy-slots-grid">';
        
        foreach ($available_slots as $slot) {
            $display_time = date('g:i A', strtotime($slot));
            $output .= '<div class="hamdy-slot-item">' . $display_time . '</div>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
}