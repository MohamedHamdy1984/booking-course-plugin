# Timezone Handling Documentation

## Overview

This document outlines the comprehensive timezone handling system implemented in the Soob Booking Plugin. The system ensures proper timezone conversion between user input, storage, and display across all interfaces.

## Key Principles

### 1. UTC Storage
- **All times are stored in UTC in the database**
- This ensures consistency and eliminates timezone-related data corruption
- Both provider availability and booking times follow this principle

### 2. Display Timezone Conversion
- Times are converted from UTC to the appropriate display timezone when shown to users
- Admin interfaces allow timezone selection for display purposes only
- Customer checkout uses auto-detected or manually selected timezone

### 3. Weekly Recurring Patterns
- Provider availability uses weekly recurring patterns (Sunday-Saturday)
- No specific calendar dates for availability - only day-of-week and time
- Actual bookings will have specific dates when implemented

## Implementation Details

### Frontend (Checkout Page)

#### Auto-Detection
```javascript
// Automatically detects user's timezone using browser API
function autoDetectTimezone() {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone;
    } catch (e) {
        return getFallbackTimezone();
    }
}
```

#### Timezone Options
- Comprehensive list of 25+ global timezones
- Includes major cities and UTC offsets
- Fallback system for unsupported browsers

#### Weekly Slot Display
- Shows days of the week (Sunday-Saturday) instead of specific dates
- Time slots are converted from UTC storage to user's selected timezone
- 3-hour time blocks for better user experience

### Backend (Admin Interfaces)

#### Provider Availability Management
```php
// Convert admin input to UTC before storage
private function convert_availability_to_utc($availability, $admin_timezone) {
    $utc_availability = array();
    foreach ($availability as $day => $slots) {
        foreach ($slots as $slot) {
            // Convert from admin timezone to UTC
            $admin_time = new DateTime($slot, new DateTimeZone($admin_timezone));
            $admin_time->setTimezone(new DateTimeZone('UTC'));
            $utc_availability[$day][] = $admin_time->format('H:i');
        }
    }
    return $utc_availability;
}
```

#### Schedule Overview Display
```php
// Convert UTC storage to display timezone
private function convert_availability_from_utc($availability, $display_timezone) {
    $display_availability = array();
    foreach ($availability as $day => $slots) {
        foreach ($slots as $slot) {
            // Convert from UTC to display timezone
            $utc_time = new DateTime($slot . ' UTC');
            $utc_time->setTimezone(new DateTimeZone($display_timezone));
            $display_availability[$day][] = $utc_time->format('H:i');
        }
    }
    return $display_availability;
}
```

## Database Schema

### Provider Availability Storage
```sql
-- Example of how availability is stored in UTC
availability = {
    "sunday": ["14:00", "17:00", "20:00"],    -- UTC times
    "monday": ["15:00", "18:00"],             -- UTC times
    "tuesday": [],
    -- ... other days
}
```

### Booking Storage
```sql
-- Booking times stored in UTC
purchase_at_utc = "2024-01-15 14:00:00"  -- UTC timestamp
customer_timezone = "America/New_York"     -- For display conversion
```

## User Experience Flow

### 1. Customer Checkout
1. **Auto-detection**: Browser automatically detects user's timezone
2. **Manual Selection**: User can override with dropdown selection
3. **Slot Display**: Available slots shown in user's timezone
4. **Booking Storage**: Selected time converted to UTC before storage

### 2. Admin Provider Management
1. **Timezone Selection**: Admin selects their timezone for input convenience
2. **Time Input**: Admin enters times in their local timezone
3. **UTC Conversion**: Times automatically converted to UTC before storage
4. **Visual Feedback**: Clear indication that times are stored in UTC

### 3. Admin Schedule Overview
1. **Display Timezone**: Admin selects timezone for viewing schedule
2. **Dynamic Conversion**: All times converted from UTC to selected timezone
3. **Weekly Grid**: Shows availability patterns by audience type
4. **Real-time Updates**: AJAX loading with timezone parameter

## Technical Implementation

### Files Modified

