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
<h4><?php _e( 'Available Balance:', 'woo-wallet' ); ?> <?php echo  get_wallet_balance('site', get_current_user_id() ); ?> Points </h4>
<p> Write 1 point is equivalent to 1 taka </p>
<table id="site-wallet-transaction" class="table">
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