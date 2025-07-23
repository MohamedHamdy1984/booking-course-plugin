<?php
/**
 * WooCommerce integration class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Hamdy_WooCommerce {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add checkout fields
        add_action('woocommerce_after_order_notes', array($this, 'add_checkout_fields'));
        
        // Validate checkout fields
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout_fields'));
        
        // Save checkout fields
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_checkout_fields'));
        
        // Display fields in admin order
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_admin_order_meta'));
        
        // Add order meta to emails
        add_action('woocommerce_email_order_meta', array($this, 'add_order_meta_to_email'), 10, 3);
        
        // Product admin settings
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_booking_field'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_booking_field'));
    }
    
    /**
     * Add checkout fields
     */
    public function add_checkout_fields($checkout) {
        // Check if cart contains bookable products
        if (!$this->cart_has_bookable_products()) {
            return;
        }
        
        echo '<div id="hamdy_booking_fields"><h3>' . __('Booking Details', 'hamdy-plugin') . '</h3>';
        
        // Timezone field
        woocommerce_form_field('hamdy_timezone', array(
            'type' => 'select',
            'class' => array('hamdy-field-wide'),
            'label' => __('Select your timezone', 'hamdy-plugin'),
            'required' => true,
            'options' => $this->get_timezone_options()
        ), $checkout->get_value('hamdy_timezone'));
        
        // Gender/Age group field
        woocommerce_form_field('hamdy_gender_age_group', array(
            'type' => 'select',
            'class' => array('hamdy-field-wide'),
            'label' => __('Select your category', 'hamdy-plugin'),
            'required' => true,
            'options' => array(
                '' => __('Choose an option', 'hamdy-plugin'),
                'man' => __('Man', 'hamdy-plugin'),
                'woman' => __('Woman', 'hamdy-plugin'),
                'child' => __('Child', 'hamdy-plugin')
            )
        ), $checkout->get_value('hamdy_gender_age_group'));
        
        // Time slots container
        echo '<div id="hamdy_time_slots_container">';
        echo '<label>' . __('Select available time slots', 'hamdy-plugin') . ' <abbr class="required" title="required">*</abbr></label>';
        echo '<div id="hamdy_time_slots_wrapper"></div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Validate checkout fields
     */
    public function validate_checkout_fields() {
        if (!$this->cart_has_bookable_products()) {
            return;
        }
        
        if (empty($_POST['hamdy_timezone'])) {
            wc_add_notice(__('Please select your timezone.', 'hamdy-plugin'), 'error');
        }
        
        if (empty($_POST['hamdy_gender_age_group'])) {
            wc_add_notice(__('Please select your category.', 'hamdy-plugin'), 'error');
        }
        
        if (empty($_POST['hamdy_selected_slots'])) {
            wc_add_notice(__('Please select at least one time slot.', 'hamdy-plugin'), 'error');
        }
    }
    
    /**
     * Save checkout fields
     */
    public function save_checkout_fields($order_id) {
        if (!$this->cart_has_bookable_products()) {
            return;
        }
        
        if (!empty($_POST['hamdy_timezone'])) {
            update_post_meta($order_id, '_hamdy_timezone', sanitize_text_field($_POST['hamdy_timezone']));
        }
        
        if (!empty($_POST['hamdy_gender_age_group'])) {
            update_post_meta($order_id, '_hamdy_gender_age_group', sanitize_text_field($_POST['hamdy_gender_age_group']));
        }
        
        if (!empty($_POST['hamdy_selected_slots'])) {
            $selected_slots = json_decode(stripslashes($_POST['hamdy_selected_slots']), true);
            update_post_meta($order_id, '_hamdy_selected_slots', $selected_slots);
            
            // Create booking record
            $order = wc_get_order($order_id);
            $booking_data = array(
                'order_id' => $order_id,
                'customer_id' => $order->get_customer_id(),
                'timezone' => sanitize_text_field($_POST['hamdy_timezone']),
                'gender_age_group' => sanitize_text_field($_POST['hamdy_gender_age_group']),
                'selected_slots' => $selected_slots,
                'booking_date' => current_time('Y-m-d'),
                'booking_time' => current_time('H:i:s'),
                'status' => 'pending',
                'notes' => ''
            );
            
            Hamdy_Booking::create($booking_data);
        }
    }
    
    /**
     * Display fields in admin order
     */
    public function display_admin_order_meta($order) {
        $timezone = get_post_meta($order->get_id(), '_hamdy_timezone', true);
        $gender_age_group = get_post_meta($order->get_id(), '_hamdy_gender_age_group', true);
        $selected_slots = get_post_meta($order->get_id(), '_hamdy_selected_slots', true);
        
        if ($timezone || $gender_age_group || $selected_slots) {
            echo '<h3>' . __('Booking Details', 'hamdy-plugin') . '</h3>';
            
            if ($timezone) {
                echo '<p><strong>' . __('Timezone:', 'hamdy-plugin') . '</strong> ' . esc_html($timezone) . '</p>';
            }
            
            if ($gender_age_group) {
                echo '<p><strong>' . __('Category:', 'hamdy-plugin') . '</strong> ' . esc_html($gender_age_group) . '</p>';
            }
            
            if ($selected_slots) {
                echo '<p><strong>' . __('Selected Time Slots:', 'hamdy-plugin') . '</strong></p>';
                echo '<ul>';
                foreach ($selected_slots as $slot) {
                    echo '<li>' . esc_html($slot['day'] . ' - ' . $slot['time']) . '</li>';
                }
                echo '</ul>';
            }
        }
    }
    
    /**
     * Add order meta to email
     */
    public function add_order_meta_to_email($order, $sent_to_admin, $plain_text) {
        $timezone = get_post_meta($order->get_id(), '_hamdy_timezone', true);
        $gender_age_group = get_post_meta($order->get_id(), '_hamdy_gender_age_group', true);
        $selected_slots = get_post_meta($order->get_id(), '_hamdy_selected_slots', true);
        
        if ($timezone || $gender_age_group || $selected_slots) {
            if ($plain_text) {
                echo "\n" . __('Booking Details:', 'hamdy-plugin') . "\n";
                if ($timezone) echo __('Timezone:', 'hamdy-plugin') . ' ' . $timezone . "\n";
                if ($gender_age_group) echo __('Category:', 'hamdy-plugin') . ' ' . $gender_age_group . "\n";
                if ($selected_slots) {
                    echo __('Selected Time Slots:', 'hamdy-plugin') . "\n";
                    foreach ($selected_slots as $slot) {
                        echo '- ' . $slot['day'] . ' - ' . $slot['time'] . "\n";
                    }
                }
            } else {
                echo '<h3>' . __('Booking Details', 'hamdy-plugin') . '</h3>';
                if ($timezone) echo '<p><strong>' . __('Timezone:', 'hamdy-plugin') . '</strong> ' . esc_html($timezone) . '</p>';
                if ($gender_age_group) echo '<p><strong>' . __('Category:', 'hamdy-plugin') . '</strong> ' . esc_html($gender_age_group) . '</p>';
                if ($selected_slots) {
                    echo '<p><strong>' . __('Selected Time Slots:', 'hamdy-plugin') . '</strong></p>';
                    echo '<ul>';
                    foreach ($selected_slots as $slot) {
                        echo '<li>' . esc_html($slot['day'] . ' - ' . $slot['time']) . '</li>';
                    }
                    echo '</ul>';
                }
            }
        }
    }
    
    /**
     * Check if cart has bookable products
     */
    private function cart_has_bookable_products() {
        if (!WC()->cart) {
            return false;
        }
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if (get_post_meta($product->get_id(), '_hamdy_bookable', true) === 'yes') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get timezone options
     */
    private function get_timezone_options() {
        $timezones = array(
            '' => __('Select timezone', 'hamdy-plugin'),
            'UTC' => 'UTC',
            'America/New_York' => 'Eastern Time (ET)',
            'America/Chicago' => 'Central Time (CT)',
            'America/Denver' => 'Mountain Time (MT)',
            'America/Los_Angeles' => 'Pacific Time (PT)',
            'Europe/London' => 'London (GMT)',
            'Europe/Paris' => 'Paris (CET)',
            'Europe/Berlin' => 'Berlin (CET)',
            'Asia/Tokyo' => 'Tokyo (JST)',
            'Asia/Shanghai' => 'Shanghai (CST)',
            'Asia/Dubai' => 'Dubai (GST)',
            'Africa/Cairo' => 'Cairo (EET)',
            'Australia/Sydney' => 'Sydney (AEST)'
        );
        
        return apply_filters('hamdy_timezone_options', $timezones);
    }
    
    /**
     * Add booking field to product admin
     */
    public function add_product_booking_field() {
        global $post;
        
        echo '<div class="options_group">';
        
        woocommerce_wp_checkbox(array(
            'id' => '_hamdy_bookable',
            'label' => __('Enable Booking', 'hamdy-plugin'),
            'description' => __('Check this box to enable booking fields on checkout for this product.', 'hamdy-plugin'),
            'desc_tip' => true,
            'value' => get_post_meta($post->ID, '_hamdy_bookable', true)
        ));
        
        echo '</div>';
    }
    
    /**
     * Save product booking field
     */
    public function save_product_booking_field($post_id) {
        $bookable = isset($_POST['_hamdy_bookable']) ? 'yes' : 'no';
        update_post_meta($post_id, '_hamdy_bookable', $bookable);
    }
}