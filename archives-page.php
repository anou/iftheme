<?php /* Template Name: IF Archives */ 
 /* 
  * This template is for IF Archives and must be used only once by language.
  * If used more then one time, the last page using it will be taken as Archives page.
  */
?>
<?php get_header(); 
  //get category if any
  $ifcat = isset($_GET['ifcat']) ? $_GET['ifcat'] : 'all';
  $cat = $ifcat != 'all' ? '&cat=' . $ifcat : NULL;
  
  if($cat): 
    $categ = get_category($ifcat); 
    $subtitle = $categ->name;
  else:
    $subtitle = __('All categories', 'iftheme');
  endif;
?>
<div id="content">
	<h1 id="titledate">
			<?php _e('Archives', 'iftheme');?>
	</h1>
  <?php if ($wp_query->have_posts()) : while ($wp_query->have_posts()) : $wp_query->the_post(); ?>
  <p><?php the_content();?></p>
	<?php endwhile; endif;  ?>
	
<?php // Display blog posts

/*
		$temp = &$wp_query;
		$wp_query = null;
*/
    $archives_args = array(
       'posts_per_page' =>  get_option('posts_per_page'),
       'paged' =>  $paged . $cat,
       'meta_key' => 'if_events_startdate',
       'orderby' => 'meta_value_num',
       'order' => 'ASC',
       'meta_query' => array(
      		array(
      		   'key' => 'if_events_enddate',
      		   'value' => strtotime('yesterday'),
      		   'compare' => '<=',
      		   'type' => 'numeric'
      		  )
        )
     );		
     
     $wp_query = new WP_Query($archives_args);
?>
	<h2><?php echo $subtitle;?></h2>

  <?php if ($wp_query->have_posts()) : while ($wp_query->have_posts()) : $wp_query->the_post(); ?>
			<article class="post-single clearfix" id="post-<?php the_ID();?>">
				<?php //prepare data 
					$pid = get_the_ID();
					//$pid =$post->ID;
					$data = get_meta_if_post($pid);
					$start = $data['start'];
					$end = $data['end'];
					$antenna_id = $data['antenna_id']; 
				?>
				<?php if ( has_post_thumbnail() ) { /* loades the post's featured thumbnail, requires Wordpress 3.0+ */ echo '<div class="featured-thumbnail">'; the_post_thumbnail('listing-post'); echo '</div>'; } ?>
				<div class="top-block bxshadow">
					<div class="date-time">
						<?php if($start):?><span class="start"><?php echo $start;?></span><span class="end"><?php echo $end;?></span><?php endif;?><span class="post-antenna"><?php if('page' == get_post_type()){ bloginfo('description'); } else { echo ' - '.get_cat_name($antenna_id);}?></span>
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
  			
  			//Show year in archives
  			//start = start.replace(startYear, '');
  			thisPostStart.text(start);
        //end = end.replace(endYear, '');
  			end = end !== start ? end : time;
  			
  			if (end) if(end !== start) thisPostEnd.text(' / '+end);
  			
			</script>
	<?php endwhile; else:  ?>
		<div class="no-results">
			<p><?php _e('No results', 'iftheme'); ?></p>
			<?php get_search_form(); /* outputs the default Wordpress search form */ ?>
		</div><!--noResults-->
	<?php endif; ?>
  
  <?php iftheme_content_nav( 'nav-below', FALSE ); //next-prev nav ?>
  
  <?php wp_reset_postdata(); ?>	

</div><!--#content-->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
