<?php 
//declare a global var to know if we have a simple or multi-antennas website
global $multi;
$multi = multi_antennas();
//$multi = false;

//Get theme options for antennas
global $options;
$options = get_antennas_details();

// Get admin categ. Only admin can configure country homepage
$categAdmin = get_cat_if_user(1);
global $custom_hp;
$custom_hp = isset($options[$categAdmin]['custom_hp']) ? $options[$categAdmin]['custom_hp'] : false;
/* $custom_hp = false; */

//check for frontpage
if( !is_front_page() || !is_home() ) $custom_hp = false;

//Make antenna id accessible everywhere
global $antenna;
$antenna = get_current_antenna();//returns always the antenna ID in default language... 
if($multi && is_front_page() && is_home()) { $antenna = 'front'; }

global $sitepress;
$default_lg = isset($sitepress) ? $sitepress->get_default_language() : 'fr';//assuming that 'fr' should be default language

global $antenna_op;
$antenna_op = function_exists('icl_object_id') ? icl_object_id($antenna, 'category', true, $default_lg) : $antenna;

global $c;
$c = get_query_var('cat');

global $current_user; 
$current_user = wp_get_current_user();

global $if_front;
$if_front = is_front_page();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<?php dev4press_debug_page_request(); ?>
	<title><?php if ( is_category() ) {
		echo  single_cat_title().' | '; bloginfo( 'name' );
	} elseif ( is_tag() ) {
		  echo single_tag_title(); echo '&quot; | '; bloginfo( 'name' );
	} elseif ( is_archive() ) {
		  wp_title('|', true, 'right'); bloginfo( 'name' );
	} elseif ( is_search() ) {
		  echo sprintf( __('Search for &quot;%s&quot; | ', 'iftheme'), esc_html($s) ); ' '.bloginfo( 'name' );
	} elseif ( is_home() ) {
		  bloginfo( 'name' ); echo ' | '; bloginfo( 'description' );
	}  elseif ( is_404() ) {
		  echo __('No results - search our archives | ','iftheme'); ' '.bloginfo( 'name' );
	} elseif ( is_single() ) {
		  wp_title('|', true, 'right'); bloginfo( 'name' );
	} else {
		  wp_title('|', true, 'right'); bloginfo( 'name' );
	} ?></title>
	<meta name="description" content="<?php wp_title(''); echo ' | '; bloginfo( 'description' ); ?>" />
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<meta name="viewport" content="width=device-width, initial-scale=1" /><?php /* Add "maximum-scale=1" to fix the Mobile Safari auto-zoom bug on orientation changes, but keep in mind that it will disable user-zooming completely. Bad for accessibility. */ ?>
	<link rel="icon" href="<?php bloginfo('template_url'); ?>/favicon.ico" type="image/x-icon" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ); ?>" href="<?php bloginfo( 'rss2_url' ); ?>" />
	<link rel="alternate" type="application/atom+xml" title="<?php bloginfo( 'name' ); ?>" href="<?php bloginfo( 'atom_url' ); ?>" />
	<?php wp_enqueue_script("jquery"); /* Loads jQuery if it hasn't been loaded already */ ?>
	<?php /* The HTML5 Shim is required for older browsers, mainly older versions IE */ ?>
	<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
	<script src='https://www.google.com/recaptcha/api.js'></script>
	<script type="text/javascript"> 
		var bInfo = new Array(); 
			bInfo['bName'] = "<?php bloginfo('name');?>"; 
			bInfo['bDesc'] = "<?php bloginfo('description');?>"; 
			bInfo['bLang'] = "<?php bloginfo('language');?>"; 
			bInfo['bRtl'] = "<?php echo is_rtl() ? 'rtl' : false; ?>"; 
			bInfo['bTheme'] = "<?php bloginfo('stylesheet_directory');?>"; 
	</script>

	<?php wp_head(); ?> <?php /* this is used by many Wordpress features and for plugins to work properly */ ?>
	
	<?php if( is_single() ) : //Add active menu class
		$post_terms = wp_get_object_terms($post->ID, 'category'); 
		$nbpost_terms = count($post_terms); 
				?>
        <script type="text/javascript">
            jQuery(function(){
              jQuery('.container.for-angle .current-cat ul.children').appendTo('nav#antennes').show();
            });
        </script>
    <?php endif;?>
	
	<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'template_url' ); ?>/lessframework.css" />
	<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
	<?php if( is_rtl() ):?><link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'template_directory' );?>/rtl.css" /><?php endif;?>

	<style type="text/css">
		<?php if(!is_date() && !is_404() && !is_search() && !is_page() && $multi && (!is_front_page() || !is_home())) : ?> #top-menu-antennes ul li.cat-item-<?php echo $antenna;?> a { color: #008ac9; } <?php endif;?>
	<?php 
	if( $multi ) { 
	  $i = 0; 
	  $j = count($options);
	  foreach($options as $k => $vals){
	  	 $k = function_exists('icl_object_id') ? icl_object_id($k, 'category', true) : $k;
	  	 printf('body.category-%s {background-image: url(%s) !important}  body.category-%s .sides {background-image: url(%s) !important}',
		   $k, $vals['background_img'], $k, $vals['bg_frame'] != 'f0' ? get_template_directory_uri() . '/inc/images/frames/'.$vals['bg_frame'] . '.png' : '');
		 
		 $i++;
		 
		 if($j <= $i){ //home css
		 	printf('body.home {background-image: url(%s) !important} body.home .sides {background-image: url(%s) !important}', 
		 		$vals['background_img_country'], $vals['bg_frame'] != 'f0' ? get_template_directory_uri() . '/inc/images/frames/'.$vals['bg_frame_country'] . '.png' : '');
		 }
	  }
	}
	else {
    $aid = defined('ICL_LANGUAGE_CODE') ? apply_filters( 'wpml_object_id', $options['aid'], 'category', true ) : $options['aid'];

    printf('body.category-%s {background-image: url(%s) !important}',
		        $aid, 
		        $options['background_img']);
		printf('body.category-%s .sides {background-image: url(%s) !important}', 
		        $aid, 
		        $options['bg_frame'] != 'f0' ? get_template_directory_uri() . '/inc/images/frames/' . $options['bg_frame'] . '.png' : '');

		//for page with category-0
		echo 'body.category-0 { background-image: url(' . $options['background_img'] . ') !important }';
		$bg0_img = $options['bg_frame'] != 'f0' ? get_template_directory_uri() . '/inc/images/frames/'.$options['bg_frame'].'.png' : ''; 
		echo 'body.category-0 .sides {background-image: url(' . $bg0_img  . ') !important}';
	
	}

	?>
  	@media only screen and (max-width: 960px) { 
    	html body.black { background-image: none !important; background-color: #000 !important; }
  	  header #header-img {
      	max-width: 100%;
      	width: 100%;
      	max-height: 135px;
      	margin: 5px 0 10px 0 !important;
      }

    }
	</style>
	<!--[if lt IE 9]>
		<link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/ie.css" type="text/css" media="all" />
  <![endif]-->

