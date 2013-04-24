<?php
/*
Plugin Name: Category Checklist Tree Limited
Version: 1.0
Description: Preserves the category hierarchy on the post editing screen and limit the category to certain user
Author: anou (thanks to scribu for the initial plug-in !!!)
Author URI: http://smol.org
Plugin URI: http://scribu.net/wordpress/category-checklist-tree
*/

class Category_Checklist_Limit {

	function init() {
		add_filter( 'wp_terms_checklist_args', array( __CLASS__, 'checklist_args_limit' ) );
	}

	function checklist_args_limit( $args ) {
		add_action( 'admin_footer', array( __CLASS__, 'script' ) );
		
		$args['checked_ontop'] = false;

		global $current_user;
		$uid = $current_user->ID;
		
		if($uid != 1) { 
			if(function_exists('get_cat_if_user_lang')){
				$cat = get_cat_if_user_lang($uid);
				
				if(!$cat) {return;}
				
				$args['descendants_and_self'] = $cat;
			}
		}
		
		unset($args['popular_cats']); //Pas efficace !!!
		
		//echo '<pre>'; print_r($cat);echo '</pre>';//wp_terms_checklist
		
		//$args['descendants_and_self'] = 1;

		return $args;
	}

	// Scrolls to first checked category
	function script() {
?>
<script type="text/javascript">
	jQuery(function(){
		jQuery('[id$="-all"] > ul.categorychecklist').each(function() {
			var $list = jQuery(this);
			var $firstChecked = $list.find(':checked').first();

			if ( !$firstChecked.length )
				return;

			var pos_first = $list.find(':checkbox').position().top;
			var pos_checked = $firstChecked.position().top;

			$list.closest('.tabs-panel').scrollTop(pos_checked - pos_first + 5);
		});
	});
</script>
<?php
	}
}

Category_Checklist_Limit::init();
