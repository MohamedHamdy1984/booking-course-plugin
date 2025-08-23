<?php
/**
 * Database management class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SOOB_Database {
    
    /**
     * Create plugin database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Providers table
        $providers_table = $wpdb->prefix . 'soob_providers';
        $providers_sql = "CREATE TABLE $providers_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            photo varchar(255) DEFAULT '',
            gender enum('male','female') NOT NULL,
            availability longtext DEFAULT '',
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Bookings table
        $bookings_table = $wpdb->prefix . 'soob_bookings';
        $bookings_sql = "CREATE TABLE $bookings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id mediumint(9) NOT NULL,
            customer_id mediumint(9) NOT NULL,
            provider_id mediumint(9) DEFAULT NULL,
            timezone varchar(100) NOT NULL,
            customer_gender enum('male','female') NOT NULL,
            customer_age int(3) NOT NULL,
            selected_slots longtext NOT NULL,
            booking_date date NOT NULL,
            purchase_at time NOT NULL,
            next_renewal_date date DEFAULT NULL,
            status enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY customer_id (customer_id),
            KEY provider_id (provider_id),
            KEY booking_date (booking_date),
            KEY next_renewal_date (next_renewal_date),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($providers_sql);
        dbDelta($bookings_sql);
    }
    
    /**
     * Drop plugin database tables
     */
    public static function drop_tables() {
        global $wpdb;

        $providers_table = $wpdb->prefix . 'soob_providers';
        $bookings_table = $wpdb->prefix . 'soob_bookings';

        $wpdb->query("DROP TABLE IF EXISTS $bookings_table");
        $wpdb->query("DROP TABLE IF EXISTS $providers_table");
    }
}