<?php

/**
 * Plugin Name: Hamdy Plugin
 * Plugin URI: https://example.com/hamdy-plugin
 * Description: A simple one-to-one booking system integrated with WooCommerce for live course sessions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hamdy-plugin
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HAMDY_PLUGIN_VERSION', '1.0.0');
define('HAMDY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HAMDY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HAMDY_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Hamdy Plugin Class
 */
class Hamdy_Plugin
{

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Check if WooCommerce is active
        add_action('admin_init', array($this, 'check_woocommerce_dependency'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Initialize the plugin
     */
    public function init()
    {
        // Load required files
        $this->load_dependencies();

        // Initialize components
        $this->init_admin();
        $this->init_public();
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('hamdy-plugin', false, dirname(HAMDY_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Check WooCommerce dependency
     */
    public function check_woocommerce_dependency()
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            deactivate_plugins(HAMDY_PLUGIN_BASENAME);
        }
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice()
    {
?>
        <div class="notice notice-error">
            <p><?php _e('Hamdy Plugin requires WooCommerce to be installed and active.', 'hamdy-plugin'); ?></p>
        </div>
<?php
    }

    /**
     * Load dependencies
     */
    private function load_dependencies()
    {
        // Core includes
        require_once HAMDY_PLUGIN_PATH . 'includes/class-hamdy-database.php';
        require_once HAMDY_PLUGIN_PATH . 'includes/class-hamdy-teacher.php';
        require_once HAMDY_PLUGIN_PATH . 'includes/class-hamdy-booking.php';
        require_once HAMDY_PLUGIN_PATH . 'includes/class-hamdy-woocommerce.php';

        // Admin includes
        if (is_admin()) {
            require_once HAMDY_PLUGIN_PATH . 'admin/class-hamdy-admin.php';
            require_once HAMDY_PLUGIN_PATH . 'admin/class-hamdy-admin-teachers.php';
            require_once HAMDY_PLUGIN_PATH . 'admin/class-hamdy-admin-schedule.php';
        }

        // Public includes
        if (!is_admin()) {
            require_once HAMDY_PLUGIN_PATH . 'public/class-hamdy-public.php';
            require_once HAMDY_PLUGIN_PATH . 'public/class-hamdy-checkout.php';
        }
    }

    /**
     * Initialize admin functionality
     */
    private function init_admin()
    {
        if (is_admin() && class_exists('Hamdy_Admin')) {
            new Hamdy_Admin();
        }
    }

    /**
     * Initialize public functionality
     */
    private function init_public()
    {
        if (!is_admin() && class_exists('Hamdy_Public')) {
            new Hamdy_Public();
        }
    }

    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets()
    {
        // CSS
        wp_enqueue_style(
            'hamdy-public-style',
            HAMDY_PLUGIN_URL . 'assets/css/public.css',
            array(),
            HAMDY_PLUGIN_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'hamdy-public-script',
            HAMDY_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            HAMDY_PLUGIN_VERSION,
            true
        );

        // Localize script
        wp_localize_script('hamdy-public-script', 'hamdy_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hamdy_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'hamdy-plugin'),
                'error' => __('An error occurred. Please try again.', 'hamdy-plugin'),
            )
        ));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on our admin pages
        if (strpos($hook, 'hamdy') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'hamdy-admin-style',
            HAMDY_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HAMDY_PLUGIN_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'hamdy-admin-script',
            HAMDY_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable'),
            HAMDY_PLUGIN_VERSION,
            true
        );

        // Localize script
        wp_localize_script('hamdy-admin-script', 'hamdy_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hamdy_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'hamdy-plugin'),
                'loading' => __('Loading...', 'hamdy-plugin'),
                'saved' => __('Saved successfully!', 'hamdy-plugin'),
                'error' => __('An error occurred. Please try again.', 'hamdy-plugin'),
            )
        ));

        if ($hook === 'toplevel_page_hamdy_teachers') {

            // Enqueue specific styles and scripts for Teachers page
            wp_enqueue_style(
                'hamdy-admin-teachers-style',
                HAMDY_PLUGIN_URL . 'assets/css/admin-teachers.css',
                array(),
                HAMDY_PLUGIN_VERSION
            );
            
            wp_enqueue_script(
                'hamdy-admin-teachers-script',
                HAMDY_PLUGIN_URL . 'assets/js/admin-teachers.js',
                array('jquery'),
                HAMDY_PLUGIN_VERSION,
                true
            );
        }
    }
}

/**
 * Plugin activation hook
 */
function hamdy_plugin_activate()
{
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        wp_die(__('Hamdy Plugin requires WordPress 5.0 or higher.', 'hamdy-plugin'));
    }

    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die(__('Hamdy Plugin requires PHP 7.4 or higher.', 'hamdy-plugin'));
    }

    // Create database tables
    require_once HAMDY_PLUGIN_PATH . 'includes/class-hamdy-database.php';
    Hamdy_Database::create_tables();

    // Set default options
    add_option('hamdy_plugin_version', HAMDY_PLUGIN_VERSION);
    add_option('hamdy_plugin_activated', time());

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 */
function hamdy_plugin_deactivate()
{
    // Clear scheduled events
    wp_clear_scheduled_hook('hamdy_cleanup_expired_bookings');

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin uninstall hook
 */
function hamdy_plugin_uninstall()
{
    // Remove options
    delete_option('hamdy_plugin_version');
    delete_option('hamdy_plugin_activated');

    // Remove database tables (optional - uncomment if needed)
    // require_once HAMDY_PLUGIN_PATH . 'includes/class-hamdy-database.php';
    // Hamdy_Database::drop_tables();
}

// Register hooks
register_activation_hook(__FILE__, 'hamdy_plugin_activate');
register_deactivation_hook(__FILE__, 'hamdy_plugin_deactivate');
register_uninstall_hook(__FILE__, 'hamdy_plugin_uninstall');

// Initialize the plugin
add_action('plugins_loaded', array('Hamdy_Plugin', 'get_instance'));
