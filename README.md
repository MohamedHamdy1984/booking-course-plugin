# Hamdy Plugin - WordPress Booking System

A simple one-to-one booking system integrated with WooCommerce for live course sessions.

## Overview

This WordPress plugin allows customers to purchase 1-on-1 live courses as WooCommerce products and select their preferred time slots during checkout. The system includes teacher management, schedule overview, and booking management features.

## Features

- **WooCommerce Integration**: Seamless integration with WooCommerce checkout
- **Teacher Management**: Add/edit teachers with availability schedules
- **Schedule Overview**: Visual weekly schedule by audience type (Men, Women, Children)
- **Timezone Support**: Automatic timezone conversion for customers
- **Booking Management**: Track and manage all bookings
- **Responsive Design**: Mobile-friendly interface

## File Structure

```
hamdy-plugin/
├── hamdy-plugin.php          # Main plugin file
├── includes/                 # Core functionality
│   ├── class-hamdy-database.php
│   ├── class-hamdy-teacher.php
│   ├── class-hamdy-booking.php
│   └── class-hamdy-woocommerce.php
├── admin/                    # Admin functionality
│   ├── class-hamdy-admin.php
│   ├── class-hamdy-admin-teachers.php
│   └── class-hamdy-admin-schedule.php
├── public/                   # Public functionality
│   ├── class-hamdy-public.php
│   └── class-hamdy-checkout.php
├── assets/                   # CSS and JS files
│   ├── css/
│   │   ├── admin.css
│   │   └── public.css
│   └── js/
│       ├── admin.js
│       └── public.js
└── README.md
```

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and activated
4. Configure teachers and their availability in the admin panel

## Requirements

- WordPress 5.0+
- PHP 7.4+
- WooCommerce 5.0+

## Admin Features

### Teachers Management
- Add/edit teachers with photos, gender, and age group
- Set weekly availability schedules
- Activate/deactivate teachers

### Schedule Overview
- Visual weekly calendar showing availability
- Separate views for Men, Women, and Children
- 24-hour time slot display
- Color-coded availability indicators

### Booking Management
- View all bookings with customer details
- Filter by status, date, and teacher
- Order integration with WooCommerce

## Customer Experience

### Checkout Process
1. Customer adds bookable product to cart
2. During checkout, they select:
   - Timezone
   - Category (Man/Woman/Child)
   - Available time slots from weekly calendar
3. Booking details are saved with the order

### Time Slot Selection
- Tabbed interface showing days of the week
- Available slots displayed as selectable cards
- Real-time availability based on teacher schedules
- Timezone conversion for accurate display

## Database Tables

### wp_hamdy_teachers
- Teacher information and availability schedules
- JSON-encoded availability data by day of week

### wp_hamdy_bookings
- Booking records linked to WooCommerce orders
- Customer preferences and selected time slots

## Hooks and Filters

### Actions
- `hamdy_booking_created` - Fired when a new booking is created
- `hamdy_teacher_updated` - Fired when teacher data is updated

### Filters
- `hamdy_timezone_options` - Modify available timezone options
- `hamdy_available_slots` - Filter available time slots

## Shortcodes

### [hamdy_booking_button]
Display a booking button for a specific product.

**Attributes:**
- `product_id` (required) - WooCommerce product ID
- `text` - Button text (default: "Book Now")
- `class` - CSS class for styling

**Example:**
```
[hamdy_booking_button product_id="123" text="Book Your Session" class="custom-button"]
```

### [hamdy_time_slots]
Display available time slots for a specific date and category.

**Attributes:**
- `gender_age_group` (required) - Target audience
- `date` - Specific date (default: today)

**Example:**
```
[hamdy_time_slots gender_age_group="man" date="2024-01-15"]
```

## Customization

### Styling
- Modify `assets/css/admin.css` for admin interface styling
- Modify `assets/css/public.css` for frontend styling
- All styles use CSS custom properties for easy theming

### JavaScript
- `assets/js/admin.js` - Admin functionality
- `assets/js/public.js` - Frontend interactions
- Both files are properly localized for translations

## Security

- All user inputs are sanitized and validated
- Nonce verification for AJAX requests
- Capability checks for admin functions
- SQL injection prevention using prepared statements

## Performance

- Efficient database queries with proper indexing
- AJAX-powered interfaces for smooth user experience
- Minimal frontend JavaScript footprint
- CSS and JS files are minified in production

## Troubleshooting

### Common Issues

1. **Time slots not showing**
   - Ensure teachers are added and marked as active
   - Check teacher availability settings
   - Verify WooCommerce product is marked as bookable

2. **Checkout fields not appearing**
   - Confirm WooCommerce is active
   - Check if cart contains bookable products
   - Verify plugin activation

3. **Database errors**
   - Deactivate and reactivate plugin to recreate tables
   - Check WordPress database permissions

## Development

### Adding New Features
1. Follow WordPress coding standards
2. Use proper sanitization and validation
3. Add appropriate hooks and filters
4. Update documentation

### Testing
- Test with different WordPress versions
- Verify WooCommerce compatibility
- Check responsive design on various devices
- Test timezone conversions

## Support

For support and feature requests, please contact the development team or create an issue in the project repository.

## License

This plugin is licensed under GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Teacher management system
- Schedule overview interface
- WooCommerce checkout integration
- Booking management features