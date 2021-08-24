<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://smgolamzilani.com
 * @since      1.0.0
 *
 * @package    Wp_Cashback_Wallet
 * @subpackage Wp_Cashback_Wallet/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Cashback_Wallet
 * @subpackage Wp_Cashback_Wallet/includes
 * @author     rasel hossain <raselsec@gmail.com>
 */
class Wp_Cashback_Wallet {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Cashback_Wallet_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// if ( defined( 'WP_CASHBACK_WALLET_VERSION' ) ) {
		// 	$this->version = WP_CASHBACK_WALLET_VERSION;
		// } else {
		// 	$this->version = '1.0.0';
		// }
		$this->plugin_name = 'wp-cashback-wallet';
        $this->define_constants();
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	private function define_constants() {
        $this->define( 'WP_CASHBACK_WALLET_ABSPATH', dirname(WP_CASHBACK_WALLET_PLUGIN_FILE) . '/' );
        $this->define( 'WP_CASHBACK_WALLET_PLUGIN_FILE', plugin_basename(WP_CASHBACK_WALLET_PLUGIN_FILE) );
        $this->define( 'WP_CASHBACK_WALLET_VERSION', '1.3.16' );
    }
	private function define( $name, $value ) {
        if ( ! defined( $name) ) {
            define( $name, $value );
        }
    }

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Cashback_Wallet_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Cashback_Wallet_i18n. Defines internationalization functionality.
	 * - Wp_Cashback_Wallet_Admin. Defines all hooks for the admin area.
	 * - Wp_Cashback_Wallet_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-cashback-wallet-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-cashback-wallet-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-cashback-wallet-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-list-table-refund-request.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-list-table-withdraw-request.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-list-table-transaction-details.php';
		
		
		
	
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-cashback-wallet-settings.php';
	
		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'helper/wp-cashback-wallet-functions.php';
		
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-cashback-wallet-public.php';
		
		/**
		  * The class responsible for defining all actions that occur in the Frontend area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front-end/wp-cashback-wallet-frontend.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front-end/wp-cashback-wallet-checkout.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'front-end/wp-cashback-wallet-refund.php';
		
		$this->loader = new Wp_Cashback_Wallet_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Cashback_Wallet_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Cashback_Wallet_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wp_Cashback_Wallet_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Cashback_Wallet_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Cashback_Wallet_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	

}
