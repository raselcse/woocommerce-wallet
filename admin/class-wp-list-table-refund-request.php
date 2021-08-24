<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Refund_Request_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Refund request', 'wp-cashback-wallet' ), //singular name of the listed records
			'plural'   => __( 'All refund request', 'wp-cashback-wallet' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );

	}


	/**
	 * Retrieve refund request data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_refund_request( $per_page = 5, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}cashback_refund_request";

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
	 * Delete a refund_request record.
	 *
	 * @param int $id refund_request ID
	 */
	public static function delete_refund_request( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}cashback_refund_request",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}
    
    	/**
	 * Delete a refund_request record.
	 *
	 * @param int $id refund_request ID
	 */
	public static function update_refund_status($id , $status) {
		global $wpdb;
        $table_name = "{$wpdb->prefix}cashback_refund_request";
        
        $wpdb->update(
             $table_name,
            array( 'status' => $status),
            array('id'=>$id)
        );
        // Print last SQL query string
		// var_dump($wpdb->last_query);
		// exit;
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}cashback_refund_request";

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no refund_request data is available */
	public function no_items() {
		_e( 'No request avaliable.', 'wp-cashback-wallet' );
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
			case 'refund_reason':
				return $item[ $column_name ];
				break;
			case 'order_id':
				$order_link = site_url().'/wp-admin/post.php?post='.$item[ $column_name ].'&action=edit';
				return "<a href='$order_link'>#".$item[ $column_name ]."</a>";

			case 'status':
				return "<span class='refund status $item[$column_name]'>".$item[$column_name]."</span>";
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

		$delete_nonce = wp_create_nonce( 'sp_delete_refund_request' );
        $accepted_nonce = wp_create_nonce( 'sp_accepted_refund_request' );
        $cancel_nonce = wp_create_nonce( 'sp_cancel_refund_request' );
        $refunded_nonce = wp_create_nonce( 'sp_refunded_refund_request' );
		$title = '<strong>' . $item['name'] . '</strong>';

		$actions = [
            'delete' => sprintf( '<a href="?page=%s&action=%s&refund_request=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce ),
            'accepted' => sprintf( '<a href="?page=%s&action=%s&refund_request=%s&_wpnonce=%s">Accepted</a>', esc_attr( $_REQUEST['page'] ), 'accepted', absint( $item['id'] ), $accepted_nonce ),
            'cancel' => sprintf( '<a href="?page=%s&action=%s&refund_request=%s&_wpnonce=%s">Cancel</a>', esc_attr( $_REQUEST['page'] ), 'cancel', absint( $item['id'] ), $cancel_nonce ),
            'refunded' => sprintf( '<a href="?page=%s&action=%s&refund_request=%s&_wpnonce=%s">Edit</a>', esc_attr( $_REQUEST['page'] ), 'refunded', absint( $item['id'] ), $refunded_nonce )
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
			'order_id' => __( 'Order ID', 'wp-cashback-wallet' ),
            'refund_reason'    => __( 'Refund reason', 'wp-cashback-wallet' ),
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
			'order_id' => array( 'order_id', false )
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
            'bulk-accepted' => 'Accepted',
            'bulk-cancel' => 'Cancel',
            'bulk-refunded' => 'Refunded',
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

		$per_page     = $this->get_items_per_page( 'request_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_refund_request( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_delete_refund_request' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				self::delete_refund_request( absint( $_GET['refund_request'] ) );

		                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		                // add_query_arg() return the current url
		                wp_redirect( esc_url_raw(add_query_arg()) );
				exit;
			}

        }
        
        if ('accepted' === $this->current_action()) {
            // In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_accepted_refund_request' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
                self::update_refund_status( absint( $_GET['refund_request'] ), 'accepted' );

                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
                // add_query_arg() return the current url
               // wp_redirect( esc_url_raw(add_query_arg()) );
        exit;
				
			}
        }

        if ('cancel' === $this->current_action()) {
            // In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_cancel_refund_request' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
                self::update_refund_status( absint( $_GET['refund_request'] ), 'cancel' );

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
				self::delete_refund_request( $id );

			}

			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		        // add_query_arg() return the current url
		        wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
        }
        
        // If the Accepted bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-accepted' )
                || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-accepted' )
        ) {

            $delete_ids = esc_sql( $_POST['request-id'] );
          
            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {
                self::update_refund_status($id , 'accepted');

            }

            // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
                // add_query_arg() return the current url
                wp_redirect( esc_url_raw(add_query_arg()) );
            exit;
        }

          // If the Accepted bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-refunded' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-refunded' )
        ) {

        $delete_ids = esc_sql( $_POST['request-id'] );

        // loop over the array of record IDs and delete them
        foreach ( $delete_ids as $id ) {
            self::update_refund_status($id , 'refunded');

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
            self::update_refund_status($id , 'cancel');

        }

        // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
            // add_query_arg() return the current url
            wp_redirect( esc_url_raw(add_query_arg()) );
        exit;
        }
	}

}


class WPCB_Refund_Request {

	// class instance
	static $instance;

	// refund_request WP_List_Table object
	public $refund_request_obj;

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
			'List of refund request',
			'List of refund request',
			'manage_options',
			'refund-request',
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

			<div id="refund-stuff">
				<div id="refund-body" class="metabox-holder columns-2">
					<div id="refund-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->refund_request_obj->prepare_items();
								$this->refund_request_obj->display(); ?>
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
			'default' =>10,
			'option'  => 'request_per_page'
		];

		add_screen_option( $option, $args );

		$this->refund_request_obj = new Refund_Request_List();
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
	WPCB_Refund_Request::get_instance();
} );

