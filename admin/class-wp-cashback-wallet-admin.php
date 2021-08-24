<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://smgolamzilani.com
 * @since      1.0.0
 *
 * @package    Wp_Cashback_Wallet
 * @subpackage Wp_Cashback_Wallet/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Cashback_Wallet
 * @subpackage Wp_Cashback_Wallet/admin
 * @author     rasel hossain <raselsec@gmail.com>
 */
class Wp_Cashback_Wallet_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_action('manage_users_columns', array($this,'register_user_wallet_balance_column'));
		add_action('manage_users_custom_column', array($this,'register_user_wallet_balance_column_view'), 10, 3);

		add_action( 'woocommerce_admin_order_totals_after_tax', array($this, 'wpcb_add_cashback_info_to_order' ), 10, 1);
		
		add_action( 'woocommerce_admin_order_totals_after_refunded', array($this, 'wpcb_add_cashback_info_after_refund_section' ), 10, 1);
		add_action( 'wpcb_wallet_partial_order_refunded', array($this, 'wpcb_wallet_payment_order_refunded' ), 10, 2);
		add_action('wpcb_wallet_order_refunded', array($this, 'wpcb_wallet_order_refunded_function'), 10, 2 );
		

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Cashback_Wallet_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Cashback_Wallet_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-cashback-wallet-admin.css', array(), $this->version, 'all' );
       
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Cashback_Wallet_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Cashback_Wallet_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-cashback-wallet-admin.js', array( 'jquery' ), $this->version, false );

	}
    public function register_user_wallet_balance_column($columns) {
		$columns['site_wallet_balance'] = 'Site Wallet';
		$columns['cash_wallet_balance'] = 'Cash Wallet';
		return $columns;
	}
	
	public function register_user_wallet_balance_column_view($value, $column_name, $user_id) {
		$user_info = get_userdata( $user_id );
		$cash_wallet_link =  add_query_arg(array('page' => 'wpcb-wallet-transactions', 'user_id' => $user_id, 'wallet_type' => 'cash'), admin_url('admin.php'));
		$site_wallet_link =  add_query_arg(array('page' => 'wpcb-wallet-transactions', 'user_id' => $user_id, 'wallet_type' => 'site'), admin_url('admin.php'));
		
		if($column_name == 'site_wallet_balance') return "<a href='$site_wallet_link'>".get_wallet_balance('site', $user_id) . "</a>";
		if($column_name == 'cash_wallet_balance') return "<a href='$cash_wallet_link'>".get_wallet_balance('cash', $user_id) . "</a>";
		return $value;
	
	}
	public function wpcb_add_cashback_info_to_order($order_id){
		$total_cashback = total_cashback_amount($order_id);//floatval(get_post_meta($order_id, '_cashback_amount_to_site_wallet', true)) +  floatval(get_post_meta($order_id, '_cashback_amount_to_cash_wallet', true));
                    
		if ( get_post_meta($order_id, '_cashback_to_wallet', true) ) : ?>
		<tr>
			<td class="label"><?php esc_html_e( 'Already get cashback', 'woocommerce' ); ?>:</td>
			<td width="1%"></td>
			<td class="total cashback-total">
				-<?php echo price_with_currency($total_cashback); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<?php endif; 
	}

    
	public function wpcb_add_cashback_info_after_refund_section($order_id){
		$total_cashback = total_cashback_amount($order_id);
		$order = wc_get_order( $order_id );           
		if ( get_post_meta($order_id, '_cashback_to_wallet', true) ) : ?>
			<tr class="cashback-total-row">
				<td class="label refunded-total"><?php esc_html_e( 'Already get cashback', 'woocommerce' ); ?>:</td>
				<td width="1%"></td>
				<td class="total refunded-total">-<?php echo price_with_currency($total_cashback); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
			</tr>
		<?php endif; 
	}
	public function wpcb_wallet_payment_order_refunded($order_id, $transaction_id){
		$order = wc_get_order($order_id);
		$is_site_wallet_payment = abs(get_order_partial_payment_amount( $order_id , 'site'));
		$is_cash_wallet_payment = abs(get_order_partial_payment_amount( $order_id , 'cash'));
		// var_dump($is_cash_wallet_payment);
		// var_dump(get_post_meta($order_id, '_cash_wallet_partial_payment_refunded', true));
		// exit;

		if($order->get_total()==0){
			if(get_post_meta($order_id, '_cashback_to_wallet', true)){
				
					if($is_cash_wallet_payment > 0){
						if( get_post_meta($order_id, '_cash_wallet_partial_payment_refunded', true) ){
							$order->update_status('refunded');
						}else{
							return;
						}
					}else{
						$order->update_status('refunded');
					}
			}else{
			
				if($is_cash_wallet_payment > 0 &&  $is_site_wallet_payment > 0){
					
					if(get_post_meta($order_id, '_site_wallet_partial_payment_refunded', true) && get_post_meta($order_id, '_cash_wallet_partial_payment_refunded', true) ){
						$order->update_status('refunded');
					}else{
						return;
					}
				}
				else if($is_cash_wallet_payment > 0 &&  $is_site_wallet_payment == 0){
				
					
				  
					if( get_post_meta($order_id, '_cash_wallet_partial_payment_refunded', true) ){
					
						$order->update_status('refunded');
					}else{
						return;
					}
				}
				else if($is_cash_wallet_payment== 0 &&  $is_site_wallet_payment > 0){
					if( get_post_meta($order_id, '_site_wallet_partial_payment_refunded', true) ){
						$order->update_status('refunded');
					}else{
						return;
					}
				}else{
					$order->update_status('refunded');
				}
			}
		}else{
			return;
		}
		
	
	}
    public function wpcb_wallet_order_refunded_function( $order_id, $transaction_id){
		$order = wc_get_order( $order_id );
		$is_site_wallet_payment = abs(get_order_partial_payment_amount( $order_id , 'site'));
		$is_cash_wallet_payment = abs(get_order_partial_payment_amount( $order_id , 'cash'));
		// var_dump(get_post_meta($order_id, '_cash_wallet_partial_payment_refunded', true));
		// exit;
		if(maximum_refund_available($order_id) == 0 ){
			if(get_post_meta($order_id, '_cashback_to_wallet', true)){
				
				if($is_cash_wallet_payment > 0){
					if( get_post_meta($order_id, '_cash_wallet_partial_payment_refunded', true) ){
						$order->update_status('refunded');
					}else{
						
					 	return;
					}
				}else{
					$order->update_status('refunded');
				}
		}else{
			if($is_cash_wallet_payment > 0 &&  $is_site_wallet_payment > 0){
				
				if(get_post_meta($order_id, '_site_wallet_partial_payment_refunded', true) && get_post_meta($order_id, '_cash_wallet_partial_payment_refunded', true) ){
					$order->update_status('refunded');
				}else{
					return;
				}
			}
			else if($is_cash_wallet_payment > 0 &&  $is_site_wallet_payment == 0){
				if( get_post_meta($order_id, '_cash_wallet_partial_payment_refunded', true) ){
					$order->update_status('refunded');
				}else{
					return;
				}
			}
			else if($is_cash_wallet_payment== 0 &&  $is_site_wallet_payment > 0){
				if( get_post_meta($order_id, '_site_wallet_partial_payment_refunded', true) ){
					$order->update_status('refunded');
				}else{
					return;
				}
			}else{
				$order->update_status('refunded');
			}
		}
	   }
	}
    
    /**
	 * Wallet page
	 */
	public function wp_cashback_wallet_page() {
        //set the settings
        
        echo "Wallet Page";
	
    }
    

}