</head>
<body <?php body_class(); ?>>
<div class="none">
	<p><a href="#content"><?php _e('Skip to Content'); ?></a></p>
  <?php /* used for accessibility, particularly for screen reader applications */ ?>
</div><!--.none-->
<div id="main" <?php if($custom_hp) echo 'class="custom-frontpage"'; ?>><!-- this encompasses the entire Web site -->
	<div id="header">
	  <header>
  		<div class="container header">
      <?php if( $custom_hp ):?>
          <?php require_once('custom-header.php'); ?>
      <?php else: ?>
  			<div id="logo-container">
  				<div id="logo">
  					<a href="<?php bloginfo('url');?>" title="<?php bloginfo('description'); ?>"><img src="<?php echo bloginfo('template_url');?>/images/logo-if.png" alt="<?php bloginfo('name'); ?>" /></a>
  				</div>
  				<div class="tagline"><?php bloginfo('description'); ?></div>
  			</div><!--#logo-->
  		<?php if( $multi ): ?>
  			<!-- Antennas menu -->
  			<div id="top-menu-antennes">
  				<ul><?php get_if_top_categ(array('orderby' => 'name')); ?></ul>
  			</div><!-- /#top-menu-antenne -->
  		<?php endif; ?>
  		<?php //MENU PAGES && HEADER IMAGE (theme option)
    			$hmenupages = 1; //default to 1 = displayed
    			if ($multi) {
      			$header_img = isset($options[$categAdmin]['header_img']) ? $options[$categAdmin]['header_img'] : false;
      			$header_img_link = isset($options[$categAdmin]['header_img_link']) ? $options[$categAdmin]['header_img_link'] : false;
      			
    			  if ( $if_front ) {
      			  $hmenupages = isset($options[$categAdmin]['theme_options_setting_hmenupage']) ? $options[$categAdmin]['theme_options_setting_hmenupage'] : $hmenupages;
    			  }
    			  else {
      			  $hmenupages = isset($options[$antenna]['theme_options_setting_hmenupage']) ? $options[$antenna]['theme_options_setting_hmenupage'] : $hmenupages;
    			  }
    			}
    			else { 
    			  $hmenupages = isset($options['theme_options_setting_hmenupage']) ? $options['theme_options_setting_hmenupage'] : $hmenupages; 
    			  $header_img = isset($options['header_img']) ? $options['header_img'] : false; 
    			  $header_img_link = isset($options['header_img_link']) ? $options['header_img_link'] : false; 
          }
        ?>
  		  <!-- header image for tomislav ;-) -->
  		  <div id="header-img">
    		  <?php if( $header_img_link ) : ?><a href="<?php echo $header_img_link;?>"><?php endif;?>
          <img src="<?php echo $header_img; ?>" alt="" />
     		  <?php if( $header_img_link ) : ?></a><?php endif;?>
 		  </div>
  			<!-- Header widget area -->
  			<div id="header-widget" class="widget-area widget-header">
  			 <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar( 'Header' ) ) : // NOT USED ?>
  				
  				<?php if(function_exists('languages_list_header')) : ?>
  					<aside id="header-languages" class="widget">
  						<?php languages_list_header(); /* outputs the language switcher */ ?>
  					</aside>
  				<?php endif;?>
    			<?php  //if ($hmenupages && !is_plugin_active( 'underconstruction/underConstruction.php' ) ) : //0 is NULL
  				if ( $hmenupages ) : //0 is NULL?>
    				<aside id="header-pages-menu" class="widget">
    					<?php wp_page_menu('show_home=0'); /* outputs the pages menu */ ?>
    				</aside>
    		  <?php endif;?>
  				<?php //if( !is_plugin_active( 'underconstruction/underConstruction.php' ) ):?>
  				<aside id="header-search" class="widget">
  					<?php get_search_form(); /* outputs the default Wordpress search form */ ?>
  				</aside>
  				<?php //endif;?>
  			 <?php endif ?>
  			</div>
  			<div class="clear"></div>
      <?php endif; ?>
  		</div><!--.container.header-->
  		<div class="container for-angle">
  			<!-- div for bevel angle -->
  			<div class="right-corner"></div>
    		  <?php $termlevel2 = get_if_level2_categ(true);
    		  if( !empty($termlevel2) ) : ?>	
    			<?php if( $multi ): //MENU multi antennes ?>
    				<?php if(!$custom_hp && !is_date() && !is_404() && !is_search() && !is_page() && (!is_front_page() || !is_home())) :?>
    				  <nav id="antennes" role="navigation"><ul class="menu clearfix"><?php  if(get_if_level2_categ()) get_if_level2_categ(); ?></ul></nav><!-- /#antennes -->
    				<?php endif;?>
    		  
    		  <?php else : ?>
    				<nav id="antennes" role="navigation"><ul class="menu clearfix"><?php get_if_level2_categ();?></ul></nav><!-- /#antennes -->
    			<?php endif;?>
    		<?php endif;?>
  		</div><!--/.container.for-angle-->
 	  </header>
	</div><!--#header-->
	<div class="container main-container">
		<div class="breadcrumbs">
			<?php if( function_exists('bcn_display') ) { bcn_display(); }?>
		</div>
