<?php global $antenna; global  $multi; global $options; global $c; global $antenna_op;

	$cat_slug = is_front_page() || is_home() ? NULL : get_cat_slug($antenna);
	
	if($multi){
		$sidebar = !$cat_slug ? 'Sidebar' : 'Sidebar '.$cat_slug; 
	} else { $sidebar = 'Sidebar';}

?>
<div id="sidebar">
			<?php  //NEWSLETTER FORM
			if(defined('WYSIJA')) {
				$modelList = &WYSIJA::get("list","model");
				$arrayOfMailingLists = $modelList->get(false,array('is_enabled'=>1));
				
				$nbMailingLists = count($arrayOfMailingLists);
				
				foreach($arrayOfMailingLists as $k => $list){
					$lists[] = $list['list_id'];
					$names[$list['list_id']] = $list['name'];
				}
				
				
				$widgetdata = array (
				   'widget_id' => 'wysija-nl-php-1',//form identifier important when many subscription forms on the same page
				   'title' =>  __('Newsletter','iftheme'),//title of widget
				   'instruction' => __('Subscribe','iftheme'), // instruction to be displayed on top of the widget
				   'lists' =>  $lists, //array of list_id to which you want to subscribe your users
				   'lists_name' => $names, //array of lists names ( array(list_id => name) )
				   'submit' => __('OK','iftheme'),//name of the subscribe button
				   'success' => __('You’ve successfully subscribed. Check your inbox now to confirm your subscription.','iftheme'),//success message returned when registered
				   'customfields' => array ( //optional array of custom fields to be displayed lastname, firstname, email
				        //'firstname' => array ('column_name' => 'firstname','label' => 'Prénom'),
				        //'lastname' => array ('column_name' => 'lastname','label' => 'Nom'),
				        'email' => array ('label' => __('Your Email','iftheme'))
				   ),
				   'labelswithin' => 'labels_within', //parameter to put the label of the custom field as a default value of the field
				   'before_title' => '<h3>',
				   'after_title' => '</h3>',
				   'before_widget' => '<aside id="sidebar-newsletter" class="widget-sidebar bxshadow clearfix">',
				   'after_widget' => '</aside>',
				);

				if($multi && $nbMailingLists > 1){
				   $widgetdata['autoregister'] = 'auto_register'; //the users can choose the mailing-list they want to subscribe to
				}
				 
				$widgetNL = new WYSIJA_NL_Widget(0);//0 > display title; 1 > hide title
				
				
				$subscriptionForm = $widgetNL->widget($widgetdata,$widgetdata);
				 
				echo $subscriptionForm;
			}
			?>
		<!-- SOCIAL NETWORKS -->
		<?php if($antenna != 'front'):
			  $fb = isset($options['theme_options_setting_facebook']) ? $options['theme_options_setting_facebook'] : $options[(int)$antenna]['theme_options_setting_facebook'];
			  $twit = isset($options['theme_options_setting_twitter']) ? $options['theme_options_setting_twitter'] : $options[(int)$antenna]['theme_options_setting_twitter'];
			  $gg = isset($options['theme_options_setting_googleplus']) ? $options['theme_options_setting_googleplus'] : $options[(int)$antenna]['theme_options_setting_googleplus'];
		  
			  if($fb || $twit || $gg):
		?>
			<aside id="sidebar-social" class="widget bxshadow clearfix">
			  <h3><?php _e('Join-us','iftheme');?></h3>
			  <ul>
				<?php if($fb):?><li id="fb"><a href="<?php echo $fb;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/fb.png" alt="facebook" /></a></li><?php endif;?>
				<?php if($twit):?><li id="twit"><a href="<?php echo $twit;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/twit.png" alt="twitter" /></a></li><?php endif;?>
				<?php if($gg):?><li id="gg"><a href="<?php echo $gg;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/gg.png" alt="google plus" /></a></li><?php endif;?>
			  </ul>
			</aside>
		<?php endif; ?>
		<?php endif; ?>
		
		<!-- CALENDAR -->
		<aside id="sidebar-calendar" class="widget ifworldlinks bxshadow">
			<div id="ajax_calendrier"><?php  include( get_template_directory() . '/inc/calendar/calendrier.php'); ?></div>
		</aside>
		
		<?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar($sidebar)) : ?><?php endif; ?>
		
		<?php if(!is_front_page() && $multi):?>
			<?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('Sidebar')) : ?><?php endif; ?>
		<?php endif; ?>

</div><!--sidebar-->
