<?php global $multi; ?>
	<div class="clear"></div>
	</div><!--.container-->
	<!-- div for bevel angle -->
	<div class="container for-angle"><div id="smol-logo"><a href="http://smol.org" target="_blank" title="Theme by smol. Studio de création sympathique."><img src="<?php bloginfo('template_url')?>/images/smol-logo-mini.png" alt="smol" /></a></div><div class="left-corner"></div></div>

	<div id="footer">
	  <footer>
		<div class="container clearfix">
		  
		  <div class="widget-footer footer-logo">
  		  <img src="<?php echo bloginfo('template_url');?>/images/logo-footer.png" alt="<?php bloginfo('name'); ?>" />
  		  <div class="tagline"><?php bloginfo('description'); ?></div>
  		</div>
  		
		  <div class="footer-all-block clearfix">
    <?php if (!function_exists('dynamic_sidebar') ||  ! dynamic_sidebar( 'Footer' )) : ?><!--Wigitized Footer-->
    		
		    <div class="widget-footer footer-pages">
				  <?php the_widget('WP_Widget_Pages','title='.get_bloginfo('description'),'before_title=<h3>&after_title=</h3>'); ?>
				</div>

  		<?php if($multi): ?>
  			<div class="widget-footer footer-antenna">
  				<h3><?php _e('Local antennas','iftheme');?></h3>
  				<ul><?php get_if_top_categ(array('orderby' => 'name')); ?></ul>
  			</div>
  		<?php else: //one antenna ?>
  			<div class="widget-footer footer-links">
  				<?php the_widget('If_Mobile'); ?>
  			</div>
  		<?php endif;?>
  			
  			<div class="widget-footer footer-links">
  				<?php the_widget('If_World_Links'); ?>
  			</div>
    <?php endif; //end dynamic_sidebar ?>
		  </div><!-- /.footer-all-block -->

		</div><!--.container-->
	  </footer>
	</div><!--#footer-->
</div><!--#main-->
<?php wp_footer(); /* this is used by many Wordpress features and plugins to work properly */ ?>
</body>
</html>
