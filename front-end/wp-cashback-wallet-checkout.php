<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('Wp_Cashback_Wallet_Checkout')) {

    class Wp_Cashback_Wallet_Checkout {

        /**
         * The single instance of the class.
         *
         * @var Wp_Cashback_Wallet_Checkout
         * @since 1.1.10
         */
        protected static $_instance = null;
        public static $user_id =0;
        public $site_wallet_title;
        public $cash_wallet_title;
        public $is_cashback_product = false;
        public $product_id =0;
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
            //$this->user_id = get_current_user_id();
            $this->site_wallet_title  = get_option( 'site_wallet_title') ?strtolower(get_option( 'site_wallet_title')):'site wallet';
            $this->cash_wallet_title = get_option( 'cash_wallet_title') ?strtolower(get_option( 'cash_wallet_title')):'cash wallet';
           
            add_action( 'woocommerce_new_order_item', array( $this, 'woocommerce_new_order_item' ), 10, 2 );

            add_action( 'wp_footer', array($this,'checkout_fee_script') );
            // Get Ajax request and saving to WC session
            add_action( 'wp_ajax_cash_wallet_checkbox', array($this,'get_cash_wallet_checkbox') );
            add_action( 'wp_ajax_nopriv_cash_wallet_checkbox', array($this,'get_cash_wallet_checkbox') );

            add_action( 'woocommerce_review_order_before_payment', array($this, 'wpcbw_add_checkout_checkbox'), 10 );
            add_action( 'woocommerce_cart_calculate_fees', array($this, 'wpcw_cash_wallet_payment'), 10, 1 );
            
            add_action( 'woocommerce_thankyou', array($this,'wpcbw_thankyou_action_callback'), 10, 1 );
       
        }

            /**
             * Store fee key to order item meta.
             * @param Int $item_id
             * @param WC_Order_Item_Fee $item
             */
            public function woocommerce_new_order_item($item_id, $item){
                if ( $item->get_type() == 'fee' ) {
                    update_metadata( 'order_item', $item_id, '_legacy_fee_key', $item->legacy_fee_key );
                }
            }
        
        public function checkout_fee_script() {
            // Only on Checkout
            if( is_checkout() && ! is_wc_endpoint_url() ) :
        
            if( WC()->session->__isset('cash-wallet-checkbox') )
                WC()->session->__unset('cash-wallet-checkbox');
            if( WC()->session->__isset('site-wallet-checkbox') )
                WC()->session->__unset('site-wallet-checkbox');
            if( WC()->session->__isset('site-wallet-fee') )
                WC()->session->__unset('site-wallet-fee');
            if( WC()->session->__isset('cash-wallet-fee') )
                WC()->session->__unset('cash-wallet-fee');
            ?>
            <script type="text/javascript">
            jQuery( function($){
                if (typeof wc_checkout_params === 'undefined')
                    return false;
        
                $('form.checkout').on('change', 'input[name=cash-wallet-checkbox]', function(e){
                    e.preventDefault();
                    var fee = $(this).prop('checked') === true ? '1' : '';
        
                    $.ajax({
                        type: 'POST',
                        url: wc_checkout_params.ajax_url,
                        data: {
                            'action': 'cash_wallet_checkbox',
                            'cash-wallet-checkbox': fee,
                        },
                        success: function (result) {
                            $('body').trigger('update_checkout');
                        },
                    });
                });
                
                $('form.checkout').on('change', 'input[name=site-wallet-checkbox]', function(e){
                    e.preventDefault();
                    var fee = $(this).prop('checked') === true ? '1' : '';
        
                    $.ajax({
                        type: 'POST',
                        url: wc_checkout_params.ajax_url,
                        data: {
                            'action': 'cash_wallet_checkbox',
                            'site-wallet-checkbox': fee,
                        },
                        success: function (result) {
                            $('body').trigger('update_checkout');
                        },
                    });
                });
            });
            </script>
            <?php
            endif;
        }
        
        
        public function get_cash_wallet_checkbox() {
            if ( isset($_POST['cash-wallet-checkbox']) ) {
                WC()->session->set('cash-wallet-checkbox', ($_POST['cash-wallet-checkbox'] ? true : false) );
            }
            if ( isset($_POST['site-wallet-checkbox']) ) {
                WC()->session->set('site-wallet-checkbox', ($_POST['site-wallet-checkbox'] ? true : false) );
            }
            die();
        }

        /**
         * Add WooCommerce Checkbox checkout
         */
        public function wpcbw_add_checkout_checkbox() {
            $user_id = get_current_user_id();
            $site_wallet_balance =  floatval(get_wallet_balance('site',$user_id ));
            $cash_wallet_balance =  floatval(get_wallet_balance('cash',$user_id));
            $items = wc()->cart->get_cart(); 
            $this->product_id = end($items)['data']->post->ID;
            $product_data = wc_get_product($this->product_id);
            # Targeting a defined product ID
            $cashback_enable = $product_data->get_meta('cashback_amount');
            
            if(!empty($cashback_enable)){
                $this->is_cashback_product = true;
            }else{
                $this->is_cashback_product = false;
            }
            if ($site_wallet_balance > 0 && !$this->is_cashback_product) {
                woocommerce_form_field( 'site-wallet-checkbox', array( // CSS ID
                    'type'          => 'checkbox',
                    'class'         => array('form-row site-wallet-checkbox'), // CSS Class
                    'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
                    'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
                    'required'      => true, // Mandatory or Optional
                    'label'         => 'Pay with '.$this->site_wallet_title.' ('.$site_wallet_balance.')', // Label and Link
                    ), '');  
            }
           
            if ($cash_wallet_balance > 0 ) {
                woocommerce_form_field( 'cash-wallet-checkbox', array( // CSS ID
                    'type'          => 'checkbox',
                    'class'         => array('form-row cash-wallet-checkbox'), // CSS Class
                    'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
                    'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
                    'required'      => true, // Mandatory or Optional
                    'label'         => 'Pay with '.$this->cash_wallet_title.' ('.$cash_wallet_balance.')',
                ), '');  
            }
            
        }


    
       public function wpcbw_thankyou_action_callback( $order_id ) {
            
            if( get_post_meta( $order_id, 'wpcbw_purchase_order_id', true ) ) 
                return; // Exit if already processed
            // Get an instance of the WC_Order Object
            $order = wc_get_order( $order_id );
        
            if( in_array( $order->get_status(), ['processing','complete','on-hold'] ) ) {
           
                foreach ( $order->get_fees() as $item_fee ) {
            
                    $fee_name = strtolower($item_fee->get_name());
                    
                    $order_link = site_url().'/my-account/view-order/'. $order->get_order_number();
                    if ( $fee_name == $this->cash_wallet_title.' payment' ) {
                        $item_id = $item_fee->get_id(); //???
                        $fee_amount = floatval(wc_get_order_item_meta( $item_id, '_fee_amount', true ));
                       
                        $details = array(
                        'order_id' => $order_id,
                        'transaction_info'=> __( 'Purchase product by '.$this->cash_wallet_title.' <a href="'.$order_link.'">#'.$order->get_order_number().'</a>' , 'wp-cashback-wallet' )
                        );
                       
                        insert_wallet_transaction('debit', get_current_user_id(), 'cash', abs($fee_amount), $details);
                    
                    }
                    
                    if ( $fee_name ==  $this->site_wallet_title.' payment' ) {
                        $item_id = $item_fee->get_id(); //???
                        $fee_amount = floatval(wc_get_order_item_meta( $item_id, '_fee_amount', true ));
                        $details = array(
                            'order_id' => $order_id,
                            'transaction_info'=> __( 'Purchase product by '.$this->site_wallet_title.' <a href="'.$order_link.'">#'.$order->get_order_number().'</a>' , 'wp-cashback-wallet' )
                            );
                        insert_wallet_transaction('debit', get_current_user_id(), 'site', abs($fee_amount), $details);
                    
                    }
                   
                }

                update_post_meta( $order_id, 'wpcbw_purchase_order_id', 'using wallet' );
                
            }
        }


        public function wpcw_cash_wallet_payment( $cart ) {
            if ( is_admin() && ! defined('DOING_AJAX') || ! is_checkout() )
            return;
    
            if ( did_action('woocommerce_cart_calculate_fees') >= 2 )
                return;
        
           
            $this->user_id = get_current_user_id();
            //var_dump( $this->user_id );
            $site_wallet_balance =  get_wallet_balance('site',$this->user_id);
            $cash_wallet_balance =  get_wallet_balance('cash',$this->user_id);
            
            $items = wc()->cart->get_cart(); 
            $this->product_id = end($items)['data']->post->ID;
            $product_data = wc_get_product($this->product_id);
            # Targeting a defined product ID
            $cashback_enable = $product_data->get_meta('cashback_amount');
            
            if(!empty($cashback_enable)){
                $this->is_cashback_product = true;
            }else{
                $this->is_cashback_product = false;
            }
            //var_dump($this->is_cashback_product);
         
           
            if ($cash_wallet_balance > 0 && (1 == WC()->session->get('cash-wallet-checkbox')) ) {
               
                $cart_total = 0;
                foreach( WC()->cart->get_cart() as $item ){ 
                   // $cart_total += $item["line_total"] + $item['shipping_total'];
                  
                   $cart_total += floatval(WC()->cart->shipping_total) +  floatval($item["line_total"]);
                    
                }
                if(!empty(WC()->session->get('site-wallet-fee'))){
                    $cart_total = $cart_total - WC()->session->get('site-wallet-fee');
                }
                if($cash_wallet_balance > $cart_total){
                    $cash_wallet_fee = $cart_total;
                }else{
                    $cash_wallet_fee = $cash_wallet_balance;
                }
                $cash_wallet_fee = floatval($cash_wallet_fee);
                $fee_name = $this->cash_wallet_title.' payment';

                $fee = array(
                    'id' => '_via_cash_wallet_partial_payment',
                    'name' => __( $fee_name , 'wp-cashback-wallet' ),
                    'amount' => -floatval($cash_wallet_fee),
                    'taxable' => false,
                    'tax_class' => '',
                );
                wc()->cart->fees_api()->add_fee($fee);
                WC()->session->set('cash-wallet-fee', $cash_wallet_fee );
            }

            if ($site_wallet_balance > 0 && !$this->is_cashback_product && (1 == WC()->session->get('site-wallet-checkbox'))) {
               
               
                $cart_total = 0;
                foreach( WC()->cart->get_cart() as $item ){ 
                   // $cart_total += $item["line_total"] + $item['shipping_total'];
                   $cart_total += floatval( WC()->cart->shipping_total ) +  floatval( $item["line_total"] );
                }

                $cart_total_half_balance = floatval($cart_total/2);

                
                
                if($site_wallet_balance > $cart_total_half_balance){
                    $site_wallet_fee = $cart_total_half_balance;
                }else{
                    $site_wallet_fee = $site_wallet_balance;
                }
                if(!empty(WC()->session->get('cash-wallet-fee'))){
                    $cart_total_available = $cart_total - WC()->session->get('cash-wallet-fee');
                    if($cart_total_available <= $cart_total_half_balance ){
                        $site_wallet_fee = $cart_total_available;  
                    }
                }
               
                // $site_wall_half_balance = floatval($site_wallet_balance/2);
                // if(!empty(WC()->session->get('cash-wallet-fee'))){
                //     $cart_total = $cart_total - WC()->session->get('cash-wallet-fee');
                // }

                // if($site_wall_half_balance > $cart_total){
                //     $site_wallet_fee = $cart_total;
                // }else{
                //     $site_wallet_fee = $site_wall_half_balance;
                // }
                    
                $site_wallet_fee = floatval($site_wallet_fee);   
                $fee_name = $this->site_wallet_title.' payment';
                $fee = array(
                    'id' => '_via_site_wallet_partial_payment',
                    'name' => __( $fee_name , 'wp-cashback-wallet' ),
                    'amount' => -$site_wallet_fee,
                    'taxable' => false,
                    'tax_class' => '',
                );
                wc()->cart->fees_api()->add_fee($fee);
                WC()->session->set('site-wallet-fee', $site_wallet_fee );
            }
            
           
        }

    }
}
Wp_Cashback_Wallet_Checkout::instance();