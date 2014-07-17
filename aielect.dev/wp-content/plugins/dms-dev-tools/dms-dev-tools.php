<?php
/*
Plugin Name: Dms Developer Tools
Plugin URI: http://www.pagelines.com/
Description: Developer only tools for DMS2
Version: 0.2
Author: PageLines
PageLines: true
 */
class PLDeveloperToolsPlugin {


	function __construct(){

		add_action( 'after_setup_theme', array( $this, 'init' ) );
	}

	function init() {

		// if we are not in DMS, ot pre 2.0.3 bomb out.
		if( ! class_exists( 'EditorInterface' ) || class_exists( 'PLDeveloperTools' ) )
			return false;
		
		$ver = PL_CORE_VERSION;
		
		if( version_compare( $ver, '2.0.3', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'need_update' ) );
			return;
		}
		
		// Add tab to toolbar
		add_filter('pl_toolbar_config', array( $this, 'toolbar'));

		// Add developer settings to JSON blob
		add_filter('pl_json_blob_objects', array( $this, 'add_to_blob'));
		add_action('wp_footer', array( $this, 'draw_developer_data'), 200);
		add_action( 'wp_before_admin_bar_render', array( $this, 'admin_bar' ) );
		add_filter( 'pagelines_section_disabled', array( $this, 'disable_sections' ) );
		$this->url = PL_PARENT_URL . '/editor';

		global $pl_perform;
		$pl_perform = array();
		
