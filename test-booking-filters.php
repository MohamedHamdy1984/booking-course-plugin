<?php
/**
 * Test script for booking filters
 * This file should be placed in the plugin root and accessed via browser
 * URL: /wp-content/plugins/soob-plugin/test-booking-filters.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

// Load our classes
require_once('includes/class-soob-database.php');
require_once('includes/class-soob-booking.php');

echo '<h1>Soob Plugin - Booking Filter Test</h1>';

// Test 1: Check if table exists and has next_renewal_date field
global $wpdb;
$table = $wpdb->prefix . 'soob_bookings';

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
    
    // Check if next_renewal_date exists
    $has_next_renewal_date = false;
    foreach ($columns as $column) {
        if ($column->Field === 'next_renewal_date') {
            $has_next_renewal_date = true;
            break;
        }
    }
    
    if ($has_next_renewal_date) {
        echo '<p style="color: green;">✓ next_renewal_date field exists</p>';
    } else {
        echo '<p style="color: red;">✗ next_renewal_date field missing</p>';
    }
} else {
    echo '<p style="color: red;">Table does not exist or cannot be accessed</p>';
}

// Test 2: Check existing bookings
echo '<h2>2. Existing Bookings</h2>';
$all_bookings = SOOB_Booking::get_all();
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
        echo '<td>' . ($booking->next_renewal_date ?: 'NULL') . '</td>';
        echo '<td>' . $booking->status . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Test 3: Test filters
echo '<h2>3. Filter Tests</h2>';

// Test male filter
$male_bookings = SOOB_Booking::get_all(['customer_gender' => 'male']);
echo '<p>Male bookings: ' . count($male_bookings) . '</p>';

// Test female filter
$female_bookings = SOOB_Booking::get_all(['customer_gender' => 'female']);
echo '<p>Female bookings: ' . count($female_bookings) . '</p>';

// Test expiring filter
$expiring_bookings = SOOB_Booking::get_all(['expiring_soon' => true]);
echo '<p>Expiring bookings: ' . count($expiring_bookings) . '</p>';

// Test 4: Create sample data if none exists

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
        'purchase_at' => '10:00:00',
        'next_renewal_date' => date('Y-m-d', strtotime('+3 days')),
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
        'purchase_at' => '14:00:00',
        'next_renewal_date' => date('Y-m-d', strtotime('+10 days')),
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
        'purchase_at' => '16:00:00',
        'next_renewal_date' => null,
        'status' => 'completed',
        'notes' => 'Test male booking without renewal'
    ],
    [
        'order_id' => 1004,
        'customer_id' => 4,
        'timezone' => 'UTC',
        'customer_gender' => 'female',
        'customer_age' => 22,
        'selected_slots' => ['monday_09'],
        'booking_date' => date('Y-m-d'),
        'purchase_at' => '09:00:00',
        'next_renewal_date' => date('Y-m-d', strtotime('+2 days')),
        'status' => 'confirmed',
        'notes' => 'Expiring soon female'
    ],
    [
        'order_id' => 1005,
        'customer_id' => 5,
        'timezone' => 'UTC',
        'customer_gender' => 'male',
        'customer_age' => 40,
        'selected_slots' => ['tuesday_11'],
        'booking_date' => date('Y-m-d'),
        'purchase_at' => '11:00:00',
        'next_renewal_date' => date('Y-m-d', strtotime('+7 days')),
        'status' => 'pending',
        'notes' => 'Test male not expiring'
    ],
    [
        'order_id' => 1006,
        'customer_id' => 6,
        'timezone' => 'UTC',
        'customer_gender' => 'female',
        'customer_age' => 29,
        'selected_slots' => ['wednesday_15'],
        'booking_date' => date('Y-m-d'),
        'purchase_at' => '15:00:00',
        'next_renewal_date' => null,
        'status' => 'completed',
        'notes' => 'Test female no renewal'
    ],
    [
        'order_id' => 1007,
        'customer_id' => 7,
        'timezone' => 'UTC',
        'customer_gender' => 'male',
        'customer_age' => 28,
        'selected_slots' => ['thursday_13'],
        'booking_date' => date('Y-m-d'),
        'purchase_at' => '13:00:00',
        'next_renewal_date' => date('Y-m-d', strtotime('+1 day')),
        'status' => 'confirmed',
        'notes' => 'Urgent male booking'
    ],
    [
        'order_id' => 1008,
        'customer_id' => 8,
        'timezone' => 'UTC',
        'customer_gender' => 'female',
        'customer_age' => 34,
        'selected_slots' => ['friday_12'],
        'booking_date' => date('Y-m-d'),
        'purchase_at' => '12:00:00',
        'next_renewal_date' => date('Y-m-d', strtotime('+6 days')),
        'status' => 'confirmed',
        'notes' => 'Normal female booking'
    ],
    [
        'order_id' => 1009,
        'customer_id' => 9,
        'timezone' => 'UTC',
        'customer_gender' => 'male',
        'customer_age' => 45,
        'selected_slots' => ['saturday_16'],
        'booking_date' => date('Y-m-d'),
        'purchase_at' => '16:00:00',
        'next_renewal_date' => date('Y-m-d', strtotime('+5 days')),
        'status' => 'pending',
        'notes' => 'Expiring borderline'
    ],
    [
        'order_id' => 1010,
        'customer_id' => 10,
        'timezone' => 'UTC',
        'customer_gender' => 'female',
        'customer_age' => 31,
        'selected_slots' => ['sunday_17'],
        'booking_date' => date('Y-m-d'),
        'purchase_at' => '17:00:00',
        'next_renewal_date' => null,
        'status' => 'completed',
        'notes' => 'Old booking female'
    ],
    [
        'order_id' => 1011,
        'customer_id' => 11,
        'timezone' => 'UTC',
        'customer_gender' => 'male',
        'customer_age' => 38,
        'selected_slots' => ['monday_10'],
        'booking_date' => date('Y-m-d'),
        'purchase_at' => '10:00:00',
        'next_renewal_date' => date('Y-m-d', strtotime('+2 days')),
        'status' => 'confirmed',
        'notes' => 'Short renewal male'
    ],
    [
        'order_id' => 1012,
        'customer_id' => 12,
        'timezone' => 'UTC',
        'customer_gender' => 'female',
        'customer_age' => 27,
        'selected_slots' => ['tuesday_14'],
        'booking_date' => date('Y-m-d'),
        'purchase_at' => '14:00:00',
        'next_renewal_date' => date('Y-m-d', strtotime('+12 days')),
        'status' => 'pending',
        'notes' => 'Test future female booking'
    ]
];

    foreach ($sample_data as $data) {
        $result = SOOB_Booking::create($data);
        if ($result) {
            echo '<p style="color: green;">✓ Created booking with ID: ' . $result . '</p>';
        } else {
            echo '<p style="color: red;">✗ Failed to create booking</p>';
        }
    }
    
    echo '<p><a href="' . $_SERVER['PHP_SELF'] . '">Refresh to see results</a></p>';


echo '<h2>5. Admin Page Links</h2>';
echo '<p><a href="' . admin_url('admin.php?page=soob-bookings') . '" target="_blank">View Bookings (All)</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=soob-bookings&tab=male') . '" target="_blank">View Bookings (Male)</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=soob-bookings&tab=female') . '" target="_blank">View Bookings (Female)</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=soob-bookings&tab=expiring') . '" target="_blank">View Bookings (Expiring)</a></p>';

echo '<hr>';
echo '<p><strong>Instructions:</strong></p>';
echo '<ol>';
echo '<li>Check the database structure above to ensure next_renewal_date field exists</li>';
echo '<li>If no bookings exist, sample data will be created automatically</li>';
echo '<li>Click the admin page links above to test the filtering</li>';
echo '<li>Check your WordPress debug.log for query debugging information</li>';
echo '</ol>';
?>