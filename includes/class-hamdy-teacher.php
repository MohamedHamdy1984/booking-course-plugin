<?php
/**
 * Teacher management class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Hamdy_Teacher {
    
    /**
     * Get all teachers
     */
    public static function get_all($status = 'active') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_teachers';
        $where = $status ? $wpdb->prepare("WHERE status = %s", $status) : '';
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY name ASC");
    }
    
    /**
     * Get teacher by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_teachers';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    /**
     * Create new teacher
     */
    public static function create($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_teachers';
        
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
     * Update teacher
     */
    public static function update($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_teachers';
        
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
     * Delete teacher
     */
    public static function delete($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_teachers';
        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }
    
    /**
     * Get available time slots for specific criteria
     */
    public static function get_available_slots($customer_gender, $day_of_week) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hamdy_teachers';
        $teachers = $wpdb->get_results($wpdb->prepare(
            "SELECT availability FROM $table WHERE status = 'active' AND gender = %s",
            $customer_gender
        ));
        
        $available_slots = array();
        
        foreach ($teachers as $teacher) {
            $availability = json_decode($teacher->availability, true);
            if (isset($availability[$day_of_week])) {
                $available_slots = array_merge($available_slots, $availability[$day_of_week]);
            }
        }
        
        return array_unique($available_slots);
    }  
}