<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_view_front extends WYSIJA_view{
    var $controller="";
    function WYSIJA_view_front(){

    }

    function addScripts($print=true){
        if($print){
            //wp_print_styles('validate-engine-css');

            /*in some case we don't want to have an ajax subscription*/
            $modelC=&WYSIJA::get('config','model');
            add_action('wp_footer', array($this,'printScripts'));
        }else{
            wp_enqueue_script('wysija-validator-lang');
            wp_enqueue_script('wysija-validator');
            wp_enqueue_script('wysija-form');
            wp_enqueue_style('validate-engine-css');

        }


    }

    function printScripts(){

        wp_print_scripts('wysija-validator-lang');
        wp_print_scripts('wysija-validator');
        wp_print_scripts('wysija-front-subscribers');

    }

}