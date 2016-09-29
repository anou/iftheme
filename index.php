<?php get_header(); ?>

<?php //for debug
  //if(!is_super_admin()) $custom_hp = false; 
?>

<?php if( $multi && !$custom_hp ) : ?>
	<?php require_once( 'multi-front-page.php' );?>
  <?php get_sidebar(); ?>

<?php elseif( $custom_hp ) : ?>
	<?php require_once( 'custom-frontpage.php' );	?>


<?php else :?>
	<div id="content">
  <?php if(is_super_admin()):?><span class="debug" style="position:fixed; bottom:0; left:0; background-color: yellow; color:green;"><i>FRONT FOR SINGLE ANTENNA (index.php)</i></span><?php endif;?>
<?php 	
  	  global $sitepress;
  	  $default_lg = isset($sitepress) ? $sitepress->get_default_language() : get_site_lang();

  	  $antenna = get_current_parent_categ();
      $original = array_key_exists( 'wpml_object_id' , $GLOBALS['wp_filter'] ) ? apply_filters( 'wpml_object_id', $antenna, 'category', true, $default_lg ) : $antenna;
	    
  		$args_slider = array(
  			'post_type'=> 'if_slider',
  			'order'    => 'DESC',
  			'meta_key' => 'slide_antenna',
  			'meta_value' => $original,
  			'posts_per_page' => 1
			);
			
			$query_slider = new WP_Query( $args_slider );
	?>
	<?php if( $query_slider->have_posts()) : while( $query_slider->have_posts()) : $query_slider->the_post(); ?>
	<?php //get slider data
			$dslide = get_meta_slider($post->ID);
			foreach($dslide['slides'] as $s => $vals){
				$slides[$s]['title'] = $vals['slide_title'];
				$slides[$s]['link'] = $vals['url_img_slide']; 
				$slides[$s]['img'] = $vals['image_slide']['id']; 
			}
			$slides = array_reverse($slides);
  ?><!-- SLIDER -->
		<div id="slider">
			<div id="slides"><!-- #slides -->
			
			<?php if(!empty($slides)):?>
				<!-- slides_container  -->
				<div class="slides_container">
				<?php foreach($slides as $slide => $values):
            $url = isset($values['link']) ? parse_url( $values['link'] ) : false;
            $blank = true;
            if( is_array($url) ) {
              if( $url['host'] == $_SERVER['HTTP_HOST'] || !$url['host'] ) $blank = false;
            } 
					  $img = wp_get_attachment_image_src( $values['img'], 'slider');
				 if($img) : ?>
					<div class="slide">
						<?php if( isset($values['link']) ) : ?>
						  <a href="<?php echo $values['link'];?>" title="<?php echo $values['title'];?>" <?php echo $blank ? 'target="_blank"' : ''; ?>><img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="" /></a>
						<?php else : ?>
						  <img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="" />
						<?php endif; ?><div class="caption"><?php echo $values['title'];?></div>
					</div><!-- /.slide -->
				
				<?php else : //default title ?>
				  <div class="slide"><div class="caption"><a title="<?php _e('Modify your slider' ,'iftheme');?>" href="<?php echo get_edit_post_link($post->ID); ?>"><?php echo $values['title'];?></a></div></div>
				
				<?php endif; endforeach;?>
				</div><!-- /.slides_container -->
				<a href="#" class="prev none"><img src="<?php bloginfo('template_directory');?>/images/slide/arrow-prev.png" width="24" height="43" alt="Arrow Prev"></a>
				<a href="#" class="next none"><img src="<?php bloginfo('template_directory');?>/images/slide/arrow-next.png" width="24" height="43" alt="Arrow Next"></a>
	  
			<?php endif;?>
			
			</div><!-- /#slides -->
		</div><!-- /#slider -->
	<?php endwhile; ?>
	<?php wp_reset_postdata(); ?>
	<?php endif; ?>
		
	<?php //get displayed home categories for antenna
		$home_cat = isset($options['theme_home_categ']) ? $options['theme_home_categ'][0] : '';
		if( $home_cat ):?>
			<div id="home-list">
			<?php foreach( $home_cat as $cid ): 
			  $show = TRUE;
			  //check if category has a translation
        if( array_key_exists( 'wpml_object_id' , $GLOBALS['wp_filter']) ) {
          $show = apply_filters( 'wpml_object_id', $cid, 'category', false );
        } ?>
  		<?php if ($show) : 
    		  $cid = is_numeric( $show ) ? $show : $cid;
  		?>
				<?php $cat = get_the_category_by_ID( $cid );?>
				<div class="block-home">
					<h2 class="posts-category"><?php echo $cat;?></h2>
				<?php //alter query
          $time = ( current_time( 'timestamp' ) - (60*60*24) ); //yesterday
          $paged = get_query_var('paged') ? get_query_var('paged') : 1;
          $post_types = array( 'post' );
          $args = array(
             'cat' => $cid,
             'meta_key' => 'if_events_startdate',
             'orderby' => 'meta_value_num',
             'order' => 'ASC',
             'posts_per_page' => $options['theme_home_nb_events'],
             'paged' => $paged,
             'meta_query' => array(
                array(
                   'key' => 'if_events_enddate',
                   'value' => $time,
                   'compare' => '>=',
                ),
              ),
              'post_type' => $post_types
           );					
          $events_query = new WP_Query( $args ); 
      ?>
    <?php include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); ?>
