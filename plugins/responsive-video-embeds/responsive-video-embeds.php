<?php
/*
Plugin Name: Responsive Video Embeds
Version: 1.1
Plugin URI: http://www.kevinleary.net/
Description: This plugin will automatically resize video embeds, objects and other iframes in a responsive fashion.
Author: Kevin Leary
Author URI: http://www.kevinleary.net
License: GPL2

Copyright 2012 Kevin Leary  (email : info@kevinleary.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Responsive Embeds in WordPress
 *
 * Custom embed sizing for basic listing template
 */
class KplResponsiveVideoEmbeds {
	
	// Constants & variables
	
	
	/**
	 * Setup the object
	 *
	 * Attached filters and actions to hook into WordPress
	 */
	function __construct( $options = array() ) 
	{
		add_filter( 'embed_oembed_html', array($this, 'modify_embed_output'), 9999, 3);
		add_filter( 'wp_enqueue_scripts', array($this, 'load_styles') );
	}
	
	/**
	 * Embed CSS
	 *
	 * CSS needed to automatically resize embedded videos. This method was originally 
	 * invented by Anders M. Andersen at http://amobil.se/2011/11/responsive-embeds/
	 */
	function load_styles() {
		// Respects SSL, style.css is relative to the current file
		wp_enqueue_style( 'responsive-video-embeds', plugins_url('css/responsive-video-embeds.css', __FILE__), array(), '1.0' );
	}
	
	/**
	 * Add Embed Container
	 *
	 * Wrap the video embed in a container for scaling
	 */
	public function modify_embed_output( $html, $url, $attr ) {
	
		// Only run this process for embeds that don't required fixed dimensions
		$resize = false;
		$accepted_providers = array(
			'youtube',
			'vimeo',
			'slideshare',
			'dailymotion',
			'viddler.com',
			'hulu.com',
			'blip.tv',
			'revision3.com',
			'funnyordie.com',
			'wordpress.tv',
			'scribd.com'
		);
		
		// Check each provider
		foreach ( $accepted_providers as $provider ) {
			if ( strstr($url, $provider) ) {
				$resize = true;
				break;
			}
		}
		
		// Remove width and height attributes
		$attr_pattern = '/(width|height)="[0-9]*"/i';
		$whitespace_pattern = '/\s+/';
		$embed = preg_replace($attr_pattern, "", $html);
		$embed = preg_replace($whitespace_pattern, ' ', $embed); // Clean-up whitespace
		$embed = trim($embed);
		$inline_styles = ( isset( $attr['width'] ) ) ? ' style="max-width:' . absint( $attr['width'] ) . 'px;"' : '';

		// Add container around the video, use a <p> to avoid conflicts with wpautop()
		$html = '<div class="rve-embed-container"' . $inline_styles . '>';
		$html .= '<div class="rve-embed-container-inner">';
		$html .= $embed;
		$html .= "</div></div>";
		
		return $html;
	}
}

// Autoload the class
$responsive_video_embeds = new KplResponsiveVideoEmbeds();