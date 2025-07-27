<?php
/**
 * Test script for booking filters
 * This file should be placed in the plugin root and accessed via browser
 * URL: /wp-content/plugins/hamdy-plugin/test-booking-filters.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

// Load our classes
require_once('includes/class-hamdy-database.php');
require_once('includes/class-hamdy-booking.php');

echo '<h1>Hamdy Plugin - Booking Filter Test</h1>';

// Test 1: Check if table exists and has renewal_date field
global $wpdb;
$table = $wpdb->prefix . 'hamdy_bookings';

echo '<h2>1. Database Table Structure</h2>';
$columns = $wpdb->get_results("DESCRIBE $table");
if ($columns) {
    echo '<table border="1" style="border-collapse: collapse;">';
    echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
    foreach ($columns as $column) {
        echo '<tr>';
        echo '<td>' . $column->Field . '</td>';
        echo '<td>' . $column->Type . '</td>';
        echo '<td>' . $column->Null . '</td>';
        echo '<td>' . $column->Key . '</td>';
        echo '<td>' . $column->Default . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    // Check if renewal_date exists
    $has_renewal_date = false;
    foreach ($columns as $column) {
        if ($column->Field === 'renewal_date') {
            $has_renewal_date = true;
            break;
        }
    }
    
    if ($has_renewal_date) {
        echo '<p style="color: green;">✓ renewal_date field exists</p>';
    } else {
        echo '<p style="color: red;">✗ renewal_date field missing</p>';
    }
} else {
    echo '<p style="color: red;">Table does not exist or cannot be accessed</p>';
}

// Test 2: Check existing bookings
echo '<h2>2. Existing Bookings</h2>';
$all_bookings = Hamdy_Booking::get_all();
echo '<p>Total bookings: ' . count($all_bookings) . '</p>';

if (!empty($all_bookings)) {
    echo '<table border="1" style="border-collapse: collapse;">';
    echo '<tr><th>ID</th><th>Order ID</th><th>Gender</th><th>Age</th><th>Renewal Date</th><th>Status</th></tr>';
    foreach ($all_bookings as $booking) {
        echo '<tr>';
        echo '<td>' . $booking->id . '</td>';
        echo '<td>' . $booking->order_id . '</td>';
        echo '<td>' . $booking->customer_gender . '</td>';
        echo '<td>' . $booking->customer_age . '</td>';
        echo '<td>' . ($booking->renewal_date ?: 'NULL') . '</td>';
        echo '<td>' . $booking->status . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Test 3: Test filters
echo '<h2>3. Filter Tests</h2>';

// Test male filter
$male_bookings = Hamdy_Booking::get_all(['customer_gender' => 'male']);
echo '<p>Male bookings: ' . count($male_bookings) . '</p>';

// Test female filter
$female_bookings = Hamdy_Booking::get_all(['customer_gender' => 'female']);
echo '<p>Female bookings: ' . count($female_bookings) . '</p>';

// Test expiring filter
$expiring_bookings = Hamdy_Booking::get_all(['expiring_soon' => true]);
echo '<p>Expiring bookings: ' . count($expiring_bookings) . '</p>';

// Test 4: Create sample data if none exists
if (empty($all_bookings)) {
    echo '<h2>4. Creating Sample Data</h2>';
    
    $sample_data = [
        [
            'order_id' => 1001,
            'customer_id' => 1,
            'timezone' => 'UTC',
            'customer_gender' => 'male',
            'customer_age' => 25,
            'selected_slots' => ['friday_10'],
            'booking_date' => date('Y-m-d'),
            'booking_time' => '10:00:00',
            'renewal_date' => date('Y-m-d', strtotime('+3 days')), // Expiring soon
            'status' => 'confirmed',
            'notes' => 'Test male booking'
        ],
        [
            'order_id' => 1002,
            'customer_id' => 2,
            'timezone' => 'UTC',
            'customer_gender' => 'female',
            'customer_age' => 30,
            'selected_slots' => ['saturday_14'],
            'booking_date' => date('Y-m-d'),
            'booking_time' => '14:00:00',
            'renewal_date' => date('Y-m-d', strtotime('+10 days')), // Not expiring
            'status' => 'pending',
            'notes' => 'Test female booking'
        ],
        [
            'order_id' => 1003,
            'customer_id' => 3,
            'timezone' => 'UTC',
            'customer_gender' => 'male',
            'customer_age' => 35,
            'selected_slots' => ['sunday_16'],
            'booking_date' => date('Y-m-d'),
            'booking_time' => '16:00:00',
            'renewal_date' => null, // No renewal date
            'status' => 'completed',
            'notes' => 'Test male booking without renewal'
        ]
    ];
    
    foreach ($sample_data as $data) {
        $result = Hamdy_Booking::create($data);
        if ($result) {
            echo '<p style="color: green;">✓ Created booking with ID: ' . $result . '</p>';
        } else {
            echo '<p style="color: red;">✗ Failed to create booking</p>';
        }
    }
    
    echo '<p><a href="' . $_SERVER['PHP_SELF'] . '">Refresh to see results</a></p>';
}

echo '<h2>5. Admin Page Links</h2>';
echo '<p><a href="' . admin_url('admin.php?page=hamdy-bookings') . '" target="_blank">View Bookings (All)</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=hamdy-bookings&tab=male') . '" target="_blank">View Bookings (Male)</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=hamdy-bookings&tab=female') . '" target="_blank">View Bookings (Female)</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=hamdy-bookings&tab=expiring') . '" target="_blank">View Bookings (Expiring)</a></p>';

echo '<hr>';
echo '<p><strong>Instructions:</strong></p>';
echo '<ol>';
echo '<li>Check the database structure above to ensure renewal_date field exists</li>';
echo '<li>If no bookings exist, sample data will be created automatically</li>';
echo '<li>Click the admin page links above to test the filtering</li>';
echo '<li>Check your WordPress debug.log for query debugging information</li>';
echo '</ol>';
?>