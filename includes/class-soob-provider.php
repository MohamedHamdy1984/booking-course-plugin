<?php
/**
 * Provider management class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SOOB_Provider {
    
    /**
     * Get all providers
     */
    public static function get_all($status = 'active') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soob_providers';
        // Updated database/query logic to return every provider regardless of status when empty string passed
        $where = (!empty($status)) ? $wpdb->prepare("WHERE status = %s", $status) : '';
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY name ASC");
    }
    
    /**
     * Get provider by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soob_providers';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    /**
     * Create new provider
     */
    public static function create($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soob_providers';
        
        $result = $wpdb->insert(
            $table,
            array(
                'name' => sanitize_text_field($data['name']),
                'photo' => sanitize_url($data['photo']),
                'gender' => sanitize_text_field($data['gender']),
                'availability' => wp_json_encode($data['availability']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update provider
     */
    public static function update($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soob_providers';
        
        return $wpdb->update(
            $table,
            array(
                'name' => sanitize_text_field($data['name']),
                'photo' => sanitize_url($data['photo']),
                'gender' => sanitize_text_field($data['gender']),
                'availability' => wp_json_encode($data['availability']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Delete provider
     */
    public static function delete($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soob_providers';
        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }
    
    /**
     * Get available time slots for specific criteria
     */
    public static function get_available_slots($customer_gender, $day_of_week) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'soob_providers';
        $providers = $wpdb->get_results($wpdb->prepare(
            "SELECT availability FROM $table WHERE status = 'active' AND gender = %s",
            $customer_gender
        ));
        
        $available_slots = array();
        
        foreach ($providers as $provider) {
            $availability = json_decode($provider->availability, true);
            if (isset($availability[$day_of_week])) {
                $available_slots = array_merge($available_slots, $availability[$day_of_week]);
            }
        }
        
        return array_unique($available_slots);
    }  
}