<div id="content">
	
	<?php if($multi): //HOME PAGE Antennas ?>

<?php if(is_super_admin()):?><span class="none" style="position:fixed; bottom:0; right:0; background-color: yellow; color:green; z-index:1000; opacity:0.5"><i>Main Home Page MULTI-ANTENNA (multi-front-page.php)</i></span><?php endif;?>

	<?php $args_slider = array(
  			'post_type'=> 'if_slider',
  			'order'    => 'DESC',
  			'meta_key' => 'is_country',
  			'meta_value' => 'on',
  			'posts_per_page' => 1
			);
			
  		$slider_query = new WP_Query( $args_slider );
	?>
	<?php if( $slider_query->have_posts() ): while( $slider_query->have_posts() ): $slider_query->the_post(); ?>
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
            $url = isset($values['link']) ? parse_url( $values['link'], PHP_URL_HOST ) : false;
            $blank = true;
            if( $url ) {
              if( $url == $_SERVER['HTTP_HOST'] ) $blank = false;
            }
					  $img = wp_get_attachment_image_src( $values['img'],'slider');
				 if($img) : ?>
					<div class="slide">
						<?php if($url) : ?>
						  <a href="<?php echo $values['link'];?>" title="<?php echo $values['link'];?>" <?php echo $blank ? 'target="_blank"' : ''; ?>><img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="" /></a>
						<?php else : ?>
						  <img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="" />
						<?php endif; ?><div class="caption"><?php echo $values['title'];?></div>
					</div><!-- /.slide -->
					
				<?php endif; endforeach;?>
				
				</div><!-- /.slides_container -->
				<a href="#" class="prev none"><img src="<?php bloginfo('template_directory');?>/images/slide/arrow-prev.png" width="24" height="43" alt="Arrow Prev"></a>
				<a href="#" class="next none"><img src="<?php bloginfo('template_directory');?>/images/slide/arrow-next.png" width="24" height="43" alt="Arrow Next"></a>
	  
			<?php endif;?>
			
			</div><!-- /#slides -->
		</div><!-- /#slider -->
	<?php endwhile; ?>
	<?php wp_reset_postdata();//end $slider_query?>
	
  <div class="widget-front-container clearfix">
  <?php if (!function_exists('dynamic_sidebar') ||  !dynamic_sidebar( 'Front-page' )) : ?><!--Wigitized Footer-->
  <?php endif; //end dynamic_sidebar ?>
  </div>

	
	<?php else :?><div class="msg warning"><?php _e("You don't have any <em>Slider</em> recorded yet. <a href=\"/wp-admin/post-new.php?post_type=if_slider\">Add one now ?</a>"); ?></div>
	<?php endif; ?>

	<?php 
	  // Get admin categ. Only admin can configure country homepage
	  $categAdmin = get_cat_if_user(1);
	  // Get displayed home categories for antenna.
		$home_cat = isset($options[$categAdmin]['theme_home_categ_country']) ? $options[$categAdmin]['theme_home_categ_country'][0] : false;
		if($home_cat):
    /**
     * Homepage country
     */
    ?>
			<div id="home-list">
			<?php foreach($home_cat as $id):?>
				<?php $id = function_exists('icl_object_id') ? icl_object_id($id,'category',true) : $id;
				  $cat = get_category($id); 
				  $antparent = get_cat_name(get_root_category($id));
				?>
				<div class="block-home">
					<h2 class="posts-category"><?php echo $cat->name;?> / <?php echo $antparent;?></h2>
					<?php //alter query
					$time = (time() - (60*60*24));
          $args = array(
             'cat' => $id,
             'meta_key' => 'if_events_startdate',
             'orderby' => 'meta_value_num',
             'order' => 'ASC',
             'posts_per_page' => $options[$categAdmin]['theme_home_nb_events_country'],
             'meta_query' => array(
                 array(
                     'key' => 'if_events_enddate',
                     'value' => $time,
                     'compare' => '>=',
                 )
             )
          );
           
          $event_query = new WP_Query( $args ); 

          include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
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
          }
           ?>
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
							</div><!-- /.top-block -->
							
							<?php if ( has_post_thumbnail() ) : /* loades the post's featured thumbnail, requires Wordpress 3.0+ */ ?>
								<div class="featured-thumbnail-home"><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>" rel="bookmark"><?php echo the_post_thumbnail('home-block');?></a></div>
							
							<?php else : ?>
								<div class="post-excerpt"><?php the_excerpt(); ?></div>
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
			<?php endforeach;?>
			</div>
		<?php else : ?>
			<div class="no-results bxshadow">
				<p><?php _e('No post for the moment & no configuration options','iftheme'); ?></p>
			</div><!--noResults-->
		<?php endif; ?>	
	
	<?php else: //Page for 1 category ?>
<?php if(is_super_admin()):?><span class="none" style="position:fixed; bottom:0; left:0; background-color: yellow; color:purple; z-index:1000; opacity:0.5"><i>Home Page ONE ANTENNA/CATEGORY (multi-front-page.php)</i></span><?php endif;?>

		<?php //prepare data (key are img, children, posts)
			$data = get_categ_data(get_query_var('cat'));
		?>
			<h1><?php echo single_cat_title( '', false ); ?></h1>
			<?php if(!empty($data['img'])) : $img = wp_get_attachment_image_src( $data['img'][0]['id'],'categ-img');?>
			  <div class="categ-image">
			    <img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="" />
			  </div>
			 <?php endif;?>
       <div class="description"><?php echo category_description(); ?></div>
		<!-- Child categories -->
		<?php if(!empty($data['children'])):?>
			<ul class="display-children">
			  <?php wp_list_categories('title_li=&use_desc_for_title=0&hide_empty=0&depth=1&child_of='.get_query_var('cat')); ?>
      </ul>
		<?php endif;?>
		<!-- POSTS -->
		<?php if (have_posts() && !empty($data['posts'])) : ?> 
			<h2 class="upcoming"><?php _e('Coming soon','iftheme');?></h2>
			<?php while (have_posts()) : the_post(); ?>
			<article class="post-single">
				<?php //prepare data 
					//$pid = get_the_ID();
					$pid =$post->ID;
					$data = get_meta_if_post($pid);
					$start = $data['start'];
					$end = $data['end'];  
				?>
				<?php if ( has_post_thumbnail() ) { echo '<div class="featured-thumbnail">'; the_post_thumbnail('listing-post'); echo '</div>'; } ?>
				<div class="top-block bxshadow">
					<div class="date-time">
						<?php if($start):?><span class="start"><?php echo $start;?></span><span class="end"><?php echo $end;?></span><?php endif;?><span class="post-antenna"><?php echo 'page' == get_post_type() ? bloginfo('description') : ' - ' . get_cat_name($antenna); ?></span>
					</div>
					<h2><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
				</div>
				<div class="post-excerpt">
					<?php the_excerpt(); ?>
				</div>
				<div class="post-meta"><?php the_category(', ') ?></div>
		
			</article><!--.post-single-->
		<?php endwhile; else: /* ?>
			<div class="no-results bxshadow">
				<p><?php _e('No post in this category','iftheme'); ?></p>
			</div><!--noResults-->
		<?php */ endif; ?>
	<?php endif;?>

</div><!--#content-->
