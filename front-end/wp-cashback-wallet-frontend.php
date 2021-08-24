<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('Wp_Cashback_Wallet_Frontend')) {

    class Wp_Cashback_Wallet_Frontend {

        /**
         * The single instance of the class.
         *
         * @var Woo_Wallet_Frontend
         * @since 1.1.10
         */
        protected static $_instance = null;
        public $user_id = 0;

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
            $this->user_id = get_current_user_id();
            // if( is_page( 'cart' ) || is_page( 'checkout' ) ){
              
            // }
          
            if(get_option( 'wallet_show_at_menu' )[0] =='yes'){
                
                add_filter('wp_nav_menu_items', array($this, 'add_wallet_nav_menu'), 100, 2);
            }
            
            add_shortcode('mini-wallet', __CLASS__ . '::mini_wallet_shortcode_callback');
            add_filter('woocommerce_get_query_vars', array($this, 'add_woocommerce_query_vars'));
            add_filter('woocommerce_endpoint_site-wallet-transactions_title', array($this, 'woocommerce_endpoint_title'), 10, 2);
            add_filter('woocommerce_endpoint_cash-wallet-transactions_title', array($this, 'woocommerce_endpoint_title'), 10, 2);
            add_filter('woocommerce_account_menu_items', array($this, 'woo_wallet_menu_items'), 10, 1);
            add_action('woocommerce_account_site-wallet-transactions_endpoint', array($this, 'wpcb_site_wallet_endpoint_content'));
            add_action('woocommerce_account_cash-wallet-transactions_endpoint', array($this, 'wpcb_cash_wallet_transactions_endpoint_content'));
            add_action('wp_enqueue_scripts', array($this, 'woo_wallet_styles'), 20);

            add_action('wp_ajax_draw_wallet_transaction_details_table', array($this, 'draw_wallet_transaction_details_table'));
            add_action( 'woocommerce_after_shop_loop_item_title', array($this, 'wp_cashback_wallet_product_listing_cashback_show'), 2);
            add_action('woocommerce_product_meta_start', array($this, 'wp_cashback_wallet_single_product_cashback_show') );
            add_filter( 'woocommerce_cart_item_name', array($this, 'wp_cashback_wallet_cart_cachback_show'), 10, 2 );
            add_filter( 'woocommerce_thankyou_order_received_text', array($this, 'wpcb_customize_thankyou_page'), 10, 2 );

            add_action( 'woocommerce_order_status_processing', array($this, 'action_order_status_completed'), 20, 2 );
            
            add_filter( 'woocommerce_add_to_cart_validation', array($this,'wpcb_wallet_limit_one_per_order'), 10, 2);
            
            add_filter( 'woocommerce_add_to_cart_validation', array($this,'wpcb_wallet_limit_item_quantity_cashback_product'), 20, 2);

            add_action('woocommerce_check_cart_items', array($this,'wpcb_wallet_validate_all_cart_contents') );
           
            add_action( 'wp_ajax_get_user_transaction',  array($this, 'wpcw_get_user_transaction') );
            add_action( 'wp_ajax_nopriv_get_user_transaction',  array($this, 'wpcw_get_user_transaction') ); 
            
            add_action( 'wp_ajax_get_user_transaction',  array($this, 'wpcw_get_user_transaction') );
            add_action( 'wp_ajax_nopriv_get_user_transaction',  array($this, 'wpcw_get_user_transaction') ); 
           
            add_action('wp_ajax_wpcb_wallet_balance_withdraw_request', array($this, 'balance_withdraw_request_function'));
            
            
        }
        

        // public function wp_nav_menu_objects($items){
        //     foreach ($items as &$item) {
        //         if ('my-wallet' === $item->post_name && get_post_meta($item->ID, '_show_wallet_icon_amount', true)) {
        //             $item->title = apply_filters('wp_wallet_nav_menu_title', '<span dir="rtl" class="woo-wallet-icon-wallet"></span>&nbsp;' . woo_wallet()->wallet->get_wallet_balance(get_current_user_id()), $item);
        //         }
        //     }
        //     return $items;
        // }

        
        public static function add_wallet_nav_menu($menu, $args){
           if( $args->theme_location == 'main-menu' ){
                $title = __('Current wallet balance', 'wp-cashback-wallet');
                $mini_wallet = '<li class="right"><a class="wpcb-wallet-menu-contents" href="' . esc_url(wc_get_account_endpoint_url(get_option('site-wallet-transactions', 'site-wallet-transactions'))) . '" title="' . $title . '">';
                $mini_wallet .= '<span dir="rtl" class="woo-wallet-icon-wallet"></span> ';
                $mini_wallet .= get_wallet_balance('site',get_current_user_id()).'(site)';
                $mini_wallet .= '</a></li>';
    
                $mini_wallet .= '<li class="right"><a class="wpcb-wallet-menu-contents" href="' . esc_url(wc_get_account_endpoint_url(get_option('cash-wallet-transactions', 'cash-wallet-transactions'))) . '" title="' . $title . '">';
                $mini_wallet .= '<span dir="rtl" class="woo-wallet-icon-wallet"></span> ';
                $mini_wallet .= get_wallet_balance('cash',get_current_user_id()).'(cash)';;
                $mini_wallet .= '</a></li>';
    
                return $menu . $mini_wallet;
            }

            return $menu;
            
        }
        /**
         * Mini Wallet shortcode
         * @param array $atts
         * @return string Shortcode output
         */
        public static function mini_wallet_shortcode_callback($atts){
            return self::shortcode_wrapper(array('Wp_Cashback_Wallet_Frontend', 'mini_wallet_shortcode_output'), $atts);
        }
        
        /**
         * Mini wallet shortcode output.
         * @param array $atts
         */
        public static function mini_wallet_shortcode_output($atts){
            if($atts['type'] =='site'){
            $end_point_url = 'site-wallet-transactions';
            }else{
                $end_point_url = 'cash-wallet-transactions'; 
            }
            $title = __('Current wallet balance', 'wp-cashback-wallet');
            $mini_wallet = '<a class="woo-wallet-menu-contents" href="' . esc_url(wc_get_account_endpoint_url(get_option( $end_point_url, $end_point_url ))) . '" title="' . $title . '">';
            $mini_wallet .= '<span dir="rtl" class="woo-wallet-icon-wallet"></span> ';
            $mini_wallet .= get_wallet_balance($atts['type'],get_current_user_id());
            $mini_wallet .= '</a>';
            echo $mini_wallet;
        }

        /**
         * Shortcode Wrapper.
         *
         * @param string[] $function Callback function.
         * @param array    $atts     Attributes. Default to empty array.
         *
         * @return string
         */
        public static function shortcode_wrapper($function, $atts = array()) {
            ob_start();
            call_user_func($function, $atts);
            return ob_get_clean();
        }
        /**
         * Add WooCommerce query vars.
         * @param type $query_vars
         * @return type
         */
        public function add_woocommerce_query_vars($query_vars) {
             $query_vars['site-wallet-transactions'] = 'site-wallet-transactions'; //get_option('woocommerce_woo_wallet_endpoint', 'wp-cashback-wallet');
            $query_vars['cash-wallet-transactions'] = 'cash-wallet-transactions' ; // get_option('woocommerce_woo_wallet_transactions_endpoint', 'woo-wallet-transactions');
            return $query_vars;
        }

        /**
         * Change WooCommerce endpoint title for wallet pages.
         */
        public function woocommerce_endpoint_title($title, $endpoint) {
            switch ($endpoint) {
                case 'site-wallet-transactions' :
                    $title = apply_filters('wpcb_site_wallet_account_menu_title', __('Kidomen wallet', 'wp-cashback-wallet'));
                    break;
                case 'cash-wallet-transactions' :
                    $title = apply_filters('wpcb_cash_wallet_account_menu_title', __('Cash wallet ', 'wp-cashback-wallet'));
                    break;
                default :
                    $title = '';
                    break;
            }
            return $title;
        }

        /**
         * Register and enqueue frontend styles and scripts
         */
        public function woo_wallet_styles() {
            $wp_scripts = wp_scripts();
           wp_register_style('datatable-css', plugin_dir_url( __DIR__ ) . 'assets/css/datatable.min.css');
           wp_register_style('wpcb-frontend-css', plugin_dir_url( __DIR__ ) . 'assets/css/frontend.css');
          


           wp_register_script( 'datatable-js',  plugin_dir_url( __DIR__ ) . 'assets/js/frontend/datatable.min.js', array( 'jquery' ));
            
            
            wp_register_script( 'wpcb-frontend-js', plugin_dir_url( __DIR__ ) . 'assets/js/frontend/frontend.js', array( 'jquery' ));
            
            $wallet_localize_param = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'transaction_table_nonce' => wp_create_nonce('wp-cashback-wallet-transactions'),
                'i18n' => array(
                    'emptyTable' => __('No transactions available', 'wp-cashback-wallet'),
                    'lengthMenu' => sprintf(__('Show %s entries', 'wp-cashback-wallet'), '_MENU_'),
                    'info' => sprintf(__('Showing %1s to %2s of %3s entries', 'wp-cashback-wallet'), '_START_', '_END_', '_TOTAL_'),
                    'infoFiltered' => sprintf(__('(filtered from %1s total entries)', 'wp-cashback-wallet'), '_MAX_'),
                    'infoEmpty' => __('Showing 0 to 0 of 0 entries', 'wp-cashback-wallet'),
                    'paginate' => array(
                        'first' => __('First', 'wp-cashback-wallet'),
                        'last' => __('Last', 'wp-cashback-wallet'),
                        'next' => __('Next', 'wp-cashback-wallet'),
                        'previous' => __('Previous', 'wp-cashback-wallet')
                    ),
                    'non_valid_email_text' => __('Please enter a valid email address', 'wp-cashback-wallet'),
                    'no_resualt' => __('No results found', 'wp-cashback-wallet'),
                    'zeroRecords' => __('No matching records found', 'wp-cashback-wallet'),
                    'inputTooShort' => __('Please enter 3 or more characters', 'wp-cashback-wallet'),
                    'searching' => __('Searching…', 'wp-cashback-wallet'),
                    'processing' => __('Processing...', 'wp-cashback-wallet'),
                    'search' => __('Search by date:', 'wp-cashback-wallet'),
                    'placeholder' => __('yyyy-mm-dd', 'wp-cashback-wallet')
                ),
            );
            wp_localize_script('wpcb-frontend-js', 'wallet_param', $wallet_localize_param);
           
            if (is_account_page()) {
                wp_enqueue_style('datatable-css');
                wp_enqueue_style('wpcb-frontend-css');

                wp_enqueue_script('datatable-js');
                wp_enqueue_script('wpcb-frontend-js');
            }

            global $post;

            if( is_page() )
            {
                switch($post->post_name) // post_name is the post slug which is more consistent for matching to here
                {
                    case 'home':
                       // wp_enqueue_script('home', get_template_directory_uri() . '/js/home.js', array('jquery'), '', false);
                        break;
                    case 'about-page':
                       // wp_enqueue_script('about', get_template_directory_uri() . '/js/about-page.js', array('jquery'), '', true);
                        break;
                    case 'my-account':
                        if(is_wc_endpoint_url( 'orders' )){
                            wp_enqueue_script('orders-script', plugin_dir_url( __DIR__ ) . 'assets/js/frontend/orders-script.js', array( 'jquery' ));
                            $wallet_localize_param['nonce'] = wp_create_nonce('refund_request_nonce');
                            $wallet_localize_param['success_icon'] = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/success_icon.png'; 
                            $wallet_localize_param['close_icon'] = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/close_icon.png'; 
                           
                            wp_localize_script('orders-script', 'wallet_param', $wallet_localize_param);
                        }

                        //if(is_wc_endpoint_url( 'cash-wallet-transactions' )){
                          
                            wp_enqueue_script('wallet-script', plugin_dir_url( __DIR__ ) . 'assets/js/frontend/wallet-script.js', array( 'jquery' ));
                            $wallet_localize_param['balance_withdraw_nonce'] = wp_create_nonce('balance_withdraw_nonce');
                            wp_localize_script('wallet-script', 'wallet_param', $wallet_localize_param);
                        //}

                        break;
                }
            } 
        
        }

        /**
         * WooCommerce wallet menu
         * @param array $items
         * @return array
         */
        public function woo_wallet_menu_items($items) {
            unset($items['edit-account']);
            unset($items['customer-logout']);
            $items['site-wallet-transactions'] = apply_filters('woo_wallet_account_menu_title', __('Kidomen wallet', 'wp-cashback-wallet'));
            $items['cash-wallet-transactions'] = apply_filters('woo_wallet_account_transaction_menu_title', __('Cash wallet', 'wp-cashback-wallet'));
            $items['edit-account'] = __('Account details', 'wp-cashback-wallet');
            $items['customer-logout'] = __('Logout', 'wp-cashback-wallet');
            return $items;
        }

        /**
         * WooCommerce endpoint contents for wallet 
         */
        public function wpcb_site_wallet_endpoint_content() {
            $this->get_template('wc-endpoint-site-wallet-transactions.php');
        }

        /**
         * WooCommerce endpoint contents for transaction details
         */
        public function wpcb_cash_wallet_transactions_endpoint_content() {
            $this->get_template('wc-endpoint-cash-wallet-transactions.php');
        }
          
    /**
     * Load template
     * @param string $template_name
     * @param array $args
     * @param string $template_path
     * @param string $default_path
     */
    public function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        if ( $args && is_array( $args ) ) {
            extract( $args );
        }
        $located = $this->locate_template( $template_name, $template_path, $default_path);
        include ( $located);
    }

    /**
     * Locate template file
     * @param string $template_name
     * @param string $template_path
     * @param string $default_path
     * @return string
     */
    public function locate_template( $template_name, $template_path = '', $default_path = '' ) {
        $default_path = apply_filters( 'woo_wallet_template_path', $default_path);
        if ( !$template_path) {
            $template_path = 'woo-wallet';
        }
        if ( !$default_path) {
            $default_path = WP_CASHBACK_WALLET_ABSPATH . 'templates/';
        }
        // Look within passed path within the theme - this is priority
        $template = locate_template( array(trailingslashit( $template_path) . $template_name, $template_name) );
        // Add support of third perty plugin
        $template = apply_filters( 'woo_wallet_locate_template', $template, $template_name, $template_path, $default_path);
        // Get default template
        if ( !$template) {
            $template = $default_path . $template_name;
        }
        return $template;
    }

        public function wp_cashback_wallet_product_listing_cashback_show(){
            global $product;
            $cashback_amount = get_post_meta( $product->id, 'cashback_amount', true ); 
            if ( ! empty( $cashback_amount ) ) {
                echo '<span class="cashback_amount">(cashback ' . $cashback_amount . '%)</span>';
            }
         }

         public function wp_cashback_wallet_single_product_cashback_show() {
            global $post;
            $product = wc_get_product($post->ID);
            $cashback_amount = $product->get_meta('cashback_amount');
            if (!empty($cashback_amount)) {
                printf('<div class="cashback_sku">%s: %s   </div>', __('Cashback Available', 'wp-cashback-wallet'), $cashback_amount . "%");
            }
        }

        public function wp_cashback_wallet_cart_cachback_show( $title, $cart_item ){
            $_product = $cart_item['data'];
            $percentise     = $_product->get_meta( 'cashback_amount', true );
            
           
            if( $percentise ) {
                $cashback= ($_product->get_price()*$percentise/100)*$cart_item['quantity'];
              
                $title .= '<span class="cashback_meta"> (Cashback '. $percentise . '%. </span>';
                $title .= '<span class="cashback_amount">You will receive '.$cashback.' tk to your wallets)</span>';
            }
            return $title;
        }
        
      
        public function wpcb_customize_thankyou_page( $thankyoutext, $order ) {
            if( isset( $_GET['key'] ) && is_wc_endpoint_url( 'order-received' ) ) { 
                $order_id = wc_get_order_id_by_order_key( $_GET['key'] );
            
                if ( ! empty( $order_id ) ) {
                $order = wc_get_order( $order_id );
                
                foreach( $order->get_items() as $item ) {
                    //check if one of the product's ID match your desired ID
                    $_product = wc_get_product($item['product_id']);
                    $percentise     = $_product->get_meta( 'cashback_amount', true );
                    
                
                    if( $percentise ) {
                        $cashback= ($_product->get_price()*$percentise/100)*$item['quantity'];
                    
                        $title = '<br><span class="cashback_meta"> This product has '. $percentise . '% Cashback. </span>';
                        $title .= '<span class="cashback_amount">So you will receive '.$cashback.' points to your wallets)</span>';
                
                        $thankyoutext = $thankyoutext .  $title;
            
                    }
                    
                }
                }
            }
            return $thankyoutext;
        }

        public function wpcb_wallet_limit_one_per_order( $passed_validation, $product_id ) {
            
            global $woocommerce;
            $is_cashback_product = get_post_meta( $product_id, 'cashback_amount', true );
            if ( WC()->cart->get_cart_contents_count() >= 1 && !$this->wpcb_wallet_in_cart($product_id)) {
            //    if( $is_cashback_product ){
                wc_add_notice( sprintf( '<strong>You can’t add more than one item in cart. Please, empty your cart first and
then add another.</strong> <a href="'.wc_get_cart_url().'"> Go Cart </a> ', 'woocommerce' ), 'error' );
                return false;
            //    }else{
                   
            //     wc_add_notice( sprintf( $is_cashback_product .'This product cannot be purchased with other products. Please, empty your cart first and then add it again. <a href="'.wc_get_cart_url().'"> Go Cart </a> ', 'woocommerce' ), 'error' );
            //     return false;
            //    }
               
            }

            return $passed_validation;
        }
        

        public function wpcb_wallet_limit_item_quantity_cashback_product( $passed_validation, $product_id ) {
            
            global $woocommerce;
            $is_cashback_product = get_post_meta( $product_id, 'cashback_amount', true );
            if ( $woocommerce->cart->cart_contents_count >=3) {
                if( $is_cashback_product ){
                wc_add_notice( sprintf( '<strong>You have purchase Max 3 items for cashback products at a time.</strong> <a href="'.wc_get_cart_url().'"> Go Cart </a> ', 'woocommerce' ), 'error' );
                return false;
                }
               
            }

            return $passed_validation;
        }


        public function wpcb_wallet_in_cart($product_id) {
            global $woocommerce;         
            foreach($woocommerce->cart->get_cart() as $key => $val ) {
                $_product = $val['data'];
                
                if($product_id == $_product->id ) {
                    
                    return true;
                }
            }         
            return false;
        }
            


        public function wpcb_wallet_validate_all_cart_contents(){
            global $woocommerce;       
            foreach($woocommerce->cart->get_cart() as $key => $val ) {
                $_product             = $val['data'];
                $is_cashback_product  = $_product->get_meta( 'cashback_amount', true );
            } 

            
            if($woocommerce->cart->cart_contents_count > 3 && !empty($is_cashback_product)):
                wc_add_notice( sprintf( '<strong>You have purchase Max 3 items for cashback products at a time</strong>'), 'error' );
                
            else:
                return true;
            endif;
        
        }

        public function action_order_status_completed( $order_id, $order ){
            
            global $wpdb;
            if ( get_post_meta($order_id, '_cashback_to_wallet', true) ) {
                return;
            }
            # Get an instance of WC_Order object
            $order = wc_get_order( $order_id );
            # Iterating through each order items (WC_Order_Item_Product objects in WC 3+)
            foreach ( $order->get_items() as $item_id => $item_values ) {
        
        
                // OR the Product id from the item data
                $item_data = $item_values->get_data();
                $product_id = $item_data['product_id'];
                $quantity = $item_data['quantity'];
                $total_product_price = $item_data['subtotal'];
                $product = wc_get_product($product_id);
                # Targeting a defined product ID
                $cashback_enable = $product->get_meta('cashback_amount');
        
                if (empty($cashback_enable) ) {
                    return 0;
                }
                $cashback_amount = ($total_product_price/100)*$cashback_enable;
                
            }
        
            // Here are the correct way to get some values:
            $customer_id           = $order->customer_user;
        
            // Getting the user data (if needed)
            $user_data             = get_userdata( $customer_id );
        
            $cash_wallet_percetise = intval(get_option( 'cash_wallet_cashback_percentise', 30 )? get_option( 'cash_wallet_cashback_percentise', 30 ):30);
            $site_wallet_percetise = intval(get_option( 'site_wallet_cashback_percentise', 70 )?get_option( 'site_wallet_cashback_percentise', 70 ):70);
             
            $cash_wallet_cashback_amount = ($cashback_amount/100)* $cash_wallet_percetise;
            $site_wallet_cashback_amount = ($cashback_amount/100)* $site_wallet_percetise;
            
            $order_link = site_url().'/my-account/view-order/'. $order->get_order_number();
            $details = array(
                'order_id' => $order_id,
                'product_id'=> $product_id,
                'product_qty' => $quantity,
                'product_price' => $product->get_price(),
                'order_total' => $total_product_price ,
                'transaction_info'=> __( 'Cashback by Product purchase <a href="'.$order_link.'">#'.$order->get_order_number().'</a>' , 'wp-cashback-wallet' )
            );

          

            if(insert_wallet_transaction('credit', $customer_id, 'cash', $cash_wallet_cashback_amount, $details) ){
                update_post_meta($order_id, '_cashback_to_wallet', true);
                update_post_meta($order_id, '_cashback_amount_to_cash_wallet', $cash_wallet_cashback_amount);
            }
            
            if(insert_wallet_transaction('credit', $customer_id, 'site', $site_wallet_cashback_amount, $details)){
                update_post_meta($order_id, '_cashback_to_wallet', true);
                update_post_meta($order_id, '_cashback_amount_to_site_wallet', $site_wallet_cashback_amount);
            }
  
        }
        
        public function balance_withdraw_request_function(){
            // nonce check for an extra layer of security, the function will exit if it fails
            $error = array();
            if ( !wp_verify_nonce( $_REQUEST['nonce'], "balance_withdraw_nonce")) {
                $error['nonce_error'] = "Your request outside form system"; 
                exit("Woof Woof Woof");
            }  
            $user_id = get_current_user_id();
            if(empty($user_id)){
                $error['user_not_available'] = "User id shouldn't empty";
                exit("Woof Woof Woof");
            }
            global $wpdb;
            $table =  $wpdb->prefix.'cashback_withdrawal_request';
        
            $withdraw_amount = intval(filter_input(INPUT_POST, 'withdraw_amount'));
            if($withdraw_amount <= 0 || $withdraw_amount > get_wallet_balance('cash', $user_id)){
                $error = "Sorry, you can’t withdraw more than your available balance";
                $response = array('error' => $error);
                wp_send_json($response);
            }
            $withdraw_method = filter_input(INPUT_POST, 'withdraw_method');
            $bkash_account_name = filter_input(INPUT_POST, 'bkash_account_name');
            $bkash_account_number = filter_input(INPUT_POST, 'bkash_account_number');
            $bkash_account_type = filter_input(INPUT_POST, 'bkash_account_type');
            $bank_name = filter_input(INPUT_POST, 'bank_name');
            $bank_branch_name = filter_input(INPUT_POST, 'bank_branch_name');
            $bank_account_name = filter_input(INPUT_POST, 'bank_account_name');
            $bank_account_number = filter_input(INPUT_POST, 'bank_account_number');
            $others_note = filter_input(INPUT_POST, 'others_note');
            if($withdraw_method=='bkash'){
                if(empty($bkash_account_number)){
                    $error = "Bkash Account number is not found";
                    $response = array('error' => $error);
                    wp_send_json($response);
                }
                $withdraw_account_details = array(
                    'bkash_account_name' =>$bkash_account_name,
                    'bkash_account_number' =>$bkash_account_number,
                    'bkash_account_type' =>$bkash_account_type,
                );
            }else if($withdraw_method=='bank'){
                if(empty($bank_name) || empty($bank_account_name) || empty($bank_account_name)){
                    $error = "Bank account all information (Bank name , Account holder name, Account number) shouldn't empty";
                    $response = array('error' => $error);
                    wp_send_json($response);
                }
                $withdraw_account_details = array(
                    'bank_name' =>$bank_name,
                    'bank_branch_name' =>$bank_branch_name,
                    'bank_account_name' =>$bank_account_name,
                    'bank_account_number' =>$bank_account_number,
                );
            }else{
                $withdraw_account_details = array();
                $error = "Withdraw method is invalid";
                
                $response = array('error' => $error);
            }
           

            
            if(!empty($withdraw_account_details)){
                $data_withdraw_request = array( 
                    'user_id'      =>  get_current_user_id(),
                    'withdraw_amount'        => $withdraw_amount,
                    'withdraw_method'        => $withdraw_method,
                    'widthraw_account_details'        => json_encode($withdraw_account_details),
                    'others_note'        => $others_note,
                    'status'       => 'pending',
                );
                $response = array('data' => $data_withdraw_request);
             
                if($wpdb->insert( $table, $data_withdraw_request )){
                    //$transid = $wpdb->insert_id;
                    $response = array('success' => true);
                }else{
                    $response = array('success' => false);
                }
            }
            
          
            
           
            wp_send_json($response);
        }

        public function wpcw_get_user_transaction(){
            
            $params = $columns = $totalRecords = $data = array();
            $params=$_REQUEST;
            

            $columns = array(
                0 =>'transaction_id',
                1 =>'details',
           //     2 =>'type',
                2 =>'amount',
                3 =>'balance',
                4 =>'date',
            );

            global $wpdb;
            $catagory_list = $_REQUEST['choices'];
            $filter_value= array();
            foreach($catagory_list as $Key=>$value){
                
                array_push($filter_value,$value);
                
            }
            $filter_value = array_values($filter_value[0]);
            $table_name = "";
            $wallet_type = $_REQUEST['wallet_type'];
            if(!empty($wallet_type) && $wallet_type=="site"){
                $table_name = "cashback_site_wallet_transactions";
            }
            elseif(!empty($wallet_type) && $wallet_type=="cash"){
                $table_name = "cashback_cash_wallet_transactions";
            }else{
                $table_name = "cashback_cash_wallet_transactions";
            }
            
            $table = $wpdb->prefix.$table_name;
            //$whereIn = implode("','", $filter_value); 

            $where_condition = $sqlTot = $sqlRec = "";
            if( !empty($filter_value) ) {
                $where_condition .=	" AND ";
                
                //('".$whereIn."') 

                
                for ($i=0; $i < sizeof($filter_value); $i++) { 

                    $where_condition .= " company_category ";    
                    $where_condition .= " like '%";
                    $where_condition .= $filter_value[$i];
                    $where_condition .= "%'";
                    if($i < sizeof($filter_value)-1 ){
                        $where_condition .= " and "; 
                    }
                    
                }
                
            }
            $current_user_id = get_current_user_id();
            $sql_query = " SELECT * FROM $table WHERE user_id=$current_user_id ";
            $sqlTot .= $sql_query;
            $sqlRec .= $sql_query;
            
            if(isset($where_condition) && $where_condition != '') {

                $sqlTot .= $where_condition;
                $sqlRec .= $where_condition;
            }

            
            $sqlRec .=  " ORDER BY ". $columns[$params['order'][0]['column']]."   ".$params['order'][0]['dir']."  LIMIT ".$params['start']." ,".$params['length']." ";


            //echo $sqlRec;
            // exit();
            $total_result = $wpdb->get_results($sqlTot);
            $total_result_data = count($total_result);

            $results = $wpdb->get_results($sqlRec);
                
            $totalFilter = count($results);

            $data = array(); 

            foreach($results as $result){
                $details = json_decode($result->details);
                $sign = ($result->type == 'debit')? '-' : '+';
                $sub_array = array();
                $sub_array[] = $result->transaction_id;
                $sub_array[] = $details->transaction_info;
                //$sub_array[] = $result->type;
                $sub_array[] = '<span class="'.$result->type.'_amount">' . $sign . $result->amount. ' points' . '</span>';
                $sub_array[] = $result->balance . ' points';
                $sub_array[] = date('jS M, Y', strtotime($result->date) ); //longDateHuman($result->date, $format = 'datetime') ;
                $data[] = $sub_array;
            }


            $output = array(
                "draw"    => intval( $params['draw'] ),
                "recordsTotal"  => intval($total_result_data),
                "recordsFiltered" => intval($total_result_data),
                "data"    => $data
            );
            
            
            echo json_encode($output);
            exit();
            
        }


    }


}
Wp_Cashback_Wallet_Frontend::instance();