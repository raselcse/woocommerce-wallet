<?php

/**
 * Fired during plugin activation
 *
 * @link       http://smgolamzilani.com
 * @since      1.0.0
 *
 * @package    Wp_Cashback_Wallet
 * @subpackage Wp_Cashback_Wallet/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Cashback_Wallet
 * @subpackage Wp_Cashback_Wallet/includes
 * @author     rasel hossain <raselsec@gmail.com>
 */
class Wp_Cashback_Wallet_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_tables();
	}

	private static function create_tables() {
        global $wpdb;
        $wpdb->hide_errors();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta( self::get_schema() );
    }

    /**
     * Plugin table schema
     * @global object $wpdb
     * @return string
     */
    private static function get_schema() {
        global $wpdb;
        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }
        $tables = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}cashback_cash_wallet_transactions (
            transaction_id BIGINT UNSIGNED NOT NULL auto_increment,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            type varchar(200 ) NOT NULL,
            amount DECIMAL( 10,2 ) NOT NULL,
            balance DECIMAL( 10,2 ) NOT NULL,
            currency varchar(20 ) NOT NULL,
            details longtext NULL,
            created_by BIGINT UNSIGNED NOT NULL DEFAULT 1,
            deleted tinyint(1 ) NOT NULL DEFAULT 0,
            date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (transaction_id ),
            KEY user_id (user_id )
        ) $collate;
        CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}cashback_site_wallet_transactions (
            transaction_id BIGINT UNSIGNED NOT NULL auto_increment,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            type varchar(200 ) NOT NULL,
            amount DECIMAL( 10,2 ) NOT NULL,
            balance DECIMAL( 10,2 ) NOT NULL,
            currency varchar(20 ) NOT NULL,
            details longtext NULL,
            created_by BIGINT UNSIGNED NOT NULL DEFAULT 1,
            deleted tinyint(1 ) NOT NULL DEFAULT 0,
            date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (transaction_id ),
            KEY user_id (user_id )
        ) $collate;
        CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}cashback_refund_request (
            id BIGINT UNSIGNED NOT NULL auto_increment,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            refund_reason longtext NULL,
            status varchar(20 ) NOT NULL DEFAULT 'pending',
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_by BIGINT UNSIGNED NULL,
            PRIMARY KEY  (id ),
            KEY user_id (user_id )
        ) $collate;
        CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}cashback_withdrawal_request (
            id BIGINT UNSIGNED NOT NULL auto_increment,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            withdraw_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
            withdraw_method longtext NULL,
            widthraw_account_details longtext NOT NULL,
            others_note longtext NULL,
            status varchar(20 ) NOT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (id ),
            KEY user_id (user_id )
        ) $collate;";
        return $tables;
    }

}
