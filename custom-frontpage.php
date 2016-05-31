<div id="content">
  <?php if(is_super_admin()):?><span class="none" style="position:fixed; bottom:0; right:0; background-color: yellow; color:green; z-index:1000; opacity: 0.5;"><i>CUSTOM FRONT PAGE (custom-frontpage.php)</i></span><?php endif;?>
  
  <?php //we could check for multi but we know we are multi :-) 
    $options = array_reverse($options, true);
    $icones = array(
      1 => 'icone-oslo.png',
      3 => 'icone-stavanger.png'
    );
  ?>
  
  <?php foreach( $options as $aid => $antenna ):
        if( !$aid ) continue;
        
        $tid = apply_filters( 'wpml_object_id', $aid, 'category', true );
        $term = get_term($tid);
        
  ?>
  <a href="<?php echo get_term_link($term);?>">
    <div class="custom-home-block <?php echo $term->slug;?>">
      <h2><?php echo $term->name; ?></h2>
      <div class="icones">
        <img src="<?php echo get_template_directory_uri() . '/images/' . $icones[$aid]; ?>" alt="<?php echo $term->name; ?>" />
      </div>
    </div>
  </a>
  <?php endforeach; ?>
  
  <div class="social-networks">
  <?php
    $fb = isset($options['theme_options_setting_facebook']) ? $options['theme_options_setting_facebook'] : $options[(int)$antenna]['theme_options_setting_facebook'];
    $twit = isset($options['theme_options_setting_twitter']) ? $options['theme_options_setting_twitter'] : $options[(int)$antenna]['theme_options_setting_twitter'];
    $gg = isset($options['theme_options_setting_googleplus']) ? $options['theme_options_setting_googleplus'] : $options[(int)$antenna]['theme_options_setting_googleplus'];
    $iftv = isset($options['theme_options_setting_iftv']) ? $options['theme_options_setting_iftv'] : $options[(int)$antenna]['theme_options_setting_iftv'];
    $youtube = isset($options['theme_options_setting_youtube']) ? $options['theme_options_setting_youtube'] : $options[(int)$antenna]['theme_options_setting_youtube'];
    $instagram = isset($options['theme_options_setting_instagram']) ? $options['theme_options_setting_instagram'] : $options[(int)$antenna]['theme_options_setting_instagram'];
		  
    if($fb || $twit || $gg || $iftv || $youtube || $instagram): ?>
    <ul>
    <?php if($fb):?><li><a href="<?php echo $fb;?>" target="_blank"><i class="fa fa-facebook" aria-hidden="true"></i></a></li><?php endif;?>
    <?php if($twit):?><li><a href="<?php echo $twit;?>" target="_blank"><i class="fa fa-twitter" aria-hidden="true"></i></a></li><?php endif;?>
    <?php if($gg):?><li><a href="<?php echo $gg;?>" target="_blank"><i class="fa fa-google-plus" aria-hidden="true"></i></a></li><?php endif;?>
    <?php if($youtube):?><li><a href="<?php echo $youtube;?>" target="_blank"><i class="fa fa-youtube" aria-hidden="true"></i></a></li><?php endif;?>
    <?php if($instagram):?><li><a href="<?php echo $instagram;?>" target="_blank"><i class="fa fa-instagram" aria-hidden="true"></i></a></li><?php endif;?>
    <?php if($iftv):?><li><a href="<?php echo $iftv;?>" target="_blank" title="IF Tv"><i class="fa fa-television" aria-hidden="true"></i></a></li><?php endif;?>
    </ul>
  <?php endif; ?>      
  </div>

</div><!--#content-->
<?php //get_sidebar(); ?>
<?php get_footer(); ?>
