<?php /* under construction page for IF */ 

//TODO : OPTION FOR SELECTING PAGE FOR UNDERCONSTRUCTION !!!!!

?>
<?php get_header(); ?>

<?php 
  global $options;
  $pageID = $options['theme_options_setting_underconstruction'];

  $uc_query = new WP_Query('post_type=page&page_id=' . $pageID );
?>
<div id="content">
<?php
  if( !$pageID ) {
 		$link = network_admin_url( 'themes.php' );
    printf( __( 'You must choose an Under construction page in theme options: <a href="%s">Go to theme options</a>.%s' ), $link, '<div class="none">' );
    
  }
?>
	<?php if ( $uc_query->have_posts() ) while ( $uc_query->have_posts() ) : $uc_query->the_post(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class('page'); ?>>
			<article>
				<h1><?php the_title(); ?></h1>
				<small><?php edit_post_link(__('Edit this entry', 'iftheme')); ?></small>
				<?php if ( has_post_thumbnail() ) { echo '<div class="featured-thumbnail-page">'; the_post_thumbnail('categ-img'); echo '</div>'; } ?>
	
				<div class="post-content page-content">
					<?php the_content(); ?>
				</div><!--.post-content .page-content -->
			</article>
		</div><!--#post-# .post-->
	<?php endwhile; ?>
<?php echo !$pageID ? '</div>' : '' ; ?>
	<?php wp_reset_postdata(); ?>
</div><!--#content-->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
