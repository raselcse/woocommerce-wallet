<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('Wp_Cashback_Wallet_Refund')) {

    class Wp_Cashback_Wallet_Refund {
        /**
         * The single instance of the class.
         *
         * @var Wp_Cashback_Wallet_Refund
         * @since 1.1.10
         */
        protected static $_instance = null;
        public static $user_id =0;
        public $is_cashback_product = false;
        public $order_id =0;
        public $product_id =0;
        public $site_wallet_title;
        public $cash_wallet_title;
        /**
         * Main instance
         * @return class object
         */
        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Class constructor
         */
        public function __construct() {
            global $post;
            //$this->user_id = get_current_user_id();
            $this->site_wallet_title  = get_option( 'site_wallet_title') ?strtolower(get_option( 'site_wallet_title')):'site wallet';
            $this->cash_wallet_title = get_option( 'cash_wallet_title') ?strtolower(get_option( 'cash_wallet_title')):'cash wallet';
           
            add_filter( 'woocommerce_my_account_my_orders_actions', array($this, 'add_my_account_my_orders_custom_action'), 10, 2 );
            // Jquery script
            add_action( 'woocommerce_after_account_orders', array($this, 'action_after_account_orders_js') );
            add_action('woocommerce_after_order_fee_item_name', array($this, 'woocommerce_after_order_fee_item_name_callback'), 10, 2);

            add_action( 'wp_ajax_woo_wallet_order_refund', array( $this, 'wpcb_wallet_order_refund' ) );
            add_action('wp_ajax_woo_wallet_refund_partial_payment', array($this, 'wpcb_wallet_refund_partial_payment'));
            add_action('wp_ajax_wpcb_wallet_refund_request', array($this, 'wpcb_wallet_send_refund_request'));
                
            wp_register_script( 'woo_wallet_admin_order', plugin_dir_url( __DIR__ ) . 'assets/js/admin/admin-order.js', array( 'jquery', 'wc-admin-order-meta-boxes' ), WP_CASHBACK_WALLET_VERSION);
         
         
           // $order = wc_get_order( $post->ID );
            wp_enqueue_script( 'woo_wallet_admin_order' );
            $order_localizer = array(
                'order_id' => $post->ID,
                //'payment_method' => $order->get_payment_method( 'edit' ),
                'default_price' => wc_price( 0 ),
                'is_refundable' => true,//apply_filters( 'woo_wallet_is_order_refundable', ( ! is_wallet_rechargeable_order( $order ) && $order->get_payment_method( 'edit' ) != 'wallet' ) && $order->get_customer_id( 'edit' ), $order ),
                'i18n' => array(
                    'refund' => __( 'Refund', 'woo-wallet' ),
                    'via_wallet' => __( 'to cash wallet', 'woo-wallet' )
                )
            );
            wp_localize_script( 'woo_wallet_admin_order', 'woo_wallet_admin_order_param', $order_localizer);
        
    
        }

        // Your additional action button
        function add_my_account_my_orders_custom_action( $actions, $order ) {
            if ( $order->has_status( 'completed' ) ) {
                $status = is_refund_request_submit($order->ID)?is_refund_request_submit($order->ID):'';
                if(!empty($status)){
                    $action_slug = 'refund_request_'.$status;
                    $name = 'Refund request '.$status;
                }else{
                    $action_slug = 'refund_request';
                    $name = 'Refund request';
                }
                
                $actions[$action_slug] = array(
                    'url'  => '#',
                    'name' => $name,
                    'id'   => 'refund_request_button'
                );
                
             
            }
            return $actions;
        }


        function action_after_account_orders_js() {
            ?>
        <script>
        jQuery(function($){
                $('a.refund_request').each( function(){
                   var order_id =  $(this).parent().parent().find('.woocommerce-orders-table__cell-order-number').text();
                   order_id = (order_id.replace(/\s/g, '')).substring(1);
                 
                   console.log(order_id);
                   $(this).attr('order-id',  parseInt(order_id));
                })
            })
        </script>
         <?php
        }
        
          /**
         * Add refund button to WooCommerce order page.
         * @param int $item_id
         * @param Object $item
         */
        public function woocommerce_after_order_fee_item_name_callback( $item_id, $item ){
            global $post, $thepostid;
            
            if( !is_wallet_partial_payment_order_item( $item_id, $item, 'site') && !is_wallet_partial_payment_order_item( $item_id, $item,'cash')){
                return;
            }
            if ( ! is_int( $thepostid ) ) {
                    $thepostid = $post->ID;
            }
            
            $order_id = $thepostid;
            $wallet_name =  $item->get_name( 'edit' );
            if($wallet_name === $this->site_wallet_title.' payment'){
                $total_site_payment_amount = get_order_wallet_payment_amount($order_id , 'site');
                if ( get_post_meta($order_id, '_site_wallet_partial_payment_refunded', true) ) {
                    echo '<small class="refunded">' . __('Refunded '.price_with_currency($total_site_payment_amount), 'woo-wallet') . '</small>';
                } else{
                    echo '<button type="button" wallet_type="site" class="button '.$wallet_name.' refund-partial-payment">'.__( 'Refund', 'wp-cashback-wallet').'</button>';
                }
            }
               
            if($wallet_name === $this->cash_wallet_title.' payment'){
                //$order = wc_get_order( $order_id );
                $total_cashback = total_cashback_amount($order_id);
                $total_cash_payment_amount = get_order_wallet_payment_amount($order_id , 'cash');
               // var_dump($total_cash_payment_amount);
                $available_for_refund = abs($total_cash_payment_amount) -$total_cashback ;// + $order->get_total();
                if ( get_post_meta($order_id, '_cash_wallet_partial_payment_refunded', true) ) {
                    echo '<small class="refunded">' . __('Refunded '.price_with_currency($available_for_refund), 'woo-wallet') . '</small>';
                } else{
                    
                    $refund_title = ($total_cash_payment_amount) ? 'Refund (Max '.$available_for_refund.')': 'Refund' ;
                    //var_dump(is_available_refund_button_of_offer_product($order_id));
                    if(is_available_refund_button_of_offer_product($order_id)){
                        
                        echo '<button type="button" wallet_type="cash" class="button '.$wallet_name.' refund-partial-payment">'.__( $refund_title, 'woo-wallet').'</button>';
               
                   }
               }
            }

           
                
        }

        /**
         * Process refund through wallet
         * @throws exception
         * @throws Exception
         */
        public function wpcb_wallet_order_refund() {
            ob_start();
            check_ajax_referer( 'order-item', 'security' );
            if ( !current_user_can( 'edit_shop_orders' ) ) {
                wp_die(-1 );
            }
            $order_id = absint( $_POST['order_id'] );
            $refund_amount = wc_format_decimal(sanitize_text_field( $_POST['refund_amount'] ), wc_get_price_decimals() );
            $refund_reason = sanitize_text_field( $_POST['refund_reason'] );
            $line_item_qtys = json_decode(sanitize_text_field(stripslashes( $_POST['line_item_qtys'] ) ), true );
            $line_item_totals = json_decode(sanitize_text_field(stripslashes( $_POST['line_item_totals'] ) ), true );
            $line_item_tax_totals = json_decode(sanitize_text_field(stripslashes( $_POST['line_item_tax_totals'] ) ), true );
            $api_refund = 'true' === $_POST['api_refund'];
            $restock_refunded_items = 'true' === $_POST['restock_refunded_items'];
            $refund = false;
            $response_data = array();
            try {
                $order = wc_get_order( $order_id );
                $order_items = $order->get_items();
                if ( get_post_meta($order_id, '_cashback_to_wallet', true) ) {
                    $total_cashback = total_cashback_amount($order_id);
                    if( $order->get_total() > $total_cashback){
                        $max_refund = wc_format_decimal( $order->get_subtotal() + $order->get_shipping_total() - $order->get_total_refunded() - $total_cashback, wc_get_price_decimals() );
                    }else{
                        $max_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded(), wc_get_price_decimals() );
                    }
                }else{
                    $max_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded(), wc_get_price_decimals() );

                }
               // wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) );
              
                if ( !$refund_amount || $max_refund < $refund_amount || 0 > $refund_amount ) {
                    throw new exception( __( 'Invalid refund amount', 'wp-cashback-wallet' ) );
                }
                // Prepare line items which we are refunding
                $line_items = array();
                $item_ids = array_unique( array_merge( array_keys( $line_item_qtys, $line_item_totals) ) );

                foreach ( $item_ids as $item_id ) {
                    $line_items[$item_id] = array( 'qty' => 0, 'refund_total' => 0, 'refund_tax' => array() );
                }
                foreach ( $line_item_qtys as $item_id => $qty) {
                    $line_items[$item_id]['qty'] = max( $qty, 0 );
                }
                foreach ( $line_item_totals as $item_id => $total ) {
                    $line_items[$item_id]['refund_total'] = wc_format_decimal( $total );
                }
                foreach ( $line_item_tax_totals as $item_id => $tax_totals) {
                    $line_items[$item_id]['refund_tax'] = array_filter( array_map( 'wc_format_decimal', $tax_totals) );
                }
                // Create the refund object.
                $refund = wc_create_refund( array(
                    'amount' => $refund_amount,
                    'reason' => $refund_reason,
                    'order_id' => $order_id,
                    'line_items' => $line_items,
                    'refund_payment' => $api_refund,
                    'restock_items' => $restock_refunded_items,
                ) );
                if ( ! is_wp_error( $refund ) ) {
                    $order_link = site_url().'/my-account/view-order/'. $order->get_order_number();
                    $details = array(
                        'order_id'=> $order->get_order_number(),
                        'transaction_info'=> __( 'Refunded to '.$this->cash_wallet_title.' <a href="'.$order_link.'">#'.$order->get_order_number().'</a>' , 'wp-cashback-wallet' )
                         );
        
                
                    $transaction_id = insert_wallet_transaction('credit', $order->get_customer_id(), 'cash', abs($refund_amount), $details);
                    if ( !$transaction_id ) {
                        throw new Exception( __( 'Refund not credited to customer cash wallet', 'wp-cashback-wallet' ) );
                    } else {
                        $order->add_order_note(sprintf( __( '%s refunded to customer cash wallet', 'wp-cashback-wallet' ), abs($refund_amount) ));
               
                       do_action( 'wpcb_wallet_order_refunded', $order_id, $transaction_id );
                       
                    }
                }

                if ( is_wp_error( $refund ) ) {
                    throw new Exception( $refund->get_error_message() );
                }

                if (did_action( 'woocommerce_order_fully_refunded' ) ) {
                    $response_data['status'] = 'fully_refunded';
                }

                wp_send_json_success( $response_data);
            } catch (Exception $ex) {
                if ( $refund && is_a( $refund, 'WC_Order_Refund' ) ) {
                    wp_delete_post( $refund->get_id(), true );
                }
                wp_send_json_error( array( 'error' => $ex->getMessage() ) );
            }
        }

        /**
         * Wallet partial payment refund.
         */
        public function wpcb_wallet_refund_partial_payment(){
            if ( !current_user_can( 'edit_shop_orders' ) ) {
                wp_die(-1 );
            }
            $response = array('success' => false);
            $order_id = absint( filter_input(INPUT_POST, 'order_id') );
            $wallet_type = filter_input(INPUT_POST, 'wallet_type');
            $order = wc_get_order($order_id);
            if ( get_post_meta($order_id, '_cashback_to_wallet', true) ){
                $partial_payment_amount = get_order_partial_payment_amount($order_id, $wallet_type);
                $total_cashback = get_post_meta($order_id, '_cashback_amount_to_site_wallet', true) +  get_post_meta($order_id, '_cashback_amount_to_cash_wallet', true);
                $available_refund = abs($partial_payment_amount) - $order->get_total_refunded() - $total_cashback ;
            }else{
                $available_refund = get_order_partial_payment_amount($order_id, $wallet_type);
            }
            // var_dump($available_refund);
            // exit();
            $order_link = site_url().'/my-account/view-order/'. $order->get_order_number();
           $payment_name =  get_wallet_payment_name($wallet_type);
            $details = array(
                'order_id'=> $order->get_order_number(),
                'transaction_info'=> __( 'Refunded to '. $payment_name .' <a href="'.$order_link.'">#'.$order->get_order_number().'</a>' , 'wp-cashback-wallet' )
                 );

        
            $transaction_id = insert_wallet_transaction('credit', $order->get_customer_id(), $wallet_type, abs($available_refund), $details);
            //$transaction_id = woo_wallet()->wallet->credit( $order->get_customer_id(), $partial_payment_amount, __( 'Wallet refund #', 'woo-wallet' ) . $order->get_order_number() );
            if($transaction_id){
                $response['success'] = true;
                $order->add_order_note(sprintf( __( '%s refunded to customer %s wallet', 'wp-cashback-wallet' ), abs($available_refund) , $payment_name ));
                update_post_meta($order_id, '_'.$wallet_type.'_wallet_partial_payment_refunded', true);
                update_post_meta($order_id, '_'.$wallet_type.'_wallet_partial_payment_refund_id', $transaction_id);
                do_action('wpcb_wallet_partial_order_refunded', $order_id, $transaction_id);
            }
            wp_send_json($response);
        }

        public function wpcb_wallet_send_refund_request(){
            // nonce check for an extra layer of security, the function will exit if it fails
            if ( !wp_verify_nonce( $_REQUEST['nonce'], "refund_request_nonce")) {
                exit("Woof Woof Woof");
            }  
            global $wpdb;
            $table =  $wpdb->prefix.'cashback_refund_request';
            
            $order_id = intval( filter_input(INPUT_POST, 'order_id') );
            if(is_refund_request_submit($order_id)){
                exit("Already requested");
            }
            $refund_reason = filter_input(INPUT_POST, 'refund_reason');
            if(empty($refund_reason)){
                $error = "You don't fillup refund reason field";
                $response = array('error' =>  $error);
                wp_send_json($response);
            }
            $data_refund_request = array( 
                'user_id'      =>  get_current_user_id(),
                'order_id'          => $order_id,
                'refund_reason'        => $refund_reason,
                'status'       => 'pending',
            );
            $response = array('data' => $data_refund_request);
         
            if($wpdb->insert( $table, $data_refund_request )){
                //$transid = $wpdb->insert_id;
                $response = array('success' => true);
            }else{
                $response = array('success' => false);
            }
            
           
            wp_send_json($response);
        }
    }
}
Wp_Cashback_Wallet_Refund::instance();