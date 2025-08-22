<?php

/**
 * WooCommerce integration class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}



class SOOB_WooCommerce
{

    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {

        // Product admin settings
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_booking_field'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_booking_field'));

        // Add checkout fields
        add_action('woocommerce_before_order_notes', array($this, 'add_checkout_fields'));

        // Validate checkout fields
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout_fields'));

        // Save checkout fields
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_checkout_fields'));

        // Display fields in admin order
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_admin_order_meta'));

        // Add order meta to emails
        add_action('woocommerce_email_order_meta', array($this, 'add_order_meta_to_email'), 10, 3);
    }


    /**
     * Add booking field to product admin
     */
    public function add_product_booking_field()
    {
        global $post;

        echo '<div class="options_group">';

        woocommerce_wp_checkbox(array(
            'id' => '_soob_bookable',
            'label' => __('Enable Booking', 'soob-plugin'),
            'description' => __('Check this box to enable booking fields on checkout for this product.', 'soob-plugin'),
            'desc_tip' => true,
            'value' => get_post_meta($post->ID, '_soob_bookable', true)
        ));

        echo '</div>';
    }

    /**
     * Save product booking field
     */
    public function save_product_booking_field($post_id)
    {
        $bookable = isset($_POST['_soob_bookable']) ? 'yes' : 'no';
        update_post_meta($post_id, '_soob_bookable', $bookable);
    }

    /**
     * Add checkout fields
     */
    public function add_checkout_fields($checkout)
    {
        // Check if cart contains bookable products
        if (!$this->cart_has_bookable_products()) {
            return;
        }

        echo '<div id="soob_booking_fields"><h3>' . __('Booking Details', 'soob-plugin') . '</h3>';

        // Timezone field
        woocommerce_form_field('soob_timezone', array(
            'type' => 'select',
            'class' => array('soob-field-wide'),
            'label' => __('Select your timezone', 'soob-plugin'),
            'required' => true,
            'options' => $this->get_timezone_options()
        ), $checkout->get_value('soob_timezone'));

        // Gender field
        woocommerce_form_field('soob_gender', array(
            'type' => 'select',
            'class' => array('soob-field-wide'),
            'label' => __('Select your gender', 'soob-plugin'),
            'required' => true,
            'options' => array(
                '' => __('Choose an option', 'soob-plugin'),
                'male' => __('Male', 'soob-plugin'),
                'female' => __('Female', 'soob-plugin')
            )
        ), $checkout->get_value('soob_gender'));

        // Age field
        woocommerce_form_field('soob_age', array(
            'type' => 'number',
            'class' => array('soob-field-wide'),
            'label' => __('Your age', 'soob-plugin'),
            'required' => true,
            'custom_attributes' => array(
                'min' => 1,
                'max' => 120
            )
        ), $checkout->get_value('soob_age'));

        // Time slots container
        echo '<div id="soob_time_slots_container">';
        echo '<label>' . __('Select available time slots', 'soob-plugin') . ' <abbr class="required" title="required">*</abbr></label>';
        echo '<div id="soob_time_slots_wrapper"></div>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Validate checkout fields
     */
    public function validate_checkout_fields()
    {
        if (!$this->cart_has_bookable_products()) {
            return;
        }

        if (empty($_POST['soob_timezone'])) {
            wc_add_notice(__('Please select your timezone.', 'soob-plugin'), 'error');
        }

        if (empty($_POST['soob_gender'])) {
            wc_add_notice(__('Please select your gender.', 'soob-plugin'), 'error');
        }

        if (empty($_POST['soob_age']) || !is_numeric($_POST['soob_age']) || $_POST['soob_age'] < 1 || $_POST['soob_age'] > 120) {
            wc_add_notice(__('Please enter a valid age (1-120).', 'soob-plugin'), 'error');
        }

        if (empty($_POST['soob_selected_slots'])) {
            wc_add_notice(__('Please select at least one time slot.', 'soob-plugin'), 'error');
        }
    }

    /**
     * Save checkout fields
     */
    public function save_checkout_fields($order_id)
    {
        if (!$this->cart_has_bookable_products()) {
            return;
        }

        if (!empty($_POST['soob_timezone'])) {
            update_post_meta($order_id, '_soob_timezone', sanitize_text_field($_POST['soob_timezone']));
        }

        if (!empty($_POST['soob_gender'])) {
            update_post_meta($order_id, '_soob_gender', sanitize_text_field($_POST['soob_gender']));
        }

        if (!empty($_POST['soob_age'])) {
            update_post_meta($order_id, '_soob_age', intval($_POST['soob_age']));
        }

        if (!empty($_POST['soob_selected_slots'])) {
            $selected_slots = json_decode(stripslashes($_POST['soob_selected_slots']), true);
            update_post_meta($order_id, '_soob_selected_slots', $selected_slots);

            // Create booking record
            $order = wc_get_order($order_id);
            $booking_data = array(
                'order_id' => $order_id,
                'customer_id' => $order->get_customer_id(),
                'timezone' => sanitize_text_field($_POST['soob_timezone']),
                'customer_gender' => sanitize_text_field($_POST['soob_gender']),
                'customer_age' => intval($_POST['soob_age']),
                'selected_slots' => $selected_slots,
                'booking_date' => current_time('Y-m-d'),
                'purchase_at' => current_time('H:i:s'),
                'status' => 'pending',
                'notes' => ''
            );

            SOOB_Booking::create($booking_data);
        }
    }

    /**
     * Display fields in admin order
     */
    public function display_admin_order_meta($order)
    {
        $timezone = get_post_meta($order->get_id(), '_soob_timezone', true);
        $gender = get_post_meta($order->get_id(), '_soob_gender', true);
        $age = get_post_meta($order->get_id(), '_soob_age', true);
        $selected_slots = get_post_meta($order->get_id(), '_soob_selected_slots', true);

        if ($timezone || $gender || $age || $selected_slots) {
            echo '<h3>' . __('Booking Details', 'soob-plugin') . '</h3>';

            if ($timezone) {
                echo '<p><strong>' . __('Timezone:', 'soob-plugin') . '</strong> ' . esc_html($timezone) . '</p>';
            }

            if ($gender) {
                echo '<p><strong>' . __('Gender:', 'soob-plugin') . '</strong> ' . esc_html(ucfirst($gender)) . '</p>';
            }

            if ($age) {
                echo '<p><strong>' . __('Age:', 'soob-plugin') . '</strong> ' . esc_html($age) . '</p>';
            }

            if ($selected_slots) {
                echo '<p><strong>' . __('Selected Time Slots:', 'soob-plugin') . '</strong></p>';
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
    public function add_order_meta_to_email($order, $sent_to_admin, $plain_text)
    {
        $timezone = get_post_meta($order->get_id(), '_soob_timezone', true);
        $gender = get_post_meta($order->get_id(), '_soob_gender', true);
        $age = get_post_meta($order->get_id(), '_soob_age', true);
        $selected_slots = get_post_meta($order->get_id(), '_soob_selected_slots', true);

        if ($timezone || $gender || $age || $selected_slots) {
            if ($plain_text) {
                echo "\n" . __('Booking Details:', 'soob-plugin') . "\n";
                if ($timezone) echo __('Timezone:', 'soob-plugin') . ' ' . $timezone . "\n";
                if ($gender) echo __('Gender:', 'soob-plugin') . ' ' . ucfirst($gender) . "\n";
                if ($age) echo __('Age:', 'soob-plugin') . ' ' . $age . "\n";
                if ($selected_slots) {
                    echo __('Selected Time Slots:', 'soob-plugin') . "\n";
                    foreach ($selected_slots as $slot) {
                        echo '- ' . $slot['day'] . ' - ' . $slot['time'] . "\n";
                    }
                }
            } else {
                echo '<h3>' . __('Booking Details', 'soob-plugin') . '</h3>';
                if ($timezone) echo '<p><strong>' . __('Timezone:', 'soob-plugin') . '</strong> ' . esc_html($timezone) . '</p>';
                if ($gender) echo '<p><strong>' . __('Gender:', 'soob-plugin') . '</strong> ' . esc_html(ucfirst($gender)) . '</p>';
                if ($age) echo '<p><strong>' . __('Age:', 'soob-plugin') . '</strong> ' . esc_html($age) . '</p>';
                if ($selected_slots) {
                    echo '<p><strong>' . __('Selected Time Slots:', 'soob-plugin') . '</strong></p>';
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
    private function cart_has_bookable_products()
    {
        if (!WC()->cart) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if (get_post_meta($product->get_id(), '_soob_bookable', true) === 'yes') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get timezone options
     */
    public function get_timezone_options()
    {
        $base_timezones = array(
            // Americas (UTC-8 to UTC-3)
            'America/Los_Angeles' => 'Pacific Time (Los Angeles, Vancouver)',
            'America/Denver' => 'Mountain Time (Denver, Phoenix)',
            'America/Chicago' => 'Central Time (Chicago, Mexico City)',
            'America/New_York' => 'Eastern Time (New York, Toronto)',
            'America/Sao_Paulo' => 'Brasília Time (São Paulo, Rio)',
            'America/Argentina/Buenos_Aires' => 'Argentina Time (Buenos Aires)',
            
            // Europe & Africa (UTC+0 to UTC+3)
            'UTC' => 'UTC (Coordinated Universal Time)',
            'Europe/London' => 'Greenwich Mean Time (London, Dublin)',
            'Africa/Casablanca' => 'Western European Time (Casablanca)',
            'Europe/Paris' => 'Central European Time (Paris, Madrid)',
            'Europe/Berlin' => 'Central European Time (Berlin, Rome)',
            'Africa/Lagos' => 'West Africa Time (Lagos, Accra)',
            'Africa/Johannesburg' => 'South Africa Standard Time (Johannesburg)',
            'Africa/Cairo' => 'Eastern European Time (Cairo, Athens)',
            'Europe/Istanbul' => 'Turkey Time (Istanbul)',
            'Europe/Moscow' => 'Moscow Time (Moscow)',
            
            // Asia (UTC+3:30 to UTC+9)
            'Asia/Tehran' => 'Iran Standard Time (Tehran)',
            'Asia/Dubai' => 'Gulf Standard Time (Dubai, Abu Dhabi)',
            'Asia/Riyadh' => 'Arabia Standard Time (Riyadh, Kuwait)',
            'Asia/Karachi' => 'Pakistan Standard Time (Karachi, Islamabad)',
            'Asia/Kolkata' => 'India Standard Time (Mumbai, Delhi)',
            'Asia/Dhaka' => 'Bangladesh Standard Time (Dhaka)',
            'Asia/Bangkok' => 'Indochina Time (Bangkok, Ho Chi Minh)',
            'Asia/Shanghai' => 'China Standard Time (Beijing, Shanghai)',
            'Asia/Tokyo' => 'Japan Standard Time (Tokyo, Osaka)',
            'Asia/Seoul' => 'Korea Standard Time (Seoul)',
            
            // Australia & Oceania (UTC+8 to UTC+13)
            'Australia/Perth' => 'Australian Western Time (Perth)',
            'Australia/Sydney' => 'Australian Eastern Time (Sydney, Melbourne)',
            'Pacific/Auckland' => 'New Zealand Standard Time (Auckland)',
        );

        // Add UTC offset to each timezone and sort by offset
        $timezones_with_offset = array();
        $timezone_offsets = array();
        
        foreach ($base_timezones as $timezone_id => $timezone_name) {
            try {
                $tz = new DateTimeZone($timezone_id);
                $now = new DateTime('now', $tz);
                $offset = $now->getOffset();
                $offset_hours = intval($offset / 3600);
                $offset_minutes = abs(($offset % 3600) / 60);
                
                $offset_string = sprintf('UTC%+d', $offset_hours);
                if ($offset_minutes > 0) {
                    $offset_string .= ':' . sprintf('%02d', $offset_minutes);
                }
                
                $display_name = $timezone_name . ' (' . $offset_string . ')';
                $timezones_with_offset[$timezone_id] = $display_name;
                $timezone_offsets[$timezone_id] = $offset;
            } catch (Exception $e) {
                // If timezone calculation fails, use original name and assume UTC
                $timezones_with_offset[$timezone_id] = $timezone_name;
                $timezone_offsets[$timezone_id] = 0;
            }
        }

        // Sort by UTC offset (lowest to highest)
        asort($timezone_offsets);
        
        // Create final sorted array
        $sorted_timezones = array(
            '' => __('Select timezone', 'soob-plugin')
        );
        
        foreach ($timezone_offsets as $timezone_id => $offset) {
            $sorted_timezones[$timezone_id] = $timezones_with_offset[$timezone_id];
        }

        return apply_filters('soob_timezone_options', $sorted_timezones);
    }
}
