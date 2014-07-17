<?php
/*
Plugin Name: PageLines Updater
Description: Keep all those PageLines Products up to date!
Version: 1.1
Author: PageLines
PageLines: true
*/

class PageLines_Updater {

	function __construct() {
		
		if( ! defined( 'PL_UPDATER_SHOW_ALL' ) )
			define( 'PL_UPDATER_SHOW_ALL', true );
	
		if( is_admin() ) {
			$this->extra_notices();
		}
		
		$path = trailingslashit( basename( dirname( __FILE__ ) ) ) . basename(__FILE__); // fix for symlinked folders :/
		register_activation_hook( $path, array( $this, 'activate_redirect' ) );		
		add_action('admin_init', array( $this, 'activate_redirect_do') );		

		$menu_hook = is_multisite() ? 'network_admin_menu' : 'admin_menu';
		add_action( $menu_hook, array( $this, 'add_menu' ) );

		add_action( 'admin_init', array( $this, 'license_functions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_style' ) );
		
		add_action( 'admin_init', array( $this, 'product_updates' ) );
		add_action( 'admin_init', array( $this, 'register_core' ), 11 );
		
		if( is_multisite() && ! is_network_admin() ) {
			remove_action( 'admin_notices', 'pagelines_updater_notice' ); // remove admin notices for plugins outside of network admin
			if ( !function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			if( !is_plugin_active_for_network( plugin_basename( __FILE__ ) ) )
				add_action( 'admin_notices', array( $this, 'admin_notice_require_network_activation' ) );
			return;
		}
		
		add_action( 'after_setup_theme', array( $this, 'bootstrap_dms' ) );
		add_filter( 'gettext', array( $this, 'update_strings' ), 20, 3 );		
	}

	function update_strings(  $translated_text, $untranslated_text, $domain ) {
		
		switch($untranslated_text) {
			case 'An error occurred while updating %1$s: <strong>%2$s</strong>':
				$translated_text = '<span class="dms_upd">An error occurred while updating %1$s: <strong>%2$s</strong></span>' . $this->add_script();
				break;
		}
		return $translated_text;
	}

	function add_script() {
		global $pagenow;
		if ( 'update.php' !== $pagenow )
			return;		
		ob_start();
		?>
		<script>			
			var code = jQuery('.code').html() || ''			
			var msg = jQuery('.dms_upd').html()
			var new_msg = '<?php printf( '<h3 style="margin:0em;">Update Failed</h3><p>There was a problem getting the updated file from the PageLines server.</p><div class="possible_reasons"><ol style="margin-top:0em;"><li>Your PageLines license is expired so you are no longer receiving updates. To reactivate an expired license, just go to the <a href="http://www.pagelines.com/my-account/">Account page</a></li><li>Updates are not configured properly. Go to the <a href="%s">Updates Configuration Page</a> and make sure the settings are correct.</li><li>WordPress could not connect to the PageLines update server. Either your server cannot make outgoing HTTP requests or the PageLines server is offline.</ol></div><p>For all user account requests please start a ticket by emailing <a href="mailto:hello@pagelines.com">the support desk</a></p>', admin_url( 'index.php?page=pagelines_updates') ); ?>'
			if( code.match(/pl\-api/)) {				
				jQuery('.dms_upd').html(new_msg)
				jQuery('.possible_reasons').expander({
					slicePoint:       0,
					expandPrefix:     ' ',
					expandText:       'Possible reasons for a failed download',
					userCollapseText: '',
					expandEffect: 'fadeIn',
					expandSpeed: 550,
				})
			}			
		</script>
		<?php
		return ob_get_clean();
		
	}

		function register_core() {

			// if we are parent theme OR a child theme, register as DMS.
			// if we are a standalone, register as the standalone.		
			global $registered_pagelines_updates;
			
			if( is_array( $registered_pagelines_updates ) && ! empty( $registered_pagelines_updates ) ) {
				if( isset( $registered_pagelines_updates['dmspro']))
					unset( $registered_pagelines_updates['dmspro'] );
					
				return;
			}
			
			if( ! is_array( $registered_pagelines_updates ) )
				$registered_pagelines_updates = array();

			$themeslug = $this->get_themeslug();

			$registered_pagelines_updates[$themeslug] = array(
				'type' => 'theme',
				'product_file_path'	=> $themeslug,
				'product_name'	=> $this->pl_get_theme_data( get_template_directory(), 'Name'),
				'product_version'	=> $this->pl_get_theme_data( get_template_directory(), 'Version'),
				'product_desc'		=> $this->pl_get_theme_data( get_template_directory(), 'Description')
				);		
		}

	function bootstrap_dms() {
		if( $this->is_free_version() ) {

			add_filter( 'pl_is_activated', array( $this, 'pl_is_activated_check' ) );
			add_filter( 'pl_is_pro', array( $this, 'pl_is_pro_check' ) );
			add_filter( 'pl_pro_text', array( $this, 'pl_pro_text_check' ) );
			add_filter( 'pl_pro_disable_class', array( $this, 'pl_pro_disable_class_check' ) );
			add_filter( 'pagelines_global_notification', array( $this, 'pagelines_check_activated' ) );
		}
	}

	function is_free_version() {
		return ( function_exists( 'pl_is_wporg' ) && pl_is_wporg() ) ? true : false;		
	}


	function pl_is_pro_check() {
		return false;
	}

	function pl_is_activated_check(){	
		// AP stop putting return true here!!
		$status = get_option( 'dms_activation', array( 'active' => false, 'key' => '', 'message' => '', 'email' => '' ) );
		$pro = (isset($status['active']) && true === $status['active']) ? true : false;
		return $pro;	
	}

	function pl_pro_text_check(){
		return __('(Pro Edition Only)', 'pagelines');
	}

	function pl_pro_disable_class_check(){
		return 'pro-only-disabled'; 
	}

	function get_themeslug() {
		if( defined( 'DMS_CORE' ) )
			return basename( get_stylesheet_directory() );
		else
			return 'dms';
	}

	function pagelines_check_activated( $note ) {
		if( $this->pl_is_activated_check() )
			return $note;
		ob_start();
		?>
		<div class="alert editor-alert">
			<button type="button" class="close" data-dismiss="alert" href="#">&times;</button>
		  	<strong><i class="icon icon-star"></i> <?php _e( 'Activate Your Site!', 'pagelines' ); ?>
		  	</strong> <br/>
			<?php _e( 'Looks like you have the updater plugin installed and activated, the final step is to enter your DMS key to enable automatic updates and get the latest PRO version.', 'pagelines' ); ?>

			<a href="http://www.pagelines.com/pricing/" class="btn btn-mini" target="_blank"><i class="icon icon-thumbs-up"></i> <?php _e( 'Learn More About Pro', 'pagelines' ); ?>
			</a>
			</div>
			<?php
			$note .= ob_get_clean();
			return $note;
	}

	/**
	 * Display require network activation error.
	 * @since  1.0.0
	 * @return  void
	 */
	public function admin_notice_require_network_activation () {
		echo '<div class="error"><p>' . __( 'PageLines Updater must be network activated when in multisite environment.', 'opt' ) . '</p></div>';
	} // End admin_notice_require_network_activation()

	function activate_redirect() {
		add_option('pl_updates_activation_redirect', true);
	}
	
	function activate_redirect_do() {
		$url = network_admin_url( 'index.php?page=pagelines_updater', 'http' );
	    if (get_option('pl_updates_activation_redirect', false)) {
	        delete_option('pl_updates_activation_redirect');
	        wp_redirect($url);
	    }
	}

	
	function register_style() {
		wp_register_style( 'pl_updates', plugins_url('assets/style.css', __FILE__) );
		wp_register_script( 'expander', plugins_url('assets/expander.min.js', __FILE__) );
		wp_register_script( 'pl_updates', plugins_url('assets/updater.js', __FILE__), array('jquery','expander' ) );
	}

	function license_functions() {
		require( 'libs/license-functions.php' );
		new PageLines_Updater_Admin_Functions;
	}

	function product_updates() {
		require( 'libs/product-updates.php' );
		global $updater_main;
		$updater_main = new PageLines_Updater_Products;
	}

	function add_menu() {
		if( !defined( 'PL_MAIN_DASH' ) )
			return;

		global $updates_menu;
		$updates_menu = add_dashboard_page( 'PageLines Licenses', 'PageLines Licenses', 'manage_options', 'pagelines_updater', array( $this, 'updater_main' ) );
		add_action( 'admin_print_styles-' . $updates_menu, array( $this, 'admin_styles' ) );
	}

	function admin_styles() {
		wp_enqueue_style( 'pl_updates' );
		wp_enqueue_script( 'pl_updates' );
	}

	function updater_main() {
			global $pl_update_error;
			if( isset( $pl_update_error ) ) {
				$defaults = array(
					'error' => 'Unknown error',
					'additional info'	=> ''
				);
				$pl_update_error = wp_parse_args( $pl_update_error, $defaults );
				printf( '<div class="updated"><p><strong>%s</strong> %s</p></div>', $pl_update_error['error'], $pl_update_error['additional info'] );
			}
			$key_data = get_site_option( 'pl-activated', false );
			?>
				<div class="wrap">
					<div class="wrap about-wrap">
						<h1>Welcome to the PageLines Updater</h1>
						<div class="about-text">
							<?php if( ! $key_data ) {
								echo '<p>Activate your PageLines product to receive updates.</p>';
							}
							 if( $this->is_free_version() )
								echo 'Need to get a DMS key?&nbsp<a class="button button-primary" href="http://www.pagelines.com/pricing/">Get One Now! &#10157;</a>';										
										if( ! $key_data ):
										?>
										<form id="activate-products" method="post" action="" class="validate">
											<input type="hidden" name="action" value="activate-products" />
											<input type="hidden" name="page" value="pagelines_updater" />
											<p><i>Your key you received with your order from PageLines</i><br /><input type="text" class="input-key" name="license_key" id="license_key" aria-required="true" placeholder="DMSxxxxxxx-xxxx-xxxx-xxxxxxxxxx" />
												<br /><br /><i>Email address to be submitted with your key</i><br />
												<?php printf( '<input class="input-email" type="text" aria-required="true" placeholder="%s" name="pl-updates-email" value="%s" /></p>',
										get_site_option('admin_email'),
										get_site_option('admin_email')
										);
										submit_button( __( 'Activate Products', 'opt' ), 'button-primary' );
										?>
									</form>
									</div></div>
									<?php 
								else:

									if( count($key_data) != count($key_data, COUNT_RECURSIVE ) ) {
										$key = reset($key_data);
									}

									if( ! isset( $key['product'] ) )
										$product = '';
									else
										$product = sprintf( 'Product: <strong>%s</strong><br />', $key['product'] );

									printf( '<p>%sKey: <i>%s</i>',
									$product,
									substr_replace($key['key'], str_repeat("*", 20), 12, 20)
									);
									?>
									
									</div>
									
									<div class="updater-reset">
											<p>
												<h4>By deactivating the key on this site you will no longer receive updates to any installed PageLines products</h4>
											<form action="" method="post">
											<input type="hidden" name="action" value="deactivate-products" />
											<input type="hidden" name="page" value="pagelines_updater" />
											<?php wp_nonce_field('deactivate-key','deactivate-key'); ?>
											<?php submit_button( 'I know what I am doing, Deactivate this key.', 'button-primary' ); ?>
											</form>
											</p>
									</div>
									
									</div>
									<?php
									require_once( 'libs/licenses-table.php' );
									$this->list_table = new PageLines_Updater_Licenses_Table();
									$this->list_table->data = $this->fetch_all_data();
									$this->list_table->prepare_items();
									$this->list_table->display();

								endif;					
					
					

						

					echo $this->footer(); ?>
				</div><!--/.col-wrap-->

		<?php
	}
	
	function fetch_all_data() {
		global $registered_pagelines_updates;
		$activated = get_site_option( 'pl-activated' );
		if( ! is_array( $registered_pagelines_updates ) )
			return array();

		foreach( $registered_pagelines_updates as $product => $k ) {
			if( isset( $activated[$product] ) )
				$registered_pagelines_updates[$product]['product_status'] = 'active';
		}
		
		// add plugins
		
		$plugins = $this->register_plugins();

		return array_merge( $registered_pagelines_updates, $plugins );
	}
	
	function register_plugins() {
		global $updater_main;
		if( ! class_exists( 'EditorStoreFront' ) )
			return array();
		
		$plugins = array();	
		global $storeapi;
		if( ! is_object( $storeapi ) )
			$storeapi = new EditorStoreFront;
		$mixed_array = $storeapi->get_latest();			
		$plugins_all = $updater_main->get_pl_plugins();
		
		foreach( $plugins_all as $path => $data ) {
			$slug = dirname( $path );
			if( ( isset( $mixed_array[$slug] ) && $mixed_array[$slug]['version'] > $data['Version'] ) || ( isset( $mixed_array[$slug] ) && defined( 'PL_UPDATER_SHOW_ALL' ) ) ){
				// plugin has data, so lets show it.
				$plugins[$slug] = array(
					'type' => 'plugin',
					'product_file_path'	=> $slug,
					'product_name'	=> $data['Plugin Name'],
					'product_version'	=> $data['Version'],
					'product_desc'		=> $data['Description'],
					'product_update'	=> ( $mixed_array[$slug]['version'] > $data['Version'] ) ? true : false
					);
			}
		}	
		return $plugins;
	}

	function header() {
		ob_start();
		?>
		<div class="wrap about-wrap">
					<h1>Welcome to the PageLines Updater</h1>
					<div class="about-text">
						</p>Activate your PageLines product to receive updates.</p>
						<?php if( $this->is_free_version() )
							echo 'Need to get a DMS key?&nbsp<a class="button button-primary" href="http://www.pagelines.com/pricing/">Get One Now! &#10157;</a>'; 
						?>
					</div>
				</div>
		
		<?php
		return ob_get_clean();
	}
	
	function footer() {
		ob_start();
		?>
		<div class="updater-help">
			<p>
				<h3>To get direct support for any issues with this updater plugin please email the support desk <a href="mailto:hello@pagelines.com">hello@pagelines.com</a></h3>
				<h3>FAQ</h3>
				<h4>When i click submit i just get Invalid License Key.</h4>
				<ol>
					<li>The key you entered does not exist. Make sure you copied the key correctly.</li>
					<li>The admin email for this site is not the same as your PageLines membership email, you can submit a different email in the box provided above.</li>

					</ol>
		</p>
		</div>
		<?php
		return ob_get_clean();
	}

	function pl_get_theme_data( $stylesheet = null, $header = 'Version') {

		if ( function_exists( 'wp_get_theme' ) ) {
			return wp_get_theme( basename( $stylesheet ) )->get( $header );
		} else {
			$data = get_theme_data( sprintf( '%s/themes/%s/style.css', WP_CONTENT_DIR, basename( $stylesheet ) ) );
			return $data[ $header ];
		}
	}
	
	function extra_notices() {
		
		global $pagenow;
		if ( 'plugins.php' !== $pagenow )
			return;

		global $pl_plugins;

		$mixed_array = json_decode( get_transient( 'plapi_store_mixed' ) );
		
		if( ! $pl_plugins )
			$pl_plugins = $this->get_pl_plugins();		

		if( ! is_array( $pl_plugins ) || empty( $pl_plugins ) )
			return false;
			
		foreach( $pl_plugins as $path => $data ) {
			$slug = dirname( $path );
			if( isset( $mixed_array->$slug ) ) {
				add_action('in_plugin_update_message-' . $path, array($this, 'plugin_update_message'), 10, 2);
			}
		}
		
	}
	function plugin_update_message($plugin_data, $r) {

		if (isset($r->upgrade_notice))
			printf('<style>.pl_upd_mesg:before {
			    font-family: "dashicons";
			    content: "\f339";</style>
			<p><strong class="pl_upd_mesg">%s</strong></p>', $r->upgrade_notice);
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
}
new PageLines_Updater;
