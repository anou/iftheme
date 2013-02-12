<?php
/* ------------------- THEME FORCE ---------------------- */

/*
 * EVENTS FUNCTION - GPL & all that good stuff obviously...
 *
 *  BY http://www.noeltock.com/web-design/wordpress/custom-post-types-events-pt1/
 */


// 0. Base

add_action('admin_init', 'if_functions_css');

function if_functions_css() {
	wp_enqueue_style('if-functions-css', get_bloginfo('template_directory') . '/inc/events/css/if-functions.css');
}


// 4. Show Meta-Box

add_action( 'admin_init', 'if_events_create' );

function if_events_create() {
    add_meta_box('if_events_meta', __('Event information','iftheme'), 'if_events_meta', 'post');
    add_meta_box('if_events_mobile', __('Additional event information','iftheme'), 'if_events_mobile', 'post');
}

function if_events_meta () {

    // - grab data -

    global $post;
    $custom = get_post_custom($post->ID);
    
    $meta_sd = $custom["if_events_startdate"][0];
    $meta_ed = !empty($custom["if_events_enddate"][0]) ? $custom["if_events_enddate"][0] : NULL;
    $meta_time = $custom["if_events_time"][0]; 
    // - grab wp time format -

    $date_format = get_option('date_format'); // Not required in my code
    $time_format = get_option('time_format');

    // - populate today if empty, 00:00 for time -

    if ($meta_sd == null) { $meta_sd = time(); $meta_ed = $meta_sd; $meta_time = '00:00';}

    // - convert to pretty formats -

    $clean_sd = date("D, M d, Y", $meta_sd);
    $clean_ed = !$meta_ed ? '' : date("D, M d, Y", $meta_ed);
    //$clean_st = date($time_format, $meta_st);


    // - output -

    ?>
    <div class="if-meta clearfix">
        <ul>
            <li><label><?php _e("Start Date",'iftheme');?>&nbsp;<span style="color:red" title="<?php __("Mandatory field", 'iftheme');?>">*</span></label><input type="text" name="if_events_startdate" id="if_events_startdate" class="ifdate" value="<?php echo $clean_sd; ?>" /></li>
            <li><label><?php _e("End Date",'iftheme');?>&nbsp;<span style="color:red" title="<?php __("Mandatory field", 'iftheme');?>">*</span></label><input type="text" name="if_events_enddate" id="if_events_enddate" class="ifdate" value="<?php echo $clean_ed; ?>" /></li>
            <li><label><?php _e("Time",'iftheme');?></label><input type="text" name="if_events_time" id="if_events_time" value="<?php echo $meta_time; ?>" /><?php _e("<em>Use 24h format (7pm = 19:00). Use the \"Time field\" only if \"End date\" is equal to \"Start date\"</em>",'iftheme');?></li>
        </ul>
    </div>
    <?php
}
function if_events_mobile () {

    // - grab data -
    global $post;
    global $current_user; get_currentuserinfo();
    $antenna = get_cat_name( get_cat_if_user($current_user->ID) );
    
    $custom = get_post_custom($post->ID);

    $meta_disciplines = isset($custom["if_events_disciplines"]) ? unserialize($custom["if_events_disciplines"][0]) : array();
    $meta_lieu = $custom["if_events_lieu"][0];
    $meta_address = $custom["if_events_adresse"][0];
    $meta_address_bis = $custom["if_events_adresse_bis"][0];
    $meta_zip = $custom["if_events_zip"][0];
    $meta_city = isset($custom["if_events_city"]) ? $custom["if_events_city"][0] : $antenna;
    $meta_pays = isset($custom["if_events_pays"][0]) ? $custom["if_events_pays"][0] : array();
    $meta_long = isset($custom["if_events_long"][0]) ? $custom["if_events_long"][0] : 4.835658999999964;
    $meta_lat = isset($custom["if_events_lat"][0]) ? $custom["if_events_lat"][0] : 45.764043;
    $zoom = isset($custom["zoom"][0]) ? $custom["zoom"][0] : 11;
    //$meta_hour = $custom["if_events_hour"][0];
    $meta_tel = $custom["if_events_tel"][0];
    $meta_mail = $custom["if_events_mmail"][0];
    $meta_link1 = $custom["if_events_link1"][0];
    $meta_link2 = $custom["if_events_link2"][0];
    $meta_link3 = $custom["if_events_link3"][0];
    $meta_url = isset($custom["if_events_url"]) ? $custom["if_events_url"][0] : $post->guid;

    // - output -
  //DISCIPLINES
  $urld = 'http://api.institutfrancais.com/lib/php/api/getDiscipline.php';
  $disciplines = file_get_contents($urld);
  //Uncomment to use curl if needed
/*
  if(!$disciplines) {
    //cf. functions.php for info
    $disciplines = curl_get($urld);
  }
*/
  
  if(!$disciplines) {$msg = '<div class="warning">' . __("You must ask your server's administrator if the function <i style=\"color:red\">file_get_contents()</i> can be used without limitation", 'iftheme') . '</div>';}

  
  $disciplines_xml = new DomDocument(); // Instanciation de la classe DomDocument : création d'un nouvel objet
  $disciplines_xml->loadXML($disciplines);
  $elements = $disciplines_xml->getElementsByTagName('disciplines');
  $element = $elements->item(0); // On obtient le nœud disciplines
  $enfants = $element->childNodes; // On récupère les nœuds enfants de disciplines avec childNodes
  foreach ($enfants as $enfant){
     $id = $enfant->attributes->getNamedItem('id')->nodeValue;
     $value = $enfant->attributes->getNamedItem('lib')->nodeValue;
     $options_dis[$id] = $value;
  }
  //PAYS
  $urlp = 'http://api.institutfrancais.com/lib/php/api/getCountry.php';
  $pays = file_get_contents($urlp);
  //Uncomment to use curl if needed
/*
  if(!$disciplines) {
    //cf. functions.php for info
    $disciplines = curl_get($urlp);
  }
*/
  if(!$pays) {$msg = '<div class="warning">' . __("You must ask your server's administrator if the function <i style=\"color:red\">file_get_contents()</i> can be used without limitation", 'iftheme') . '</div>';}
  $pays_xml = new DomDocument(); // Instanciation de la classe DomDocument : création d'un nouvel objet
  $pays_xml->loadXML($pays);
  $payz = $pays_xml->getElementsByTagName('countries');
  $p = $payz->item(0); // On obtient le nœud countries
  $penfants = $p->childNodes; // On récupère les nœuds enfants de countries avec childNodes
  foreach ($penfants as $penfant) {
     $iso = $penfant->attributes->getNamedItem('iso')->nodeValue;
     $pvalue = $penfant->attributes->getNamedItem('lib')->nodeValue;
     $options_p[$iso] = $pvalue;
     asort($options_p);
  }
?>
    <div class="if-meta">
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
  function googleMaps (lat, lng, zoom) {
    geocoder = new google.maps.Geocoder();
    var myLatlng = new google.maps.LatLng(lat, lng);
    var myOptions = {
      scrollwheel: false,
      zoom: zoom,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    var marker = new google.maps.Marker({
        position: myLatlng,  
        map: map
    });
    google.maps.event.addListener(map, "center_changed", function(){
      document.getElementById("if_events_lat").value = map.getCenter().lat();
      document.getElementById("if_events_long").value = map.getCenter().lng();
      marker.setPosition(map.getCenter());
      document.getElementById("zoom").value = map.getZoom();
    });
    google.maps.event.addListener(map, "zoom_changed", function(){
      document.getElementById("zoom").value = map.getZoom();
    });
  }
</script>
<script type="text/javascript">jQuery(document).ready(function(){
  //$(".accordion").accordion();
  //$("#gmaplatlon").validate({rules:{"latitude":{number:true}, "longitude":{number:true}, "zoom":{digits:true,min:0}}, errorPlacement:errorMessages});
  jQuery("#longlat").change(function(){ geocoder.geocode({"address": jQuery(this).attr("value")}, function(results, status) { if (status == google.maps.GeocoderStatus.OK) { map.setZoom(14); map.setCenter(results[0].geometry.location); } else { alert("Geocode was not successful for the following reason: " + status); } }); });
  });
  jQuery(window).load(function(){

    googleMaps(<?php echo $meta_lat;?>,<?php echo $meta_long;?>,<?php echo $zoom;?>);
  })
</script>
    
    
    
    
        <ul>
            <li>
              <label><?php _e("Disciplines",'iftheme');?>&nbsp;<span style="color:red" title="<?php __("Mandatory field", 'iftheme');?>">*</span></label>
              <span class="description"><em><?php _e('The proposed disciplines are useful for the mobile application only and have <b>no impact on your website</b>', 'iftheme');?></em></span><br style="line-height:2.5em" />
              <div class="container-disciplines"><!-- Disciplines -->
              <?php if(!$disciplines) {echo $msg;}?>              
              <?php foreach ($options_dis as $kdis => $valdis) :?>
                <input type="checkbox" name="if_events_disciplines[]" id="if_events_disciplines-<?php echo $kdis ?>" value="<?php echo $kdis ?>" <?php checked( in_array( $kdis, $meta_disciplines ) ); ?> />&nbsp;<?php echo $valdis;?> 
              <?php endforeach;?>
              
              </div>
            </li>
            <li><label><?php _e("Place",'iftheme');?>&nbsp;<span style="color:red" title="<?php __("Mandatory field", 'iftheme');?>">*</span></label><input size="50" type="text" name="if_events_lieu" id="if_events_lieu" value="<?php echo $meta_lieu; ?>" /></li>
            <li><label><?php _e("Address",'iftheme');?></label><input size="50" type="text" name="if_events_adresse" id="if_events_adresse" value="<?php echo $meta_address; ?>" /></li>
            <li><label><?php _e("Additional address",'iftheme');?></label><input size="50" type="text" name="if_events_adresse_bis" id="if_events_adresse_bis" value="<?php echo $meta_address_bis; ?>" /></li>
            <li><label><?php _e("Zip code",'iftheme');?></label><input type="text" name="if_events_zip" id="if_events_zip" value="<?php echo $meta_zip; ?>" /></li>
            <li><label><?php _e("City",'iftheme');?>&nbsp;<span style="color:red" title="<?php __("Mandatory field", 'iftheme');?>">*</span></label><input type="text" name="if_events_city" id="if_events_city" value="<?php echo $meta_city; ?>" /></li>
            <li>
              <label><?php _e("Country",'iftheme');?>&nbsp;<span style="color:red" title="<?php __("Mandatory field", 'iftheme');?>">*</span></label>
              <select name="if_events_pays" id="if_events_pays">
                <option value=""><?php _e("Select");?></option> 
              <?php foreach ($options_p as $kp => $valp) :?>
                <option value="<?php echo $kp ?>" <?php selected( $meta_pays, $kp ); ?>><?php echo $valp;?></option> 
              <?php endforeach;?>
              </select>
              <?php if(!$pays) {echo $msg;}?>
            </li>
            <li>
              <label><?php _e("Get longitude & latitude",'iftheme');?></label><input size="50" type="text" name="longlat" id="longlat" value="" /><?php _e("<em>Type here the full address of your event. <b>Press \"tab\" key</b> to get/modify the longitude and latitude</em>",'iftheme');?><br /><br /><br /><br />
              <div id="map_canvas" style="height:200px; margin:5px 0px; clear:both"></div>
              <form id="gmaplatlon" method="post" action="">              
              <fieldset style="margin:10px 0 10px 150px"><li><label style="width:auto"><?php _e("Longitude",'iftheme');?>&nbsp;<span style="color:red" title="<?php __("Mandatory field", 'iftheme');?>">*</span></label>&nbsp;<input type="text" name="if_events_long" id="if_events_long" value="<?php echo $meta_long; ?>" />&nbsp;&nbsp;&nbsp;&nbsp;<label style="width:auto"><?php _e("Latitude",'iftheme');?>&nbsp;<span style="color:red" title="<?php __("Mandatory field", 'iftheme');?>">*</span></label>&nbsp;<input type="text" name="if_events_lat" id="if_events_lat" value="<?php echo $meta_lat; ?>" />&nbsp;<label for="zoom" style="width:auto"><?php _e("Zoom",'iftheme');?></label><input type="text" id="zoom" name="zoom" style="width:25px;" maxlength="50" value="<?php echo $zoom; ?>"></fieldset>
              </form>
            </li>
            
            <!--<li><label><?php //_e("Schedules",'iftheme');?></label><input size="50" type="text" name="if_events_hour" id="if_events_hour" value="<?php //echo $meta_hour; ?>" /></li>-->
            <li><label><?php _e("Phone number",'iftheme');?></label><input type="text" name="if_events_tel" id="if_events_tel" value="<?php echo $meta_tel; ?>" /></li>
            <li><label><?php _e("Email", 'iftheme');?></label><input type="text" name="if_events_mmail" id="if_events_mmail" value="<?php echo $meta_mail; ?>" /></li>
            <li><label><?php _e("Link",'iftheme');?> 1</label><input size="50" type="text" name="if_events_link1" id="if_events_link1" value="<?php echo $meta_link1; ?>" /></li>
            <li><label><?php _e("Link",'iftheme');?> 2</label><input size="50" type="text" name="if_events_link2" id="if_events_link2" value="<?php echo $meta_link2; ?>" /></li>
            <li><label><?php _e("Link",'iftheme');?> 3</label><input size="50" type="text" name="if_events_link3" id="if_events_link3" value="<?php echo $meta_link3; ?>" /></li>
            
            <li style="display:none"><!-- post url --><input type="hidden" name="if_events_url" id="if_events_url" value="<?php echo $post->guid; ?>" /></li>
            <li>&nbsp;<span style="color:red" title="<?php __("Mandatory field", 'iftheme');?>">*</span>&nbsp;<em style="color:black"><?php _e("Mandatory fields", 'iftheme');?></em></li>
        </ul>
    </div>
    <?php
}

// 5. Save Data

add_action ('save_post', 'save_if_events');

function save_if_events() {

    global $post;
    //$meta_dis = get_post_meta($post->ID, 'if_events_disciplines');
    
    if ( !current_user_can( 'edit_posts' ) )
    	return $post->ID;

    // - convert back to unix & update post

    if(isset($_POST["if_events_startdate"])):
     $updatestartd = strtotime ( $_POST["if_events_startdate"] );
     update_post_meta($post->ID, "if_events_startdate", $updatestartd );
    endif;

    if(isset($_POST["if_events_enddate"])):
        $updateendd = strtotime ( $_POST["if_events_enddate"]);
        
        if($updateendd < $updatestartd) $updateendd = $updatestartd;
        
        update_post_meta($post->ID, "if_events_enddate", $updateendd );
    endif;

    if(isset($_POST["if_events_time"])):
        $updatetime = $_POST["if_events_time"];
        update_post_meta($post->ID, "if_events_time", $updatetime );
    endif;
    
    //disciplines
    if(isset($_POST["if_events_disciplines"])):
      $updatedis = $_POST["if_events_disciplines"];
      update_post_meta($post->ID, "if_events_disciplines", $updatedis );
    endif;
    
    if(isset($_POST["if_events_lieu"])):
        $updatelieu = $_POST["if_events_lieu"];
        update_post_meta($post->ID, "if_events_lieu", $updatelieu );
    endif;
    if(isset($_POST["if_events_adresse"])):
        $updateadresse = $_POST["if_events_adresse"];
        update_post_meta($post->ID, "if_events_adresse", $updateadresse );
    endif;
    if(isset($_POST["if_events_adresse_bis"])):
        $updateadressebis = $_POST["if_events_adresse_bis"];
        update_post_meta($post->ID, "if_events_adresse_bis", $updateadressebis );
    endif;
    if(isset($_POST["if_events_zip"])):
        $updatezip = $_POST["if_events_zip"];
        update_post_meta($post->ID, "if_events_zip", $updatezip );
    endif;
    if(isset($_POST["if_events_city"])):
        $updatecity = $_POST["if_events_city"];
        update_post_meta($post->ID, "if_events_city", $updatecity );
    endif;
    if(isset($_POST["if_events_pays"])):
        $updatepays = $_POST["if_events_pays"];
        update_post_meta($post->ID, "if_events_pays", $updatepays );
    endif;
    if(isset($_POST["if_events_long"])):
        $updatelong = $_POST["if_events_long"];
        update_post_meta($post->ID, "if_events_long", $updatelong );
    endif;
    if(isset($_POST["if_events_lat"])):
        $updatelat = $_POST["if_events_lat"];
        update_post_meta($post->ID, "if_events_lat", $updatelat );
    endif;
    if(isset($_POST["zoom"])):
        $updatezoom = $_POST["zoom"];
        update_post_meta($post->ID, "zoom", $updatezoom );
    endif;
    if(isset($_POST["if_events_hour"])):
        $updatehour = $_POST["if_events_hour"];
        update_post_meta($post->ID, "if_events_hour", $updatehour );
    endif;
    if(isset($_POST["if_events_tel"])):
        $updatetel = $_POST["if_events_tel"];
        update_post_meta($post->ID, "if_events_tel", $updatetel );
    endif;
    if(isset($_POST["if_events_mmail"])):
        $updatemail = $_POST["if_events_mmail"];
        update_post_meta($post->ID, "if_events_mmail", $updatemail );
    endif;
    if(isset($_POST["if_events_link1"])):
        $updatelink1 = $_POST["if_events_link1"];
        update_post_meta($post->ID, "if_events_link1", $updatelink1 );
    endif;
    if(isset($_POST["if_events_link2"])):
        $updatelink2 = $_POST["if_events_link2"];
        update_post_meta($post->ID, "if_events_link2", $updatelink2 );
    endif;
    if(isset($_POST["if_events_link3"])):
        $updatelink3 = $_POST["if_events_link3"];
        update_post_meta($post->ID, "if_events_link3", $updatelink3 );
    endif;



    if(isset($_POST["if_events_url"])):
        $updateurl = $_POST["if_events_url"];
        update_post_meta($post->ID, "if_events_url", $updateurl );
    endif;

}

// 6. Customize Update Messages

add_filter('post_updated_messages', 'events_updated_messages');

function events_updated_messages( $messages ) {

  global $post, $post_ID;

  $messages['post'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Event updated. <a href="%s">View item</a>','iftheme'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.','iftheme'),
    3 => __('Custom field deleted.','iftheme'),
    4 => __('Event updated.','iftheme'),
    // translators: %s: date and time of the revision 
    5 => isset($_GET['revision']) ? sprintf( __('Event restored to revision from %s','iftheme'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Event published. <a href="%s">View event</a>','iftheme'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Event saved.','iftheme'),
    8 => sprintf( __('Event submitted. <a target="_blank" href="%s">Preview event</a>','iftheme'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>','iftheme'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i','iftheme'), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Event draft updated. <a target="_blank" href="%s">Preview event</a>','iftheme'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );

  return $messages;
}

// 7. JS Datepicker UI

function events_styles() {
    global $post_type;
    if( 'post' != $post_type )
        return;
    wp_enqueue_style('ui-datepicker', get_bloginfo('template_url') . '/inc/events/css/jquery-ui-1.8.9.custom.css');
}

function events_scripts() {
    global $post_type;
    if( 'post' != $post_type )
    return;
    //wp_enqueue_script('jquery-ui', get_bloginfo('template_url') . '/inc/events/js/jquery-ui-1.8.9.custom.min.js', array('jquery'));

    wp_enqueue_script('ui-datepicker', get_bloginfo('template_url') . '/inc/events/js/jquery.ui.datepicker.js', array('jquery'));
		
		wp_enqueue_script('jquery-validate', get_bloginfo('template_url') . '/inc/events/js/jquery.validate.min.js', array('jquery'), '1.8.1',true);
    
    wp_enqueue_script('custom_script', get_bloginfo('template_url').'/inc/events/js/ifevents-admin.js', array('jquery'));
    wp_localize_script( 'custom_script', 'objectL10n', array(
  			'place' => __("You must tell where this event takes place.", 'iftheme'),
  			'mandatory' => __("Mandatory field", 'iftheme'),
  			'long' => __("Longitude is required and must be a valid decimal", 'iftheme'),
  			'lat' => __("Latitude is required and must be a valid decimal", 'iftheme'),
  			'httpurl' => __('Must be like: <b>http://www.my-link.ext</b>','iftheme'),
  			'disciplines' => __('You must check at least one discipline !','iftheme'),
        ) 
    );
}

add_action( 'admin_print_styles-post.php', 'events_styles', 1000 );
add_action( 'admin_print_styles-post-new.php', 'events_styles', 1000 );

add_action( 'admin_print_scripts-post.php', 'events_scripts', 1000 );
add_action( 'admin_print_scripts-post-new.php', 'events_scripts', 1000 );


?>
