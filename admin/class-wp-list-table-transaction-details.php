<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WPCB_Wallet_Transaction_Details extends WP_List_Table {

    /**
     * Total number of found users for the current query
     *
     * @since 3.1.0
     * @var int
     */
    private $total_count = 0;

    public function __construct() {
        parent::__construct( array(
            'singular' => 'transaction',
            'plural'   => 'transactions',
            'ajax'     => false,
            'screen'   => 'wc-wallet-transactions',
        ) );
    }

    public function get_columns() {
        return apply_filters('manage_woo_wallet_transactions_columns', array(
            
            'transaction_id' => __( 'ID', 'wp-cashback-wallet' ),
            'name'           => __( 'Name', 'wp-cashback-wallet' ),
            'type'           => __( 'Type', 'wp-cashback-wallet' ),
            'amount'         => __( 'Amount', 'wp-cashback-wallet' ),
            'details'        => __( 'Details', 'wp-cashback-wallet' ),
            'date'           => __( 'Date', 'wp-cashback-wallet' )
        ));
    }

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $perPage     = $this->get_items_per_page( 'transactions_per_page', 10 );
        $currentPage = $this->get_pagenum();

        $data = $this->table_data( ( $currentPage - 1 ) * $perPage, $perPage );
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $this->total_count,
            'per_page'    => $perPage
        ) );
    }
    
    /**
    * Output 'no users' message.
    *
    * @since 3.1.0
    */
    public function no_items() {
        _e( 'No transactions found.', 'wp-cashback-wallet' );
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns() {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'transaction_id' => array( 'transaction_id', true ),
            'type' => array( 'type', true ),
		);

		return $sortable_columns;
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data( $lower = 0, $uper = 10 ) {
        global $wpdb;
        $data    = array();
        $user_id = filter_input(INPUT_GET, 'user_id' );
        $wallet_type = filter_input(INPUT_GET, 'wallet_type' );
        if ( $user_id == NULL ) {
            return $data;
        }
        $transactions = get_wallet_transactions( array( 'wallet_type' => $wallet_type, 'user_id' => $user_id, 'limit' => $lower . ',' . $uper ) );
        $this->total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->base_prefix}cashback_".$wallet_type."_wallet_transactions WHERE user_id={$user_id}" );
        if ( ! empty( $transactions ) && is_array( $transactions ) ) {
            foreach ( $transactions as $key => $transaction ) {
                $details = json_decode($transaction->details);
                $data[] = array(
                    'transaction_id' => $transaction->transaction_id,
                    'name'           => get_user_by( 'ID', $transaction->user_id )->display_name,
                    'type'           => ( 'credit' === $transaction->type) ? __( 'Credit', 'wp-cashback-wallet' ) : __( 'Debit', 'wp-cashback-wallet' ),
                    'amount'         => $transaction->amount,
                    'details'        => $details->transaction_info,
                    'date'           => wc_string_to_datetime( $transaction->date )->date_i18n( wc_date_format() )
                );
            }
        }
        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'transaction_id':
            case 'name':
            case 'type':
            case 'amount':
            case 'details':
            case 'date':
                return $item[$column_name];
            default:
                return print_r( $item, true );
        }
    }

}


class WPCB_Wallet_transaction {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $transaction_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function plugin_menu() {
        $icon = 'dashicons-admin-plugins';
    	$position = 100;
        $hook = add_submenu_page(
            '',
			'user transaction',
			'user transaction',
			'manage_options',
			'wpcb-wallet-transactions',
			[ $this, 'plugin_settings_page' ]
		);

		add_action( "load-$hook", [ $this, 'screen_option' ] );

	}


	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {
		?>
		<div class="wrap">
            <h2> Wallet Transaction </h2>
            <?php 
                if(!empty($_REQUEST['wallet_type'])){
                    $wallet_type = $_REQUEST['wallet_type'];
                }

                if(!empty($_REQUEST['user_id'])){
                    $user_id = $_REQUEST['user_id'];
                }
            ?>
            <p>Current <?PHP echo $wallet_type ?> wallet balance: <?PHP echo get_wallet_balance($wallet_type, $user_id)?> </p>
			<div id="withdrawstuff">
				<div id="withdraw-body" class="metabox-holder columns-2">
					<div id="withdraw-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->transaction_obj->prepare_items();
								$this->transaction_obj->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	<?php
	}

	/**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Transaction',
			'default' => 5,
			'option'  => 'transactions_per_page'
		];

		add_screen_option( $option, $args );

		$this->transaction_obj = new WPCB_Wallet_Transaction_Details();
	}


	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}


add_action( 'plugins_loaded', function () {
	WPCB_Wallet_transaction::get_instance();
} );