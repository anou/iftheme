<?php get_header(); ?>

<?php if($multi) : ?>
	<?php require_once('multi-front-page.php');?>
<?php else :?>

	<div id="content"><span class="none">FRONT FOR SINGLE ANTENNA</span>
<?php 	
  	  global $sitepress;
  	  $default_lg = isset($sitepress) ? $sitepress->get_default_language() : 'fr';//assuming that 'fr' should be default language
  	  $antenna = get_current_antenna();
	    $original = function_exists('icl_object_id') ? icl_object_id($currenta, 'category', true, $default_lg) : $antenna;

		$args_slider = array(
			'post_type'=> 'if_slider',
			'order'    => 'DESC',
			'meta_key' => 'slide_antenna',
			'meta_value' => $original,
			'posts_per_page' => 1
			);
			
			query_posts( $args_slider );
	?>
	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	<?php //get slider data
			$dslide = get_meta_slider($post->ID);
			foreach($dslide['slides'] as $s => $vals){
				$slides[$s]['title'] = $vals['slide_title'];
				$slides[$s]['link'] = $vals['url_img_slide']; 
				$slides[$s]['img'] = $vals['image_slide']['id']; 
			}
			$slides = array_reverse($slides);
	?>
		<div id="slider">
			<div id="slides"><!-- #slides -->
			
			<?php if(!empty($slides)):?>
				<!-- slides_container  -->
				<div class="slides_container">
				<?php foreach($slides as $slide => $values):
						  $img = wp_get_attachment_image_src( $values['img'],'slider');
				?>
					<div class="slide">
						<a href="<?php echo $values['link'];?>" title="<?php echo $values['link'];?>"><img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="" /></a><div class="caption"><?php echo $values['title'];?></div>
					</div><!-- /.slide -->
					
				<?php endforeach;?>
				
				</div><!-- /.slides_container -->
				<a href="#" class="prev none"><img src="<?php bloginfo('template_directory');?>/images/slide/arrow-prev.png" width="24" height="43" alt="Arrow Prev"></a>
				<a href="#" class="next none"><img src="<?php bloginfo('template_directory');?>/images/slide/arrow-next.png" width="24" height="43" alt="Arrow Next"></a>
	  
			<?php endif;?>
			
			</div><!-- /#slides -->
		</div><!-- /#slider -->
	<?php endwhile; ?>
	<?php /*end query slider*/ wp_reset_query(); ?>		
	<?php endif; ?>
		
		
	<?php //get displayed home categories for antenna
		$home_cat = isset($options['theme_home_categ']) ? $options['theme_home_categ'][0] : '';
		if($home_cat):?>
			<div id="home-list">
			<?php foreach($home_cat as $id):?>
				<?php $cat = get_the_category_by_ID($id);?>
				<div class="block-home">
					<h2 class="posts-category"><?php echo $cat;?></h2>
					<?php //alter query
					$time = (time() - (60*60*24));
					$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
          $args = array(
             'cat' => $id,
             'meta_key' => 'if_events_startdate',
             'orderby' => 'meta_value_num',
             'order' => 'ASC',
             'paged' => $paged,
             'meta_query' => array(
                 array(
                     'key' => 'if_events_enddate',
                     'value' => $time,
                     'compare' => '>=',
                 )
             )
           );					
					query_posts($args); ?>

					<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
						<article class="post-single-home clearfix" id="post-<?php the_ID();?>">
							<?php //prepare data 
									$pid = get_the_ID();
									$data = get_meta_if_post($pid);
									$start = $data['start'];
									$end = $data['end'];  

							?>
							<div class="top-block">
								<?php if($start):?><div class="date-time"><span class="start"><?php echo $start;?></span><span class="end"><?php echo $end;?></span><?php endif;?> - <span class="post-antenna"><?php echo(get_cat_name($antenna));?></span></div>
								<h3 class="post-title"><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>" rel="bookmark"><?php the_title(); ?></a></h3>
							</div>
							<?php if ( has_post_thumbnail() ) : /* loades the post's featured thumbnail, requires Wordpress 3.0+ */ ?>
								<div class="featured-thumbnail-home"><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>" rel="bookmark"><?php echo  the_post_thumbnail('home-block');?></a></div>
							<?php else : ?>
								<div class="post-excerpt">
									<?php the_excerpt(); /* the excerpt is loaded to help avoid duplicate content issues */ ?>
								</div>
							<?php endif;?>
						</article><!--.post-single-->

      			<?php //prepare data for dates in JS 
      			   $raw_data = get_meta_raw_if_post($pid);
      			?>
      			<script type="text/javascript">
      			  var lang = !icl_lang ? bInfo['bLang'] : icl_lang;
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
        			end = !time ? end : time;
        			
        			if(end !== start) thisPostEnd.text(' / '+end);
        			
      			</script>

					<?php endwhile; ?>
					<?php wp_reset_query();?>
					<?php else: ?>
						<div class="no-results bxshadow">
							<p><?php _e('No post for the moment','iftheme'); ?></p>
						</div><!--noResults-->
					<?php endif; ?>
				</div><!-- /.block-home -->
			<?php endforeach;?>
			</div>
		<?php else : ?>
			<div class="no-results bxshadow">
				<p><?php _e('No post for the moment & no homepage category selected','iftheme'); ?></p>
			</div><!--noResults-->
		<?php endif; ?>	
	</div><!--#content-->
	
<?php endif;?>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
