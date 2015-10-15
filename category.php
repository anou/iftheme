<?php get_header(); ?>
<div id="content">
<?php 
  	  global $sitepress;
  	  $default_lg = isset($sitepress) ? $sitepress->get_default_language() : get_site_lang();
  	  //$default_lg = isset($sitepress) ? $sitepress->get_default_language() : 'fr';//assuming that 'fr' should be default language

  	  $currenta = get_current_parent_categ();
	    $original = function_exists('icl_object_id') ? icl_object_id($currenta, 'category', true, $default_lg) : $currenta;
	
	if(get_query_var('cat') === $currenta && $multi):?>

<span style="color:blue" class="none"> Home Page ONE ANTENNA in MULTI </span>

	<?php $args_slider = array(
  			'post_type'=> 'if_slider',
  			'order'    => 'DESC',
  			'meta_query' => array(
          array(
            'key' => 'slide_antenna',
            'value' => $original
          ),
        ),
			);
			
  		$slider_query = new WP_Query( $args_slider );
	?>
	<?php if ($slider_query->have_posts()) : while ($slider_query->have_posts()) : $slider_query->the_post(); ?>
	<?php //get slider data
			$dslide = get_meta_slider($post->ID);
			$slides = array();

			if(empty($dslide['frontpage'])) { 

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
				 if($img) : ?>
					<div class="slide">
						<a href="<?php echo $values['link'];?>" title="<?php echo $values['link'];?>"><img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="" /></a><div class="caption"><?php echo $values['title'];?></div>
					</div><!-- /.slide -->
					
				<?php endif; endforeach; ?>
				
				</div><!-- /.slides_container -->
				<a href="#" class="prev none"><img src="<?php bloginfo('template_directory');?>/images/slide/arrow-prev.png" width="24" height="43" alt="Arrow Prev"></a>
				<a href="#" class="next none"><img src="<?php bloginfo('template_directory');?>/images/slide/arrow-next.png" width="24" height="43" alt="Arrow Next"></a>
	  
			<?php endif;?>
			
			</div><!-- /#slides -->
		</div><!-- /#slider -->
		<?php } ?>
	<?php endwhile; ?>
	<?php wp_reset_postdata();?>
	<?php endif; ?>

  <div class="widget-front-container clearfix">
  <?php if (!function_exists('dynamic_sidebar') ||  !dynamic_sidebar( 'Front-page' )) : ?>
  <?php endif; //end dynamic_sidebar ?>
  </div>

	<?php //get displayed home categories for antenna
		
		$home_cat = isset($options[$original]['theme_home_categ']) ? $options[$original]['theme_home_categ'][0] : '';

		if($home_cat):?>
			<div id="home-list">
			<?php foreach($home_cat as $id): ?>
				<?php $id = function_exists('icl_object_id') ? icl_object_id($id,'category',true) : $id; ?>
				<?php $cat = get_the_category_by_ID($id); ?>
				<div class="block-home">
					<h2 class="posts-category"><?php echo $cat;?></h2>
					<?php //alter query
					$time = (time() - (60*60*24));
					$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
          $args = array(
             'cat' => (int)$id,
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
          $event_query = new WP_Query( $args ); ?>

          <?php include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
          // check for IF plugin
          if ( is_plugin_active( 'ifplugin/ifplugin.php' ) ) {
            //plugin is activated
            
            $news_args = array(
              'cat' => $id,
              'post_type' => array('news'),
              'meta_key' => '_ifp_news_date',
              'orderby' => 'meta_value_num',
              'order' => 'ASC',
              'meta_query' => array(
                array(
                   'key' => '_ifp_news_date',
                   'value' => $time,
                   'compare' => '>=',
                )
              )
            );
            $posts_ids = array();
            $news_query = new WP_Query( $news_args );
            $posts = array_merge($event_query->posts, $news_query->posts);
            foreach($posts as $obj) {
              $posts_ids[] = (int)$obj->ID;
            }

            $event_query = new WP_Query(array('cat' => $id, 'post__in' => $posts_ids, 'post_type' => array('news', 'post'), 'orderby' => 'post__in'));
          } ?>

					<?php if ($event_query->have_posts()) : while ($event_query->have_posts()) : $event_query->the_post(); ?>
						<?php //prepare data 
								$pid = get_the_ID();
								$data = apply_filters('if_event_data', get_meta_if_post($pid));
								$type = isset($data['type']) ? $data['type'] : false;
								$classes = 'post-single-home clearfix';
								$classes .= $type ? ' ' . $type : '';
								$classes = apply_filters('if_event_classes', $classes);
								
								$start = $type == 'news' ? $data['subhead'] : $data['start'];
								$end = $data['end'];
								$antenna_id = $data['antenna_id'];
								$town = $data['city'];
						?>
						<article class="<?php echo $classes;?>" id="post-<?php the_ID();?>">
							<div class="top-block">
							  <div class="date-time">
								  <?php if($start):?><span class="start"><?php echo $start;?></span><span class="end"><?php echo $end;?></span><?php endif;?>
                   <?php if('news' != $type):?>
                    <span class="post-antenna"><?php echo 'page' == get_post_type() ? bloginfo('description') : !$town ? ' - ' . get_cat_name($antenna_id) : ' - ' . $town;?></span>
                   <?php endif;?>
						    </div><!-- /.date-time -->
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
        			
        			if (end) if(end !== start) thisPostEnd.text(' / '+end);
        			
      			</script>
					<?php endwhile; ?>
					<?php wp_reset_postdata();?>

					<?php else: ?>
						<div class="no-results bxshadow">
							<p><?php _e('No post for the moment','iftheme'); ?></p>
						</div><!--noResults-->
					<?php endif; ?>
				</div><!-- /.block-home -->
			<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="no-results bxshadow">
				<p><?php _e('No post for the moment and/or no homepage category selected','iftheme'); ?></p>
			</div><!--noResults-->
		<?php endif; ?>	
	
	<?php else: //Page category ------------------------ ?>
		<?php //get data from categ (key are img, children, posts)
			$data = get_categ_data(get_query_var('cat'));
		?>

<span class="none" style="color:red;">HOME PAGE 1 category</span>

			<h1><?php echo single_cat_title( '', false ); ?></h1>
			<?php if(!empty($data['img'])) : $img = wp_get_attachment_image_src( $data['img']['id'],'categ-img');?><div class="categ-image"><img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="" /></div><?php endif;?>
			<div class="description"><?php echo category_description(); /* displays the category's description from the Wordpress admin */ ?></div>
		<!-- Child categories -->
		<?php if(!empty($data['children'])):?>
			<ul class="display-children clearfix">
  			<?php  wp_list_categories('title_li=&use_desc_for_title=0&hide_empty=0&depth=1&child_of='.get_query_var('cat')); ?>
			</ul>
		<?php endif;?>
		
		<?php iftheme_content_nav( 'nav-top' ); //next-prev nav ?>

		<!-- POSTS -->
		<?php if (have_posts() && !empty($data['posts'])) : ?> 

			<h2 class="upcoming"><?php _e('Agenda','iftheme');?></h2>
			<?php while (have_posts()) : the_post(); ?>
			<?php //prepare data 
				//$pid = get_the_ID();
				$pid = $post->ID;
				$data = get_meta_if_post($pid);
				$start = $data['start'];
				$end = $data['end'];
				$town = $data['city'];
      ?>
			<article class="post-single clearfix" id="post-<?php the_ID();?>">
				<?php if ( has_post_thumbnail() ) { /* loades the post's featured thumbnail, requires Wordpress 3.0+ */ echo '<div class="featured-thumbnail">'; the_post_thumbnail('listing-post'); echo '</div>'; } ?>
				<div class="top-block bxshadow">
					<div class="date-time">
						<?php if($start):?><span class="start"><?php echo $start;?></span><span class="end"><?php echo $end;?></span><?php endif;?> - <span class="post-antenna"><?php echo !$town ? get_cat_name($antenna) : $town;?></span>
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
  			
  			if (end) if(end !== start) thisPostEnd.text(' / '+end);
  			
			</script>

		<?php endwhile; ?>
		
		<?php iftheme_content_nav( 'nav-below' ); //next-prev nav ?>
    <!-- NEWS -->
    <?php
    /**
     * Detect plugin. For use on Front End only.
     */
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    // check for plugin using plugin name
    if ( is_plugin_active( 'ifplugin/ifplugin.php' ) ) :
       //plugin is activated
      $time = (time() - (60*60*24));
      $news_args = array(
        'cat' => get_query_var('cat'),
        'post_type' => array('news'),
        'meta_key' => '_ifp_news_date',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'meta_query' => array(
          array(
             'key' => '_ifp_news_date',
             'value' => $time,
             'compare' => '>=',
          )
        )
      );
      $posts_ids = array();
      $news_query = new WP_Query( $news_args );
      if ($news_query->have_posts()) : ?> 
  			<h2 class="upcoming"><?php _e('News','iftheme');?></h2>
      <?php while ($news_query->have_posts()) : $news_query->the_post(); ?>
    			<?php //prepare data 
    				$pid = get_the_ID();
    				//$pid = $post->ID;
						$data = apply_filters('if_event_data', get_meta_if_post($pid));
						$subhead = $data['subhead'];
//     				$start = $data['start'];
//     				$end = $data['end'];
						$classes = 'post-single clearfix';
						$classes .= isset($data['type']) ? ' ' . $data['type'] : '';
						$classes = apply_filters('if_event_classes', $classes);
          ?>
    			<article class="<?php echo $classes;?>" id="post-<?php the_ID();?>">
    				<?php if ( has_post_thumbnail() ) { /* loades the post's featured thumbnail, requires Wordpress 3.0+ */ echo '<div class="featured-thumbnail">'; the_post_thumbnail('listing-post'); echo '</div>'; } ?>
    				<div class="top-block bxshadow">
    					<div class="date-time">
    						<?php if($start):?><span class="start"><?php echo $subhead;?></span><?php endif;?>
    					</div>
    					<h2><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
    				</div>
    				<div class="post-excerpt">
    					<?php the_excerpt(); /* the excerpt is loaded to help avoid duplicate content issues */ ?>
    				</div>
    				<div class="post-meta"><?php the_category(', ') ?></div>
    			</article><!--.post-single-->
      <?php endwhile; ?>
    <?php endif; ?>
    <?php wp_reset_postdata();?>
   <?php endif; ?>
    
		<!-- OLD POSTS -->
		<?php elseif (!have_posts() && !empty($data['posts'])) : 
  		//list old post/event from this category
   	  $args_alternative = array(
  			'meta_query' => array(
          array(
            'key' => 'if_events_startdate',
          ),
        ),
  			'order' => 'DESC',
        'orderby' => 'meta_value_num',
        'meta_key'=> 'if_events_startdate',
        'cat' => get_query_var('cat')
        );
  			
    		$query_alter = new WP_Query( $args_alternative );
		
		if($query_alter->have_posts()) : ?> 
			<h2 class="upcoming"><?php _e('Archives','iftheme');?></h2>
			<?php while ($query_alter->have_posts()) : $query_alter->the_post(); ?>
			<?php //prepare data 
				//$pid = get_the_ID();
				$pid = $post->ID;
				$data = get_meta_if_post($pid);
				$start = $data['start'];
				$end = $data['end'];  
			?>			 
			<article class="post-single clearfix" id="post-<?php the_ID();?>">
				<?php if ( has_post_thumbnail() ) { /* loades the post's featured thumbnail, requires Wordpress 3.0+ */ echo '<div class="featured-thumbnail">'; the_post_thumbnail('listing-post'); echo '</div>'; } ?>
				<div class="top-block bxshadow">
					<div class="date-time">
						<?php if($start):?><span class="start"><?php echo $start;?></span><span class="end"><?php echo $end;?></span><?php endif;?> - <span class="post-antenna"><?php echo(get_cat_name($antenna));?></span>
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
  			
  			if (end) if(end !== start) thisPostEnd.text(' / '+end);
  			
			</script>

  		<?php endwhile; ?>
      
      <?php iftheme_content_nav( 'nav-below' ); //next-prev nav ?>

  		<?php endif;?>		
    <?php wp_reset_postdata();?>
 		<?php else: /* ?>
			<div class="no-results bxshadow">
				<p><?php _e('No post in this category','iftheme'); ?></p>
			</div><!--noResults-->
		<?php */ endif; ?>
		
	<?php endif;?>

</div><!--#content-->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
