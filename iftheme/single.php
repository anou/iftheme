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
		
	} elseif('post' == get_post_type()) { 

			$data = get_meta_if_post();
			$start = '<span class="start">' . $data['start'] . '</span>';
			$end = '<span class="end">' . $data['end'] . '</span>'; 
			$book = $data['booking'];
			$town = $data['city']; 
	}
?>
<div id="content">
	<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>

			<article class="post">
				<?php if($start):?><div class="infos-post bxshadow"><?php echo $start ? $start . $end .' - ' : ''; ?><?php echo $multi ? get_cat_name($antenna) : $town;?></div><?php endif;?>
				<h1><a href="<?php the_permalink() ?>" title="<?php the_title(); ?>" rel="bookmark"><?php the_title(); ?></a></h1>
				<small><?php edit_post_link(__('Edit this entry', 'iftheme')); ?></small>
				<?php if ( has_post_thumbnail() ) { echo '<div class="featured-post-img">'; the_post_thumbnail('post-img'); echo '</div>'; } ?>
				<div class="post-content">
					
					<?php if(!empty($slides)):?>
					  <?php foreach($slides as $slide => $values):
						  		$img = wp_get_attachment_image_src( $values['img'],'partner');
					  ?>
						<div class="slider"><h3><?php echo $values['title'];?></h3><a href="<?php echo $values['link'];?>" title="<?php echo $values['link'];?>"><img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="" /></a></div>
					  <?php endforeach;?>
					  
					<?php else :?>
						<?php the_content(); ?>
					<?php endif;?>
				
				</div><!--.post-content-->

				<div id="post-meta">
					<p><?php  the_category(', ') ?></p>
				</div><!--#post-meta-->
			</article>

		<?php if('post' == get_post_type()): ?>
		<!-- ADDITIONAL INFOS -->
			<div class="booking-container add-infos">
				<h3 class="booking-title"><span class="picto-book"></span><?php _e("Useful informations",'iftheme'); ?></h3>
				<div class="form-content bxshadow">
	            <h4><?php echo $data['lieu'];?></h4>
	            <p class="date-time"><?php echo $start . $end;?> <?php echo $data['time'] != str_replace(' / ', '', $data['end']) ? ' - ' .$data['time'] : '';?></p>
	            <p>
	              <?php echo $data['adresse'] ? $data['adresse'].'<br />':'';?>
	              <?php echo $data['adressebis'] ? $data['adressebis'].'<br />':'';?>
	              <?php echo $data['zip'] ? $data['zip'] . ' - ':''; ?> <?php echo $data['city'];?> <?php $data['pays'] ? ' - '.$data['pays']:'';?><br />
	              <?php echo $data['tel'] ? $data['tel'].'<br />':'';?>
	              <?php echo $data['event_mail'] ? '<a href="mailto:'. $data['event_mail'] .'">'. $data['event_mail'] .'</a><br />' : '';?>
	              <?php echo $data['link1'] ? '<a href="'. $data['link1'] .'" target="_blank">'. $data['link1'] .'</a><br />' : '';?>
	              <?php echo $data['link2'] ? '<a href="'. $data['link2'] .'" target="_blank">'. $data['link2'] .'</a><br />' : '';?>
	              <?php echo $data['link3'] ? '<a href="'. $data['link3'] .'" target="_blank">'. $data['link3'] .'</a><br />' : '';?>
				</div>
			</div><!-- #booking-container -->
		<?php endif;?>
		<!-- BOOKING FORM -->
		<?php if($book == 'on'):?>
			<div class="booking-container">
				<h3 class="booking-title"><span class="picto-book"></span><?php _e("Booking",'iftheme');?></h3>
				<div class="form-content bxshadow clearfix">
					<?php echo get_booking_form(); ?>
				</div>
			</div><!-- #booking-container -->
		<?php endif;?>
			
		</div><!-- #post-## -->

		<!--
		<div class="newer-older">
			<p class="older"><?php //previous_post_link('%link', '&laquo; Previous post') ?></p>
			<p class="newer"><?php //next_post_link('%link', 'Next Post &raquo;') ?></p>
		</div>
		--><!--.newer-older-->
		
		<?php //comments_template( '', true ); ?>

		<?php //prepare data for dates in JS 
		   $pid = get_the_ID();
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

	<?php endwhile; /* end loop */ ?>
</div><!--#content-->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
