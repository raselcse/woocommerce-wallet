<?php

use PHPUnit\Framework\MockObject\Stub\ReturnStub;

function calculateBalance($amount , $table , $customer_id){
    global $wpdb;
    $result = $wpdb->get_col( $wpdb->prepare( "SELECT balance FROM {$table} WHERE user_id= %d ORDER BY transaction_id DESC LIMIT 1", $customer_id ) );
    if ($result == NULL){
        $result = 0;
    }
    $newBalance = floatval ($result[0])+ floatval ($amount);
    
    
    return $newBalance;
}

function calculateBalanceOnDebit($amount , $table , $customer_id){
    global $wpdb;
    $result = $wpdb->get_col( $wpdb->prepare( "SELECT balance FROM {$table} WHERE user_id= %d ORDER BY transaction_id DESC LIMIT 1", $customer_id ) );
    if ($result == NULL){
        $result = 0;
    }
    $newBalance = floatval ($result[0]) - floatval ($amount);
    
    
    return $newBalance;
}
if (!function_exists('get_wallet_balance')) {
    function get_wallet_balance ($wallet, $customer_id){
        global $wpdb;
        $table = $wpdb->prefix."cashback_".$wallet."_wallet_transactions";
        $result = $wpdb->get_col( $wpdb->prepare( "SELECT balance FROM {$table} WHERE user_id= %d ORDER BY transaction_id DESC LIMIT 1", $customer_id ) );
        return floatval ($result[0]);
    }
}

if (!function_exists('insert_wallet_transaction')) {

    function insert_wallet_transaction($trans_type, $customer_id, $wallet_type, $amount, $details=array()){
        global $wpdb;
        if($wallet_type == 'cash'){
            $table = $wpdb->prefix."cashback_".$wallet_type."_wallet_transactions";
        }else{
            $table = $wpdb->prefix."cashback_".$wallet_type."_wallet_transactions";
        }
        if($trans_type == 'credit'){
            $balance = calculateBalance($amount, $table, $customer_id);
        }else if($trans_type == 'debit'){
            $balance = calculateBalanceOnDebit($amount, $table, $customer_id);
        }


        $data_cash_wallet = array( 
            'user_id'      => $customer_id,
            'type'          => $trans_type,
            'amount'        => $amount,
            'balance'       => $balance,
            'currency'     => "BDT",
            'details'       => json_encode($details),
            'created_by'    => get_current_user_id(),
            'deleted'       => 0,
        );
        
        if($wpdb->insert( $table, $data_cash_wallet )){
            $transid = $wpdb->insert_id;
            return $transid;
        }
        return false;

    }
}

function get_wallet_payment_name($wallet_type){
    $title = ($wallet_type == 'site')?get_option( 'site_wallet_title'):get_option( 'cash_wallet_title');
    if(empty($title)) return;

    return $title;
}

if( !function_exists( 'is_wallet_partial_payment_order_item' ) ){
    /**
     * Check if order item is partial payment instance.
     * @param Int $item_id
     * @param WC_Order_Item_Fee $item
     * @return boolean
     */
    function is_wallet_partial_payment_order_item($item_id, $item, $wallet_type){
        $title = get_wallet_payment_name($wallet_type);
        if( get_metadata( 'order_item', $item_id, '_legacy_fee_key', true ) && '_via_'.$wallet_type.'_wallet_partial_payment' === get_metadata( 'order_item', $item_id, '_legacy_fee_key', true ) ){
            return true;
        }
        else if ( $title.' payment' ===  $item->get_name( 'edit' ) ) {
            return true;
        }
        return false;
    }
    
}

