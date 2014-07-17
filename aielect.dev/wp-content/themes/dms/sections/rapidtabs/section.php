<?php
/*
	Section: RapidTabs
	Author: PageLines
	Author URI: http://www.pagelines.com
	Description: Displays your most popular, and latest posts as well as comments and tags in a tabbed format.
	Class Name: PLRapidTabs
	Filter: widgetized
	Edition: pro
*/

class PLRapidTabs extends PageLinesSection {

	function section_persistent(){

	}
	
	function section_styles(){
		pl_enqueue_script( 'jquery-ui-tabs' );
	}
	

	function section_head(){

		?>
		<script>
		!function ($) {
			$(document).on('sectionStart', function( e ) {
				$('.the-rapid-tabs').tabs({
					show: true
				})
			})
		}(window.jQuery);
		</script>
		<?php

	}


   function section_template() {
		
		global $plpg; 
		$pageID = $plpg->id;


		$num_posts = 4; 

		?>
	<div class="widget">
		<div class="widget-pad">
	<div class="the-rapid-tabs">
		<ul class="tabbed-list rapid-nav fix">
			<li><a href="#rapid-popular"><?php _e( 'Popular', 'pagelines' ); ?></a></li>
			<li><a href="#rapid-recent"><?php _e( 'Recent', 'pagelines' ); ?></a></li>
			<li><a href="#rapid-comments"><?php _e( 'Comments', 'pagelines' ); ?></a></li>
			<li><a href="#rapid-tags"><?php _e( 'Tags', 'pagelines' ); ?></a></li>
		</ul>

		<div id="rapid-popular">

				
				<ul class="media-list">
					<?php

					foreach( 
					get_posts( array('numberposts' => $num_posts, 'ignore_sticky_posts' => 1, 'orderby' => 'meta_value', 'meta_key' => '_pl_karma', 'exclude' => $pageID) )
					 as $p ){
						$img = (has_post_thumbnail( $p->ID )) ? sprintf('<div class="img"><a class="the-media" href="%s" style="background-image: url(%s)"></a></div>', get_permalink( $p->ID ), pl_the_thumbnail_url( $p->ID, 'thumbnail')) : '';

						printf(
							'<li class="media fix">%s<div class="bd"><a class="title" href="%s">%s</a><span class="excerpt">%s</span></div></li>', 
							$img,
							get_permalink( $p->ID ), 
							$p->post_title, 
							pl_short_excerpt($p->ID)
						);

					} ?>


				</ul>
	
		</div>
		<div id="rapid-recent">
			
				<ul class="media-list">
					<?php

					foreach( get_posts( array('ignore_sticky_posts' => 1, 'orderby' => 'post_date', 'order' => 'desc', 'numberposts' => $num_posts, 'exclude' => $pageID) ) as $p ){
						$img = (has_post_thumbnail( $p->ID )) ? sprintf('<div class="img"><a class="the-media" href="%s" style="background-image: url(%s)"></a></div>', get_permalink( $p->ID ), pl_the_thumbnail_url( $p->ID, 'thumbnail')) : '';

						printf(
							'<li class="media fix">%s<div class="bd"><a class="title" href="%s">%s</a><span class="excerpt">%s</span></div></li>', 
							$img,
							get_permalink( $p->ID ), 
							$p->post_title, 
							pl_short_excerpt($p->ID)
						);

					} ?>


				</ul>
		</div>

		<div id="rapid-comments">
			
			<ul class="quote-list">
				<?php  pl_recent_comments();  ?>

			</ul>
			
		</div>
		<div id="rapid-tags">
				
				<div class="tags-list">
					<?php

					wp_tag_cloud( array('number'=> 30, 'smallest' => 10, 'largest' => 10) );
					 ?>


				</div>
		
		</div>
				
		</div>
	</div>
</div>
		<?php
		
		


	}



	
}


