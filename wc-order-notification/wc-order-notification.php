<?php
/**
 * Plugin Name: WC Order Notification
 * Plugin URI:  https://example.com/plugins/wc-order-notification
 * Description: Notifies users if they order a product already in an open order or ordered in the last three months, with option to ignore and links to existing orders.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: wc-order-notification
 * Domain Path: /languages
 *
 * Requires at least: 5.0
 * Tested up to: 6.2
 * WC requires at least: 3.0
 * WC tested up to: 7.0
 *
 * @package WC_Order_Notification
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Order_Notification' ) ) :

final class WC_Order_Notification {

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Singleton instance.
     *
     * @var WC_Order_Notification
     */
    protected static $_instance = null;

    /**
     * Main WC_Order_Notification Instance.
     *
     * @return WC_Order_Notification
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
            self::$_instance->init_hooks();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        // Intentionally left empty.
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Load plugin textdomain for translations.
        add_action( 'init', array( $this, 'load_textdomain' ) );

        // Check WooCommerce dependency.
        add_action( 'admin_notices', array( $this, 'check_woocommerce_active' ) );

        // Include required files.
        $this->includes();

        // Initialize classes.
        add_action( 'plugins_loaded', array( $this, 'init_classes' ) );
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'wc-order-notification', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Check if WooCommerce is active.
     */
    public function check_woocommerce_active() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            echo '<div class="error"><p><strong>' . esc_html__( 'WC Order Notification requires WooCommerce to be installed and active.', 'wc-order-notification' ) . '</strong></p></div>';
        }
    }

    /**
     * Include required files.
     */
    private function includes() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-duplicate-checker.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-notification-handler.php';
    }

    /**
     * Initialize classes.
     */
    public function init_classes() {
        if ( class_exists( 'WC_Duplicate_Checker' ) && class_exists( 'WC_Notification_Handler' ) ) {
            $duplicate_checker = new WC_Duplicate_Checker();
            $notification_handler = new WC_Notification_Handler( $duplicate_checker );
        }
    }
}

/**
 * Returns the main instance of WC_Order_Notification.
 *
 * @return WC_Order_Notification
 */
function WC_Order_Notification() {
    return WC_Order_Notification::instance();
}

// Initialize the plugin.
WC_Order_Notification();

endif;
