jQuery(document).ready(function() {	
		jQuery('td.product_name').expander();
		jQuery('.updater-help').expander({
			slicePoint:       0,
			expandPrefix:     ' ',
			expandText:       'Click here for help.',
			userCollapseText: '',
			expandEffect: 'fadeIn',
			expandSpeed: 550,
		})
		jQuery('.updater-reset').expander({
			slicePoint:       0,
			expandPrefix:     ' ',
			expandText:       'Reset',
			userCollapseText: '',
		//	expandEffect: 'fadeIn',
			expandEffect: 'slideDown',
		  	collapseEffect: 'fadeOut',
			expandSpeed: 550,
			collapseTimer: 5000,
		})
})