		add_action('after_pl_up_image', array( $this, 'custom_upload_action' ), 10, 2 );
		
	}
	
	function custom_upload_action( $ID, $meta ){
		
		$path = get_attached_file( $ID );
		
		if( pl_setting( 'kraken_api_key' ) && pl_setting( 'kraken_secret_key' ) ) {
			$this->kraken($path);
			return false;
		}
			
		
		if( pl_setting( 'tinypng_api_key' ) ) {
			$this->tinypng($path);
			return false;
		}
			
		
		return false;
		
		// folowing code loops through all sizes, might implement later...
		$paths = array();

		$sizes = pl_get_image_sizes();
		$uploads = wp_upload_dir();

		foreach( $sizes as $size ) {
			
			$image = wp_get_attachment_image_src( $ID, $size );
			$image = str_replace( $uploads['baseurl'], $uploads['basedir'], $image[0] );
			$paths[$size] = $image;
		}

		
		foreach( $paths as $size => $path )
			$this->tinypng($path);
	}
	
	function tinypng( $path ) {
		
		$attachment_file_path 	= $path;
		
		if ( !function_exists( 'download_url' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		
		$key = pl_setting( 'tinypng_api_key' );

		$request = curl_init();
		curl_setopt_array($request, array(
		  CURLOPT_URL => "https://api.tinypng.com/shrink",
		  CURLOPT_USERPWD => "api:" . $key,
		  CURLOPT_POSTFIELDS => file_get_contents($attachment_file_path),
		  CURLOPT_BINARYTRANSFER => true,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_HEADER => true,
		  /* Uncomment below if you have trouble validating our SSL certificate.
		     Download cacert.pem from: http://curl.haxx.se/ca/cacert.pem */
		  // CURLOPT_CAINFO => __DIR__ . "/cacert.pem",
		  CURLOPT_SSL_VERIFYPEER => true
		));

		$response = curl_exec($request);
		if (curl_getinfo($request, CURLINFO_HTTP_CODE) === 201) {
		  /* Compression was successful, retrieve output from Location header. */
		  $headers = substr($response, 0, curl_getinfo($request, CURLINFO_HEADER_SIZE));
		  foreach (explode("\r\n", $headers) as $header) {
		    if (substr($header, 0, 10) === "Location: ") {
		      $request = curl_init();
		      curl_setopt_array($request, array(
		        CURLOPT_URL => substr($header, 10),
		        CURLOPT_RETURNTRANSFER => true,
		        /* Uncomment below if you have trouble validating our SSL certificate. */
		        // CURLOPT_CAINFO => __DIR__ . "/cacert.pem",
		        CURLOPT_SSL_VERIFYPEER => true
		      ));
		      file_put_contents($attachment_file_path, curl_exec($request));
		    }
		  }
		} else {
			//failed...
		}
	}
	
	function kraken( $path ) {
		
		$attachment_file_path 	= $path;
		
		$key = pl_setting( 'kraken_api_key' );
		$secret = pl_setting( 'kraken_secret_key' );
		$lossy = ( pl_setting( 'kraken_api_lossy') ) ? true : false;

		require_once("Kraken.php");

		$kraken = new Kraken($key, $secret);

		$params = array(
		    "file" => $path,
		    "wait" => true,
			"lossy" => $lossy,
		);

		$data = $kraken->upload($params);

		if ($data["success"]) {
			$get = wp_remote_get( $data["kraked_url"] );			
			$request = wp_remote_get( $data["kraked_url"] );
			file_put_contents($attachment_file_path, wp_remote_retrieve_body($request));
		} else {
		    return false;
		}
	}
	
	function need_update() {
		?>
		<div class="updated">
			<p>The DMS Developer Tools require at least version 2.0.3 of DMS to function.
			</p>
			</div>
			<?php	
	}

	function get_sections() {
		global $editor_sections_data;
		
		$settings = array();
		
		// An array of sections that must not be disbled for obvious reasons..
		$do_not_disable = array(
			'PageLinesComments',
			'PageLinesNoPosts',
			'PLSectionArea',
			'PLColumn',
			'PageLinesPostLoop'	
		);
		
		foreach( $editor_sections_data as $type => $sections ) {
			
			foreach( $sections as $class => $data ) {
				if( in_array( $class, $do_not_disable ) )
					continue;
				$settings[] = array(
					'key'	=> 'disable-' . $class,
					'type'	=> 'check',
					'default'	=> false,
					'title'		=> $class,
					'label'		=> $data['name'],
					'help'		=> $data['description']
				);
			}			
		}
		
		return $settings;
	}

	function disable_sections( $sections ) {

		$editor_sections_data = get_theme_mod( 'editor-sections-data' );

		foreach( $editor_sections_data as $type => $s ) {

			foreach( $s as $class => $data ) {
				if( '1' == pl_setting( 'disable-' . $class ) )
					$sections[$type][$class] = true;
			}
		}
		return $sections;
	}

	function sections() {
		$settings = array(
			array(
			'key'	=> 'secopts',
			'type'	=> 'multi',
			'title'	=> 'Use this feature to completely disable sections from loading.',
			'opts'	=> $this->get_sections()
		)
		);
		return $settings;
	}

	function draw_developer_data(){

		if( ! pl_draft_mode() )
			return false;

			?><script>
				!function ($) {

					$.plDevData = {
						<?php echo $this->pl_performance_object();?>
					}
				}(window.jQuery);
			</script>
			<?php
	}

	function pl_performance_object(){

		// blob objects to add to json blob // format: array( 'name' => array() )
		$blob_objects = apply_filters('pl_performance_object', $this->basic_performance() );

		$output = '';
		if( ! empty($blob_objects) ){

			foreach( $blob_objects as $name => $array ){
				$output .= sprintf('%s:%s, %s', $name, json_encode( pl_arrays_to_objects( $array ) ), "\n\n");
			}
		}

		return $output;

	}

	function basic_performance(){

		global $pl_start_time, $pl_start_mem, $pl_perform;



		$pl_perform['memory'] = array(
			'num'		=> round( (memory_get_usage() - $pl_start_mem) / (1024 * 1024), 3 ),
			'label'		=> 'MB',
			'title'		=> __( 'Editor Memory', 'pagelines' ),
			'info'		=> __( 'Amount of memory used by the DMS editor in MB during this page load.', 'pagelines' )
		);

		$pl_perform['queries'] = array(
			'num'		=> get_num_queries(),
			'label'		=> __( 'Queries', 'pagelines' ),
			'title'		=> __( 'Total Queries', 'pagelines' ),
			'info'		=> __( 'The number of database queries during the WordPress/Editor execution.', 'pagelines' )
		);

		$pl_perform['total_time'] = array(
			'num'		=> timer_stop( 0 ),
			'label'		=> __( 'Seconds', 'pagelines' ),
			'title'		=> __( 'Total Time', 'pagelines' ),
			'info'		=> __( 'Total time to render this page including WordPress and DMS editor.', 'pagelines' )
		);

		$pl_perform['time'] = array(
			'num'		=> round( microtime(TRUE) - $pl_start_time, 3),
			'label'		=> __( 'Seconds', 'pagelines' ),
			'title'		=> __( 'Editor Time', 'pagelines' ),
			'info'		=> __( 'Amount of time it took to load this page once DMS had started.', 'pagelines' )
		);

		return $pl_perform;

	}

	function add_to_blob( $objects ){

		$objects['dev'] = $this->get_set();
		return $objects;

	}

	function toolbar( $toolbar ){

		$toolbar[ 'dev' ] = array(
			'name'	=> '',
			'icon'	=> 'icon-wrench',
			'pos'	=> 105,
			'panel'	=> $this->get_settings_tabs()
		);


		return $toolbar;
	}

	function get_settings_tabs(){

		$tabs = array();

		$tabs['heading'] = __( 'Developer Tools', 'pagelines' );

		foreach( $this->get_set() as $tabkey => $tab ){

			$tabs[ $tabkey ] = array(
				'key' 	=> $tabkey,
				'name' 	=> $tab['name'],
				'icon'	=> isset($tab['icon']) ? $tab['icon'] : ''
			);
		}

		return $tabs;

	}


	function get_set( ){

		$settings = array();

		$settings['secopts'] = array(
			'name' 	=> __( 'Disable Sections', 'pagelines' ),
			'icon'	=> 'icon-rocket',
			'opts' 	=> $this->sections()
			);

		$settings['dev_log'] = array(
			'name' 	=> __( 'Logging', 'pagelines' ),
			'icon'	=> 'icon-copy',
			'opts' 	=> array(

				array(
					'key'		=> 'fill-in',
					'type' 		=> 	'template',
					'template'	=> __( 'Nothing appears to have been logged.', 'pagelines' )
				),
			),
			'class'	=> 'dev_logging'
		);

		$settings['dev-page'] = array(
			'name' 	=> __( 'Performance', 'pagelines' ),
			'icon'	=> 'icon-tachometer',
			'opts' 	=> array(
				array(
					'key'		=> 'fill-in',
					'type' 		=> 	'template',
					'template'	=> __( 'No performance data exists on the page.', 'pagelines' )
				),
			),
		);

		$settings['devopts'] = array(
			'name' 	=> __( 'Options', 'pagelines' ),
			'icon'	=> 'icon-wrench',
			'opts' 	=> $this->basic()
		);

		$settings = apply_filters( 'pl_developer_settings_array', $settings );

		$default = array(
			'icon'	=> 'icon-edit',
			'pos'	=> 100
		);

		foreach($settings as $key => &$info){
			$info = wp_parse_args( $info, $default );
		}
		unset($info);

		uasort($settings, "cmp_by_position" );

		return apply_filters('pl_sorted_developer_array', $settings);
	}


	function basic(){

			$settings = array(
				array(
					'key'		=> 'images_opts',
					'col'		=> 1,
					'type' 		=> 'multi',
					'title' 	=> __( 'Image Optimisations', 'pagelines' ),
					'ref'		=> 'When enabled DMS will pass any image you use with the DMS editor to TintPNG/Kraken and then use the reduced sized image.<br />If both are enabled Kraken will be used as it supports more formats.<br />Only the main full size image is reduced at this time.<strong>DMS 2.1 Required</strong>',
					'opts'		=> array(
						array(
							'key'	=> 'kraken_api_key',
							'type'	=> 'text',
							'label'	=> 'Kraken.io API Key',
							'help'	=> ''
						),
						array(
							'key'	=> 'kraken_secret_key',
							'type'	=> 'text',
							'label'	=> 'Kraken.io Secret Key',
							'help'	=> 'Kraken is a robust, ultra-fast image optimizer. Thanks to its vast array of optimization algorithms Kraken is a world ahead of other tools. Want to save bandwidth and improve your website’s load times? Look no further and welcome to Kraken!<br /><strong>Kraken supports JPEG, PNG and GIF files.</strong>'
						),
						array(
							'key'	=> 'kraken_api_lossy',
							'type'	=> 'check',
							'default' => false,
							'label'	=> 'Enable lossy compression (even smaller files, unnoticeable difference)',
						),
						array(
							'key'	=> 'tinypng_api_key',
							'type'	=> 'text',
							'label'	=> 'TinyPNG API Key',
							'help'	=> 'TinyPNG uses smart lossy compression techniques to reduce the file size of your PNG files.<br />Registration for a key is free, just click here: <a target="_blank" href="https://tinypng.com/developers">Get a free key now</a><br /><strong>TinyPNG supports PNG files.</strong>'
						)
						
					)
				),
				array(
					'key'		=> 'less_dev_mode',
					'col'		=> 2,
					'type' 		=> 'check',
					'label' 	=> __( 'Enable LESS dev mode', 'pagelines' ),
					'title' 	=> __( 'LESS Developer Mode', 'pagelines' ),
					'help' 		=> sprintf( __( 'Less subsystem will check files for changed less code on every pageload and recompile if there are changes. %s', 'pagelines' ), $this->get_api_key() )
				),
				array(
					'key'		=> 'no_cache_mode',
					'col'		=> 3,
					'type' 		=> 'check',
					'label' 	=> __( 'Enable no cache mode', 'pagelines' ),
					'title' 	=> __( 'No Cache Mode', 'pagelines' ),
					'help' 		=> __( 'Disables all caching including all CSS/LESS.', 'pagelines' )
				)
			);

		return $settings;
	}

	
	function admin_bar() {
		
		if( is_admin() )
			return;

		global $wp_admin_bar;
		$message = '';
		if ( 1 == pl_setting( "less_dev_mode" ) ) {				
			$message = sprintf( '&nbsp;<span class="label label-info pl-admin-bar-label">%s</span>', __( 'DMS LESS Dev', 'pagelines' ) );
		}
			
		if ( 1 == pl_setting( "no_cache_mode" ) ) {
			$message .= sprintf( '&nbsp;<span class="label label-warning pl-admin-bar-label">%s</span>', __( 'DMS No Cache Mode!', 'pagelines' ) );
		}
		
		if( ! $message )
			return;
			
		$wp_admin_bar->add_menu( array(
				'parent' => false,
				'id' => 'no_cache_mode',
				'title' => $message,
				'href' => site_url(),
				'meta' => false
			));				

	}

	function get_api_key() {

		$key = md5( site_url() );
		$link = sprintf( '%s?pl_purge=%s', trailingslashit( site_url() ), $key );
		return sprintf( '<br />To remote purge all caches and update the js/css cache number use this url: <a href="%s">link</a>', $link );
	}
}
if( ! defined( 'PL_DEV' ) )
	define( 'PL_DEV', true );
new PLDeveloperToolsPlugin;