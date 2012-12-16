<?php
/*
 * Multilingual text widget
 */
add_action('plugins_loaded', 'icl_widget_text_init', 11);

function icl_widget_text_init() {
    if (defined('ICL_SITEPRESS_VERSION') && !ICL_PLUGIN_INACTIVE) {
        add_action('widgets_init', 'icl_widget_text_widgets_init_hook');
        add_action('in_widget_form', 'icl_widget_text_in_widget_form_hook', 10, 3);
    }
}

function icl_widget_text_widgets_init_hook() {
    register_widget('WP_Widget_Text_Icl');
}

function icl_widget_text_in_widget_form_hook($widget, $return, $instance) {
    if ($widget->name == 'Text') {
        // Convert if necessary
        if ($widget->updated && isset($_POST['icl_convert'])) {
            if (icl_widget_text_convert_to_multilingual($widget, $instance) === TRUE) {
                _e('This widget is converted to multilingual', 'sitepress');
            }
            return '';
        }
        // Display form
        if (!icl_widget_text_is_converted($widget)) {
            icl_widget_text_language_selectbox();
            echo '<label><input type="checkbox" name="icl_convert" value="1" />&nbsp;'
            . __('Convert to multilingual widget', 'sitepress') . '</label>';
        } else {
            _e('This widget is converted to multilingual', 'sitepress');
        }
    }
}

function icl_widget_text_language_selectbox($language = 'multilingual',
        $field_name = 'icl_language') {
    global $sitepress;
    $languages = $sitepress->get_active_languages();
    echo '<select name="' . $field_name . '"><option value="multilingual"';
    echo $language == 'multilingual' ? ' selected="selected"' : '';
    echo '>Multilingual</option>';
    if (!empty($languages)) {
        foreach ($languages as $lang) {
            echo '<option value="' . $lang['code'] . '"';
            echo $language == $lang['code'] ? ' selected="selected"' : '';
            echo '>' . $lang['display_name'] . '</option>';
        }
    }
    echo '</select>';
}

function icl_widget_text_is_converted($widget) {
    $widgets = get_option('widget_text_icl', array());
    foreach ($widgets as $icl_widget) {
        if (isset($icl_widget['icl_converted_from'])
                && $icl_widget['icl_converted_from'] == $widget->id) {
            return TRUE;
        }
    }
    return FALSE;
}

function icl_widget_text_convert_to_multilingual($text_widget, $instance) {
    global $wp_widget_factory;
    $icl_widget = $wp_widget_factory->widgets['WP_Widget_Text_Icl'];
    $number = $icl_widget->number + 1;
    $icl_widget->_set($number);
    $icl_widget->_register_one($number);

    // Get in which sidebar
    $sidebars = wp_get_sidebars_widgets();
    if (!isset($_POST['sidebar']) || !isset($sidebars[$_POST['sidebar']])) {
        _e('Converting to multilingual widget failed. No sidebar specified.', 'sitepress');
        return FALSE;
    }

    // Add new instance
    $icl_widgets_text = get_option('widget_text_icl', array());
    if (isset($icl_widgets_text[$icl_widget->number])) {
        _e('Widget is already converted', 'sitepress');
        return FALSE;
    }
    unset($icl_widgets_text['_multiwidget']);
    $_POST['icl_language'] = isset($_POST['icl_language']) ? $_POST['icl_language'] : 'multilingual';
    $icl_widgets_text[$icl_widget->number] = array(
        'icl_language' => $_POST['icl_language'],
        'icl_converted_from' => $text_widget->id,
        'title' => $instance['title'],
        'text' => $instance['text'],
        'filter' => isset($new_instance['filter']),
    );
    $icl_widgets_text['_multiwidget'] = 1;
    update_option('widget_text_icl', $icl_widgets_text);

    // Set in sidebar
    $sidebars[$_POST['sidebar']][] = $icl_widget->id;
    wp_set_sidebars_widgets($sidebars);

    // Register strings
    if ($_POST['icl_language'] == 'multilingual') {
        icl_register_string('Widgets', 'widget title', $instance['title']);
        icl_register_string('Widgets', 'widget body - ' . $icl_widget->id, $instance['text']);
    }

    // Refresh
    echo '
<script type="text/javascript">
<!--
window.location = "' . admin_url('widgets.php') . '"
//-->
</script>
';
    return TRUE;
}

class WP_Widget_Text_Icl extends WP_Widget
{

    function WP_Widget_Text_Icl() {
        $widget_ops = array('classname' => 'widget_text_icl', 'description' => __('Multilingual arbitrary text or HTML', 'sitepress'));
        $control_ops = array('width' => 400, 'height' => 350);
        $this->WP_Widget('text_icl', __('Multilingual Text', 'sitepress'), $widget_ops, $control_ops);
    }

    function widget($args, $instance) {
        extract($args);
        if ($instance['icl_language'] != 'multilingual' && $instance['icl_language'] != ICL_LANGUAGE_CODE) {
            return '';
        } else if ($instance['icl_language'] == 'multilingual' && function_exists('icl_t')) {
            // Get translations
            $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
            remove_filter('widget_text', 'icl_sw_filters_widget_text');
            $text = apply_filters('widget_text', icl_t('Widgets', 'widget body - ' . $this->id, $instance['text']), $instance);
            add_filter('widget_text', 'icl_sw_filters_widget_text');
        } else {
            remove_filter('widget_title', 'icl_sw_filters_widget_title');
            remove_filter('widget_text', 'icl_sw_filters_widget_text');
            $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
            $text = apply_filters('widget_text', $instance['text'], $instance);
            add_filter('widget_title', 'icl_sw_filters_widget_title');
            add_filter('widget_text', 'icl_sw_filters_widget_text');
        }
        echo $before_widget;
        if (!empty($title)) {
            echo $before_title . $title . $after_title;
        }

?>
        <div class="textwidget"><?php echo $instance['filter'] ? wpautop($text) : $text; ?></div>
<?php
        echo $after_widget;
    }

    function update($new_instance, $old_instance) {
        global $wpdb;
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        if (current_user_can('unfiltered_html'))
            $instance['text'] = $new_instance['text'];
        else
            $instance['text'] = stripslashes(wp_filter_post_kses(addslashes($new_instance['text']))); // wp_filter_post_kses() expects slashed
 $instance['filter'] = isset($new_instance['filter']);

        if ($new_instance['icl_language'] == 'multilingual') {
            $string = $wpdb->get_row($wpdb->prepare("SELECT id, value, status FROM {$wpdb->prefix}icl_strings WHERE context=%s AND name=%s", 'Widgets', 'widget body - ' . $this->id));
            if ($string) {
                icl_st_update_string_actions('Widgets', 'widget body - ' . $this->id, $old_instance['text'], $instance['text']);
            } else {
                icl_register_string('Widgets', 'widget body - ' . $this->id, $instance['text']);
            }
        }
        $instance['icl_language'] = $new_instance['icl_language'];
        return $instance;
    }

    function form($instance) {
        $instance = wp_parse_args((array) $instance, array(
                    'title' => '',
                    'text' => '',
                    'icl_language' => 'multilingual',
                    'icl_converted_from' => -1));
        $title = strip_tags($instance['title']);
        $text = esc_textarea($instance['text']);
        $language = $instance['icl_language'];

?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

        <textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

        <p><input id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" type="checkbox" <?php checked(isset($instance['filter']) ? $instance['filter'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _e('Automatically add paragraphs'); ?></label></p>
<?php
        icl_widget_text_language_selectbox($language, $this->get_field_name('icl_language'));
    }

}