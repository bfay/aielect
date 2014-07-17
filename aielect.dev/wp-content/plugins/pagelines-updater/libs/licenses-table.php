<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class PageLines_Updater_Licenses_Table extends WP_List_Table {
	public $per_page = 100;

	/**
	 * Constructor.
	 * @since  1.0.0
	 */
	public function __construct () {
		global $status, $page;

		$args = array(
	            'singular'  => 'license',     //singular name of the listed records
	            'plural'    => 'licenses',   //plural name of the listed records
	            'ajax'      => false        //does this table support ajax?

	    );

		$this->data = array();

		// Make sure this file is loaded, so we have access to plugins_api(), etc.
		require_once( ABSPATH . '/wp-admin/includes/plugin-install.php' );
		
		global $storeapi;
		if( ! class_exists( 'EditorStoreFront' ) )
			return $updates;

		if( ! is_object( $storeapi ) )
			$storeapi = new EditorStoreFront;

		$this->mixed_array = $storeapi->get_latest();

	    parent::__construct( $args );
	} // End __construct()

	/**
	 * Text to display if no items are present.
	 * @since  1.0.0
	 * @return  void
	 */
	public function no_items () {
	    echo wpautop( __( 'No PageLines products found.', 'opt' ) );
	} // End no_items(0)

	/**
	 * The content of each column.
	 * @param  array $item         The current item in the list.
	 * @param  string $column_name The key of the current column.
	 * @since  1.0.0
	 * @return string              Output for the current column.
	 */
	public function column_default ( $item, $column_name ) {
	    switch( $column_name ) {
	        case 'product':
//	        case 'product_status':
	        case 'product_version':
	            return $item[$column_name];
	        break;
	    }
	} // End column_default()

	/**
	 * Retrieve an array of sortable columns.
	 * @since  1.0.0
	 * @return array
	 */
	public function get_sortable_columns () {
	  return array();
	} // End get_sortable_columns()

	/**
	 * Retrieve an array of columns for the list table.
	 * @since  1.0.0
	 * @return array Key => Value pairs.
	 */
	public function get_columns () {
        $columns = array(
            'product_name' => __( 'Installed Products', 'opt' ),
            'product_version' => __( 'Version', 'opt' ),
       //     'product_status' => __( 'License Key', 'opt' )
        );
         return $columns;
    } // End get_columns()

    /**
     * Content for the "product_name" column.
     * @param  array  $item The current item.
     * @since  1.0.0
     * @return string       The content of this column.
     */
	public function column_product_name ( $item ) {
		if( !isset( $item['product_name'] ) )
			return false;
		$name = '<strong>' . $item['product_name'] . '</strong>';
		$out = sprintf( '%s<br />%s', $name, $item['product_desc'] );
		return $out;
	} // End column_product_name()

	/**
     * Content for the "product_version" column.
     * @param  array  $item The current item.
     * @since  1.0.0
     * @return string       The content of this column.
     */
	public function column_product_version ( $item ) {
		if( !isset( $item['product_name'] ) )
			return false;
		if( 'plugin' == $item['type'] && $item['product_update'] ) {
			$out =  sprintf( '%s<br />Update Available',
			$item['product_version']
			
			);
			 } else {
				$out = $item['product_version'];
			}
			return wpautop( $out );		
	} // End column_product_version()

	/**
     * Content for the "status" column.
     * @param  array  $item The current item.
     * @since  1.0.0
     * @return string       The content of this column.
     */
	public function column_product_status ( $item ) {
		if( !isset( $item['product_name'] ) )
			return false;
		$defaults = array(
			'type' 				=> 'theme',
			'product_file_path'	=> '',
			'product_name'		=> 'No name',
			'product_version'	=> '',
			'product_desc'		=> '',
			'product_status'	=> ''
			);
		$item = wp_parse_args( $item, $defaults );
		$response = '';
		$activated = get_site_option( 'pl-activated', array() );
		$deactivate_url = wp_nonce_url( add_query_arg( 'action', 'deactivate-product', add_query_arg( 'filepath', $item['product_file_path'], add_query_arg( 'page', 'pagelines_updater', network_admin_url( 'index.php' ) ) ) ), 'bulk-licenses' );
				
		if ( 'active' == $item['product_status'] ) {
			$response = '<a class="button button-primary" href="' . esc_url( $deactivate_url ) . '">' . __( 'Deactivate', 'opt' ) . '</a>' . "\n";
		} else {
			$response .= '<input class="input-key" name="license_keys[' . esc_attr( $item['product_file_path'] ) . ']" id="license_keys-' . esc_attr( $item['product_file_path'] ) . '" type="text" value="" size="37" aria-required="true" placeholder="' . esc_attr( sprintf( __( '%s key', 'opt' ), $item['product_name'] ) ) . '" />' . "\n";
		}

		if( isset( $activated['dmspro']) && $item['product_file_path'] != 'dmspro' )
			$response = 'Already activated.';
		
		if( 'plugin' == $item['type'] && $item['product_update'] )
			$response = '<strong>Update available!</strong>';

		return $response;
	} // End column_status()

	/**
	 * Retrieve an array of possible bulk actions.
	 * @since  1.0.0
	 * @return array
	 */
	public function get_bulk_actions () {
	  $actions = array();
	  return $actions;
	} // End get_bulk_actions()



	/**
	 * Prepare an array of items to be listed.
	 * @since  1.0.0
	 * @return array Prepared items.
	 */
	public function prepare_items () {
	  $columns  = $this->get_columns();
	  $hidden   = array();
	  $sortable = $this->get_sortable_columns();
	  $this->_column_headers = array( $columns, $hidden, $sortable );

	  $total_items = count( $this->data );

	  // only ncessary because we have sample data
	  $this->found_data = $this->data;

	  $this->set_pagination_args( array(
	    'total_items' => $total_items,                  //WE have to calculate the total number of items
	    'per_page'    => $total_items                   //WE have to determine how many items to show on a page
	  ) );
	  $this->items = $this->found_data;
	} // End prepare_items()
} // End Class
?>