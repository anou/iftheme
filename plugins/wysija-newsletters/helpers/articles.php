<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_articles extends WYSIJA_object {
    function WYSIJA_help_articles() {
    }
    function getPosts($params = array()) {
        if(!empty($params['exclude'])) {
            $exclude = $params['exclude'];
        } else {
            $exclude = NULL;
        }
        if(!empty($params['include'])) {
            $include = $params['include'];
        } else {
            $include = NULL;
        }

        if(strlen($params['category_ids']) === 0) {
            $categories = NULL;
        } else {
            $categories = explode(',', $params['category_ids']);
        }
        if(!isset($params['cpt'])) $params['cpt']='post';
        $args = array(
            'numberposts'     => (int)$params['post_limit'],
            'offset'          => 0,
            'category'        => $categories,
            'orderby'         => 'post_date',
            'order'           => 'DESC',
            'include'         => $include,
            'exclude'         => $exclude,
            'meta_key'        => NULL,
            'meta_value'      => NULL,
            'post_type'       => $params['cpt'],
            'post_mime_type'  => NULL,
            'post_parent'     => NULL,
            'post_status'     => 'publish'
        );
        if(isset($params['post_date'])) {
            $args['post_date'] = $params['post_date'];
        }
        $modelPosts=&WYSIJA::get('wp_posts','model');
        $posts=$modelPosts->get_posts($args);
        if(empty($posts)) return array();
        $mConfig=&WYSIJA::get('config','model');
        foreach($posts as $key => $post) {
            if($mConfig->getValue('interp_shortcode'))    $post['post_content']=apply_filters('the_content',$post['post_content']);
            $posts[$key] = (array)$post;
        }
        return $posts;
    }
    function convertPostToBlock($post, $params = array()) {

        $defaults = array(
            'title_tag' => 'h1',
            'title_alignment' => 'left',
            'image_alignment' => 'left',
            'readmore' => __('Read online.', WYSIJA),
            'post_content' => 'full'
        );

        $params = array_merge($defaults, $params);
        if($params['post_content'] === 'full') {
            $content = $post['post_content'];
        } else {

            if(!empty($post['post_excerpt'])) {
                $content = $post['post_excerpt'];
            } else {

                $post['post_content'] = preg_replace('/\[.*\]/', '', $post['post_content']);

                $excerpts = explode('<!--more-->', $post['post_content']);
                if(count($excerpts) > 1){
                    $content = $excerpts[0];
                }else{

                    $helperToolbox =& WYSIJA::get('toolbox', 'helper');
                    $content = $helperToolbox->excerpt($post['post_content'], 60);
                }
            }

            $content = preg_replace('/<([\/])?h[123456](.*?)>/', '<$1p$2>', $content);
        }

        $content = wpautop($content);

        $content = preg_replace('/<img[^>]+./','', $content);

        $content = preg_replace('/\[.*\]/', '', $content);

        $content= preg_replace('/\<div class="wysija-register">(.*?)\<\/div>/','',$content);

        $content = $this->convertEmbeddedContent($content);

        $content = preg_replace('/<([\/])?h[456](.*?)>/', '<$1h3$2>', $content);

        $content = preg_replace('/<([\/])?ol(.*?)>/', '<$1ul$2>', $content);

        $content = str_replace(array('$', '€', '£', '¥'), array('&#36;', '&euro;', '&pound;', '&#165;'), $content);

        $content = strip_tags($content, '<p><em><span><b><strong><i><h1><h2><h3><a><ul><ol><li><br>');

        if(strlen(trim($post['post_title'])) > 0) {

            $post['post_title'] = trim(str_replace(array('$', '€', '£', '¥'), array('&#36;', '&euro;', '&pound;', '&#165;'), strip_tags($post['post_title'])));

            $content = '<'.$params['title_tag'].' class="align-'.$params['title_alignment'].'">'.  $post['post_title'].'</'.$params['title_tag'].'>'.$content;
        }

        $content .= '<p><a href="'.get_permalink($post['ID']).'" target="_blank">'.esc_attr($params['readmore']).'</a></p>';

        $post_image = null;
        if(isset($post['post_image'])) {
            $post_image = $post['post_image'];

            $post_image['alignment'] = $params['image_alignment'];

            if(empty($post_image['height']) or $post_image['height'] === 0) {
                $post_image = null;
            } else {
                $ratio = round(($post_image['width'] / $post_image['height']) * 1000) / 1000;
                switch($params['image_alignment']) {
                    case 'alternate':
                    case 'left':
                    case 'right':

                        $post_image['width'] = min($post_image['width'], 325);
                        break;
                    case 'center':

                        $post_image['width'] = min($post_image['width'], 564);
                        break;
                }
                if($ratio > 0) {

                    $post_image['height'] = (int)($post_image['width'] / $ratio);
                } else {

                    $post_image = null;
                }
            }
        }
        $block = array(
          'position' => 0,
          'type' => 'content',
          'text' => array(
              'value' => base64_encode($content)
          ),
          'image' => $post_image,
          'alignment' => $params['image_alignment']
        );
        return $block;
    }
    function getImage($post) {
        $image_info = null;
        $post_image = null;

        if(!function_exists('has_post_thumbnail')) {
            require_once(ABSPATH.WPINC.'/post-thumbnail-template.php');
        }

        if(has_post_thumbnail($post['ID'])) {
            $post_thumbnail = get_post_thumbnail_id($post['ID']);

            $image_info = wp_get_attachment_image_src($post_thumbnail, 'single-post-thumbnail');

            $altText = trim(strip_tags(get_post_meta($post_thumbnail, '_wp_attachment_image_alt', true)));
            if(strlen($altText) === 0) {

                $altText = trim(strip_tags($post['post_title']));
            }
        }
        if($image_info !== null) {
            $post_image = array(
                'src' => $image_info[0],
                'width' => $image_info[1],
                'height' => $image_info[2],
                'alt' => urlencode($altText)
            );
        } else {
            $matches = $matches2 = array();
            $output = preg_match_all('/<img.+src=['."'".'"]([^'."'".'"]+)['."'".'"].*>/i', $post['post_content'], $matches);
            if(isset($matches[0][0])){
                preg_match_all('/(src|height|width|alt)="([^"]*)"/i', $matches[0][0], $matches2);
                if(isset($matches2[1])){
                    foreach($matches2[1] as $k2 => $v2) {
                        if(in_array($v2, array('src', 'width', 'height', 'alt'))) {
                            if($post_image === null) $post_image = array();
                            if($v2 === 'alt') {

                                $post_image[$v2] = urlencode($matches2[2][$k2]);
                            } else {

                                $post_image[$v2] = $matches2[2][$k2];
                            }
                        }
                    }
                }
            }
        }
        if(isset($post_image['src'])) {

            if(array_key_exists('height', $post_image) === false || array_key_exists('width', $post_image) === false) {
                try {
                    $image_info = getimagesize($post_image['src']);
                    if($image_info !== false) {
                        $post_image['width'] = $image_info[0];
                        $post_image['height'] = $image_info[1];
                    }
                } catch(Exception $e) {
                    return null;
                }
            }
            $post_image = array_merge($post_image, array('url' => get_permalink($post['ID'])));
        } else {
            $post_image = null;
        }
        return $post_image;
    }
    function convertEmbeddedContent($content = '') {

        $content = preg_replace('#<iframe.*?src=\"(.+?)\".*><\/iframe>#', '<a href="$1">'.__('Click here to view media.', WYSIJA).'</a>', $content);

        $content = preg_replace('#http://www.youtube.com/embed/([a-zA-Z0-9_-]*)#Ui', 'http://www.youtube.com/watch?v=$1', $content);
        return $content;
    }
}