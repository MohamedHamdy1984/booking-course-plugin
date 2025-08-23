<?php

/**
 * Plugin Name: Simple One-to-One Booking
 * Plugin URI: https://example.com/simple-one-to-one-booking
 * Description: Book one-to-one sessions with any provider (provider, therapist, coach) via WooCommerce checkout with timezone-aware scheduling.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-one-to-one-booking
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
define('SOOB_PLUGIN_VERSION', '1.0.0');
define('SOOB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SOOB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SOOB_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SOOB_PLUGIN_FILE', __FILE__);

/**
 * Main SOOB Plugin Class
 */
class SOOB_Plugin
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
        load_plugin_textdomain('simple-one-to-one-booking', false, dirname(SOOB_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Check WooCommerce dependency
     */
    public function check_woocommerce_dependency()
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            deactivate_plugins(SOOB_PLUGIN_BASENAME);
        }
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice()
    {
?>
        <div class="notice notice-error">
            <p><?php _e('Soob Plugin requires WooCommerce to be installed and active.', 'soob-plugin'); ?></p>
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
            error_log("Soob Plugin: File not found - " . $file_path);
        }
    }

    /**
     * Load dependencies
     */
    private function load_dependencies()
    {
        // Core includes
        $this->safe_require(SOOB_PLUGIN_PATH . 'includes/class-soob-database.php');
        $this->safe_require(SOOB_PLUGIN_PATH . 'includes/class-soob-provider.php');
        $this->safe_require(SOOB_PLUGIN_PATH . 'includes/class-soob-booking.php');
        $this->safe_require(SOOB_PLUGIN_PATH . 'includes/class-soob-woocommerce.php');

        // Admin includes
        if (is_admin()) {
            $this->safe_require(SOOB_PLUGIN_PATH . 'admin/class-soob-admin.php');
            $this->safe_require(SOOB_PLUGIN_PATH . 'admin/class-soob-admin-providers.php');
            $this->safe_require(SOOB_PLUGIN_PATH . 'admin/class-soob-admin-schedule.php');
        }

        // Public includes
        $this->safe_require(SOOB_PLUGIN_PATH . 'public/class-soob-checkout.php');
    }


    /**
     * Initialize admin functionality
     */
    private function init_admin()
    {
        if (is_admin() && class_exists('SOOB_Admin')) {
            new SOOB_Admin();
        }
    }

    /**
     * Initialize Checkout functionality
     */
    private function init_checkout()
    {
        // Initialize checkout functionality for AJAX handling
        if (class_exists('SOOB_Checkout')) {
            new SOOB_Checkout();
        }
    }

    /**
     * Initialize WooCommerce functionality
     */
    private function init_woocommerce()
    {
        // Initialize WooCommerce integration for both admin and frontend
        if (class_exists('WooCommerce') && class_exists('SOOB_WooCommerce')) {
            new SOOB_WooCommerce();
        }
    }


    

}

/**
 * Plugin activation hook
 */
function soob_plugin_activate()
{
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        wp_die(__('Soob Plugin requires WordPress 5.0 or higher.', 'soob-plugin'));
    }

    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die(__('Soob Plugin requires PHP 7.4 or higher.', 'soob-plugin'));
    }

    // Create database tables
    require_once SOOB_PLUGIN_PATH . 'includes/class-soob-database.php';
    SOOB_Database::create_tables();

    // Set default options
    add_option('soob_plugin_version', SOOB_PLUGIN_VERSION);
    add_option('soob_plugin_activated', time());

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 */
function soob_plugin_deactivate()
{
    // Clear scheduled events
    wp_clear_scheduled_hook('soob_cleanup_expired_bookings');

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin uninstall hook
 */
function soob_plugin_uninstall()
{
    // Remove options
    delete_option('soob_plugin_version');
    delete_option('soob_plugin_activated');

    // Remove database tables (optional - uncomment if needed)
    // require_once SOOB_PLUGIN_PATH . 'includes/class-soob-database.php';
    // SOOB_Database::drop_tables();
}

// Register hooks
register_activation_hook(__FILE__, 'soob_plugin_activate');
register_deactivation_hook(__FILE__, 'soob_plugin_deactivate');
register_uninstall_hook(__FILE__, 'soob_plugin_uninstall');

// Initialize the plugin
add_action('plugins_loaded', array('SOOB_Plugin', 'get_instance'));