<!-- COURSES -->
   <?php 
        /**
         * Detect plugin. For use on Front End only.
         */
        if ( is_plugin_active( 'courses/courses.php' ) ) {
          //plugin is activated
          //check for Courses plugin & add Courses in query.
          array_push($post_types, 'course');
          
          //@todo: find a way to "merge" meta_key xx_startdate to order by this value
          $events_courses_query_args = array(
            'cat' => $cid,
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'posts_per_page' => $options['theme_home_nb_events'],
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'meta_query' => array(
              'relation' => 'OR',
              array(
                 'key' => '_courses_enddate',
                 'value' => $time,
                 'compare' => '>=',
              ),
              array(
                 'key' => 'if_events_enddate',
                 'value' => $time,
                 'compare' => '>=',
              ),
            ),
            'post_type' => $post_types
          );

          $events_courses_query = new WP_Query($events_courses_query_args);
        }
        //replace query if Courses query exist
        if( isset( $events_courses_query ) ) $events_query = $events_courses_query; 
  ?>
      <?php // check for IF plugin
          if ( is_plugin_active( 'ifplugin/ifplugin.php' ) ) {
            $always_news = isset( $options['display_news'] ) ? $options['display_news'] : 0;
            //plugin is activated
            $news_args = array(
              'cat' => $cid,
              'post_type' => array('news'),
              'meta_key' => '_ifp_news_date',
              'orderby' => 'meta_value_num',
              'order' => 'ASC',
            );
            if( !$always_news ) {
              $news_args['meta_query'] = array(
                array(
                   'key' => '_ifp_news_date',
                   'value' => $time,
                   'compare' => '>=',
                )
              );
            }
            $posts_ids = array();
            $news_query = new WP_Query( $news_args );
            $posts = array_merge($events_query->posts, $news_query->posts);
            foreach($posts as $obj) {
              $posts_ids[] = (int)$obj->ID;
            }
            array_push($post_types, 'news');
            //we add news after the main query
            $events_query = !empty($posts_ids) ? new WP_Query(array('post__in' => $posts_ids, 'post_type' => $post_types, 'orderby' => 'post__in', 'paged' => $paged )) : $events_query;
          }
      ?>
      <?php if ($events_query->have_posts()) : while ($events_query->have_posts()) : $events_query->the_post(); ?>
  			<?php //prepare data
  					$pid = get_the_ID();
  					$data = get_meta_if_post($pid);
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
    				</div><!-- /.top-block -->
    				
    				<?php if ( has_post_thumbnail() ) : /* loades the post's featured thumbnail, requires Wordpress 3.0+ */ ?>
    					<div class="featured-thumbnail-home"><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>" rel="bookmark"><?php echo the_post_thumbnail('home-block');?></a></div>
    				
    				<?php else : ?>
    					<div class="post-excerpt"><?php the_excerpt(); ?></div>
    				<?php endif;?>
    			</article><!--.post-single-->
    			<?php //prepare data for dates in JS 
    			   $raw_data = get_meta_raw_if_post($pid);
    			   
    			   if( isset($raw_data['start']) && $raw_data['start'] ) : ?>
		<script type="text/javascript">
		  var lang = (typeof(icl_lang) != "undefined" && icl_lang !== null) ? icl_lang : bInfo['bLang'].substr(0,2); //TODO: check if bInfo['bLang'] is construct like this xx-XX...
		  var startDate = <?php echo $raw_data['start'];?>, endDate = <?php echo $raw_data['end'];?>;

		  moment.locale(lang);
		  
		  var startYear = new Date(startDate*1000).getFullYear();
		  var endYear = new Date(endDate*1000).getFullYear();
			var thisPostStart = jQuery("#post-<?php the_ID();?> .start");
			var thisPostEnd = jQuery("#post-<?php the_ID();?> .end");
			
			var start = moment.unix(startDate).utc().format('ll');
			var end = moment.unix(endDate).utc().format('ll');
			var time = '<?php echo $raw_data['time'];?>';
			
			start = start.replace(startYear, '');//remove year from start
			thisPostStart.text(start);//display start
			end = end.replace(endYear, '');//remove year from end
			end = end !== start ? end : time;//if no end, display time

			if (end) if(end !== start) thisPostEnd.text(' / '+end);//display end and prepend a / (slash)
			
		</script>
      			<?php endif; ?>
					<?php endwhile; ?>
					<?php wp_reset_query();?>
					<?php else: ?>
						<div class="no-results bxshadow">
							<p><?php _e('No post for the moment','iftheme'); ?></p>
						</div><!--noResults-->
					<?php endif; ?>
				</div><!-- /.block-home -->
					<?php endif; ?>
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
