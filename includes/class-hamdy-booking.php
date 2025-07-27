<?php
/**
 * Booking management class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Hamdy_Booking {
    
    /**
     * Create new booking
     */
    public static function create($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_bookings';
        
        $result = $wpdb->insert(
            $table,
            array(
                'order_id' => intval($data['order_id']),
                'customer_id' => intval($data['customer_id']),
                'teacher_id' => isset($data['teacher_id']) ? intval($data['teacher_id']) : null,
                'timezone' => sanitize_text_field($data['timezone']),
                'customer_gender' => sanitize_text_field($data['customer_gender']),
                'customer_age' => intval($data['customer_age']),
                'selected_slots' => wp_json_encode($data['selected_slots']),
                'booking_date' => sanitize_text_field($data['booking_date']),
                'booking_time' => sanitize_text_field($data['booking_time']),
                'renewal_date' => isset($data['renewal_date']) ? sanitize_text_field($data['renewal_date']) : null,
                'status' => sanitize_text_field($data['status']),
                'notes' => sanitize_textarea_field($data['notes'])
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get booking by order ID
     */
    public static function get_by_order_id($order_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_bookings';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE order_id = %d", $order_id));
    }
    
    /**
     * Get booking by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_bookings';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    /**
     * Update booking
     */
    public static function update($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_bookings';
        
        $update_data = array();
        $update_format = array();
        
        if (isset($data['teacher_id'])) {
            $update_data['teacher_id'] = intval($data['teacher_id']);
            $update_format[] = '%d';
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $update_format[] = '%s';
        }
        
        if (isset($data['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($data['notes']);
            $update_format[] = '%s';
        }
        
        if (isset($data['booking_date'])) {
            $update_data['booking_date'] = sanitize_text_field($data['booking_date']);
            $update_format[] = '%s';
        }
        
        if (isset($data['booking_time'])) {
            $update_data['booking_time'] = sanitize_text_field($data['booking_time']);
            $update_format[] = '%s';
        }
        
        if (isset($data['renewal_date'])) {
            $update_data['renewal_date'] = sanitize_text_field($data['renewal_date']);
            $update_format[] = '%s';
        }
        
        return $wpdb->update(
            $table,
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );
    }
    
    /**
     * Get all bookings with optional filters
     */
    public static function get_all($filters = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_bookings';
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "status = %s";
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "booking_date >= %s";
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "booking_date <= %s";
            $where_values[] = $filters['date_to'];
        }
        
        if (!empty($filters['teacher_id'])) {
            $where_conditions[] = "teacher_id = %d";
            $where_values[] = $filters['teacher_id'];
        }
        
        if (!empty($filters['customer_gender'])) {
            $where_conditions[] = "customer_gender = %s";
            $where_values[] = $filters['customer_gender'];
        }
        
        if (!empty($filters['expiring_soon'])) {
            $where_conditions[] = "renewal_date IS NOT NULL AND renewal_date <= DATE_ADD(CURDATE(), INTERVAL 5 DAY) AND renewal_date >= CURDATE()";
        }
        
        // Build the query
        $query = "SELECT * FROM $table";
        
        if (!empty($where_conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $query .= ' ORDER BY booking_date DESC, booking_time DESC';
        
        // Prepare query if we have values to bind
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        // Debug: Log the query for troubleshooting
        error_log('Hamdy Booking Query: ' . $query);
        error_log('Hamdy Booking Filters: ' . print_r($filters, true));
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Delete booking
     */
    public static function delete($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_bookings';
        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }
}