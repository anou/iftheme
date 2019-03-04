<?php global $antenna, $multi, $options, $c, $antenna_op, $if_front;

  $cat_slug = is_front_page() || is_home() ? NULL : get_cat_slug($antenna);

  if($multi){
    $sidebar = !$cat_slug ? 'Sidebar' : 'Sidebar '.$cat_slug;
  } 
  else { $sidebar = 'Sidebar';}
?>
<div id="sidebar">
      <?php  //NEWSLETTER FORM
      $wysija_embedded = 1; //default to 1 = Form NL from IFtheme is displayed

      if ($multi) {
        // Get admin categ. Only admin can configure country homepage
        $categAdmin = get_cat_if_user(1);
        $wysija_embedded = isset($options[$categAdmin]['theme_options_setting_wysija_embed']) ? $options[$categAdmin]['theme_options_setting_wysija_embed'] : $wysija_embedded;
      }
      else {
        $wysija_embedded = isset($options['theme_options_setting_wysija_embed']) ? $options['theme_options_setting_wysija_embed'] : $wysija_embedded;
      }
      // mailpoet 2
      $wysija2 = defined('WYSIJA');

      if($wysija2 && $wysija_embedded) { // mailpoet 2
        $modelList = WYSIJA::get("list","model");
        $arrayOfMailingLists = $modelList->get(false,array('is_enabled'=>1));

        foreach($arrayOfMailingLists as $k => $list){
          //remove non-public mailing list from array
          if( $list['is_public'] ) {
            $lists[] = $list['list_id'];
            $names[$list['list_id']] = $list['name'];
          }
        }

        $nbMailingLists = count($names);

        $widgetdata = array (
           'widget_id' => 'wysija-nl-iftheme',//form identifier important when many subscription forms on the same page
           'title' =>  __('Newsletter', 'iftheme'),//title of widget
           'instruction' => __('Subscribe', 'iftheme'), // instruction to be displayed on top of the widget
           'lists' =>  $lists, //array of list_id to which you want to subscribe your users
           'lists_name' => $names, //array of lists names ( array(list_id => name) )
           'submit' => __('OK','iftheme'),//name of the subscribe button
           'success' => __('You’ve successfully subscribed. Check your inbox now to confirm your subscription.', 'iftheme'),//success message returned when registered
           'customfields' => array ( //optional array of custom fields to be displayed lastname, firstname, email
                //'firstname' => array ('column_name' => 'firstname','label' => __('First name', 'iftheme')),
                //'lastname' => array ('column_name' => 'lastname','label' => __('Last name', 'iftheme')),
                'email' => array ('label' => __('Your Email', 'iftheme'))
           ),
           'labelswithin' => 'labels_within', //parameter to put the label of the custom field as a default value of the field
           'before_title' => '<h3>',
           'after_title' => '</h3>',
           'before_widget' => '<aside id="sidebar-newsletter" class="widget-sidebar bxshadow clearfix">',
           'after_widget' => '</aside>',
        );

        if( $nbMailingLists > 1 ){
           $widgetdata['autoregister'] = 'auto_register'; //the users can choose the mailing-list they want to subscribe to
        }

        $widgetNL = new WYSIJA_NL_Widget(0);//0 > display title; 1 > hide title
        $subscriptionForm = $widgetNL->widget($widgetdata,$widgetdata);

        echo $subscriptionForm;
      }
      elseif( !$wysija2 && $wysija_embedded ) { // mailpoet 3
          $widgetdata = array (
           'widget_id' => 'wysija-nl-iftheme',//form identifier important when many subscription forms on the same page
           'title' =>  __('Newsletter', 'iftheme'),//title of widget
           'instruction' => __('Subscribe', 'iftheme'), // instruction to be displayed on top of the widget
           'submit' => __('OK','iftheme'),//name of the subscribe button
           'success' => __('You’ve successfully subscribed. Check your inbox now to confirm your subscription.', 'iftheme'),//success message returned when registered
           'form' => 1,
           'form_type' => 'php',

           'labelswithin' => 'labels_within', //parameter to put the label of the custom field as a default value of the field
           'before_title' => '<h3>',
           'after_title' => '</h3>',
           'before_widget' => '<aside id="sidebar-newsletter" class="widget-sidebar bxshadow clearfix">',
           'after_widget' => '</aside>',
        );

        $form_widget = new \MailPoet\Form\Widget();
        //echo $form_widget->widget(array('form' => 1, 'form_type' => 'php'));
        echo $form_widget->widget($widgetdata);
      }
      
      ?>
    <!-- CUSTOM NL FORM -->
    <?php $custom_nl_form = false;
          $custom_nl_form = isset($options['custom_nl_form']) ? $options['custom_nl_form'] : $custom_nl_form;
          if( $custom_nl_form ) {
            the_widget( 'Nl_YMLP_Widget', array(), array('before_widget' => '<aside id="custom-newsletter" class="widget-sidebar bxshadow clearfix">', 'before_title' => '<h3>', 'after_title' => '</h3>', 'after_widget' => '</aside>') );
          }
    ?>
    <!-- SOCIAL NETWORKS -->
    <?php if($antenna != 'front'):
        $fb = isset($options['theme_options_setting_facebook']) ? $options['theme_options_setting_facebook'] : $options[(int)$antenna]['theme_options_setting_facebook'];
        $twit = isset($options['theme_options_setting_twitter']) ? $options['theme_options_setting_twitter'] : $options[(int)$antenna]['theme_options_setting_twitter'];
        $gg = isset($options['theme_options_setting_googleplus']) ? $options['theme_options_setting_googleplus'] : $options[(int)$antenna]['theme_options_setting_googleplus'];
        $iftv = isset($options['theme_options_setting_iftv']) ? $options['theme_options_setting_iftv'] : $options[(int)$antenna]['theme_options_setting_iftv'];
        $youtube = isset($options['theme_options_setting_youtube']) ? $options['theme_options_setting_youtube'] : $options[(int)$antenna]['theme_options_setting_youtube'];
        $instagram = isset($options['theme_options_setting_instagram']) ? $options['theme_options_setting_instagram'] : $options[(int)$antenna]['theme_options_setting_instagram'];

        if($fb || $twit || $gg || $iftv || $youtube || $instagram):
    ?>
      <aside id="sidebar-social" class="widget bxshadow clearfix">
        <h3><?php _e('Join-us','iftheme');?></h3>
        <ul>
        <?php if($fb):?><li id="fb"><a href="<?php echo $fb;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/fb.png" alt="facebook" /></a></li><?php endif;?>
        <?php if($twit):?><li id="twit"><a href="<?php echo $twit;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/twit.png" alt="twitter" /></a></li><?php endif;?>
        <?php if($gg):?><li id="gg"><a href="<?php echo $gg;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/gg.png" alt="google plus" /></a></li><?php endif;?>
        <?php if($youtube):?><li id="youtube"><a href="<?php echo $youtube;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/youtube.png" alt="youtube" /></a></li><?php endif;?>
        <?php if($instagram):?><li id="instagram"><a href="<?php echo $instagram;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/instagram.png" alt="instagram" /></a></li><?php endif;?>
        <?php if($iftv):?><li id="iftv"><a href="<?php echo $iftv;?>" target="_blank"><img src="<?php bloginfo('template_directory');?>/images/social/iftv.png" alt="Institut Français TV" /></a></li><?php endif;?>
        </ul>
      </aside>
    <?php endif; ?>
    <?php endif; ?>

    <!-- CALENDAR -->
    <?php $calendar_on = 1;
      if ($multi) {
        // Get admin categ. Only admin can configure country homepage
        $categAdmin = get_cat_if_user(1);
        $calendar_on = isset($options[$categAdmin]['theme_options_setting_calendar']) ? $options[$categAdmin]['theme_options_setting_calendar'] : $calendar_on;
      }
      else {
        $calendar_on = isset($options['theme_options_setting_calendar']) ? $options['theme_options_setting_calendar'] : $calendar_on;
      }
      if( $calendar_on ) :
    ?>
    <aside id="sidebar-calendar" class="widget ifworldlinks bxshadow">
      <div id="ajax_calendrier"><?php  include( get_template_directory() . '/inc/calendar/calendrier.php'); ?></div>
    </aside>
    <?php endif; ?>
    <?php //if ( !is_plugin_active( 'underconstruction/underConstruction.php' ) ):?>
      <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar($sidebar)) : ?><?php endif; ?>

      <?php if(!$if_front && $multi):?>
        <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('Sidebar')) : ?><?php endif; ?>
      <?php endif; ?>
    <?php //endif; ?>

</div><!--sidebar-->
