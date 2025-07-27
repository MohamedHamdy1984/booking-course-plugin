<?php
/**
 * Database management class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Hamdy_Database {
    
    /**
     * Create plugin database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Teachers table
        $teachers_table = $wpdb->prefix . 'hamdy_teachers';
        $teachers_sql = "CREATE TABLE $teachers_table (
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
        $bookings_table = $wpdb->prefix . 'hamdy_bookings';
        $bookings_sql = "CREATE TABLE $bookings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id mediumint(9) NOT NULL,
            customer_id mediumint(9) NOT NULL,
            teacher_id mediumint(9) DEFAULT NULL,
            timezone varchar(100) NOT NULL,
            customer_gender enum('male','female') NOT NULL,
            customer_age int(3) NOT NULL,
            selected_slots longtext NOT NULL,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            renewal_date date DEFAULT NULL,
            status enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
            notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY customer_id (customer_id),
            KEY teacher_id (teacher_id),
            KEY booking_date (booking_date),
            KEY renewal_date (renewal_date),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($teachers_sql);
        dbDelta($bookings_sql);
    }
    
    /**
     * Drop plugin database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $teachers_table = $wpdb->prefix . 'hamdy_teachers';
        $bookings_table = $wpdb->prefix . 'hamdy_bookings';
        
        $wpdb->query("DROP TABLE IF EXISTS $bookings_table");
        $wpdb->query("DROP TABLE IF EXISTS $teachers_table");
    }
}