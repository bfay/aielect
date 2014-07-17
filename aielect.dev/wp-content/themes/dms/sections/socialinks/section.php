<?php
/*
	Section: Socialinks
	Author: PageLines
	Author URI: http://www.pagelines.com
	Description: A social icons listing.
	Class Name: PLSocialinks
	Filter: social
	Loading: active
*/


class PLSocialinks extends PageLinesSection {

	function section_opts(){

		$the_urls = array(); 
		
		$icons = $this->the_icons();
		
		foreach($icons as $icon){
			$the_urls[] = array(
				'label'	=> ui_key($icon) . ' URL', 
				'key'	=> 'sl_'.$icon,
				'type'	=> 'text',
				'scope'	=> 'global',
			); 
		}

		$opts = array(
		
			array(
				'type'	=> 'multi',
				'key'	=> 'sl_config', 
				'title'	=> 'Text',
				'col'	=> 1,
				'opts'	=> array(
					array(
						'type'	=> 'text',
						'key'	=> 'sl_text', 
						'label'	=> 'Socialinks Text (e.g. copyright information)',
					),
					array(
						'type'	=> 'select',
						'key'	=> 'sl_align', 
						'label'	=> 'Alignment',
						'opts'	=> array(
							'sl-links-right'	=> array( 'name' => 'Social links on right'),
							'sl-links-left'	=> array( 'name' => 'Social links on left'),
						), 
					),
					array(
						'type'	=> 'check',
						'key'	=> 'sl_web_disable', 
						'label'	=> 'Disable "Built With" Icons (HTML5, CSS3, PageLines)',
						'scope'	=> 'global'
					),
					array(
						'key'	=> 'menu',
						'type'	=> 'select_menu',
						'label'	=> 'Select Menu',
					),
				)
				
			),
			array(
				'type'	=> 'multi',
				'key'	=> 'sl_urls', 
				'title'	=> 'Link URLs',
				
				'col'	=> 2,
				'opts'	=> $the_urls
				
			)
			

		);

		return $opts;

	}
	
	function the_icons( ){
		
		$icons = array(
			'facebook',
			'linkedin',
			'instagram',
			'twitter',
			'youtube',
			'google-plus',
			'pinterest',
			'dribbble',
			'flickr',
			'github',
		); 
		
		
		
		return $icons;
		
	}
	
	
   function section_template( $location = false ) {


		$icons = $this->the_icons(); 

		$target = "target='_blank'";
		
		$text = ( $this->opt('sl_text') ) ? $this->opt('sl_text') : sprintf('&copy; %s %s', date("Y"), get_bloginfo('name'));
		
		$align = ( $this->opt('sl_align') ) ? $this->opt('sl_align') : 'sl-links-right';
		
		$menu = ( $this->opt('menu') ) ? $this->opt('menu') : false;

	?>
	<div class="socialinks-wrap fix <?php echo $align;?>">
		
		<?php 
		
				$menu_args = array(
					'theme_location' => 'socialinks_nav',
					'menu' 			=> $menu,
					'menu_class'	=> 'inline-list pl-nav sl-nav', 
					'respond'		=> false
				);
			
				$nav = ($menu) ? pl_navigation( $menu_args ) : '';
			
				echo sprintf('<div class="sl-text"><span class="sl-copy">%s</span> %s</div>', $text, $nav); 
				
			
				
		?>
		
		<div class="sl-links">
		<?php 
		
		foreach($icons as $icon){
		
			$url = ( pl_setting('sl_'.$icon) ) ? pl_setting('sl_'.$icon) : false;
		
			if( $url )
				printf('<a href="%s" class="sl-link" %s><i class="icon icon-%s"></i></a>', $url, $target, $icon); 
		}
		
		if( ! pl_setting( 'sl_web_disable' ) ){
			
			?><span class="sl-web-links"><a class="sl-link"  title="CSS3 Valid"><i class="icon icon-css3"></i></a><a class="sl-link" title="HTML5 Valid"><i class="icon icon-html5"></i></a><a class="sl-link" href="http://www.pagelines.com" title="Built with PageLines DMS"><i class="icon icon-pagelines"></i></a>
			</span>
			<?php 
			
		}
	
		
		
		
		?>
		</div>
	</div>
<?php }

}
