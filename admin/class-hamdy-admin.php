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
            // Enqueue common admin styles
            wp_enqueue_style('hamdy-admin', HAMDY_PLUGIN_URL . 'assets/css/admin.css', array(), HAMDY_PLUGIN_VERSION);
            
            // Enqueue specific scripts based on page
            if ($hook === 'hamdy-booking_page_hamdy-schedule') {
                $this->schedule_admin->enqueue_scripts();
            } elseif ($hook === 'hamdy-booking_page_hamdy-teachers') {
                $this->teachers_admin->enqueue_scripts();
            }
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
    ?>
        <div class="wrap">
            <h1><?php _e('Bookings Management', 'hamdy-plugin'); ?></h1>

            <div class="hamdy-bookings-list">
                <?php $this->display_bookings_table(); ?>
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
    private function display_bookings_table()
    {
        $bookings = Hamdy_Booking::get_all();

        if (empty($bookings)) {
            echo '<p>' . __('No bookings found.', 'hamdy-plugin') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('ID', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Order ID', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Customer', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Gender', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Age', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Timezone', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Date', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Status', 'hamdy-plugin') . '</th>';
        echo '<th>' . __('Actions', 'hamdy-plugin') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($bookings as $booking) {
            $customer = get_user_by('id', $booking->customer_id);
            echo '<tr>';
            echo '<td>' . $booking->id . '</td>';
            echo '<td>#' . $booking->order_id . '</td>';
            echo '<td>' . ($customer ? $customer->display_name : __('Guest', 'hamdy-plugin')) . '</td>';
            echo '<td>' . esc_html(ucfirst($booking->customer_gender)) . '</td>';
            echo '<td>' . esc_html($booking->customer_age) . '</td>';
            echo '<td>' . esc_html($booking->timezone) . '</td>';
            echo '<td>' . date('M j, Y', strtotime($booking->booking_date)) . '</td>';
            echo '<td><span class="hamdy-status hamdy-status-' . $booking->status . '">' . ucfirst($booking->status) . '</span></td>';
            echo '<td>';
            echo '<a href="#" class="button button-small">' . __('Edit', 'hamdy-plugin') . '</a> ';
            echo '<a href="#" class="button button-small button-link-delete">' . __('Delete', 'hamdy-plugin') . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }
}