#### Frontend Files
- [`assets/js/checkout.js`](assets/js/checkout.js): Auto-detection and weekly slots
- [`public/class-soob-checkout.php`](public/class-soob-checkout.php): UTC conversion logic

#### Admin Files
- [`admin/class-soob-admin-providers.php`](admin/class-soob-admin-providers.php): Timezone input and UTC conversion
- [`admin/class-soob-admin-schedule.php`](admin/class-soob-admin-schedule.php): Schedule display with timezone conversion
- [`assets/js/admin-schedule.js`](assets/js/admin-schedule.js): AJAX handling with timezone parameter
- [`assets/css/admin-schedule.css`](assets/css/admin-schedule.css): Styling for schedule grid

#### Core Files
- [`includes/class-soob-woocommerce.php`](includes/class-soob-woocommerce.php): Expanded timezone options
- [`includes/class-soob-provider.php`](includes/class-soob-provider.php): UTC storage methods

### Key Methods

#### Timezone Detection
```javascript
// Browser-based auto-detection with fallbacks
autoDetectTimezone()
getFallbackTimezone()
```

#### UTC Conversion
```php
// PHP timezone conversion methods
convert_availability_to_utc($availability, $timezone)
convert_availability_from_utc($availability, $timezone)
```

#### AJAX Loading
```javascript
// Dynamic schedule loading with timezone parameter
loadScheduleForAudience(audience, timezone)
```

## Testing Scenarios

### 1. Timezone Auto-Detection
- **Test**: Load checkout page in different browsers
- **Expected**: Correct timezone auto-detected and set as default
- **Fallback**: Manual selection available if auto-detection fails

### 2. UTC Storage Verification
- **Test**: Create provider availability in different admin timezones
- **Expected**: All times stored consistently in UTC in database
- **Verification**: Direct database inspection shows UTC times

### 3. Display Conversion Accuracy
- **Test**: View schedule in different display timezones
- **Expected**: Times correctly converted from UTC to selected timezone
- **Edge Cases**: Handle daylight saving time transitions

### 4. Weekly Pattern Consistency
- **Test**: Provider availability spans multiple days/timezones
- **Expected**: Weekly pattern maintained regardless of timezone
- **Verification**: Same availability shows consistently across timezones

## Future Considerations

### 1. Daylight Saving Time
- Current implementation handles DST automatically via PHP DateTime
- Consider explicit DST transition handling for edge cases

### 2. Booking Conflicts
- UTC storage enables accurate conflict detection across timezones
- Future booking validation should use UTC comparisons

### 3. Reporting and Analytics
- All time-based reports should use UTC for accuracy
- Display conversion applied only at presentation layer

### 4. Performance Optimization
- Consider caching converted availability data
- Optimize AJAX calls for large provider datasets

## Troubleshooting

### Common Issues

#### 1. Incorrect Time Display
- **Cause**: Timezone conversion error
- **Solution**: Verify timezone string format and PHP timezone support

#### 2. Auto-Detection Failure
- **Cause**: Browser doesn't support Intl API
- **Solution**: Fallback timezone mapping implemented

#### 3. AJAX Loading Errors
- **Cause**: Missing timezone parameter in AJAX calls
- **Solution**: Ensure timezone passed in all AJAX requests

### Debug Methods

#### 1. Console Logging
```javascript
console.log('Detected timezone:', detectedTimezone);
console.log('Selected timezone:', selectedTimezone);
```

#### 2. PHP Debug Output
```php
error_log('UTC time: ' . $utc_time->format('Y-m-d H:i:s'));
error_log('Display time: ' . $display_time->format('Y-m-d H:i:s'));
```

#### 3. Database Inspection
```sql
-- Verify UTC storage
SELECT availability FROM wp_soob_providers WHERE id = 1;
```

## Conclusion

The timezone handling system provides:
- **Consistency**: All times stored in UTC
- **Flexibility**: Display in any timezone
- **User Experience**: Auto-detection with manual override
- **Accuracy**: Proper conversion handling including DST
- **Scalability**: Weekly patterns support global usage

This implementation ensures the booking system works reliably across different timezones while maintaining data integrity and providing an intuitive user experience.