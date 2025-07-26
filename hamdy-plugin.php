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

        
        //  WooCommerce HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
    }


    /**
     * Declare HPOS compatibility with WooCommerce
     */
    public function declare_hpos_compatibility()
    {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
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
     * Initialize the plugin
     */
    public function init()
    {
        // Load required files
        $this->load_dependencies();

        // Initialize components
        $this->init_admin();
        $this->init_checkout();
        $this->init_woocommerce();
    }


    /**
     * Safely require a PHP file if it exists.
     *
     * @param string $file_path Full path to the file.
     */
    private function safe_require($file_path)
    {
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            // Optional: log missing file for debugging
            error_log("Hamdy Plugin: File not found - " . $file_path);
        }
    }

    /**
     * Load dependencies
     */
    private function load_dependencies()
    {
        // Core includes
        $this->safe_require(HAMDY_PLUGIN_PATH . 'includes/class-hamdy-database.php');
        $this->safe_require(HAMDY_PLUGIN_PATH . 'includes/class-hamdy-teacher.php');
        $this->safe_require(HAMDY_PLUGIN_PATH . 'includes/class-hamdy-booking.php');
        $this->safe_require(HAMDY_PLUGIN_PATH . 'includes/class-hamdy-woocommerce.php');

        // Admin includes
        if (is_admin()) {
            $this->safe_require(HAMDY_PLUGIN_PATH . 'admin/class-hamdy-admin.php');
            $this->safe_require(HAMDY_PLUGIN_PATH . 'admin/class-hamdy-admin-teachers.php');
            $this->safe_require(HAMDY_PLUGIN_PATH . 'admin/class-hamdy-admin-schedule.php');
        }

        // Public includes
        $this->safe_require(HAMDY_PLUGIN_PATH . 'public/class-hamdy-checkout.php');
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
     * Initialize Checkout functionality
     */
    private function init_checkout()
    {
        // Initialize checkout functionality for AJAX handling
        if (class_exists('Hamdy_Checkout')) {
            new Hamdy_Checkout();
        }
    }

    /**
     * Initialize WooCommerce functionality
     */
    private function init_woocommerce()
    {
        // Initialize WooCommerce integration for both admin and frontend
        if (class_exists('WooCommerce') && class_exists('Hamdy_WooCommerce')) {
            new Hamdy_WooCommerce();
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
