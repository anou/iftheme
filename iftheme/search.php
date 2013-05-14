<?php get_header(); ?>
<div id="content" class="search">

	<h1><?php the_search_query(); ?></h1>

	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			<article class="post-single clearfix" id="post-<?php the_ID();?>">
				<?php //prepare data 
					$pid = get_the_ID();
					//$pid =$post->ID;
					$data = get_meta_if_post($pid);
					$start = $data['start'];
					$end = $data['end'];  
				?>
				<?php if ( has_post_thumbnail() ) { /* loades the post's featured thumbnail, requires Wordpress 3.0+ */ echo '<div class="featured-thumbnail">'; the_post_thumbnail('listing-post'); echo '</div>'; } ?>
				<div class="top-block bxshadow">
					<div class="date-time">
						<?php if($start):?><span class="start"><?php echo $start;?></span><span class="end"><?php echo $end;?></span><?php endif;?><span class="post-antenna"><?php if('page' == get_post_type()){ bloginfo('description'); } else { echo ' - '.get_cat_name($antenna);}?></span>
					</div>
					<h2><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
				</div>
				<div class="post-excerpt">
					<?php the_excerpt(); /* the excerpt is loaded to help avoid duplicate content issues */ ?>
				</div>
				<div class="post-meta"><?php the_category(', ') ?></div>
		
			</article><!--.post-single-->

			<?php //prepare data for dates in JS 
			   $raw_data = get_meta_raw_if_post($pid);
			?>
			<script type="text/javascript">
			  var lang = (typeof(icl_lang) != "undefined" && icl_lang !== null) ? icl_lang : bInfo['bLang'].substr(0,2); //TODO: check if bInfo['bLang'] is construct like this xx-XX...
			  moment.lang(lang);
			  
			  var startYear = new Date(<?php echo $raw_data['start'];?>*1000).getFullYear();
			  var endYear = new Date(<?php echo $raw_data['end'];?>*1000).getFullYear();
  			var thisPostStart = jQuery("#post-<?php the_ID();?> .start");
  			var thisPostEnd = jQuery("#post-<?php the_ID();?> .end");
  			
  			var start = moment.unix(<?php echo $raw_data['start'];?>).format('ll');
  			var end = moment.unix(<?php echo $raw_data['end'];?>).format('ll');
  			var time = '<?php echo $raw_data['time'];?>';
  			
  			start = start.replace(startYear, '');
  			thisPostStart.text(start);
  			end = end.replace(endYear, '');
  			end = end !== start ? end : time;
  			
  			if(end !== start) thisPostEnd.text(' / '+end);
  			
			</script>

	<?php endwhile; else:  ?>
		<div class="no-results">
			<p><?php _e('No results', 'iftheme'); ?></p>
			<?php get_search_form(); /* outputs the default Wordpress search form */ ?>
		</div><!--noResults-->
	<?php endif; ?>

	<div class="oldernewer">
		<p class="older"><?php next_posts_link('&laquo; Older Entries') ?></p>
		<p class="newer"><?php previous_posts_link('Newer Entries &raquo;') ?></p>
	</div><!--.oldernewer-->
	
</div><!-- #content -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
