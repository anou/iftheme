<?php get_header(); 
  //setlocale(LC_ALL, get_locale());
  $q = $wp_query->query;
?>
<div id="content">
	<h1 id="titledate">
		<?php if ( is_day() ) : /* if the daily archive is loaded */ ?>
			<?php echo utf8_encode(strftime('%d %B %Y', mktime(0, 0, 0, $q['monthnum'], $q['day'], $q['year'])));?>
		<?php elseif ( is_month() ) : /* if the montly archive is loaded */ ?>
			<?php echo utf8_encode(strftime('%B %Y', mktime(0, 0, 0, $q['monthnum']+1, 0, $q['year'])));?>
		<?php elseif ( is_year() ) : /* if the yearly archive is loaded */ ?>
			<?php echo utf8_encode(strftime('%Y', mktime(0, 0, 0, 12, 31, $q['year'])));?>
		<?php else : /* if anything else is loaded, ex. if the tags or categories template is missing this page will load */ ?>
			<?php _e('Archives', 'iftheme');?>
		<?php endif; ?>
	</h1>

	<script type="text/javascript">
	  var month = '<?php echo is_month();?>';
	  month = month ? true : false;
	  var year = '<?php echo is_year();?>';
	  year = year ? true : false;

    var lang = (typeof(icl_lang) != "undefined" && icl_lang !== null) ? icl_lang : bInfo['bLang'].substr(0,2); //TODO: check if bInfo['bLang'] is construct like this xx-XX...
	  moment.lang(lang);
	  
	  var titleDate = jQuery('#titledate').text();
    titleDate = month ? '1 '+titleDate  : titleDate;
    titleDate = year ? '1 january '+titleDate  : titleDate;;
    
    titleDate = moment(titleDate).format('LL');
    
    titleDate = month ? titleDate.substr(2) : titleDate;
    titleDate = year ? titleDate.substr(-4) : titleDate;
    
    jQuery('#titledate').text(titleDate);
  </script>

	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
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
		<p class="older"><?php next_posts_link('&laquo; Older Entries', 'iftheme') ?></p>
		<p class="newer"><?php previous_posts_link('Newer Entries &raquo;', 'iftheme') ?></p>
	</div><!--.oldernewer-->

</div><!--#content-->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
