<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://smgolamzilani.com
 * @since             1.0.0
 * @package           Wp_Cashback_Wallet
 *
 * @wordpress-plugin
 * Plugin Name:       WpCashbackWallet
 * Plugin URI:        http://smgolamzilani.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            rasel hossain
 * Author URI:        http://smgolamzilani.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-cashback-wallet
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'WP_CASHBACK_WALLET_PLUGIN_FILE' ) ) {
    define( 'WP_CASHBACK_WALLET_PLUGIN_FILE', __FILE__);
}
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WP_CASHBACK_WALLET_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-cashback-wallet-activator.php
 */
function activate_wp_cashback_wallet() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-cashback-wallet-activator.php';
	Wp_Cashback_Wallet_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-cashback-wallet-deactivator.php
 */
function deactivate_wp_cashback_wallet() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-cashback-wallet-deactivator.php';
	Wp_Cashback_Wallet_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_cashback_wallet' );
register_deactivation_hook( __FILE__, 'deactivate_wp_cashback_wallet' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-cashback-wallet.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_cashack_wallet() {

	$plugin = new Wp_Cashback_Wallet();
	$plugin->run();

}
run_wp_cashack_wallet();



/*
 * Tab
 */
add_filter('woocommerce_product_data_tabs', 'wpcbw_product_settings_tabs' );
function wpcbw_product_settings_tabs( $tabs ){
 
	//unset( $tabs['inventory'] );
 
	$tabs['Cashback'] = array(
		'label'    => 'Cashback',
		'target'   => 'cashback_product_data',
		'class'    => array(''),
		'priority' => 21,
	);
	return $tabs;
 
}
 
/*
 * Tab content
 */
add_action( 'woocommerce_product_data_panels', 'wpcbw_cashback_product_panels' );
function wpcbw_cashback_product_panels(){
 
	echo '<div id="cashback_product_data" class="panel woocommerce_options_panel hidden">';
 
	woocommerce_wp_textarea_input( array(
		'id'          => 'cashback_amount',
		'value'       => get_post_meta( get_the_ID(), 'cashback_amount', true ),
		'label'       => 'Cashback Amount',
		'desc_tip'    => true,
		'description' => 'Set % amount for cashback',
	) );
 
	
 
	echo '</div>';
 
}

add_action('woocommerce_process_product_meta', 'product_meta_cashback_save',20,1);

function product_meta_cashback_save($post_id) {
	$product = wc_get_product($post_id);
	$cashback_amount = isset($_POST['cashback_amount']) ? $_POST['cashback_amount'] : '';
	$product->update_meta_data('cashback_amount', sanitize_text_field($cashback_amount));
	$product->save();
}


// add_action( 'woocommerce_checkout_process', 'bt_add_checkout_checkbox_warning' );
// /**
//  * Alert if checkbox not checked
//  */ 
// function bt_add_checkout_checkbox_warning() {
//     if ( ! (int) isset( $_POST['checkout-checkbox'] ) ) {
//         wc_add_notice( __( 'Please acknowledge the Checkbox' ), 'error' );
//     }
// }





// $cash_wallet_title = intval(get_option( 'cash_wallet_cashback_percentise', 30 )? get_option( 'cash_wallet_cashback_percentise', 30 ):30);

//var_dump(maximum_refund_available(9161));
//var_dump( get_post_meta(9195, 'cash_wallet_partial_payment_refunded', true) );
//var_dump(get_withdraw_request_amount(1)->user_id);

function wpb_woo_endpoint_title( $title, $id ) {

	if ( is_wc_endpoint_url( 'site-wallet-transactions' ) && in_the_loop() ) { // add your endpoint urls
		$title = "Kidomen Points"; // change your entry-title
	}

	elseif ( is_wc_endpoint_url( 'cash-wallet-transactions' ) && in_the_loop() ) {
		$title = "Cash Points";
	}

	elseif ( is_wc_endpoint_url( 'edit-account' ) && in_the_loop() ) {
		$title = "Change My Details";
	}
	return $title;
}

add_filter( 'the_title', 'wpb_woo_endpoint_title', 10, 2 );