<?php

class PageLines_Updater_Admin_Functions {
	
	function __construct() {
		add_action( 'admin_init', array( $this, 'new_check_post' ),11 );
	//	add_action( 'admin_init', array( $this, 'check_get' ),11 );
		add_action( 'admin_init', array( $this, 'upgrade' ), 12 );
	}
	
	
	function new_check_post() {
		
		global $pl_update_error;


		if( ! isset( $_REQUEST['action'] ) )
			return false;
		
		if( 'activate-products' == $_REQUEST['action'] && '' != $_REQUEST['license_key'] ) {
			
			$email = ( '' != $_POST['pl-updates-email'] ) ? $_POST['pl-updates-email'] : get_site_option('admin_email');
			
			$key = $_REQUEST['license_key'];

			// first lets verify the key...
			
			$request = $this->verify_key_request( 'update_key', $key, $email );
			
			if( ! is_array( $request ) ) {
				$pl_update_error = array( 'error' => 'Unable to verify key, try again in a moment.');
				return false;
			}
						
			if( true == $request['error'] ) {
				$pl_update_error = array( 'error' => $request['error'] );
				return false;
			}
						
			$this->maybe_activate( $request['id'], $key, $email );

			update_site_option( 'pl-activated', array( 
				$request['id'] => array(
					'key' => $key,
					'product' => $request['desc'],
					'email'	=> $email
						)
				) );
		}
		
		if( 'deactivate-products' == $_REQUEST['action'] && check_admin_referer( 'deactivate-key','deactivate-key' )  ) {
		
			update_site_option( 'pl-activated', false );
			
		}
		
	}
	
	function verify_key_request( $request, $key, $email ){
		
		$url = sprintf( 
			'http://www.pagelines.com/?pl-api=init&request=&request=%s&key=%s&email=%s', 
			$request,
			$key, 
			apply_filters( 'pl_updates_email', $email ) // allow devs to use their emails/keys
		);

		$headers = array(
			'headers' => array( 'Accept-Encoding' => '' ),
			'sslverify' => false,
			'timeout' => 300
		);

		$data = wp_remote_get( $url, $headers );

		$rsp = ( ! is_wp_error( $data ) && isset( $data['body'] ) ) ? (array) json_decode( $data['body'] ) : array();
		
		return $rsp;
		
	}
	
	function remote_key_request( $product, $request, $key, $email ){
		
		$url = sprintf( 
			'http://www.pagelines.com/?wc-api=software-api&request=%s&product_id=%s&licence_key=%s&instance=%s&email=%s', 
			$request,
			$product,
			$key, 
			site_url(),
			apply_filters( 'pl_updates_email', $email ) // allow devs to use their emails/keys
		);

		$headers = array(
			'headers' => array( 'Accept-Encoding' => '' ),
			'sslverify' => false,
			'timeout' => 300
		);

		$data = wp_remote_get( $url, $headers );

		$rsp = ( ! is_wp_error( $data ) && isset( $data['body'] ) ) ? (array) json_decode( $data['body'] ) : array();
		
		return $rsp;
		
	}

	function upgrade() {
		$updater = get_site_option( 'pl-activated', array() );
		$dms_core = get_option( 'dms_activation', array() );

		// make sure keys are valid...
		$need_save = false;
		
		if( ! is_array( $updater ) || ! $updater )
			return false;
		foreach( $updater as $slug => $key ) {
			if( ! is_array( $key ) ) {
				unset( $updater[$slug] );
				$need_save = true;
			}
		}
		
		if( true == $need_save )
			update_site_option( 'pl-activated', $updater );

	//	global $registered_pagelines_updates;
		
		// // if dms is already active...upgrade or old install, tell updater..
		// if( !is_multisite() && isset( $dms_core['active']) && $dms_core['active'] && isset( $dms_core['key'] ) && ! isset( $updater['dms'] ) ) {
		// 	$updater['dmspro'] = $dms_core['key'];
		// 	update_site_option( 'pl-activated', $updater );
		// 	$registered_pagelines_updates['dmspro']['product_status'] = 'active';
		// }
		
		// likewise, tell dms that were activated..
		if( is_array( $updater) ) {
			$dms_core = get_option( 'dms_activation', array() );
			
			foreach( $updater as $s => $k ) {
				$dms_core = array(
					'active'	=> true,
					'key'		=> $k['key']
				);
				break; // we only need 1
			}
			update_option( 'dms_activation', $dms_core );
		}		
	}

	function check_get() {
		if( ! isset( $_GET ) )
			return;

		if( ! isset( $_GET['action'] ) )
			return;	

		if( 'deactivate-product' == $_GET['action'] ) {
			$slug = $_GET['filepath'];
			
			// get key..
			$activated = get_site_option( 'pl-activated', array() );
			
			if( ! isset( $activated[$slug] ) || ! isset( $activated[$slug]['key'] ) )
				return false;
			$key = $activated[$slug]['key'];
			$email = $activated[$slug]['email'];
			// if( 'dms' == $slug )
			// 	$product = 'dmspro';
			// else
			$product = $slug;

			$request = $this->remote_key_request( $product, 'deactivation', $key, $email );
		
			if( is_array( $request ) && isset( $request['reset'] ) && true == $request['reset'] ) {
				
				unset($activated[$product]);
				// update new system

				update_site_option( 'pl-activated', $activated );
				// update old..
				$dms_core = get_option( 'dms_activation' );
				$dms_core['active'] = false;
				update_option( 'dms_activation', $dms_core );
				delete_transient( 'pl_updates_errors' );
			} else {
				if( is_array( $request ) && isset( $request['error'] ) && 'Invalid License Key' == $request['error'] ) {
					unset($activated[$product]);
					// update new system

					update_site_option( 'pl-activated', $activated );
					// update old..
					$dms_core = get_option( 'dms_activation' );
					$dms_core['active'] = false;
					update_option( 'dms_activation', $dms_core );
					delete_transient( 'pl_updates_errors' );
				}
				global $pl_update_error;
				$pl_update_error = $request;
			}
		}	
	}

	function check_post() {

		//check for $_POST
		if( ! isset( $_POST ) )
			return;
			
		if( ! isset( $_POST['action'] ) )
			return;
		
		if( 'activate-products' == $_POST['action'] ) {
			
			$email = ( '' != $_POST['pl-updates-email'] ) ? $_POST['pl-updates-email'] : get_site_option('admin_email');
			
			$products = (array) $_POST['license_keys'];
			foreach( $products as $slug => $key ) {
				if( $key ) {
					$this->maybe_activate( $slug, $key, $email );
				}
			}
			return false; //fin
		}
		// error?
	}
		
	function maybe_activate( $slug, $key, $email ) {
		
		global $registered_pagelines_updates;

		$product = $slug;

		$request = $this->remote_key_request( $product, 'activation', $key, $email );

			if( is_array( $request ) && true == $request['activated'] ) {

				$activated = get_site_option( 'pl-activated', array() );
				$activated[$slug]['key'] = $key;
				$activated[$slug]['email'] = $email;
				update_site_option( 'pl-activated', $activated );
				$registered_pagelines_updates[$slug]['product_status'] = 'active';
				delete_transient( 'pl_updates_errors' );
			} else {
				// log error..
				global $pl_update_error;
				$pl_update_error = $request;
			}
	}
		

	
}