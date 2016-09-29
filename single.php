<?php get_header(); 
	$slides = array();
	if(post_type_exists( 'if_slider' ) && 'if_slider' == get_post_type()) {
		$data = get_meta_slider($post->ID);
		$antenna = $data['antenna'];
		
		if($data['slides']){
			foreach($data['slides'] as $s => $vals){
				$slides[$s]['title'] = $vals['slide_title']; 
				$slides[$s]['link'] = $vals['url_img_slide']; 
				$slides[$s]['img'] = $vals['image_slide']['id']; 
			}
			$slides = array_reverse($slides);
		}

	} elseif(post_type_exists( 'if_partner' ) && 'if_partner' == get_post_type()) {
		$data = get_meta_partners($post->ID);
		if(!is_array($data)) echo $data;
		
		$antenna = is_array($data) ? $data['antenna'] : NULL;

		if(is_array($data) && $data['partners']){
			foreach($data['partners'] as $s => $vals){
				$part[$s]['title'] = $vals['partner_title']; 
				$part[$s]['link'] = $vals['link_to_partner']; 
				$part[$s]['img'] = $vals['image_logo']['id']; 
			}
			$part = array_reverse($part);
			//to avoid coding twice...
			$slides = $part;
		}
		
	} elseif('post' == get_post_type() || 'news' == get_post_type() || 'course' == get_post_type()) { 

			$data = get_meta_if_post();
      $news = isset($data['type']) && $data['type'] == 'news' ? true : false;
      $data['start'] = $news ? $data['subhead'] : $data['start'];
      if( !$data['start'] && $news ) $data['start'] = __('News', 'iftheme');
			$start = '<span class="start">' . $data['start'] . '</span>';
			$end = '<span class="end">' . $data['end'] . '</span>'; 
			$book = $data['booking'];
			$town = $data['city']; 
	} 
?>
<?php if(is_super_admin()):?><span class="debug" style="position:fixed; bottom:0; left:0; background-color: yellow; color:purple;"> single.php </span><?php endif;?>

<div id="content">
  
	<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>

			<article class="post">
				<?php if( isset($start) ):?><div class="infos-post bxshadow"><?php echo $start ? $start . $end : ''; ?><?php echo $multi && !$news ? ' - ' . get_cat_name($antenna) :  !$news ? ' - ' . $town : '';?></div><?php endif;?>
				<h1><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>" rel="bookmark"><?php the_title(); ?></a></h1>
				<small><?php edit_post_link( sprintf(__('Edit this %s', 'iftheme'), get_post_type()) ); ?></small>
				
				<?php if ( has_post_thumbnail() ) { echo '<div class="featured-post-img">'; the_post_thumbnail('post-img'); echo '</div>'; } ?>

				<div class="post-content">
					<?php if(!empty($slides)):?>
					  <?php foreach($slides as $slide => $values):
						  		$img = wp_get_attachment_image_src( $values['img'],'partner');
					  ?>
						<div class="slider"><h3><?php echo $values['title'];?></h3><a href="<?php echo $values['link'];?>" title="<?php echo $values['link'];?>"><img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="" /></a></div>
					  <?php endforeach;?>
					  
					<?php else : ?>
						<?php the_content(); ?>
					<?php endif;?>
				
				</div><!--.post-content-->

				<div id="post-meta">
					<p><?php  the_category(', ') ?></p>
				</div><!--#post-meta-->
			</article>

		<?php if('post' == get_post_type() || 'course' == get_post_type() ): ?>
		<!-- ADDITIONAL INFOS -->
			<div class="booking-container add-infos">
				<h3 class="booking-title"><span class="picto-book"></span><?php _e("Useful informations",'iftheme'); ?></h3>
				<div class="form-content bxshadow">
	            <h4><?php echo $data['lieu']; ?></h4>
	            <p class="date-time"><?php echo $start . $end;?> <?php echo $data['time'] != str_replace(' / ', '', $data['end']) && $data['time'] ? ' - ' .$data['time'] : '';?></p>
	            <p>
	              <?php echo $data['adresse'] ? $data['adresse'].'<br />':'';?>
	              <?php echo $data['adressebis'] ? $data['adressebis'].'<br />':'';?>
	              <?php echo $data['zip'] ? $data['zip'] . ' - ':''; ?> <?php echo $data['city'];?> <?php //echo $data['pays'] ? ' <br /> '.$data['pays']:'';?><br />
	              <?php echo $data['tel'] ? $data['tel'].'<br />':'';?>
	              <?php echo $data['event_mail'] ? '<a href="mailto:'. $data['event_mail'] .'">'. $data['event_mail'] .'</a><br />' : '';?>
	              <?php echo $data['link1'] ? '<a href="'. $data['link1'] .'" target="_blank">'. $data['link1'] .'</a><br />' : '';?>
	              <?php echo $data['link2'] ? '<a href="'. $data['link2'] .'" target="_blank">'. $data['link2'] .'</a><br />' : '';?>
	              <?php echo $data['link3'] ? '<a href="'. $data['link3'] .'" target="_blank">'. $data['link3'] .'</a><br />' : '';?>
	              <?php if( isset($data['type']) && $data['type'] == 'course' ): //courses data ?>
  	              <div id="courses-list">
  	              <?php 
    	              $teacher = isset($data['teacher']) ? $data['teacher'] : false;
    	              $special_infos = isset($data['special_infos']) ? $data['special_infos'] : false;
    	              $courses = isset($data['courses']) ? $data['courses'] : false;
                  ?>
                  <?php if( $teacher ):?><p><?php printf(  esc_html__( 'Teacher: %s', 'iftheme' ), $teacher ); ?></p><?php endif;?>
                  <?php if( $special_infos ):?><div class="special-infos"><?php echo  wpautop(esc_html( $special_infos ), true ); ?></div><?php endif;?>
                  <?php if( $courses ):?>
                    <h3><?php esc_html_e('Courses Schedule', 'iftheme');?></h3>
                    <ul>
                    <?php foreach( $courses as $k => $course ): ?>
                      <li class="bxshadow"><?php echo esc_html($course);?></li>
                    <?php endforeach;?>
                    </ul>
                  <?php endif;?>
  	              <?php //echo '<br /><p><a class="bxshadow" href="#" title="' . __('Subscribe to this course', 'iftheme') . '">' . __('Subscription form', 'iftheme') . '</a></p>';?>
  	              </div>
	              <?php endif;?>
	              
				</div>
			</div><!-- #booking-container -->
  		<!-- BOOKING FORM -->
  		<?php if($book == 'on'): ?>
  			<div class="booking-container">
  				<h3 class="booking-title"><span class="picto-book"></span><?php _e("Booking",'iftheme');?></h3>
  				<div class="form-content bxshadow">
  					<?php echo get_booking_form(); ?>
  				</div>
  			</div><!-- #booking-container -->
  		<?php endif;?>
		
		<?php endif;?>
			
		</div><!-- #post-## -->
		
		<?php //comments_template( '', true ); ?>

		<?php //prepare data for dates in JS 
		   $pid = get_the_ID();
		   $raw_data = get_meta_raw_if_post($pid);
		?>
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

	<?php endwhile; /* end loop */ ?>
</div><!--#content-->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
