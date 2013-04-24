<?php
/*
Plugin Name: WPML String Translation
Plugin URI: http://wpml.org/
Description: Adds theme and plugins localization capabilities to WPML. <a href="http://wpml.org">Documentation</a>.
Author: ICanLocalize
Author URI: http://wpml.org
Version: 1.7
*/

if(defined('WPML_ST_VERSION')) return;

define('WPML_ST_VERSION', '1.7');
define('WPML_ST_PATH', dirname(__FILE__));

require WPML_ST_PATH . '/inc/constants.php';
require WPML_ST_PATH . '/inc/wpml-string-translation.class.php';
require WPML_ST_PATH . '/inc/widget-text.php';

$WPML_String_Translation = new WPML_String_Translation;
?>