<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://smgolamzilani.com
 * @since      1.0.0
 *
 * @package    Wp_Cashback_Wallet
 * @subpackage Wp_Cashback_Wallet/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wp_Cashback_Wallet
 * @subpackage Wp_Cashback_Wallet/includes
 * @author     rasel hossain <raselsec@gmail.com>
 */
class Wp_Cashback_Wallet_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wp-cashback-wallet',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
