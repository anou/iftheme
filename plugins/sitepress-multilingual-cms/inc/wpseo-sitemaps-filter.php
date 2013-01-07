<?php

class WPSEO_XML_Sitemaps_Filter {

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'wpseo_sitemap_index', array( $this, 'add_to_index' ) );				
	}

    public function init(){
		global $sitepress_settings, $sitepress;
		$options = get_option('wpseo_xml');
		
		add_filter('wpseo_typecount_join', array( $this, 'typecount_join' ), 10, 2 );
		add_filter('wpseo_typecount_where', array( $this, 'typecount_where' ), 10, 2 );
		add_filter('wpseo_posts_join', array( $this, 'posts_join' ), 10, 2 );
		add_filter('wpseo_posts_where', array( $this, 'posts_where' ), 10, 2 );
		add_filter('wpseo_stylesheet_url', array( $this, 'set_stylesheet' ) );
		add_filter('wpseo_build_sitemap_post_type', array( $this, 'get_post_type' ));
        
    }
    
    /**
     * Adds active languages sitemap links to sitemap_index.xml 
     * @param type $str
     */
	function add_to_index( $str ) {
		global $sitepress, $sitepress_settings, $wpdb;
		$options = get_wpseo_options();
		$default_language = $sitepress->get_default_language();
		
		foreach($sitepress->get_active_languages() as $lang_code => $array){
			if(isset($sitepress_settings['language_domains'][$lang_code])){
				$home_url = $sitepress_settings['language_domains'][$lang_code];
			} else {
				$home_url = home_url();
			}
			
			foreach (get_post_types(array('public' => true)) as $post_type) {
				$sitepress->switch_lang($lang_code);
				$count = get_posts(array('post_type' => $post_type, 'post_status' => 'publish', 'suppress_filters' => 0));
				$sitepress->switch_lang(null);

				if(count($count) > 0 && $sitepress->is_translated_post_type($post_type)){
					if (!isset($options['post_types-'.$post_type.'-not_in_sitemap']) && $lang_code !== $default_language){
						$filename = $post_type .'-'.  $lang_code .'-sitemap.xml';
						$date = $this->get_last_mod_date($post_type, $lang_code);
						$str .= '<sitemap>' . "\n";
						$str .= '<loc>' . $home_url . '/' . $filename . '</loc>' . "\n";
						$str .= '<lastmod>' . $date . '</lastmod>' . "\n";
						$str .= '</sitemap>' . "\n";
					}
				}
			}
			
			foreach ( get_taxonomies( array('public' => true) ) as $tax ) {				
				$sitepress->switch_lang($lang_code);
				$count = get_terms($tax, array('suppress_filters' => 0));
				$sitepress->switch_lang(null);
				
				if ( count($count) > 0 && $sitepress->is_translated_taxonomy($tax)){
					if (!isset($options['taxonomies-'.$tax.'-not_in_sitemap']) && $lang_code !== $default_language){
						$filename = $tax .'-'. $lang_code .'-sitemap.xml';
						$date = $this->get_last_mod_date('post', $lang_code);
						$str .= '<sitemap>' . "\n";
						$str .= '<loc>' . $home_url . '/' . $filename . '</loc>' . "\n";
						$str .= '<lastmod>' . $date . '</lastmod>' . "\n";
						$str .= '</sitemap>' . "\n";
					}
				}
			}
		}
		
		return $str;
    }
	
    /**
     * Filters WPSEO typecount SQL query
     */
    function typecount_join($join, $post_type){
    	global $wpdb, $sitepress;

        if($sitepress->is_translated_post_type($post_type)){
    	    $join .= " INNER JOIN {$wpdb->prefix}icl_translations 
    	              ON $wpdb->posts.ID = {$wpdb->prefix}icl_translations.element_id";
        }
    	
    	return $join;
    }
    
    function typecount_where($where, $post_type){
    	global $wpdb, $sitepress;
    	$sitemap_language = $this->get_sitemap_language();
    	
        if($sitepress->is_translated_post_type($post_type)){
    	    $where .= " AND {$wpdb->prefix}icl_translations.language_code = '{$sitemap_language}'
                        AND {$wpdb->prefix}icl_translations.element_type = 'post_{$post_type}'";
        }
    	
    	return $where;
    }
    
    /**
     * Filters WPSEO posts query
     */
	function posts_join($join, $post_type){
    	global $wpdb, $sitepress;

        if($sitepress->is_translated_post_type($post_type)){
    	    $join .= " INNER JOIN {$wpdb->prefix}icl_translations 
    	               ON $wpdb->posts.ID = {$wpdb->prefix}icl_translations.element_id";
        }
    	
    	return $join;
	}
	
    function posts_where($where, $post_type){
    	global $wpdb, $sitepress;
        
        if($sitepress->is_translated_post_type($post_type)){
    	    $sitemap_language = $this->get_sitemap_language();
    	    
    	    $where .= " AND {$wpdb->prefix}icl_translations.language_code = '{$sitemap_language}' 
                        AND {$wpdb->prefix}icl_translations.element_type = 'post_{$post_type}'";
        }
    	
    	return $where;
    }
	
    /**
     * Filters XML sitemap stylesheet 
     */
    function set_stylesheet(){
    	global $sitepress_settings;
    	
    	if(@$sitepress_settings['language_domains'][$this->get_sitemap_language()]){
	    	$language_domain = $sitepress_settings['language_domains'][$this->get_sitemap_language()];
			$wpseo_dirname = str_replace('wp-seo.php', '', WPSEO_BASENAME);
			$wpseo_domain_path = $language_domain . '/wp-content/plugins/' . $wpseo_dirname;
			
			$this->stylesheet = '<?xml-stylesheet type="text/xsl" href="'.$wpseo_domain_path.'css/xml-sitemap.xsl"?>';
    	} else {
    		$this->stylesheet = '<?xml-stylesheet type="text/xsl" href="'.WPSEO_FRONT_URL.'css/xml-sitemap.xsl"?>';
    	}
    	
    	return $this->stylesheet;
    }
	
	/**
	 * Get post type from sitemap name
	 */
	function get_post_type($post_type){
		if($post_type !== '1'){ // sitemap_index.xml 
			$get_sitemap_name = basename($_SERVER['REQUEST_URI']);
			$post_type = explode("-", $get_sitemap_name);	
			$post_type = $post_type[0];
		}
		
		return $post_type;
	}
	
	/**
	 * Get sitemap language from sitemap name
	 */
	function get_sitemap_language(){
		global $sitepress;
		
		$get_sitemap_name = basename($_SERVER['REQUEST_URI']);
		$sitemap_language = explode("-", $get_sitemap_name);

		if(isset($sitemap_language[1])){
			$sitemap_language = $sitemap_language[1];
		}
		
		foreach($sitepress->get_active_languages() as $language_code => $array){
			$active_languages[] = $language_code;
		}
		
		if(!in_array($sitemap_language, $active_languages)){
			$sitemap_language = $sitepress->get_default_language();
		}
		
		return $sitemap_language;
	}
	
	/**
	 * Get last sitemap post type modified date by language
	 * @param type $post_type 
	 * @param type $language_code
	 */
	function get_last_mod_date($post_type, $language_code){
		global $wpdb;
		
		$date = $wpdb->get_var( "SELECT post_modified_gmt, ID FROM $wpdb->posts 
		INNER JOIN {$wpdb->prefix}icl_translations
		ON $wpdb->posts.ID = {$wpdb->prefix}icl_translations.element_id
		WHERE $wpdb->posts.post_status = 'publish' 
		AND $wpdb->posts.post_type = '$post_type' 
		AND {$wpdb->prefix}icl_translations.language_code = '$language_code'
		ORDER BY post_modified_gmt DESC LIMIT 1 OFFSET 0");
		
		$date = strtotime($date);
		$date = date( 'c', $date );
		
		if(!isset($date)){
			$result = strtotime( get_lastpostmodified( 'gmt' ) );
			$date = date( 'c', $result );
		}
		
		return $date;
	}
}

$wpseo_xml_filter = new WPSEO_XML_Sitemaps_Filter();