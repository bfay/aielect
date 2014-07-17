<?php

class PageLines_Updater_Products {
	
	function __construct() {
		if( ! class_exists( 'EditorStoreFront' ) )
			return;
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'injectUpdatePlugins' ), 9999 );		
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'injectUpdateThemes' ), 9999 );
		add_action( 'load-update-core.php', array( $this, 'del_store_data' ), 1 );
		add_filter( 'plugins_api', array( $this, 'check_info' ), 10, 3 );
		$hook = is_multisite() ? 'network_admin_notices' : 'admin_notices';
		add_action( $hook, array( $this, 'display_errors' ), 999 );
		add_action( $hook, array( $this, 'theme_changelogs' ), 999 );
			
	}
	
	function theme_changelogs() {
		global $pagenow, $storeapi;
		$changelog_text = '';
		
		
		$check = get_site_transient( 'update_themes' );
		
		$screen = get_current_screen();

		if( ! in_array( $screen->id, array( 'update-core', 'dashboard', 'toplevel_page_PageLines-Admin' ) ) )
			return false;
		
		if( ! isset( $check->response['dms'] ) )
			return false;

		$data = $check->response['dms']['upgrade_notice'];
		
		$pagelines_update = $check->response['dms'];
	
		$cl_array = explode( '*', $data );

		foreach( $cl_array as $k => $cl ) {
			if ( ! $cl ) {
				unset( $cl_array[$k] );
				continue;
			}
			$cl_array[$k] = ltrim($cl);
		}

		$dms_changelog = $cl_array;
		
		if( empty( $dms_changelog ) )
			return false;
		
		// format the update data
		
		$changelog_text = '<ul>';
		
		foreach( $dms_changelog as $k => $text ) {
			
			$changelog_text .= sprintf( '<li>%s</li>', $text );
			
		}
		
		$changelog_text .= '</ul>';
		
		$account_set_url = add_query_arg( array( 'tablink' => 'account', 'tabsublink' => 'pl_account#pl_account' ), site_url() );

		$details_button = '<span style="float:right" class="pl_updates"><a href="#">Click for changelog details</a></span><span style="float:right" class="pl_updates hidden"><a href="#">Hide</a></span>';

		$warning = ( $screen->id == 'update-core' ) ? '<br /><strong>Please</strong> update all plugins before upgrading DMS and remember to <strong>make a backup!</strong>' : '';

		$details = sprintf( '<div id="pl_updates_data" style="display:none"><h3>Version %s</h3>%s</div>', $pagelines_update['new_version'], $changelog_text );

		$content = sprintf( 'There is an update for DMS, version <strong>%s</strong> is now available. %s%s%s',

		$pagelines_update['new_version'],
			$details_button,
			$warning,
			$details
		 );
		printf( '<div class="updated"><p>%s</p></div>
			<script>
				jQuery( ".pl_updates" ).click(function() {
					jQuery( "#pl_updates_data" ).toggle( "slow" )
					jQuery( ".pl_updates" ).toggle()
				})
			</script>', $content );
		

	}
	
		function del_store_data() {
			global $storeapi;
			if( ! is_object( $storeapi ) )
				$storeapi = new EditorStoreFront;

			$storeapi->del( 'store_mixed' );
			delete_transient( 'pl_updates_errors' );
		}

		function injectUpdateThemes( $updates ) {
			
			global $dms_changelog;
			
			$hook = is_multisite() ? 'network_admin_notices' : 'admin_notices';
			
			
			if( ! class_exists( 'EditorStoreFront' ) )
				return $updates;

			global $storeapi;
			if( ! is_object( $storeapi ) )
				$storeapi = new EditorStoreFront;
			$mixed_array = $storeapi->get_latest();
			$themes = $this->get_pl_themes();

			foreach( $themes as $slug => $data ) {

				if( ! isset( $mixed_array[$slug]['version'] ) )
					continue;
				// all is good, inject update.
				if( $mixed_array[$slug]['version'] > $data['Version'] && $this->pl_is_pro() ) {
					$updates->response[$slug] = $this->build_theme_array( $mixed_array[$slug], $data );
					continue;	
				} 
				
				// update avalailable, but no key registered :/
				if( $mixed_array[$slug]['version'] > $data['Version'] && ! $this->pl_is_pro() ) {
					$this->register_error( $slug, $data, $mixed_array[$slug]['version'] );
					if( is_object( $updates ) && isset( $updates->response ) && isset( $updates->response[$slug] ) )
						unset( $updates->response[$slug] );
					
					continue;	
				}
			}
			return $updates;
		}

		function register_error( $slug, $data, $new_version ) {
			$errors = get_transient( 'pl_updates_errors' );
			if( ! $errors || ! is_array( $errors ) )
				$errors = array();
				
			$errors[$slug] = $data;
			$errors[$slug]['new'] = $new_version;
			
			set_transient( 'pl_updates_errors', $errors );
		}

		function display_errors() {
			
			$errors = get_transient( 'pl_updates_errors' );
			$screen = get_current_screen();
			$products = array();
			
			if( 'dashboard_page_pagelines_updater' == $screen->id )
				return;

			if( ! is_array( $errors ) )
				return;
				
			// display errors...
			$total = count( $errors );
			
			foreach( $errors as $error ) {
				$products[] = sprintf( '<strong>%s</strong> (v%s &rarr; v%s)', $error['Plugin Name'], $error['Version'], $error['new'] );
			}
			$products = implode( '<br />', $products );
			
			$text = _n( 'The following PageLines product has', 'The following PageLines products have', $total );
			
			$wrap = sprintf( '<div class="updated"><p>%s updates available but no key has been registered. <br />%s<br />Please enter a key in the PageLines Updater <a href="%s">here</a>.</p></div>', $text, $products, network_admin_url( 'index.php?page=pagelines_updater' ) );
			
			echo $wrap;
		}

		function injectUpdatePlugins( $updates ) {

			global $pl_plugins;
			global $storeapi;
			if( ! class_exists( 'EditorStoreFront' ) )
				return $updates;

			if( ! is_object( $storeapi ) )
				$storeapi = new EditorStoreFront;

			$mixed_array = $storeapi->get_latest();

			if( ! $pl_plugins )
				$pl_plugins = $this->get_pl_plugins();		

			if( ! is_array( $pl_plugins ) || empty( $pl_plugins ) )
				return $updates;

			foreach( $pl_plugins as $path => $data ) {
				$slug = dirname( $path );

				// If PageLines plugin has no API data pass on it.
				if( ! isset( $mixed_array[$slug] ) ) {
					if( is_object( $updates ) && isset( $updates->response ) && isset( $updates->response[$path] ) ) {
						unset( $updates->response[$path] );
					}
				}

				// If PageLines plugin has API data and a version check it but no key :/.
				if( isset( $mixed_array[$slug]['version'] ) && ( $mixed_array[$slug]['version'] > $data['Version'] ) && ! $this->pl_is_pro( $slug ) ) {
					$this->register_error( $slug, $data, $mixed_array[$slug]['version'] );
					
					if( is_object( $updates ) && isset( $updates->response ) && isset( $updates->response[$path] ) )
						unset( $updates->response[$path] );

					continue;
				}

				// If PageLines plugin has API data and a version check it and build a response.
				if( isset( $mixed_array[$slug]['version'] ) && ( $mixed_array[$slug]['version'] > $data['Version'] ) && $this->pl_is_pro( $slug ) ) {
					$updates->response[$path] = $this->build_plugin_object( $mixed_array[$slug], $data );
					continue;
				}

			}
		//	var_dump($updates);	
			return $updates;
		}

		function build_theme_array( $api_data, $data ) {

			$object = array();
			$object['new_version'] = $api_data['version'];
			$object['upgrade_notice'] = $api_data['changelog'];
			$object['url'] = ( $api_data['pid'] ) ? sprintf( 'http://www.pagelines.com/?changelog=%s', $api_data['pid'] ) : $api_data['overview'];
			$object['package'] = $this->build_url( $api_data, $data );
			return $object;
		}

		function build_plugin_object( $api_data, $data ) {

			$object = new stdClass;		

			
			if( isset( $api_data['changelog'] ) ) {
				
				$update = explode( '*', $api_data['changelog'] );
				$update = $update[1];
			} else {
				$update = 'No changelog infomation.';
			}
			$object->slug = $api_data['slug'];
			$object->new_version = $api_data['version'];
			$object->upgrade_notice = $update;
			$object->package = $this->build_url( $api_data, $data );
			$object->download_link = $this->build_url( $api_data, $data );
			return $object;
		}

		function check_info( $false, $action, $arg ){

			global $storeapi;
			if( ! is_object( $storeapi ) )
				$storeapi = new EditorStoreFront;

			$mixed_array = $storeapi->get_latest();

			if( is_object( $arg ) && isset( $arg->slug ) && isset( $mixed_array[$arg->slug] ) ) {
				$data = $mixed_array[$arg->slug];

				$obj = new stdClass();
				      $obj->slug = $data['slug'];
				      $obj->plugin_name = $data['name'];
				      $obj->new_version = $data['version'];
				      $obj->requires = '3.8';
				      $obj->tested = '4.0';
				      $obj->downloaded = 0; // needs API update....
				      $obj->last_updated = $data['last_mod'];
				      $obj->sections = array(
				        'description' => $this->build_desc( $data ),
				        'changelog' => $this->build_logs( $data )
				      );

				      $obj->homepage = $data['overview'];
				      return $obj;
			} 
			return false;
		}

		function build_logs( $data ) {

			$logs = ( isset( $data['changelog'] ) ) ? explode( '*', $data['changelog'] ) : '';

			if( ! is_array( $logs ) || empty( $logs ) )
				return 'Nothing to see here!';

			$out = '<ul>';

			foreach( $logs as $k => $log ) {
				if( '' != $log )
					$out .= sprintf( '<li>%s</li>', $log );
			}

			return $out . '</ul>'; 
		}

		function build_desc( $data ) {

			$desc = sprintf( "<h1><img src='%s' /></h1>%s", $data['thumb'], $data['description'] );
			return $desc;
		}

		function build_url( $api_data, $data ) {
			
			$keydata = $this->get_key_info();
			
			// this shouldnt happen!
			if( false === $keydata )
				return false;
						
			$url = sprintf( 'http://www.pagelines.com/?pl-api=init&request=download_zip&email=%s&file=%s.zip&key=%s&id=%s&rand=%s',
			$keydata['email'],
			$api_data['slug'],
			$keydata['key'],
			$keydata['id'],
			rand()
			);
			return $url;
		}

		function get_pl_plugins() {

			global $pl_plugins;
			$default_headers = array(
				'Version'	=> 'Version',
				'v3'	=> 'v3',
				'PageLines'	=> 'PageLines',
				'Plugin Name'	=> 'Plugin Name',
				'Description'	=> 'Description',
				'Version'		=> 'Version'
				);

			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			$plugins = get_plugins();

			foreach ( $plugins as $path => $data ) {

				$fullpath = sprintf( '%s%s', trailingslashit( WP_PLUGIN_DIR ), $path );		
				$plugins[$path] = get_file_data( $fullpath, $default_headers );
			}

			foreach ( $plugins as $path => $data ) {
				if( ! $data['PageLines'] )
					unset( $plugins[$path] );
			}
			return $plugins;
		}

		function get_pl_themes() {
			$installed_themes = pl_get_themes();

			foreach( $installed_themes as $slug => $theme ) {

				if( 'dms' == $slug )
					continue;
					
				if( '' != $theme['Template'] && 'dms' != $theme['Template'] )
					unset( $installed_themes[$slug]);
			}
			return $installed_themes;
		}

		function get_key_info() {
			$status = get_site_option( 'pl-activated' );
			if( ! is_array( $status ) || empty( $status ) )
				return false;
				
			// we only want the 1st available key
			
			foreach( $status as $slug => $data ) {
				$status[$slug]['id'] = $slug;
				return $status[$slug];
			}
		}
		function pl_is_pro( $path = false ){
			
			if( $path ) {
				if( 'pagelines-updater' == $path )
					return true;
			}
			// editor functions not loaded yet so we need this
			$status = get_site_option( 'pl-activated' );
			return (is_array( $status ) && ! empty( $status ) ) ? true : false;
		}
}