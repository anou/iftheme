<?php 
/*
 This file is part of underConstruction.
 underConstruction is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.
 underConstruction is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with underConstruction.  If not, see <http://www.gnu.org/licenses/>.
 */

function displayDefaultComingSoonPage() {
    displayComingSoonPage(trim(get_bloginfo('title')).' is coming soon', get_bloginfo('url'), 'is coming soon');
}

function displayComingSoonPage($title, $headerText, $bodyText) {
    
?>
 <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>
            <?php echo $title; ?>
        </title>
      	<meta name="description" content="<?php bloginfo('name'); echo ' | '; bloginfo( 'description' ); ?>" />
      	<meta charset="<?php bloginfo( 'charset' ); ?>" />
        <link rel="icon" href="<?php bloginfo('url');?>/wp-content/themes/iftheme/favicon.ico" type="image/x-icon">
        <script type="text/javascript" src="<?php bloginfo('url');?>/wp-includes/js/jquery/jquery.js?ver=1.8.3"></script>
        <!-- <script type="text/javascript" src="<?php bloginfo('url');?>/wp-content/themes/iftheme/js/if-script.js?ver=3.5.1"></script> -->
        <script type="text/javascript" src="<?php bloginfo('url');?>/wp-content/themes/iftheme/inc/calendar/ajax.js?ver=3.5.1"></script>
        
        <link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('url');?>/wp-content/themes/iftheme/lessframework.css">
        <link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('url');?>/wp-content/themes/iftheme/style.css">
    </head>
    <body class="home blog category-1 black cat-1-id customize-support">
      <div id="main"><!-- this encompasses the entire Web site -->
      	<div id="header">
      	  <header>
      		<div class="container header">
      			<div id="logo-container">
      				<div id="logo">
      					<a href="<?php bloginfo('url');?>" title="<?php bloginfo('name');?>"><img src="<?php bloginfo('url');?>/wp-content/themes/iftheme/images/logo-if.png" alt="<?php bloginfo( 'name' );?>"></a>
      				</div>
      				<div class="tagline"><?php bloginfo('description');?></div>
      			</div><!--#logo-->
      			<div class="clear"></div>
      		</div><!--.container.header-->
      		<div class="container for-angle"><!-- div for bevel angle --><div class="right-corner"></div></div><!--/.container.for-angle-->
      		</header>
      	</div><!--#header-->
      	
      	<div class="container main-container">
      		<div class="breadcrumbs"><img src="<?php bloginfo('url');?>/wp-content/themes/iftheme/images/pict-home-on.png" alt=""></div>
      		<div id="content"><span class="none">UNDER CONSTRUCTION PAGE</span>
      			<div class="no-results bxshadow">
      				<h1><?php echo $headerText; ?></h1>
      				<p><?php echo nl2br($bodyText);?></p>
      			</div><!--noResults-->
      	</div><!--#content-->
      	
      <div id="sidebar">
      <?php if (function_exists('get_antennas_details')):?>
    		<!-- SOCIAL NETWORKS -->
    		<?php $options = get_antennas_details();

    		    $fb = isset($options['theme_options_setting_facebook']) ? $options['theme_options_setting_facebook'] : $options[(int)$antenna]['theme_options_setting_facebook'];
    			  $twit = isset($options['theme_options_setting_twitter']) ? $options['theme_options_setting_twitter'] : $options[(int)$antenna]['theme_options_setting_twitter'];
    			  $gg = isset($options['theme_options_setting_googleplus']) ? $options['theme_options_setting_googleplus'] : $options[(int)$antenna]['theme_options_setting_googleplus'];
    			  $iftv = isset($options['theme_options_setting_iftv']) ? $options['theme_options_setting_iftv'] : $options[(int)$antenna]['theme_options_setting_iftv'];
    		  
    			  if($fb || $twit || $gg || $iftv):
    		?>
    			<aside id="sidebar-social" class="widget bxshadow clearfix">
    			  <h3><?php _e('Join-us','iftheme');?></h3>
    			  <ul>
    				<?php if($fb):?><li id="fb"><a href="<?php echo $fb;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/fb.png" alt="facebook" /></a></li><?php endif;?>
    				<?php if($twit):?><li id="twit"><a href="<?php echo $twit;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/twit.png" alt="twitter" /></a></li><?php endif;?>
    				<?php if($gg):?><li id="gg"><a href="<?php echo $gg;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/gg.png" alt="google plus" /></a></li><?php endif;?>
    				<?php if($iftv):?><li id="iftv"><a href="<?php echo $iftv;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/iftv.png" alt="Institut Français TV" /></a></li><?php endif;?>
    			  </ul>
    			</aside>
    		<?php endif; ?>
    		<?php endif;?>
    		
    		<!-- CALENDAR -->
    		<aside id="sidebar-calendar" class="widget ifworldlinks bxshadow">
    			<div id="ajax_calendrier"><?php  include( get_template_directory() . '/inc/calendar/calendrier.php'); ?></div>
    		</aside>
      </div><!--sidebar-->
      
      <div class="clear"></div>
      </div><!--.container-->
      	<!-- div for bevel angle -->
      	<div class="container for-angle"><div id="smol-logo"><a href="http://smol.org" target="_blank" title="Theme by smol. Studio de création sympathique."><img src="<?php bloginfo('url');?>/wp-content/themes/iftheme/images/smol-logo-mini.png" alt="smol"></a></div><div class="left-corner"></div></div>
      
      	<div id="footer">
      	  <footer>
      		<div class="container clearfix">
      		  
      		  <div class="widget-footer footer-logo">
        		  <img src="<?php bloginfo('url');?>/wp-content/themes/iftheme/images/logo-footer.png" alt="<?php bloginfo( 'name' );?>">
        		  <div class="tagline"><?php bloginfo('description');?></div>
        		</div>
        		
      		  <div class="footer-all-block clearfix">
      <!--Wigitized Footer-->
      		    <div class="widget-footer">&nbsp;</div>
      		    <div class="widget-footer">&nbsp;</div>
      
      						
      			<div class="widget-footer footer-links">
      				<div class="widget ifworldlinks"><h2 class="widgettitle">IF Numérique</h2>		<ul class="xoxo blogroll">
        		  		<li><a href="http://www.institutfrancais.com/ifmobile-pour-smartphones-et-tablettes" title="IFmobile" target="_blank">IFmobile</a></li>
        		  		<li><a href="http://ifprog.institutfrancais.com" title="IFprog" target="_blank">IFprog</a></li>
        		  		<li><a href="http://ifmapp.institutfrancais.com" title="IFmapp" target="_blank">IFmapp</a></li>
        		  		<li><a href="http://ifcinema.institutfrancais.com" title="IFcinéma" target="_blank">IFcinéma</a></li>
        		  		<li><a href="http://www.ifverso.com" title="IFverso" target="_blank">IFverso</a></li>
        		  		<li><a href="http://institutfrancais.tv" title="Web TV" target="_blank">Web TV</a></li>
        		  		<li><a href="http://www.institutfrancais.com" title="Institut français" target="_blank">Institut français</a></li>
        		  		<li><a href="" title="" target="_blank"></a></li>
        		  		<li><a href="" title="" target="_blank"></a></li>
        		  		<li><a href="" title="" target="_blank"></a></li>
        				</ul>
        				</div>
      		  	</div>
      		  </div><!-- /.footer-all-block -->
      		 </div><!--.container-->
      	  </footer>
      	</div><!--#footer-->
      </div><!--#main-->
  <div class="sides" id="side-left"></div>
  <div class="sides" id="side-right"></div>
  </body>
</html>
<?php 
}
/* EOF */
?>