if ( ! function_exists( 'get_order_partial_payment_amount' ) ) {
    /**
     * Get total partial payment amount from an order.
     * @param Int $order_id
     * @return Number
     */
    function get_order_partial_payment_amount( $order_id , $wallet_type) {
        $via_wallet = 0;
        $title = get_wallet_payment_name($wallet_type);
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $line_items_fee = $order->get_items( 'fee' );
            foreach ( $line_items_fee as $item_id => $item ) {
                $fee_name = $item->get_name();
                if($fee_name === $title.' payment'){
                    if(is_wallet_partial_payment_order_item($item_id, $item , $wallet_type)){
                        $via_wallet += $item->get_total( 'edit' ) + $item->get_total_tax( 'edit' );
                    }
                }
                
            }
        }
        return $via_wallet;
    }

}
if ( ! function_exists( 'get_order_wallet_payment_amount' ) ) {
    function get_order_wallet_payment_amount($order_id , $wallet_type){
        // (optional if not defined) An instance of the WC_Order object
        // if(get_post_meta($order_id, '_cashback_amount_to_'.$name.'_wallet', true))
        //     return floatval(get_post_meta($order_id, '_cashback_amount_to_'.$name.'_wallet', true));
        $title = get_wallet_payment_name($wallet_type);
        $the_order = wc_get_order( $order_id );

        // Iterating through order fee items ONLY
        foreach( $the_order->get_items('fee') as $item_id => $item_fee ){

            // The fee name
            $fee_name = $item_fee->get_name();
            if($fee_name === $title.' payment'){
                // The fee total amount
                // $fee_total = $item_fee->get_total();

                // // The fee total tax amount
                // $fee_total_tax = $item_fee->get_total_tax();
                
                $item_id = $item_fee->get_id(); //???
                $fee_amount = floatval(wc_get_order_item_meta( $item_id, '_fee_amount', true ));
                return $fee_amount;
            }
        
        }

        return ;
    }
}

if ( ! function_exists( 'total_cashback_amount' ) ) {
    function total_cashback_amount($order_id){
        if ( get_post_meta($order_id, '_cashback_to_wallet', true) ) {
            return floatval(get_post_meta($order_id, '_cashback_amount_to_site_wallet', true)) +  floatval(get_post_meta($order_id, '_cashback_amount_to_cash_wallet', true));
        }
        return 0;	
    }
}

if ( ! function_exists( 'is_available_refund_button_of_offer_product' ) ) {
    function is_available_refund_button_of_offer_product($order_id){
        if ( get_post_meta($order_id, '_cashback_to_wallet', true) ) {
            $order = wc_get_order( $order_id );
            $max_refund = wc_format_decimal( $order->get_total() , wc_get_price_decimals() );
            $cash_wallet_payment = abs(get_order_wallet_payment_amount($order_id, 'cash'));
             return $cash_wallet_payment . $max_refund;
            if($cash_wallet_payment > $max_refund){
                return true;
            }else{
                return false;
            }
        }
        return true;	
    }
}

function maximum_refund_available($order_id){
    $order = wc_get_order( $order_id );

    $total_cashback = total_cashback_amount($order_id);
    if( $order->get_total() > $total_cashback){
        $max_refund = wc_format_decimal( $order->get_subtotal() + $order->get_shipping_total() - $order->get_total_refunded() - $total_cashback, wc_get_price_decimals() );
    }else{
        $max_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded(), wc_get_price_decimals() );
    }
    return $max_refund;
}
 

if (!function_exists('is_refund_request_submit')) {
    function is_refund_request_submit($order_id){
        global $wpdb;
        $user_id = get_post_meta($order_id, '_customer_user', true);
        $table = $wpdb->prefix."cashback_refund_request";
        $result = $wpdb->get_col( $wpdb->prepare( "SELECT status FROM {$table} WHERE order_id= %d AND user_id=%d", $order_id , $user_id ) );
        return $result[0];
    }
}

if (!function_exists('is_balance_withdraw_request')) {
    function is_balance_withdraw_request($status){
        global $wpdb;
        $user_id = get_current_user_id();
        $table = $wpdb->prefix."cashback_withdrawal_request";
        $result = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE user_id=%d and status=%s", $user_id, $status ) );
        return $result[0];
    }
}

if (!function_exists('get_withdraw_request_amount')) {
    function get_withdraw_request_amount($id){
        global $wpdb;
        $table = $wpdb->prefix."cashback_withdrawal_request";
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $id ) );
        return $result;
    }
}

if(!function_exists('convert_nearest_intiger')){
    function convert_nearest_intiger($value){
        return round($value);
    }
}

function price_with_currency($total_cashback){
    return get_woocommerce_currency_symbol().floatval($total_cashback);
}

if(!function_exists('get_wallet_transactions')){
    function get_wallet_transactions($arg){
        global $wpdb;
        $table = $wpdb->prefix."cashback_".$arg['wallet_type']."_wallet_transactions";

        $sql = "SELECT * FROM $table WHERE user_id = {$arg['user_id']}";

        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
        }
        $sql .= " LIMIT {$arg['limit']}";
    
        $result = $wpdb->get_results( $wpdb->prepare( $sql ) );
        return $result;
    
    }
}


