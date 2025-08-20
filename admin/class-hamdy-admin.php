<?php

/**
 * Admin functionality class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Hamdy_Admin
{

    private $teachers_admin;
    private $schedule_admin;

    private $all_bookings = null;

    private function get_all_bookings()
    {
        if ($this->all_bookings === null) {
            $this->all_bookings = Hamdy_Booking::get_all();
        }

        return $this->all_bookings;
    }



    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers for booking management
        add_action('wp_ajax_hamdy_delete_booking', array($this, 'ajax_delete_booking'));
        add_action('wp_ajax_hamdy_get_bookings', array($this, 'ajax_get_bookings'));

        // Initialize sub-admin classes
        $this->teachers_admin = new Hamdy_Admin_Teachers();
        $this->schedule_admin = new Hamdy_Admin_Schedule();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Hamdy Booking', 'hamdy-plugin'),
            __('Hamdy Booking', 'hamdy-plugin'),
            'manage_options',
            'hamdy-booking',
            array($this, 'admin_page'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'hamdy-booking',
            __('Dashboard', 'hamdy-plugin'),
            __('Dashboard', 'hamdy-plugin'),
            'manage_options',
            'hamdy-booking',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'hamdy-booking',
            __('Teachers', 'hamdy-plugin'),
            __('Teachers', 'hamdy-plugin'),
            'manage_options',
            'hamdy-teachers',
            array($this, 'teachers_page')
        );

        add_submenu_page(
            'hamdy-booking',
            __('Schedule Overview', 'hamdy-plugin'),
            __('Schedule Overview', 'hamdy-plugin'),
            'manage_options',
            'hamdy-schedule',
            array($this, 'schedule_page')
        );

        add_submenu_page(
            'hamdy-booking',
            __('Bookings', 'hamdy-plugin'),
            __('Bookings', 'hamdy-plugin'),
            'manage_options',
            'hamdy-bookings',
            array($this, 'bookings_page')
        );
    }

    /**
     * Admin init
     */
    public function admin_init()
    {
        // Register settings if needed
        register_setting('hamdy_settings', 'hamdy_options');
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only enqueue on our admin pages
        if (strpos($hook, 'hamdy-') !== false) {
            // Enqueue common admin styles and scripts for all hamdy pages
            $this->enqueue_common_admin_assets();
            
            // Enqueue page-specific assets
            $this->enqueue_page_specific_assets($hook);
        }
    }

    /**
     * Enqueue common admin assets used across all admin pages
     */
    private function enqueue_common_admin_assets()
    {
        // Common admin CSS
        wp_enqueue_style(
            'hamdy-admin',
            HAMDY_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HAMDY_PLUGIN_VERSION
        );

        // Common admin JavaScript
        wp_enqueue_script(
            'hamdy-admin',
            HAMDY_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable'),
            HAMDY_PLUGIN_VERSION,
            true
        );

        // Localize common admin script
        wp_localize_script('hamdy-admin', 'hamdy_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hamdy_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'hamdy-plugin'),
                'loading' => __('Loading...', 'hamdy-plugin'),
                'saved' => __('Saved successfully!', 'hamdy-plugin'),
                'error' => __('An error occurred. Please try again.', 'hamdy-plugin'),
            )
        ));
    }

    /**
     * Enqueue page-specific assets based on current admin page
     */
    private function enqueue_page_specific_assets($hook)
    {
        switch ($hook) {
            case 'hamdy-booking_page_hamdy-schedule':
                // Load schedule-specific assets
                if ($this->schedule_admin) {
                    $this->schedule_admin->enqueue_scripts();
                }
                break;
                
            case 'hamdy-booking_page_hamdy-teachers':
                // Load teachers-specific assets
                if ($this->teachers_admin) {
                    $this->teachers_admin->enqueue_scripts();
                }
                break;
                
            case 'toplevel_page_hamdy-booking':
            case 'hamdy-booking_page_hamdy-bookings':
                // Dashboard and bookings pages only need common assets
                // No additional assets needed
                break;
        }
    }

    /**
     * Main admin page
     */
    public function admin_page()
    {
?>
        <div class="wrap">
            <h1><?php _e('Hamdy Booking Dashboard', 'hamdy-plugin'); ?></h1>

            <div class="hamdy-dashboard">
                <div class="hamdy-stats-grid">
                    <div class="hamdy-stat-card">
                        <h3><?php _e('Total Bookings', 'hamdy-plugin'); ?></h3>
                        <div class="hamdy-stat-number"><?php echo $this->get_total_bookings(); ?></div>
                    </div>

                    <div class="hamdy-stat-card">
                        <h3><?php _e('Pending Bookings', 'hamdy-plugin'); ?></h3>
                        <div class="hamdy-stat-number"><?php echo $this->get_pending_bookings(); ?></div>
                    </div>

                    <div class="hamdy-stat-card">
                        <h3><?php _e('Active Teachers', 'hamdy-plugin'); ?></h3>
                        <div class="hamdy-stat-number"><?php echo $this->get_active_teachers(); ?></div>
                    </div>

                    <div class="hamdy-stat-card">
                        <h3><?php _e('This Week', 'hamdy-plugin'); ?></h3>
                        <div class="hamdy-stat-number"><?php echo $this->get_this_week_bookings(); ?></div>
                    </div>
                </div>

                <div class="hamdy-recent-bookings">
                    <h2><?php _e('Recent Bookings', 'hamdy-plugin'); ?></h2>
                    <?php $this->display_recent_bookings(); ?>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Teachers page
     */
    public function teachers_page()
    {
        $this->teachers_admin->display_page();
    }

    /**
     * Schedule page
     */
    public function schedule_page()
    {
        $this->schedule_admin->display_page();
    }

    /**
     * Bookings page
     */
    public function bookings_page()
    {
        // Handle edit redirect
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['booking_id'])) {
            $this->display_edit_booking_page(intval($_GET['booking_id']));
            return;
        }
        
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'all';
    ?>
        <div class="wrap">
            <h1><?php _e('Bookings Management', 'hamdy-plugin'); ?></h1>

            <div class="hamdy-bookings-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="<?php echo admin_url('admin.php?page=hamdy-bookings&tab=all'); ?>" 
                       class="nav-tab <?php echo $current_tab === 'all' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('All', 'hamdy-plugin'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=hamdy-bookings&tab=male'); ?>" 
                       class="nav-tab <?php echo $current_tab === 'male' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('Male', 'hamdy-plugin'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=hamdy-bookings&tab=female'); ?>" 
                       class="nav-tab <?php echo $current_tab === 'female' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('Female', 'hamdy-plugin'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=hamdy-bookings&tab=expiring'); ?>" 
                       class="nav-tab <?php echo $current_tab === 'expiring' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('Expiring Soon', 'hamdy-plugin'); ?>
                    </a>
                </nav>
            </div>

            <div class="hamdy-bookings-list">
                <?php $this->display_bookings_table($current_tab); ?>
            </div>
        </div>
