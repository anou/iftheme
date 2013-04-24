<?php 
/*
Plugin Name: WPML Translation Management
Plugin URI: http://wpml.org/
Description: Add a complete translation process for WPML. <a href="http://wpml.org">Documentation</a>.
Author: ICanLocalize
Author URI: http://wpml.org
Version: 1.6
*/

if(defined('WPML_TM_VERSION')) return;

define('WPML_TM_VERSION', '1.6');
define('WPML_TM_PATH', dirname(__FILE__));

require WPML_TM_PATH . '/inc/constants.php';
require WPML_TM_PATH . '/inc/wpml-translation-management.class.php';

$WPML_Translation_Management = new WPML_Translation_Management;