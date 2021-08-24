<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Withdraw_Request_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Refund request', 'wp-cashback-wallet' ), //singular name of the listed records
			'plural'   => __( 'All refund request', 'wp-cashback-wallet' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );

	}


	/**
	 * Retrieve withdraw request data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_withdraw_request( $per_page = 5, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}cashback_withdrawal_request";

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}


	/**
	 * Delete a withdraw_request record.
	 *
	 * @param int $id withdraw_request ID
	 */
	public static function delete_withdraw_request( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}cashback_withdrawal_request",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}
    
    	/**
	 * Delete a withdraw_request record.
	 *
	 * @param int $id withdraw_request ID
	 */
	public static function update_withdraw_request_status($id , $status) {
		global $wpdb;
        $table_name = "{$wpdb->prefix}cashback_withdrawal_request";
        
        $wpdb->update(
             $table_name,
            array( 'status' => $status),
            array('id'=>$id)
        );
        // Print last SQL query string
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}cashback_withdrawal_request";

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no withdraw_request data is available */
	public function no_items() {
		_e( 'No withdraw request avaliable.', 'wp-cashback-wallet' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
             
            case 'user_id':
                $user_link = site_url().'/wp-admin/user-edit.php?user_id='.$item[ $column_name ];
				$the_user = get_userdata( $item[ $column_name ] );
                return "<a href='$user_link'>".$the_user->first_name." " .$the_user->last_name."</a>";
            break;
            case 'withdraw_amount':
            case 'withdraw_method':
               return $item[$column_name];
            case 'widthraw_account_details':  
                $data =  json_decode($item[ $column_name ] , true);
                $item = '';
                foreach($data as $key=>$value){
                    $label = str_replace('_', ' ', $key);
                    $item .= $label.' : ' . $value .',</br>';
                }
                return $item;
            break;

            case 'status':
                return "<span class='withdraw status $item[$column_name]'>".$item[$column_name]."</span>";
            break;

            case 'created_at':
                return date('jS M, Y h:i A', strtotime($item[ $column_name ]) );
            break;
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="request-id[]" value="%s" />', $item['id']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

		$delete_nonce = wp_create_nonce( 'sp_delete_withdraw_request' );
        $cancel_nonce = wp_create_nonce( 'sp_cancel_withdraw_request' );
        $successful_nonce = wp_create_nonce( 'sp_successful_withdraw_request' );
		$title = '<strong>' . $item['name'] . '</strong>';

		$actions = [
            'delete' => sprintf( '<a href="?page=%s&action=%s&withdraw_request=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce ),
            'cancel' => sprintf( '<a href="?page=%s&action=%s&withdraw_request=%s&_wpnonce=%s">Cancel</a>', esc_attr( $_REQUEST['page'] ), 'cancel', absint( $item['id'] ), $cancel_nonce ),
            'sucessful' => sprintf( '<a href="?page=%s&action=%s&withdraw_request=%s&_wpnonce=%s">Edit</a>', esc_attr( $_REQUEST['page'] ), 'refunded', absint( $item['id'] ), $successful_nonce )
		];

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'user_id'    => __( 'User ID', 'wp-cashback-wallet' ),
			'withdraw_amount' => __( 'Withdraw amount', 'wp-cashback-wallet' ),
            'withdraw_method'    => __( 'Withdraw method', 'wp-cashback-wallet' ),
            'widthraw_account_details'    => __( 'Withdraw details', 'wp-cashback-wallet' ),
            'status' => __( 'Status', 'wp-cashback-wallet' ),
            'created_at' => __( 'Date', 'wp-cashback-wallet' ),
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
            'user_id' => array( 'user_id', true ),
            'status' => array( 'status', true ),
			'withdraw_amount' => array( 'withdraw_amount', true )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
            'bulk-delete' => 'Delete',
            'bulk-cancel' => 'Cancel',
            'bulk-successful' => 'Successful',
		];

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'customers_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_withdraw_request( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_delete_withdraw_request' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				self::delete_withdraw_request( absint( $_GET['withdraw_request'] ) );

		                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		                // add_query_arg() return the current url
		                wp_redirect( esc_url_raw(add_query_arg()) );
				exit;
			}

        }
        
        

        if ('cancel' === $this->current_action()) {
            // In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_cancel_withdraw_request' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
                self::update_withdraw_request_status( absint( $_GET['withdraw_request'] ), 'cancel' );

                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
                // add_query_arg() return the current url
                wp_redirect( esc_url_raw(add_query_arg()) );
        exit;
				
			}
        }

        
        if ('successful' === $this->current_action()) {
            // In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_successful_withdraw_request' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
                self::update_withdraw_request_status( absint( $_GET['withdraw_request'] ), 'successful' );

                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
                // add_query_arg() return the current url
                wp_redirect( esc_url_raw(add_query_arg()) );
        exit;
				
			}
        }

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['request-id'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_withdraw_request( $id );

			}

			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		        // add_query_arg() return the current url
		        wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
        }
        
       

          // If the Accepted bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-successful' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-successful' )
        ) {

        $delete_ids = esc_sql( $_POST['request-id'] );

        // loop over the array of record IDs and delete them
        foreach ( $delete_ids as $id ) {
			$request_data = get_withdraw_request_amount($id);
			$available_cash = get_wallet_balance('cash',$request_data->user_id);
			if($available_cash < $request_data->withdraw_amount){
				die( 'Your withdrawal balance must be equal or less from cash wallet balance ' );
			}
            $details = array(
                'transaction_info' => __('Withdraw from Cash Wallet to '.$request_data->withdraw_method.'', 'wp-cashback-wallet')
			);
			if($request_data->status !=='pending'){
				die( 'You only change successful status from pending ' );
			}
            $trans_id = insert_wallet_transaction('debit', $request_data->user_id, 'cash', $request_data->withdraw_amount, $details);
            if($trans_id ){
                self::update_withdraw_request_status($id , 'successful');
            }
            //exit;
            

        }

        // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
            // add_query_arg() return the current url
            wp_redirect( esc_url_raw(add_query_arg()) );
        exit;
        }

         // If the Accepted bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-cancel' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-cancel' )
        ) {

        $delete_ids = esc_sql( $_POST['request-id'] );

        // loop over the array of record IDs and delete them
        foreach ( $delete_ids as $id ) {
            self::update_withdraw_request_status($id , 'cancel');

        }

        // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
            // add_query_arg() return the current url
            wp_redirect( esc_url_raw(add_query_arg()) );
        exit;
        }
	}

}


class WPCB_Wallet_Table {

	// class instance
	static $instance;

	// withdraw_request WP_List_Table object
	public $withdraw_request_obj;

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
            'wp_cashback_wallet_settings',
			'List of withdraw request',
			'List of withdraw request',
			'manage_options',
			'withdraw-request',
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
			<h2>All refund request</h2>

			<div id="withdrawstuff">
				<div id="withdraw-body" class="metabox-holder columns-2">
					<div id="withdraw-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->withdraw_request_obj->prepare_items();
								$this->withdraw_request_obj->display(); ?>
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
			'label'   => 'Request',
			'default' => 5,
			'option'  => 'customers_per_page'
		];

		add_screen_option( $option, $args );

		$this->withdraw_request_obj = new Withdraw_Request_List();
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
	WPCB_Wallet_Table::get_instance();
} );

