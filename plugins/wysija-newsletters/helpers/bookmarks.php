<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_bookmarks extends WYSIJA_object {
    function WYSIJA_help_bookmarks() {
    }
    
    function getAll($size = 'medium', $theme = 'default') {
        $fileHelper =& WYSIJA::get('file', 'helper');
        $dirHandle = $fileHelper->exists('bookmarks'.DS.$size);
        if($dirHandle['result'] === FALSE) {
            return array();
        } else {
            $bookmarks = array();

            if($size === 'medium' and $theme !== 'default') {
                $themeIcons = $this->getAllByTheme($theme, 'url');
                if(!empty($themeIcons)) {
                    $bookmarks['00'] = $themeIcons;
                }
            }
            $sourceDir = $dirHandle['file'];
            $iconsets = scandir($sourceDir);
            foreach($iconsets as $iconset) {

                if(in_array($iconset, array('.', '..', '.DS_Store', 'Thumbs.db')) === FALSE and is_dir($sourceDir.DS.$iconset)) {

                    $icons = scandir($sourceDir.DS.$iconset);
                    foreach($icons as $icon) {
                        if(in_array($icon, array('.', '..', '.DS_Store', 'Thumbs.db')) === FALSE and strrpos($icon, '.txt') === FALSE) {
                            $info = pathinfo($sourceDir.DS.$iconset.DS.$icon);
                            $bookmarks[$iconset][basename($icon, '.'.$info['extension'])] = $fileHelper->url($icon, 'bookmarks'.DS.$size.DS.$iconset);
                        }
                    }
                }
            }
            return $bookmarks;
        }
    }
    
    function getAllByIconset($size = 'medium', $iconset)
    {
        $fileHelper =& WYSIJA::get('file', 'helper');
        $dirHandle = $fileHelper->exists('bookmarks'.DS.$size.DS.$iconset);
        if($dirHandle['result'] === FALSE) {
            return array();
        } else {
            $bookmarks = array();
            $sourceDir = $dirHandle['file'];
            $icons = scandir($sourceDir);
            foreach($icons as $icon) {
                if(in_array($icon, array('.', '..', '.DS_Store', 'Thumbs.db')) === FALSE and strrpos($icon, '.txt') === FALSE) {
                    $info = pathinfo($sourceDir.DS.$icon);
                    $dimensions = @getimagesize($sourceDir.DS.$icon);
                    $bookmarks[basename($icon, '.'.$info['extension'])] = array(
                        'src' => $fileHelper->url($icon, 'bookmarks/'.$size.'/'.$iconset),
                        'width' => $dimensions[0],
                        'height' => $dimensions[1]
                    );
                }
            }
            return $bookmarks;
        }
    }
    function getAllByTheme($theme, $type = 'all')
    {
        $fileHelper =& WYSIJA::get('file', 'helper');
        $dirHandle = $fileHelper->exists('themes'.DS.$theme.DS.'bookmarks');
        if($dirHandle['result'] === FALSE) {
            return array();
        } else {
            $bookmarks = array();
            $sourceDir = $dirHandle['file'];
            $icons = scandir($sourceDir);
            foreach($icons as $icon) {
                if(in_array($icon, array('.', '..', '.DS_Store', 'Thumbs.db')) === FALSE and strrpos($icon, '.txt') === FALSE) {
                    if($type === 'all') {
                        $info = pathinfo($sourceDir.DS.$icon);
                        $dimensions = @getimagesize($sourceDir.DS.$icon);
                        $bookmarks[basename($icon, '.'.$info['extension'])] = array(
                            'src' => $fileHelper->url($icon, 'themes/'.$theme.'/bookmarks'),
                            'width' => $dimensions[0],
                            'height' => $dimensions[1]
                        );
                    } else if($type === 'url') {
                        $info = pathinfo($sourceDir.DS.$icon);
                        $bookmarks[basename($icon, '.'.$info['extension'])] = $fileHelper->url($icon, 'themes/'.$theme.'/bookmarks');
                    }
                }
            }
            return $bookmarks;
        }
    }
}