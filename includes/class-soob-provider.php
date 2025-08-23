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
     * Validate database connection before performing operations
     * Prevents fatal errors from null $wpdb object
     */
    private static function validate_database_connection() {
        global $wpdb;
        
        if (!$wpdb || !is_object($wpdb)) {
            error_log('SOOB Provider: Database connection not available');
            return false;
        }
        
        // Additional check for WordPress database prefix
        if (!property_exists($wpdb, 'prefix') || empty($wpdb->prefix)) {
            error_log('SOOB Provider: Database prefix not available');
            return false;
        }
        
        return true;
    }
    
    /**
     * Get all providers with database validation and error handling
     */
    public static function get_all($status = 'active') {
        global $wpdb;
        
        // Validate database connection first to prevent fatal errors
        if (!self::validate_database_connection()) {
            error_log('SOOB Provider: get_all() failed - invalid database connection');
            return array(); // Return empty array instead of null to prevent foreach errors
        }
        
        $table = $wpdb->prefix . 'soob_providers';
        
        try {
            // Updated database/query logic to return every provider regardless of status when empty string passed
            $where = (!empty($status)) ? $wpdb->prepare("WHERE status = %s", $status) : '';
            
            $results = $wpdb->get_results("SELECT * FROM $table $where ORDER BY name ASC");
            
            // Handle query errors gracefully
            if ($wpdb->last_error) {
                error_log('SOOB Provider: get_all() database error: ' . $wpdb->last_error);
                return array();
            }
            
            return $results ?: array();
            
        } catch (Exception $e) {
            error_log('SOOB Provider: get_all() exception: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get provider by ID with comprehensive error handling
     * Prevents fatal errors when database is unavailable or provider doesn't exist
     */
    public static function get_by_id($id) {
        global $wpdb;
        
        // Validate input parameter
        if (!$id || !is_numeric($id) || $id <= 0) {
            error_log('SOOB Provider: get_by_id() called with invalid ID: ' . var_export($id, true));
            return null;
        }
        
        // Validate database connection to prevent fatal errors
        if (!self::validate_database_connection()) {
            error_log('SOOB Provider: get_by_id() failed - invalid database connection for ID: ' . $id);
            return null;
        }
        
        try {
            $table = $wpdb->prefix . 'soob_providers';
            $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
            
            // Check for database query errors
            if ($wpdb->last_error) {
                error_log('SOOB Provider: get_by_id() database error for ID ' . $id . ': ' . $wpdb->last_error);
                return null;
            }
            
            // Log when provider is not found (for debugging)
            if (!$result) {
                error_log('SOOB Provider: get_by_id() - provider not found for ID: ' . $id);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('SOOB Provider: get_by_id() exception for ID ' . $id . ': ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new provider with database validation and error handling
     */
    public static function create($data) {
        global $wpdb;
        
        // Validate database connection
        if (!self::validate_database_connection()) {
            error_log('SOOB Provider: create() failed - invalid database connection');
            return false;
        }
        
        // Validate required data
        if (empty($data['name']) || empty($data['gender'])) {
            error_log('SOOB Provider: create() failed - missing required fields');
            return false;
        }
        
        try {
            $table = $wpdb->prefix . 'soob_providers';
            
            $result = $wpdb->insert(
                $table,
                array(
                    'name' => sanitize_text_field($data['name']),
                    'photo' => sanitize_url($data['photo']),
                    'gender' => sanitize_text_field($data['gender']),
                    'timezone' => sanitize_text_field($data['timezone']),
                    'availability' => wp_json_encode($data['availability']),
                    'status' => sanitize_text_field($data['status'])
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($wpdb->last_error) {
                error_log('SOOB Provider: create() database error: ' . $wpdb->last_error);
                return false;
            }
            
            return $result ? $wpdb->insert_id : false;
            
        } catch (Exception $e) {
            error_log('SOOB Provider: create() exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update provider with database validation and error handling
     */
    public static function update($id, $data) {
        global $wpdb;
        
        // Validate database connection
        if (!self::validate_database_connection()) {
            error_log('SOOB Provider: update() failed - invalid database connection for ID: ' . $id);
            return false;
        }
        
        // Validate input parameters
        if (!$id || !is_numeric($id) || $id <= 0) {
            error_log('SOOB Provider: update() called with invalid ID: ' . var_export($id, true));
            return false;
        }
        
        if (empty($data) || !is_array($data)) {
            error_log('SOOB Provider: update() called with invalid data for ID: ' . $id);
            return false;
        }
        
        try {
            $table = $wpdb->prefix . 'soob_providers';
            
            $result = $wpdb->update(
                $table,
                array(
                    'name' => sanitize_text_field($data['name']),
                    'photo' => sanitize_url($data['photo']),
                    'gender' => sanitize_text_field($data['gender']),
                    'timezone' => sanitize_text_field($data['timezone']),
                    'availability' => wp_json_encode($data['availability']),
                    'status' => sanitize_text_field($data['status'])
                ),
                array('id' => $id),
                array('%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($wpdb->last_error) {
                error_log('SOOB Provider: update() database error for ID ' . $id . ': ' . $wpdb->last_error);
                return false;
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('SOOB Provider: update() exception for ID ' . $id . ': ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete provider with database validation and error handling
     */
    public static function delete($id) {
        global $wpdb;
        
        // Validate database connection
        if (!self::validate_database_connection()) {
            error_log('SOOB Provider: delete() failed - invalid database connection for ID: ' . $id);
            return false;
        }
        
        // Validate input parameter
        if (!$id || !is_numeric($id) || $id <= 0) {
            error_log('SOOB Provider: delete() called with invalid ID: ' . var_export($id, true));
            return false;
        }
        
        try {
            $table = $wpdb->prefix . 'soob_providers';
            $result = $wpdb->delete($table, array('id' => $id), array('%d'));
            
            if ($wpdb->last_error) {
                error_log('SOOB Provider: delete() database error for ID ' . $id . ': ' . $wpdb->last_error);
                return false;
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('SOOB Provider: delete() exception for ID ' . $id . ': ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available time slots for specific criteria with database validation
     */
    public static function get_available_slots($customer_gender, $day_of_week) {
        global $wpdb;
        
        // Validate database connection
        if (!self::validate_database_connection()) {
            error_log('SOOB Provider: get_available_slots() failed - invalid database connection');
            return array(); // Return empty array to prevent errors in calling code
        }
        
        // Validate input parameters
        if (empty($customer_gender) || empty($day_of_week)) {
            error_log('SOOB Provider: get_available_slots() called with invalid parameters');
            return array();
        }
        
        try {
            $table = $wpdb->prefix . 'soob_providers';
            $providers = $wpdb->get_results($wpdb->prepare(
                "SELECT availability FROM $table WHERE status = 'active' AND gender = %s",
                $customer_gender
            ));
            
            if ($wpdb->last_error) {
                error_log('SOOB Provider: get_available_slots() database error: ' . $wpdb->last_error);
                return array();
            }
            
            $available_slots = array();
            
            // Safely process provider availability data
            if ($providers && is_array($providers)) {
                foreach ($providers as $provider) {
                    if (!empty($provider->availability)) {
                        $availability = json_decode($provider->availability, true);
                        // Validate JSON decode was successful
                        if (is_array($availability) && isset($availability[$day_of_week]) && is_array($availability[$day_of_week])) {
                            $available_slots = array_merge($available_slots, $availability[$day_of_week]);
                        }
                    }
                }
            }
            
            return array_unique($available_slots);
            
        } catch (Exception $e) {
            error_log('SOOB Provider: get_available_slots() exception: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get provider timezone with comprehensive fallbacks and error handling
     *
     * Auto-fetches the provider's timezone when opening the provider page.
     * Fallback order: provider timezone -> current user timezone -> site timezone -> UTC
     * Validates timezone using timezone_identifiers_list()
     *
     * CRITICAL FIX: Handles database connection failures and invalid provider IDs gracefully
     * to prevent fatal errors that were occurring in the original implementation
     */
    public static function get_provider_timezone($provider_id = null) {
        $timezone = null;
        
        // First try: Get provider's stored timezone if provider ID is provided
        // Enhanced with comprehensive error handling to prevent fatal errors
        if ($provider_id && is_numeric($provider_id) && $provider_id > 0) {
            try {
                // This call is now safe because get_by_id() has comprehensive error handling
                $provider = self::get_by_id($provider_id);
                
                // Safely access provider timezone with null checking
                if ($provider && is_object($provider) && property_exists($provider, 'timezone') && !empty($provider->timezone)) {
                    $timezone = $provider->timezone;
                    error_log('SOOB Provider: Using provider timezone: ' . $timezone . ' for ID: ' . $provider_id);
                } else {
                    error_log('SOOB Provider: Provider timezone not available for ID: ' . $provider_id . ', falling back to user/site timezone');
                }
            } catch (Exception $e) {
                error_log('SOOB Provider: Exception getting provider timezone for ID ' . $provider_id . ': ' . $e->getMessage());
                // Continue to fallback options instead of failing
            }
        } else if ($provider_id !== null) {
            error_log('SOOB Provider: Invalid provider ID provided: ' . var_export($provider_id, true));
        }
        
        // Second try: Get current user's timezone from user meta with error handling
        if (empty($timezone)) {
            try {
                if (function_exists('get_current_user_id')) {
                    $current_user_id = get_current_user_id();
                    if ($current_user_id && is_numeric($current_user_id) && $current_user_id > 0) {
                        if (function_exists('get_user_meta')) {
                            $user_timezone = get_user_meta($current_user_id, 'timezone', true);
                            if (!empty($user_timezone) && is_string($user_timezone)) {
                                $timezone = $user_timezone;
                                error_log('SOOB Provider: Using user timezone: ' . $timezone . ' for user ID: ' . $current_user_id);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('SOOB Provider: Exception getting user timezone: ' . $e->getMessage());
            }
        }
        
        // Third try: Get site timezone setting with enhanced Middle East/Cairo detection
        if (empty($timezone)) {
            try {
                if (function_exists('get_option')) {
                    $site_timezone = get_option('timezone_string');
                    if (!empty($site_timezone) && is_string($site_timezone)) {
                        $timezone = $site_timezone;
                        error_log('SOOB Provider: Using site timezone: ' . $timezone);
                    } else {
                        // Handle manual UTC offset format with Cairo-specific logic
                        $gmt_offset = get_option('gmt_offset');
                        if ($gmt_offset !== false && is_numeric($gmt_offset)) {
                            // Enhanced offset to timezone mapping for Middle East
                            $timezone = self::enhanced_offset_to_timezone($gmt_offset);
                            error_log('SOOB Provider: Converted GMT offset ' . $gmt_offset . ' to timezone: ' . $timezone);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('SOOB Provider: Exception getting site timezone: ' . $e->getMessage());
            }
        }
        
        // Fourth try: Detect based on server location/environment (Cairo specific)
        if (empty($timezone)) {
            try {
                // Try to detect based on server date settings
                $server_timezone = date_default_timezone_get();
                if (!empty($server_timezone) && self::is_valid_timezone($server_timezone)) {
                    $timezone = $server_timezone;
                    error_log('SOOB Provider: Using server timezone: ' . $timezone);
                }
            } catch (Exception $e) {
                error_log('SOOB Provider: Exception getting server timezone: ' . $e->getMessage());
            }
        }
        
        // Final fallback: UTC
        if (empty($timezone) || !is_string($timezone)) {
            $timezone = 'UTC';
            error_log('SOOB Provider: Using final fallback timezone: UTC');
        }
        
        // Validate timezone using timezone_identifiers_list() to ensure it's valid
        if (!self::is_valid_timezone($timezone)) {
            error_log("SOOB Provider: Invalid timezone detected: " . $timezone . ". Falling back to UTC.");
            $timezone = 'UTC';
        }
        
        return $timezone;
    }
    
    /**
     * Validate timezone against PHP's timezone_identifiers_list()
     */
    public static function is_valid_timezone($timezone) {
        if (empty($timezone)) {
            return false;
        }
        
        // Get all valid timezone identifiers from PHP
        $valid_timezones = timezone_identifiers_list();
        return in_array($timezone, $valid_timezones, true);
    }
    
    /**
     * Enhanced offset to timezone conversion with Cairo/Middle East specific mapping
     * Addresses the Cairo UTC+1 vs UTC+3 issue
     */
    private static function enhanced_offset_to_timezone($offset) {
        // Enhanced mapping for common timezones, with Cairo-specific fix
        $offset_mapping = array(
            '3' => 'Africa/Cairo',        // Egypt Standard Time (Cairo) - CAIRO FIX
            '3.5' => 'Asia/Tehran',       // Iran Standard Time
            '4' => 'Asia/Dubai',          // Gulf Standard Time
            '2' => 'Europe/Berlin',       // Central European Time
            '1' => 'Europe/London',       // British Time / Central European Time
            '0' => 'UTC',                 // Coordinated Universal Time
            '-1' => 'Atlantic/Azores',    // Azores Time
            '-3' => 'America/Sao_Paulo',  // Brazil Time
            '-5' => 'America/New_York',   // Eastern Time
            '-6' => 'America/Chicago',    // Central Time
            '-7' => 'America/Denver',     // Mountain Time
            '-8' => 'America/Los_Angeles', // Pacific Time
        );
        
        // Check direct mapping first
        $offset_str = (string)$offset;
        if (isset($offset_mapping[$offset_str])) {
            error_log('SOOB Provider: Direct offset mapping found - offset ' . $offset . ' -> ' . $offset_mapping[$offset_str]);
            return $offset_mapping[$offset_str];
        }
        
        // Try PHP's built-in function as fallback
        try {
            $seconds = $offset * 3600;
            $timezone_name = timezone_name_from_abbr('', $seconds, 0);
            if ($timezone_name !== false && self::is_valid_timezone($timezone_name)) {
                error_log('SOOB Provider: PHP built-in offset mapping - offset ' . $offset . ' -> ' . $timezone_name);
                return $timezone_name;
            }
        } catch (Exception $e) {
            error_log('SOOB Provider: Failed to convert offset to timezone using PHP built-in: ' . $e->getMessage());
        }
        
        // Final fallback: return UTC
        error_log('SOOB Provider: No timezone mapping found for offset ' . $offset . ', using UTC fallback');
        return 'UTC';
    }
    
    /**
     * Convert GMT offset to timezone identifier
     * Helper function for sites using manual UTC offset instead of timezone string
     * (Legacy method that now uses enhanced mapping)
     */
    private static function offset_to_timezone($offset) {
        return self::enhanced_offset_to_timezone($offset);
    }
}