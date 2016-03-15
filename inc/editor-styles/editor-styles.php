<?php
/*
Plugin Name: Custom Styles
Plugin URI: http://www.speckygeek.com
Description: Add custom styles in your posts and pages content using TinyMCE WYSIWYG editor. The plugin adds a Styles dropdown menu in the visual post editor.
Based on TinyMCE Kit plug-in for WordPress

http://plugins.svn.wordpress.org/tinymce-advanced/branches/tinymce-kit/tinymce-kit.php

*/

/**
 * Add "Styles" drop-down
 */ 
add_filter( 'mce_buttons_2', 'if_mce2_editor_buttons' );
function if_mce2_editor_buttons( $buttons ) {
    array_unshift( $buttons, 'styleselect' );
    return $buttons;
}

add_filter( 'mce_buttons', 'if_mce_editor_buttons' );
function if_mce_editor_buttons( $buttons ) {

    return $buttons;
}

/**
 * Add styles/classes to the "Styles" drop-down
 */ 
add_filter( 'tiny_mce_before_init', 'if_mce_before_init' );

function if_mce_before_init( $settings ) {

    $style_formats = array(
        array(
            'title' => 'Chapeau',
            'block' => 'p',
            'classes' => 'chapo',
            ),

        array(
            'title' => 'Note',
            'inline' => 'span',
            'classes' => 'note',
            ),

        array(
            'title' => 'Encadré',
            'block' => 'div',
            'classes' => 'framed',
            'wrapper' => true
            ),

/*
        array(
            'title' => 'Chapeau',
            'inline' => 'span',
            'styles' => array(
                'color' => '#ff0000',
                'fontWeight' => 'bold',
                'textTransform' => 'uppercase'
            )
        )
*/
    );

    $settings['style_formats'] = json_encode( $style_formats );

    return $settings;

}

/* Learn TinyMCE style format options at http://www.tinymce.com/wiki.php/Configuration:formats */

?>