<?php
    }

    /**
     * Get total bookings count
     */
    private function get_total_bookings()
    {
        $bookings = $this->get_all_bookings();
        return count($bookings);

    }

    /**
     * Get pending bookings count
     */
    private function get_pending_bookings()
    {
        $bookings = Hamdy_Booking::get_all(array('status' => 'pending'));
        return count($bookings);
    }

    /**
     * Get active teachers count
     */
    private function get_active_teachers()
    {
        $teachers = Hamdy_Teacher::get_all('active');
        return count($teachers);
    }

    /**
     * Get this week bookings count
     */
    private function get_this_week_bookings()
    {
        $start_of_week = date('Y-m-d', strtotime('monday this week'));
        $end_of_week = date('Y-m-d', strtotime('sunday this week'));

        $bookings = Hamdy_Booking::get_all(array(
            'date_from' => $start_of_week,
            'date_to' => $end_of_week
        ));

        return count($bookings);
    }

    /**
     * Display recent bookings
     */
    private function display_recent_bookings()
    {
        $bookings = $this->get_all_bookings();
        $recent_bookings = array_slice($bookings, 0, 5);

        if (empty($recent_bookings)) {
            echo '<p>' . __('No recent bookings found.', 'hamdy-plugin') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Order ID', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Customer', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Gender', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Age', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Date', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Status', 'hamdy-plugin') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($recent_bookings as $booking) {
            $customer = get_user_by('id', $booking->customer_id);
            echo '<tr>';
            echo '<td>#' . $booking->order_id . '</td>';
            echo '<td>' . ($customer ? $customer->display_name : __('Guest', 'hamdy-plugin')) . '</td>';
            echo '<td>' . esc_html(ucfirst($booking->customer_gender)) . '</td>';
            echo '<td>' . esc_html($booking->customer_age) . '</td>';
            echo '<td>' . date('M j, Y', strtotime($booking->booking_date)) . '</td>';
            echo '<td><span class="hamdy-status hamdy-status-' . $booking->status . '">' . ucfirst($booking->status) . '</span></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Display bookings table
     */
    private function display_bookings_table($tab = 'all')
    {
        $filters = array();
        
        // Debug: Log the current tab
        error_log('Hamdy Admin: Current tab = ' . $tab);
        
        switch ($tab) {
            case 'male':
                $filters['customer_gender'] = 'male';
                break;
            case 'female':
                $filters['customer_gender'] = 'female';
                break;
            case 'expiring':
                $filters['expiring_soon'] = true;
                break;
            default:
                // 'all' tab - no filters
                break;
        }
        
        // Debug: Log the filters being applied
        error_log('Hamdy Admin: Filters = ' . print_r($filters, true));
        
        $bookings = Hamdy_Booking::get_all($filters);
        
        // Debug: Log the number of bookings returned
        error_log('Hamdy Admin: Found ' . count($bookings) . ' bookings');

        if (empty($bookings)) {
            echo '<p>' . __('No bookings found.', 'hamdy-plugin') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped hamdy-bookings-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('ID', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('WooCommerce Order ID', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Student Name', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Gender', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Age', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Renewal Date', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Status', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Actions', 'hamdy-plugin') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $today = date('Y-m-d');
        $expiring_threshold = date('Y-m-d', strtotime('+5 days'));

        foreach ($bookings as $booking) {
            $customer = get_user_by('id', $booking->customer_id);
            $customer_name = $customer ? $customer->display_name : __('Guest', 'hamdy-plugin');
            
            // Check if booking is expiring soon
            $is_expiring = false;
            if (!empty($booking->renewal_date)) {
                $is_expiring = $booking->renewal_date <= $expiring_threshold && $booking->renewal_date >= $today;
            }
            
            $row_class = $is_expiring ? 'expiring' : '';
            
            echo '<tr class="' . $row_class . '" data-booking-id="' . $booking->id . '">';
            echo '<td>' . $booking->id . '</td>';
            echo '<td>#' . $booking->order_id . '</td>';
            echo '<td>' . esc_html($customer_name) . '</td>';
            echo '<td>' . esc_html(ucfirst($booking->customer_gender)) . '</td>';
            echo '<td>' . esc_html($booking->customer_age) . '</td>';
            echo '<td>' . ($booking->renewal_date ? date('M j, Y', strtotime($booking->renewal_date)) : '—') . '</td>';
            echo '<td><span class="hamdy-status hamdy-status-' . $booking->status . '">' . ucfirst($booking->status) . '</span></td>';
            echo '<td>';
            echo '<a href="' . admin_url('admin.php?page=hamdy-bookings&action=edit&booking_id=' . $booking->id) . '" class="button button-small hamdy-edit-booking">' . __('Edit', 'hamdy-plugin') . '</a> ';
            echo '<button class="button button-small button-link-delete hamdy-delete-booking" data-booking-id="' . $booking->id . '">' . __('Delete', 'hamdy-plugin') . '</button>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * Display edit booking page
     */
    private function display_edit_booking_page($booking_id)
    {
        // Handle form submission
        if (isset($_POST['hamdy_save_booking']) && wp_verify_nonce($_POST['hamdy_booking_nonce'], 'hamdy_save_booking_' . $booking_id)) {
            $this->handle_booking_save($booking_id);
        }
        
        $booking = Hamdy_Booking::get_by_id($booking_id);
        
        if (!$booking) {
            echo '<div class="wrap">';
            echo '<h1>' . __('Edit Booking', 'hamdy-plugin') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('Booking not found.', 'hamdy-plugin') . '</p></div>';
            echo '<a href="' . admin_url('admin.php?page=hamdy-bookings') . '" class="button">' . __('Back to Bookings', 'hamdy-plugin') . '</a>';
            echo '</div>';
            return;
        }
        
        // Get customer info
        $customer = get_user_by('id', $booking->customer_id);
        $customer_name = $customer ? $customer->display_name : __('Guest', 'hamdy-plugin');
        
        // Get order info
        $purchase_date = __('Unknown', 'hamdy-plugin');
        if (function_exists('wc_get_order')) {
            $order = wc_get_order($booking->order_id);
            if ($order) {
                $purchase_date = $order->get_date_created()->format('M j, Y');
            }
        }
        
        // Get teachers for dropdown
        $teachers = Hamdy_Teacher::get_all('active');
        
        // Parse selected slots
        $selected_slots = json_decode($booking->selected_slots, true) ?: array();
        
        ?>
        <div class="wrap hamdy-edit-booking-page">
            <h1><?php _e('Edit Booking', 'hamdy-plugin'); ?> #<?php echo $booking->id; ?></h1>
            
            <form method="post" action="" class="hamdy-booking-edit-form">
                <?php wp_nonce_field('hamdy_save_booking_' . $booking_id, 'hamdy_booking_nonce'); ?>
                
                <!-- Non-editable Information -->
                <div class="hamdy-form-section">
                    <h3><?php _e('Order Information', 'hamdy-plugin'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Order ID', 'hamdy-plugin'); ?></th>
                            <td><strong>#<?php echo $booking->order_id; ?></strong></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Purchase Date', 'hamdy-plugin'); ?></th>
                            <td><?php echo $purchase_date; ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Editable Student Information -->
                <div class="hamdy-form-section">
                    <h3><?php _e('Student Information', 'hamdy-plugin'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="student_name"><?php _e('Student Name', 'hamdy-plugin'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="student_name" name="student_name"
                                       value="<?php echo esc_attr($customer_name); ?>"
                                       class="regular-text hamdy-editable-field" />
                                <p class="description"><?php _e('The name of the student taking the course.', 'hamdy-plugin'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="customer_gender"><?php _e('Gender', 'hamdy-plugin'); ?></label>
                            </th>
                            <td>
                                <select id="customer_gender" name="customer_gender" class="hamdy-editable-field">
                                    <option value="male" <?php selected($booking->customer_gender, 'male'); ?>><?php _e('Male', 'hamdy-plugin'); ?></option>
                                    <option value="female" <?php selected($booking->customer_gender, 'female'); ?>><?php _e('Female', 'hamdy-plugin'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="customer_age"><?php _e('Age', 'hamdy-plugin'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="customer_age" name="customer_age"
                                       value="<?php echo esc_attr($booking->customer_age); ?>"
                                       min="1" max="100" class="small-text hamdy-editable-field" />
                                <p class="description"><?php _e('Student age in years.', 'hamdy-plugin'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Booking Management -->
                <div class="hamdy-form-section">
                    <h3><?php _e('Booking Management', 'hamdy-plugin'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="booking_status"><?php _e('Booking Status', 'hamdy-plugin'); ?></label>
                            </th>
                            <td>
                                <select id="booking_status" name="booking_status" class="hamdy-editable-field">
                                    <option value="pending" <?php selected($booking->status, 'pending'); ?>><?php _e('Pending', 'hamdy-plugin'); ?></option>
                                    <option value="approved" <?php selected($booking->status, 'approved'); ?>><?php _e('Approved', 'hamdy-plugin'); ?></option>
                                    <option value="canceled" <?php selected($booking->status, 'canceled'); ?>><?php _e('Canceled', 'hamdy-plugin'); ?></option>
                                </select>
                                <p class="description"><?php _e('Current status of the booking.', 'hamdy-plugin'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="course_start_date"><?php _e('Course Start Date', 'hamdy-plugin'); ?></label>
                            </th>
                            <td>
                                <input type="date" id="course_start_date" name="course_start_date"
                                       value="<?php echo esc_attr($booking->booking_date); ?>"
                                       class="hamdy-editable-field hamdy-date-picker" />
                                <p class="description"><?php _e('When the course will start.', 'hamdy-plugin'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="assigned_teacher"><?php _e('Assigned Teacher', 'hamdy-plugin'); ?></label>
                            </th>
                            <td>
                                <select id="assigned_teacher" name="assigned_teacher" class="hamdy-editable-field">
                                    <option value=""><?php _e('Select a teacher...', 'hamdy-plugin'); ?></option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher->id; ?>"
                                                <?php selected($booking->teacher_id, $teacher->id); ?>>
                                            <?php echo esc_html($teacher->name); ?> (<?php echo ucfirst($teacher->gender); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Teacher assigned to this booking.', 'hamdy-plugin'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Class Sessions -->
                <div class="hamdy-form-section">
                    <h3><?php _e('Class Sessions', 'hamdy-plugin'); ?></h3>
                    <div id="hamdy-sessions-container">
                        <?php if (!empty($selected_slots)): ?>
                            <?php foreach ($selected_slots as $index => $slot): ?>
                                <div class="hamdy-session-row" data-index="<?php echo $index; ?>">
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php _e('Day of Week', 'hamdy-plugin'); ?></th>
                                            <td>
                                                <select name="sessions[<?php echo $index; ?>][day]" class="hamdy-session-day">
                                                    <option value="sunday" <?php selected($slot['day'], 'sunday'); ?>><?php _e('Sunday', 'hamdy-plugin'); ?></option>
                                                    <option value="monday" <?php selected($slot['day'], 'monday'); ?>><?php _e('Monday', 'hamdy-plugin'); ?></option>
                                                    <option value="tuesday" <?php selected($slot['day'], 'tuesday'); ?>><?php _e('Tuesday', 'hamdy-plugin'); ?></option>
                                                    <option value="wednesday" <?php selected($slot['day'], 'wednesday'); ?>><?php _e('Wednesday', 'hamdy-plugin'); ?></option>
                                                    <option value="thursday" <?php selected($slot['day'], 'thursday'); ?>><?php _e('Thursday', 'hamdy-plugin'); ?></option>
                                                    <option value="friday" <?php selected($slot['day'], 'friday'); ?>><?php _e('Friday', 'hamdy-plugin'); ?></option>
                                                    <option value="saturday" <?php selected($slot['day'], 'saturday'); ?>><?php _e('Saturday', 'hamdy-plugin'); ?></option>
                                                </select>
                                            </td>
                                            <th scope="row"><?php _e('Start Time', 'hamdy-plugin'); ?></th>
                                            <td>
                                                <input type="time" name="sessions[<?php echo $index; ?>][start_time]"
                                                       value="<?php echo esc_attr($slot['start_time']); ?>"
                                                       class="hamdy-session-start-time" />
                                            </td>
                                            <th scope="row"><?php _e('End Time', 'hamdy-plugin'); ?></th>
                                            <td>
                                                <input type="time" name="sessions[<?php echo $index; ?>][end_time]"
                                                       value="<?php echo esc_attr($slot['end_time']); ?>"
                                                       class="hamdy-session-end-time" />
                                            </td>
                                            <td>
                                                <button type="button" class="button button-secondary hamdy-remove-session">
                                                    <?php _e('Remove', 'hamdy-plugin'); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="hamdy-no-sessions"><?php _e('No sessions scheduled yet.', 'hamdy-plugin'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <p>
                        <button type="button" id="hamdy-add-session" class="button button-secondary">
                            <?php _e('Add Session', 'hamdy-plugin'); ?>
                        </button>
                    </p>
                </div>
                
                <!-- Save Button -->
                <p class="submit">
                    <input type="submit" name="hamdy_save_booking" class="button-primary"
                           value="<?php _e('Save Changes', 'hamdy-plugin'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=hamdy-bookings'); ?>" class="button">
                        <?php _e('Back to Bookings', 'hamdy-plugin'); ?>
                    </a>
                </p>
            </form>
        </div>
        
        <!-- Session Template (hidden) -->
        <script type="text/template" id="hamdy-session-template">
            <div class="hamdy-session-row" data-index="{{INDEX}}">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Day of Week', 'hamdy-plugin'); ?></th>
                        <td>
                            <select name="sessions[{{INDEX}}][day]" class="hamdy-session-day">
                                <option value="sunday"><?php _e('Sunday', 'hamdy-plugin'); ?></option>
                                <option value="monday"><?php _e('Monday', 'hamdy-plugin'); ?></option>
                                <option value="tuesday"><?php _e('Tuesday', 'hamdy-plugin'); ?></option>
                                <option value="wednesday"><?php _e('Wednesday', 'hamdy-plugin'); ?></option>
                                <option value="thursday"><?php _e('Thursday', 'hamdy-plugin'); ?></option>
                                <option value="friday"><?php _e('Friday', 'hamdy-plugin'); ?></option>
                                <option value="saturday"><?php _e('Saturday', 'hamdy-plugin'); ?></option>
                            </select>
                        </td>
                        <th scope="row"><?php _e('Start Time', 'hamdy-plugin'); ?></th>
                        <td>
                            <input type="time" name="sessions[{{INDEX}}][start_time]"
                                   value="" class="hamdy-session-start-time" />
                        </td>
                        <th scope="row"><?php _e('End Time', 'hamdy-plugin'); ?></th>
                        <td>
                            <input type="time" name="sessions[{{INDEX}}][end_time]"
                                   value="" class="hamdy-session-end-time" />
                        </td>
                        <td>
                            <button type="button" class="button button-secondary hamdy-remove-session">
                                <?php _e('Remove', 'hamdy-plugin'); ?>
                            </button>
                        </td>
                    </tr>
                </table>
            </div>
        </script>
        <?php
    }
    
    /**
     * AJAX handler for deleting bookings
     */
    public function ajax_delete_booking()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hamdy_admin_nonce')) {
            wp_die(__('Security check failed', 'hamdy-plugin'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hamdy-plugin'));
        }
        
        $booking_id = intval($_POST['booking_id']);
        
        if (!$booking_id) {
            wp_send_json_error(__('Invalid booking ID', 'hamdy-plugin'));
        }
        
        $result = Hamdy_Booking::delete($booking_id);
        
        if ($result) {
            wp_send_json_success(__('Booking deleted successfully', 'hamdy-plugin'));
        } else {
            wp_send_json_error(__('Failed to delete booking', 'hamdy-plugin'));
        }
    }
    
    /**
     * AJAX handler for getting bookings (for future use)
     */
    public function ajax_get_bookings()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hamdy_admin_nonce')) {
            wp_die(__('Security check failed', 'hamdy-plugin'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'hamdy-plugin'));
        }
        
        $tab = sanitize_text_field($_POST['tab']);
        $filters = array();
        
        switch ($tab) {
            case 'male':
                $filters['customer_gender'] = 'male';
                break;
            case 'female':
                $filters['customer_gender'] = 'female';
                break;
            case 'expiring':
                $filters['expiring_soon'] = true;
                break;
        }
        
        $bookings = Hamdy_Booking::get_all($filters);
        wp_send_json_success($bookings);
    }
    
    /**
     * Handle booking save from edit form
     */
    private function handle_booking_save($booking_id)
    {
        // Validate and sanitize input
        $student_name = sanitize_text_field($_POST['student_name']);
        $customer_gender = sanitize_text_field($_POST['customer_gender']);
        $customer_age = intval($_POST['customer_age']);
        $booking_status = sanitize_text_field($_POST['booking_status']);
        $course_start_date = sanitize_text_field($_POST['course_start_date']);
        $assigned_teacher = intval($_POST['assigned_teacher']);
        
        // Validate required fields
        $errors = array();
        
        if (empty($student_name)) {
            $errors[] = __('Student name is required.', 'hamdy-plugin');
        }
        
        if (!in_array($customer_gender, array('male', 'female'))) {
            $errors[] = __('Please select a valid gender.', 'hamdy-plugin');
        }
        
        if ($customer_age < 1 || $customer_age > 100) {
            $errors[] = __('Please enter a valid age between 1 and 100.', 'hamdy-plugin');
        }
        
        if (!in_array($booking_status, array('pending', 'approved', 'canceled'))) {
            $errors[] = __('Please select a valid booking status.', 'hamdy-plugin');
        }
        
        if (!empty($course_start_date) && !strtotime($course_start_date)) {
            $errors[] = __('Please enter a valid course start date.', 'hamdy-plugin');
        }
        
        // Process sessions
        $sessions = array();
        if (isset($_POST['sessions']) && is_array($_POST['sessions'])) {
            foreach ($_POST['sessions'] as $session) {
                if (!empty($session['day']) && !empty($session['start_time']) && !empty($session['end_time'])) {
                    $sessions[] = array(
                        'day' => sanitize_text_field($session['day']),
                        'start_time' => sanitize_text_field($session['start_time']),
                        'end_time' => sanitize_text_field($session['end_time'])
                    );
                }
            }
        }
        
        // Show errors if any
        if (!empty($errors)) {
            echo '<div class="notice notice-error"><ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
            return;
        }
        
        // Prepare update data
        $update_data = array(
            'customer_gender' => $customer_gender,
            'customer_age' => $customer_age,
            'status' => $booking_status,
            'booking_date' => $course_start_date,
            'teacher_id' => $assigned_teacher > 0 ? $assigned_teacher : null,
            'selected_slots' => wp_json_encode($sessions)
        );
        
        // Update booking
        $result = Hamdy_Booking::update($booking_id, $update_data);
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>' . __('Booking updated successfully!', 'hamdy-plugin') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to update booking. Please try again.', 'hamdy-plugin') . '</p></div>';
        }
    }
}
