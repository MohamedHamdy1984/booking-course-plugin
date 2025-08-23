<?php

/**
 * Admin functionality class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SOOB_Admin
{

    private $providers_admin;
    private $schedule_admin;

    private $all_bookings = null;

    private function get_all_bookings()
    {
        if ($this->all_bookings === null) {
            $this->all_bookings = SOOB_Booking::get_all();
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
        add_action('wp_ajax_soob_delete_booking', array($this, 'ajax_delete_booking'));
        add_action('wp_ajax_soob_get_bookings', array($this, 'ajax_get_bookings'));

        // Initialize sub-admin classes
        $this->providers_admin = new SOOB_Admin_Providers();
        $this->schedule_admin = new SOOB_Admin_Schedule();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Soob Booking', 'soob-plugin'),
            __('Soob Booking', 'soob-plugin'),
            'manage_options',
            'soob-booking',
            array($this, 'admin_page'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'soob-booking',
            __('Dashboard', 'soob-plugin'),
            __('Dashboard', 'soob-plugin'),
            'manage_options',
            'soob-booking',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'soob-booking',
            __('Providers', 'soob-plugin'),
            __('Providers', 'soob-plugin'),
            'manage_options',
            'soob-providers',
            array($this, 'providers_page')
        );

        add_submenu_page(
            'soob-booking',
            __('Schedule Overview', 'soob-plugin'),
            __('Schedule Overview', 'soob-plugin'),
            'manage_options',
            'soob-schedule',
            array($this, 'schedule_page')
        );

        add_submenu_page(
            'soob-booking',
            __('Bookings', 'soob-plugin'),
            __('Bookings', 'soob-plugin'),
            'manage_options',
            'soob-bookings',
            array($this, 'bookings_page')
        );
    }

    /**
     * Admin init
     */
    public function admin_init()
    {
        // Register settings if needed
        register_setting('soob_settings', 'soob_options');
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only enqueue on our admin pages
        if (strpos($hook, 'soob-') !== false) {
            // Enqueue common admin styles and scripts for all soob pages
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
            'soob-admin',
            SOOB_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SOOB_PLUGIN_VERSION
        );

        // Common admin JavaScript
        wp_enqueue_script(
            'soob-admin',
            SOOB_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable'),
            SOOB_PLUGIN_VERSION,
            true
        );

        // Localize common admin script
        wp_localize_script('soob-admin', 'soob_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('soob_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'soob-plugin'),
                'loading' => __('Loading...', 'soob-plugin'),
                'saved' => __('Saved successfully!', 'soob-plugin'),
                'error' => __('An error occurred. Please try again.', 'soob-plugin'),
            )
        ));
    }

    /**
     * Enqueue page-specific assets based on current admin page
     */
    private function enqueue_page_specific_assets($hook)
    {
        switch ($hook) {
            case 'soob-booking_page_soob-schedule':
                // Load schedule-specific assets
                if ($this->schedule_admin) {
                    $this->schedule_admin->enqueue_scripts();
                }
                break;
                
            case 'soob-booking_page_soob-providers':
                // Load providers-specific assets
                if ($this->providers_admin) {
                    $this->providers_admin->enqueue_scripts();
                }
                break;
                
            case 'toplevel_page_soob-booking':
            case 'soob-booking_page_soob-bookings':
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
            <h1><?php _e('Soob Booking Dashboard', 'soob-plugin'); ?></h1>

            <div class="soob-dashboard">
                <div class="soob-stats-grid">
                    <div class="soob-stat-card">
                        <h3><?php _e('Total Bookings', 'soob-plugin'); ?></h3>
                        <div class="soob-stat-number"><?php echo $this->get_total_bookings(); ?></div>
                    </div>

                    <div class="soob-stat-card">
                        <h3><?php _e('Pending Bookings', 'soob-plugin'); ?></h3>
                        <div class="soob-stat-number"><?php echo $this->get_pending_bookings(); ?></div>
                    </div>

                    <div class="soob-stat-card">
                        <h3><?php _e('Active Providers', 'soob-plugin'); ?></h3>
                        <div class="soob-stat-number"><?php echo $this->get_active_providers(); ?></div>
                    </div>

                    <div class="soob-stat-card">
                        <h3><?php _e('This Week', 'soob-plugin'); ?></h3>
                        <div class="soob-stat-number"><?php echo $this->get_this_week_bookings(); ?></div>
                    </div>
                </div>

                <div class="soob-recent-bookings">
                    <h2><?php _e('Recent Bookings', 'soob-plugin'); ?></h2>
                    <?php $this->display_recent_bookings(); ?>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Providers page
     */
    public function providers_page()
    {
        $this->providers_admin->display_page();
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
            <h1><?php _e('Bookings Management', 'soob-plugin'); ?></h1>

            <div class="soob-bookings-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="<?php echo admin_url('admin.php?page=soob-bookings&tab=all'); ?>" 
                       class="nav-tab <?php echo $current_tab === 'all' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('All', 'soob-plugin'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=soob-bookings&tab=male'); ?>" 
                       class="nav-tab <?php echo $current_tab === 'male' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('Male', 'soob-plugin'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=soob-bookings&tab=female'); ?>" 
                       class="nav-tab <?php echo $current_tab === 'female' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('Female', 'soob-plugin'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=soob-bookings&tab=expiring'); ?>" 
                       class="nav-tab <?php echo $current_tab === 'expiring' ? 'nav-tab-active' : ''; ?>">
                        <?php _e('Expiring Soon', 'soob-plugin'); ?>
                    </a>
                </nav>
            </div>

            <div class="soob-bookings-list">
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
        $bookings = SOOB_Booking::get_all(array('status' => 'pending'));
        return count($bookings);
    }

    /**
     * Get active providers count
     */
    private function get_active_providers()
    {
        $providers = SOOB_Provider::get_all('active');
        return count($providers);
    }

    /**
     * Get this week bookings count
     */
    private function get_this_week_bookings()
    {
        $start_of_week = date('Y-m-d', strtotime('monday this week'));
        $end_of_week = date('Y-m-d', strtotime('sunday this week'));

        $bookings = SOOB_Booking::get_all(array(
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
            echo '<p>' . __('No recent bookings found.', 'soob-plugin') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Order ID', 'soob-plugin') . '</th>';
        echo '<th>' . __('Customer', 'soob-plugin') . '</th>';
        echo '<th>' . __('Gender', 'soob-plugin') . '</th>';
        echo '<th>' . __('Age', 'soob-plugin') . '</th>';
        echo '<th>' . __('Date', 'soob-plugin') . '</th>';
        echo '<th>' . __('Status', 'soob-plugin') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($recent_bookings as $booking) {
            $customer = get_user_by('id', $booking->customer_id);
            echo '<tr>';
            echo '<td>#' . $booking->order_id . '</td>';
            echo '<td>' . ($customer ? $customer->display_name : __('Guest', 'soob-plugin')) . '</td>';
            echo '<td>' . esc_html(ucfirst($booking->customer_gender)) . '</td>';
            echo '<td>' . esc_html($booking->customer_age) . '</td>';
            echo '<td>' . date('M j, Y', strtotime($booking->booking_date)) . '</td>';
            echo '<td><span class="soob-status soob-status-' . $booking->status . '">' . ucfirst($booking->status) . '</span></td>';
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
        error_log('Soob Admin: Current tab = ' . $tab);
        
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
        error_log('Soob Admin: Filters = ' . print_r($filters, true));
        
        $bookings = SOOB_Booking::get_all($filters);
        
        // Debug: Log the number of bookings returned
        error_log('Soob Admin: Found ' . count($bookings) . ' bookings');

        if (empty($bookings)) {
            echo '<p>' . __('No bookings found.', 'soob-plugin') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped soob-bookings-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('ID', 'soob-plugin') . '</th>';
        echo '<th>' . __('WooCommerce Order ID', 'soob-plugin') . '</th>';
        echo '<th>' . __('Client Name', 'soob-plugin') . '</th>';
        echo '<th>' . __('Gender', 'soob-plugin') . '</th>';
        echo '<th>' . __('Age', 'soob-plugin') . '</th>';
        echo '<th>' . __('Renewal Date', 'soob-plugin') . '</th>';
        echo '<th>' . __('Status', 'soob-plugin') . '</th>';
        echo '<th>' . __('Actions', 'soob-plugin') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $today = date('Y-m-d');
        $expiring_threshold = date('Y-m-d', strtotime('+5 days'));

        foreach ($bookings as $booking) {
            $customer = get_user_by('id', $booking->customer_id);
            $customer_name = $customer ? $customer->display_name : __('Guest', 'soob-plugin');
            
            // Check if booking is expiring soon
            $is_expiring = false;
            if (!empty($booking->next_renewal_date)) {
                $is_expiring = $booking->next_renewal_date <= $expiring_threshold && $booking->next_renewal_date >= $today;
            }
            
            $row_class = $is_expiring ? 'expiring' : '';
            
            echo '<tr class="' . $row_class . '" data-booking-id="' . $booking->id . '">';
            echo '<td>' . $booking->id . '</td>';
            echo '<td>#' . $booking->order_id . '</td>';
            echo '<td>' . esc_html($customer_name) . '</td>';
            echo '<td>' . esc_html(ucfirst($booking->customer_gender)) . '</td>';
            echo '<td>' . esc_html($booking->customer_age) . '</td>';
            echo '<td>' . ($booking->next_renewal_date ? date('M j, Y', strtotime($booking->next_renewal_date)) : '—') . '</td>';
            echo '<td><span class="soob-status soob-status-' . $booking->status . '">' . ucfirst($booking->status) . '</span></td>';
            echo '<td>';
            echo '<a href="' . admin_url('admin.php?page=soob-bookings&action=edit&booking_id=' . $booking->id) . '" class="button button-small soob-edit-booking">' . __('Edit', 'soob-plugin') . '</a> ';
            echo '<button class="button button-small button-link-delete soob-delete-booking" data-booking-id="' . $booking->id . '">' . __('Delete', 'soob-plugin') . '</button>';
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
        if (isset($_POST['soob_save_booking']) && wp_verify_nonce($_POST['soob_booking_nonce'], 'soob_save_booking_' . $booking_id)) {
            $this->handle_booking_save($booking_id);
        }
        
        $booking = SOOB_Booking::get_by_id($booking_id);
        
        if (!$booking) {
            echo '<div class="wrap">';
            echo '<h1>' . __('Edit Booking', 'soob-plugin') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('Booking not found.', 'soob-plugin') . '</p></div>';
            echo '<a href="' . admin_url('admin.php?page=soob-bookings') . '" class="button">' . __('Back to Bookings', 'soob-plugin') . '</a>';
            echo '</div>';
            return;
        }
        
        // Get customer info
        $customer = get_user_by('id', $booking->customer_id);
        $customer_name = $customer ? $customer->display_name : __('Guest', 'soob-plugin');
        
        // Get order info
        $purchase_date = __('Unknown', 'soob-plugin');
        if (function_exists('wc_get_order')) {
            $order = wc_get_order($booking->order_id);
            if ($order) {
                $purchase_date = $order->get_date_created()->format('M j, Y');
            }
        }
        
        // Get providers for dropdown
        $providers = SOOB_Provider::get_all('active');
        
        // Parse selected slots
        $selected_slots = json_decode($booking->selected_slots, true) ?: array();
        
        ?>
        <div class="wrap soob-edit-booking-page">
            <h1><?php _e('Edit Booking', 'soob-plugin'); ?> #<?php echo $booking->id; ?></h1>
            
            <form method="post" action="" class="soob-booking-edit-form">
                <?php wp_nonce_field('soob_save_booking_' . $booking_id, 'soob_booking_nonce'); ?>
                
                <!-- Non-editable Information -->
                <div class="soob-form-section">
                    <h3><?php _e('Order Information', 'soob-plugin'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Order ID', 'soob-plugin'); ?></th>
                            <td><strong>#<?php echo $booking->order_id; ?></strong></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Purchase Date', 'soob-plugin'); ?></th>
                            <td><?php echo $purchase_date; ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Editable Client Information -->
                <div class="soob-form-section">
                    <h3><?php _e('Client Information', 'soob-plugin'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="client_name"><?php _e('Client Name', 'soob-plugin'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="client_name" name="client_name"
                                       value="<?php echo esc_attr($customer_name); ?>"
                                       class="regular-text soob-editable-field" />
                                <p class="description"><?php _e('The name of the client taking the course.', 'soob-plugin'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="customer_gender"><?php _e('Gender', 'soob-plugin'); ?></label>
                            </th>
                            <td>
                                <select id="customer_gender" name="customer_gender" class="soob-editable-field">
                                    <option value="male" <?php selected($booking->customer_gender, 'male'); ?>><?php _e('Male', 'soob-plugin'); ?></option>
                                    <option value="female" <?php selected($booking->customer_gender, 'female'); ?>><?php _e('Female', 'soob-plugin'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="customer_age"><?php _e('Age', 'soob-plugin'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="customer_age" name="customer_age"
                                       value="<?php echo esc_attr($booking->customer_age); ?>"
                                       min="1" max="100" class="small-text soob-editable-field" />
                                <p class="description"><?php _e('Client age in years.', 'soob-plugin'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Booking Management -->
                <div class="soob-form-section">
                    <h3><?php _e('Booking Management', 'soob-plugin'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="booking_status"><?php _e('Booking Status', 'soob-plugin'); ?></label>
                            </th>
                            <td>
                                <select id="booking_status" name="booking_status" class="soob-editable-field">
                                    <option value="pending" <?php selected($booking->status, 'pending'); ?>><?php _e('Pending', 'soob-plugin'); ?></option>
                                    <option value="approved" <?php selected($booking->status, 'approved'); ?>><?php _e('Approved', 'soob-plugin'); ?></option>
                                    <option value="canceled" <?php selected($booking->status, 'canceled'); ?>><?php _e('Canceled', 'soob-plugin'); ?></option>
                                </select>
                                <p class="description"><?php _e('Current status of the booking.', 'soob-plugin'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="course_start_date"><?php _e('Course Start Date', 'soob-plugin'); ?></label>
                            </th>
                            <td>
                                <input type="date" id="course_start_date" name="course_start_date"
                                       value="<?php echo esc_attr($booking->booking_date); ?>"
                                       class="soob-editable-field soob-date-picker" />
                                <p class="description"><?php _e('When the course will start.', 'soob-plugin'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="assigned_provider"><?php _e('Assigned Provider', 'soob-plugin'); ?></label>
                            </th>
                            <td>
                                <select id="assigned_provider" name="assigned_provider" class="soob-editable-field">
                                    <option value=""><?php _e('Select a provider...', 'soob-plugin'); ?></option>
                                    <?php foreach ($providers as $provider): ?>
                                        <option value="<?php echo $provider->id; ?>"
                                                <?php selected($booking->provider_id, $provider->id); ?>>
                                            <?php echo esc_html($provider->name); ?> (<?php echo ucfirst($provider->gender); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Provider assigned to this booking.', 'soob-plugin'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Class Sessions -->
                <div class="soob-form-section">
                    <h3><?php _e('Class Sessions', 'soob-plugin'); ?></h3>
                    <div id="soob-sessions-container">
                        <?php if (!empty($selected_slots)): ?>
                            <?php foreach ($selected_slots as $index => $slot): ?>
                                <div class="soob-session-row" data-index="<?php echo $index; ?>">
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php _e('Day of Week', 'soob-plugin'); ?></th>
                                            <td>
                                                <select name="sessions[<?php echo $index; ?>][day]" class="soob-session-day">
                                                    <option value="sunday" <?php selected($slot['day'], 'sunday'); ?>><?php _e('Sunday', 'soob-plugin'); ?></option>
                                                    <option value="monday" <?php selected($slot['day'], 'monday'); ?>><?php _e('Monday', 'soob-plugin'); ?></option>
                                                    <option value="tuesday" <?php selected($slot['day'], 'tuesday'); ?>><?php _e('Tuesday', 'soob-plugin'); ?></option>
                                                    <option value="wednesday" <?php selected($slot['day'], 'wednesday'); ?>><?php _e('Wednesday', 'soob-plugin'); ?></option>
                                                    <option value="thursday" <?php selected($slot['day'], 'thursday'); ?>><?php _e('Thursday', 'soob-plugin'); ?></option>
                                                    <option value="friday" <?php selected($slot['day'], 'friday'); ?>><?php _e('Friday', 'soob-plugin'); ?></option>
                                                    <option value="saturday" <?php selected($slot['day'], 'saturday'); ?>><?php _e('Saturday', 'soob-plugin'); ?></option>
                                                </select>
                                            </td>
                                            <th scope="row"><?php _e('Start Time', 'soob-plugin'); ?></th>
                                            <td>
                                                <input type="time" name="sessions[<?php echo $index; ?>][start_time]"
                                                       value="<?php echo esc_attr($slot['start_time']); ?>"
                                                       class="soob-session-start-time" />
                                            </td>
                                            <th scope="row"><?php _e('End Time', 'soob-plugin'); ?></th>
                                            <td>
                                                <input type="time" name="sessions[<?php echo $index; ?>][end_time]"
                                                       value="<?php echo esc_attr($slot['end_time']); ?>"
                                                       class="soob-session-end-time" />
                                            </td>
                                            <td>
                                                <button type="button" class="button button-secondary soob-remove-session">
                                                    <?php _e('Remove', 'soob-plugin'); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="soob-no-sessions"><?php _e('No sessions scheduled yet.', 'soob-plugin'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <p>
                        <button type="button" id="soob-add-session" class="button button-secondary">
                            <?php _e('Add Session', 'soob-plugin'); ?>
                        </button>
                    </p>
                </div>
                
                <!-- Save Button -->
                <p class="submit">
                    <input type="submit" name="soob_save_booking" class="button-primary"
                           value="<?php _e('Save Changes', 'soob-plugin'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=soob-bookings'); ?>" class="button">
                        <?php _e('Back to Bookings', 'soob-plugin'); ?>
                    </a>
                </p>
            </form>
        </div>
        
        <!-- Session Template (hidden) -->
        <script type="text/template" id="soob-session-template">
            <div class="soob-session-row" data-index="{{INDEX}}">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Day of Week', 'soob-plugin'); ?></th>
                        <td>
                            <select name="sessions[{{INDEX}}][day]" class="soob-session-day">
                                <option value="sunday"><?php _e('Sunday', 'soob-plugin'); ?></option>
                                <option value="monday"><?php _e('Monday', 'soob-plugin'); ?></option>
                                <option value="tuesday"><?php _e('Tuesday', 'soob-plugin'); ?></option>
                                <option value="wednesday"><?php _e('Wednesday', 'soob-plugin'); ?></option>
                                <option value="thursday"><?php _e('Thursday', 'soob-plugin'); ?></option>
                                <option value="friday"><?php _e('Friday', 'soob-plugin'); ?></option>
                                <option value="saturday"><?php _e('Saturday', 'soob-plugin'); ?></option>
                            </select>
                        </td>
                        <th scope="row"><?php _e('Start Time', 'soob-plugin'); ?></th>
                        <td>
                            <input type="time" name="sessions[{{INDEX}}][start_time]"
                                   value="" class="soob-session-start-time" />
                        </td>
                        <th scope="row"><?php _e('End Time', 'soob-plugin'); ?></th>
                        <td>
                            <input type="time" name="sessions[{{INDEX}}][end_time]"
                                   value="" class="soob-session-end-time" />
                        </td>
                        <td>
                            <button type="button" class="button button-secondary soob-remove-session">
                                <?php _e('Remove', 'soob-plugin'); ?>
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
        if (!wp_verify_nonce($_POST['nonce'], 'soob_admin_nonce')) {
            wp_die(__('Security check failed', 'soob-plugin'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'soob-plugin'));
        }
        
        $booking_id = intval($_POST['booking_id']);
        
        if (!$booking_id) {
            wp_send_json_error(__('Invalid booking ID', 'soob-plugin'));
        }
        
        $result = SOOB_Booking::delete($booking_id);
        
        if ($result) {
            wp_send_json_success(__('Booking deleted successfully', 'soob-plugin'));
        } else {
            wp_send_json_error(__('Failed to delete booking', 'soob-plugin'));
        }
    }
    
    /**
     * AJAX handler for getting bookings (for future use)
     */
    public function ajax_get_bookings()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'soob_admin_nonce')) {
            wp_die(__('Security check failed', 'soob-plugin'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'soob-plugin'));
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
        
        $bookings = SOOB_Booking::get_all($filters);
        wp_send_json_success($bookings);
    }
    
    /**
     * Handle booking save from edit form
     */
    private function handle_booking_save($booking_id)
    {
        // Validate and sanitize input
        $client_name = sanitize_text_field($_POST['client_name']);
        $customer_gender = sanitize_text_field($_POST['customer_gender']);
        $customer_age = intval($_POST['customer_age']);
        $booking_status = sanitize_text_field($_POST['booking_status']);
        $course_start_date = sanitize_text_field($_POST['course_start_date']);
        $assigned_provider = intval($_POST['assigned_provider']);
        
        // Validate required fields
        $errors = array();
        
        if (empty($client_name)) {
            $errors[] = __('Client name is required.', 'soob-plugin');
        }
        
        if (!in_array($customer_gender, array('male', 'female'))) {
            $errors[] = __('Please select a valid gender.', 'soob-plugin');
        }
        
        if ($customer_age < 1 || $customer_age > 100) {
            $errors[] = __('Please enter a valid age between 1 and 100.', 'soob-plugin');
        }
        
        if (!in_array($booking_status, array('pending', 'approved', 'canceled'))) {
            $errors[] = __('Please select a valid booking status.', 'soob-plugin');
        }
        
        if (!empty($course_start_date) && !strtotime($course_start_date)) {
            $errors[] = __('Please enter a valid course start date.', 'soob-plugin');
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
            'provider_id' => $assigned_provider > 0 ? $assigned_provider : null,
            'selected_slots' => wp_json_encode($sessions)
        );
        
        // Update booking
        $result = SOOB_Booking::update($booking_id, $update_data);
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>' . __('Booking updated successfully!', 'soob-plugin') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to update booking. Please try again.', 'soob-plugin') . '</p></div>';
        }
    }
}
