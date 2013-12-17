<?php get_header(); ?>

<div id="content">
	<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class('page'); ?>>
			<article>
				<h1><?php the_title(); ?></h1>
				<small><?php edit_post_link(__('Edit this entry', 'iftheme')); ?></small>
				<?php if ( has_post_thumbnail() ) { echo '<div class="featured-thumbnail-page">'; the_post_thumbnail('categ-img'); echo '</div>'; } ?>
	
				<div class="post-content page-content">
					<?php the_content(); ?>
					<?php wp_link_pages('before=<div class="pagination">&after=</div>'); ?>
				</div><!--.post-content .page-content -->
			</article>
		</div><!--#post-# .post-->

		<?php //comments_template( '', true ); ?>

	<?php endwhile; ?>
</div><!--#content-->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
