# Project Context: Simple One-to-One Booking Plugin with WooCommerce

You are working on a WordPress project where the goal is to implement a very simple and flexible one-to-one booking system using WooCommerce .

All the logic and UI will be handled through a custom plugin named `hamdy-plugin`, and bookings will be tied to WooCommerce orders.

## Objective

Allow customers to purchase a 1-on-1 live course as a WooCommerce product, and during checkout, select:
- Their timezone
- Their gender/age group (man / woman / child)
- One or more available time slots for the session

These selected values will be saved in the WooCommerce order meta.

## UI Flow

- Customers land on a landing page or course page
- They click “Book Now” → redirected directly to WooCommerce checkout for that product
- On checkout:
  - They select their timezone
  - Their gender/age group
  - They see available days of the week (e.g., Friday, Saturday, etc.) as tabs
  - For each day, available time slots appear as styled cards, each card represent 3 hours period
  - Unavailable time slots are disabled
  - The user must select at least one valid time slot


## Admin Interface

The admin panel includes **two separate pages**:

### 1. Teachers Management Page

- Admin can add/edit teachers with:
  - Name
  - Photo
  - Gender (man / woman)
  - Age group they can teach (adults / children)
  - Available time slots for each day of the week
- Time slots are selected in a visual way (e.g., checkboxes or hour selectors)
- Teacher availability is saved in the database and used to populate the global schedule

### 2. Time Slots Overview Page

- Displays a full weekly schedule broken down by **audience type**:
  - The page contains **three main tabs**:
    - **Men**
    - **Women**
    - **Children**
  - Inside each tab:
    - A vertical list of the 7 days of the week (Sunday to Saturday)
    - Under each day, display **24 time blocks** (00:00 to 23:00), each representing one hour
    - Each time block is styled as a small square (e.g., a button or div):
      - **Available** hours are **highlighted** (e.g., colored or active)
      - **Unavailable** hours are **disabled** (e.g., greyed out or inactive)
- All availability data is calculated dynamically from teacher availability in the database, filtered by audience type.
- This page provides a quick and visual overview of booking availability across the week for each group (men, women, children).




## Plugin Scope

All functionality will be implemented inside a single plugin: `hamdy-plugin`

- The plugin will:
  - Register a custom admin page
  - Save time slot configuration
  - Hook into WooCommerce checkout
  - Display selection fields

## Booking Storage Strategy

- Booking data should be saved in two places:
  1. **WooCommerce order meta**, for quick visibility and admin access
  2. **A custom database table** (e.g., `wp_hamdy_bookings`), for structured access, filtering, and future expansion



## Guidelines

- Follow WordPress and WooCommerce coding standards
- Validate and sanitize all user input
- Use nonces and proper security checks
- Time should be stored in UTC and converted on display
- Avoid over-engineering; keep it simple and extensible

## Optional Enhancements (for future phases)

These features are **not required in the initial version**, but may be considered for future expansion depending on client needs or project growth:

- **Admin Booking Overview UI**  
  A custom admin page to view and manage booking records in a calendar or table format, separate from WooCommerce orders.

- **Automated Confirmation Email**  
  Send a custom confirmation email to the customer only after a session time is confirmed (manually or automatically).  
  This is different from the default WooCommerce order email.

- **Multi-product Support**  
  Support assigning different booking settings (time slots, teacher availability) to different WooCommerce products, for offering multiple types of 1-on-1 sessions.

- **LearnPress Integration**  
  Optional future integration with LearnPress, if course structure needs to be tied to the booking system.




