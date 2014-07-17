<?php

// Load Framework - don't delete this
require_once( dirname(__FILE__) . '/setup.php' );

// Create a function with your theme's name
class pl_your_theme {

	function __construct() {

		// Constants
		$this->url = sprintf('%s', PL_CHILD_URL);
		$this->dir = sprintf('/%s', PL_CHILD_DIR);

		// Add a filter so we can build a few custom LESS vars
		add_filter( 'pless_vars', array(&$this,'custom_less_vars'));

		$this->init();
	}

	function init(){

		// Run the theme options
		$this->theme_options();

		// Send the user to the Theme Config panel after they activate.
		add_filter('pl_activate_url', array(&$this,'activation_url'));
	}

	// Send the user to the Theme Config panel after they activate. Note how link=pl_theme_config is the same name of the array settings. This must match.
	function activation_url( $url ){

	    $url = home_url() . '?tablink=theme&tabsublink=pl_theme_config';
	    return $url;
	}

	// Custom LESS Vars
	function custom_less_vars($less){

		// Adding a custom LESS var, use this in LESS as @my-var. In this example, its linked to a custom color picker in options. We also must set a default or else it's going to error.
		// pl_hashify must be used with color pickers so that it appends the # symbol to the hex code
		// pl_setting is being used because this is a global option used in the theme
		$less['my-var']   =  pl_setting('my_custom_color') ? pl_hashify(pl_setting('my_custom_color')) : 'f7f7f7';

		return $less;
	}

    // WELCOME MESSAGE - HTML content for the welcome/intro option field
	function welcome(){

		ob_start();

		?><div style="font-size:12px;line-height:14px;color:#444;"><p><?php _e('You can have some custom text here.','pl-starter-theme');?></p></div><?php

		return ob_get_clean();
	}

	// Theme Options
	function theme_options(){

		$options = array();

		$options['pl_theme_config'] = array(
		   'pos'                  => 50,
		   'name'                 => __('PageLines Child Theme','pl-starter-theme'),
		   'icon'                 => 'icon-rocket',
		   'opts'                 => array(
		   		array(
		       	    'type'        => 'template',
            		'title'       => __('Welcome to My Theme','pl-starter-theme'),
            		'template'    => $this->welcome()
		       	),
		       	array(
		           'type'         => 'color',
		           'title'        => __('Sample Color','pl-starter-theme'),
		           'key'          => 'my_custom_color',
		           'label'        => __('Sample Color','pl-starter-theme'),
		           'default'      =>'#FFFFFF'
		       	),
		   )
		);
		pl_add_theme_tab($options);
	}

}
new pl_your_theme;
