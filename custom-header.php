<div id="custom-header">
  <div id="logo-container">
  	<div id="logo">
  		<a href="<?php bloginfo('url');?>" title="<?php bloginfo('description'); ?>"><img src="<?php echo bloginfo('template_url');?>/images/logo-if.png" alt="<?php bloginfo('name'); ?>" /></a>
  	</div>
  	<div class="tagline"><?php bloginfo('description'); ?></div>
  </div><!--#logo-->
  
  <aside id="header-languages" class="widget">
  	<?php languages_list_header(); /* outputs the language switcher */ ?>
  </aside>
  <div class="clear"></div>
</div>