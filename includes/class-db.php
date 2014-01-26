<?php

class Affiliate_WP_DB {

	public $table_name;

	public $version;

	public $primary_key;

	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'affiliate_wp_affiliates';
		$this->primary_key = 'affiliate_id';
		$this->version     = '1.0';

	}


	public function get_columns() {
		return array(
			'affiliate_id' => '%d',
			'user_id'      => '%d',
			'earnings'     => '%s',
			'referrals'    => '%d',
			'visits'       => '%d',
		);
	}

	public function get_column_defaults() {
		return array(
			'user_id'  => get_current_user_id()
		);
	}

	public function add( $data = array() ) {
		return $this->insert( $data, 'affiliate' );
	}

	public function get( $column, $row_id ) {
		global $wpdb;
		return $wpdb->get_var( "SELECT $column FROM $this->table_name WHERE $this->primary_key = $row_id;" );
	}

	public function get_by( $column, $row_id ) {
		global $wpdb;
		return $wpdb->get_var( "SELECT $this->primary_key FROM $this->table_name WHERE $column = $row_id;" );
	}

	public function set( $row_id, $column, $value ) {
		$data = array();
		$data[ $column ] = $value;
		$this->update( $row_id, $data );
	}

	public function insert( $data, $type = 'affiliate' ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( 'affwp_pre_insert_' . $type, $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );

		do_action( 'affwp_post_insert_' . $type, $wpdb->insert_id, $data );

		return $wpdb->insert_id;
	}

	public function update( $row_id, $data = array() ) {
		global $wpdb;        

		// Row ID must be positive integer
		$row_id = absint( $row_id );     
		if( empty( $row_id ) )
			return false;

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case ( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === $wpdb->update( $this->table_name, $data, array( $this->primary_key => $row_id ), $column_formats ) ) {
			return false;
		}

		return true;
	}
	
	public function create_table() {

		global $wpdb;

		if( $wpdb->get_var( "show tables like '{$this->table_name}'" ) == $this->table_name )
			return;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		`affiliate_id` bigint(20) NOT NULL AUTO_INCREMENT,
		`user_id` bigint(20) NOT NULL,
		`earnings` mediumtext NOT NULL,
		`referrals` bigint(20) NOT NULL,
		`visits` bigint(20) NOT NULL,
		PRIMARY KEY  (affiliate_id),
		KEY user_id (user_id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

}