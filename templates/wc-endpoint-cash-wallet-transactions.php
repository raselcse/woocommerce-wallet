<?php
/**
 * The Template for displaying transaction history
 *
 * This template can be overridden by copying it to yourtheme/woo-wallet/wc-endpoint-wallet-transactions.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author 	Subrata Mal
 * @version     1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

//$transactions = get_wallet_transactions();
do_action( 'woo_wallet_before_transaction_details_content' );
?>
<h4><?php _e( 'Available Balance:', 'wp-cashback-wallet' ); ?> <?php echo  get_wallet_balance('cash', get_current_user_id() ). ' Points. ';?></h4>

<p> Write 1 point is equivalent to 1 taka </p>
<?php
if(is_balance_withdraw_request('pending')) {
    echo "<span class='pending_request_msg'> we received a withdrwal request and after review we will send to your account.</span>";
}else{
    echo '<a href="#" id="balance_withdrow_button" class="woocommerce-button button view">Request Point Withdrawal</a>';
}
?> 
<table id="cash-wallet-transaction" class="table">
<thead>
        <tr>
            <th>Trans ID</th>
            <th>Details</th>
            <!-- <th>Type</th> -->
            <th>Points</th>
            <th>Balance</th>
            <th>date</th>
        </tr>
    </thead>

</table>
<?php do_action( 'woo_wallet_after_transaction_details_content' );
?>

<div id="request_balance_withdraw_modal" class="modal" style="display: none;">
    <div class="modal-content"> 
    <div id="loader" class="lds-dual-ring hidden overlay"></div>   
         <span class="close">×</span>    
         <h3 style="text-align:center"> Withdraw request form </h3> 
         <form class="request_balance_widrow_form" name="refund_reason_form" method="post"> 
                <input type="number" name="withdraw_amount" placeholder="Withdraw amount" id="withdraw_amount" step="any" /> 
                <select name="withdraw" id="withdraw_method">
                    <option value="0">select withdraw method</option>
                    <option value="bkash">Bkash</option>
                    <option value="bank">Bank</option>
                </select>
                <div id="bkash-section" style="display: none;">
                    <input type="text" name="bkash_account_name" id="bkash_account_name" placeholder="Bkash account name"/>
                    <input type="number" name="bkash_account_number" id="bkash_account_number" placeholder="Bkash account number"/> 
                    <select name="bkash_account_type" id="bkash_account_type">
                        <option value="0">select account type</option>
                        <option value="personal">Personal</option>
                        <option value="agent">Agent</option>
                    </select>
                </div>

                <div id="bank-section" style="display: none;">
                    <input type="text" name="bank_name" id="bank_name" placeholder="Bank name"/> 
                    <input type="text" name="bank_branch_name" id="bank_branch_name" placeholder="Bank branch name"/> 
                    <input type="text" name="bank_account_name" id="bank_account_name" placeholder="Bank account name"/>
                    <input type="number" name="bank_account_number" id="bank_account_number" placeholder="Bank account number"/> 
                </div>
                
                <textarea placeholder="Others note" id="others_note" name="others_note" autocomplete="off" class=""></textarea>  
                <input type="button" id="balance_widrow_submit" value="Submit">      
        </form>
    </div>
</div>

 <!-- Modal -->
 <div class="modal fade" id="success" role="dialog">
    <div class="modal-dialog">
    
      <!-- Modal content-->
      <div class="modal-content" style="border:none;border-radius: 5px;">
        <div class="modal-header" style="    background: #1ab394;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;">
         <h4 class="modal-title text-center"><img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/success_icon.png'; ?>" alt=""></h4>
        </div>
        <div class="modal-body">
          <p style="text-align:center;color:#1ab394;font-size:24px;font-weight:500;">Nice! Your balance withdraw request successfully send</p>
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default btn-close" data-dismiss="modal">Close</button>
        </div>
      </div>
      
    </div>
  </div>

<div class="modal fade" id="fail" role="dialog">
    <div class="modal-dialog">
    
      <!-- Modal content-->
      <div class="modal-content" style="border:none;border-radius: 5px;">
        <div class="modal-header" style="    background: #d75a4a;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;">
           <h4 class="modal-title text-center"><img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/close_icon.png'; ?>" alt=""></h4>
        </div>
        <div class="modal-body">
          <p style="text-align:center;color:#d75a4a;font-size:24px;font-weight:500;">Sorry! your request was wrong!</p>
          <p class="error_msg"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default btn-close" data-dismiss="modal">Close</button>
        </div>
      </div>
      
    </div>
  </div>