<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_themes extends WYSIJA_object{
    var $extensions = array('png', 'jpg', 'jpeg', 'gif');
    function WYSIJA_help_themes(){
    }
    
    function getInstalled(){
        $helperF=&WYSIJA::get('file','helper');
        $filenameres=$helperF->exists('themes');
        if(!$filenameres['result']) {
            return array();
        }
        $installedThemes = array();
        $files = scandir($filenameres['file']);
        foreach($files as $filename){
            if(!in_array($filename, array('.','..','.DS_Store','Thumbs.db','__MACOSX')) && is_dir($filenameres['file'].DS.$filename) && file_exists($filenameres['file'].DS.$filename.DS.'style.css')){
                $installedThemes[]=$filename;
            }
        }
        return $installedThemes;
    }
    
    function getInformation($theme) {

        $fileHelper =& WYSIJA::get('file', 'helper');

        $thumbnail = NULL;
        for($i = 0; $i < count($this->extensions); $i++) {

            $result = $fileHelper->exists('themes'.DS.$theme.DS.'thumbnail.'.$this->extensions[$i]);
            if($result['result'] !== FALSE){
                $thumbnail = $fileHelper->url('thumbnail.'.$this->extensions[$i], 'themes'.DS.$theme);
            }
        }

        $screenshot = NULL;
        $width = $height = 0;
        for($i = 0; $i < count($this->extensions); $i++) {

            $result = $fileHelper->exists('themes'.DS.$theme.DS.'screenshot.'.$this->extensions[$i]);
            if($result['result'] !== FALSE){
                $screenshot = $fileHelper->url('screenshot.'.$this->extensions[$i], 'themes'.DS.$theme);
                $dimensions = @getimagesize($result['file']);
                if($dimensions !== FALSE) {
                    list($width, $height) = $dimensions;
                }
            }
        }
        return array(
            'name' => $theme,
            'thumbnail' => $thumbnail,
            'screenshot' => $screenshot,
            'width' => $width,
            'height' => $height
        );
    }

    function getStylesheet($theme)
    {
        $fileHelper =& WYSIJA::get('file', 'helper');
        $result = $fileHelper->exists('themes'.DS.$theme.DS.'style.css');
        if($result['result'] === FALSE) {
            return NULL;
        } else {
            $stylesheet = file_get_contents($result['file']);

            $stylesheet = preg_replace('/[\n|\t|\'|\"]/', '', $stylesheet);

            $stylesheet = preg_replace('/[\s]+/', ' ', $stylesheet);
            return $stylesheet;
        }
    }
    function getData($theme)
    {

        $this->extensions = array('png', 'jpg', 'jpeg', 'gif');
        $fileHelper =& WYSIJA::get('file', 'helper');

        $header = NULL;
        for($i = 0; $i < count($this->extensions); $i++) {

            $result = $fileHelper->exists('themes'.DS.$theme.DS.'images'.DS.'header.'.$this->extensions[$i]);
            if($result['result'] !== FALSE) {
                $dimensions = @getimagesize($result['file']);
                if($dimensions !== FALSE and count($dimensions) >= 2) {

                    list($width, $height) = $dimensions;
                    $ratio = round(($width / $height) * 1000) / 1000;
                    $width = 600;
                    $height = (int)($width / $ratio);

                    $header = array(
                        'alignment' => 'center',
                        'type' => 'header',
                        'text' => null,
                        'image' => array(
                            'src' => $fileHelper->url('header.'.$this->extensions[$i], 'themes'.DS.$theme.DS.'images'),
                            'width' => $width,
                            'height' => $height,
                            'url' => null,
                            'alt' => __("Header", WYSIJA),
                            'alignment' => 'center'
                        )
                    );
                }
            }
        }

        $footer = NULL;
        for($i = 0; $i < count($this->extensions); $i++) {

            $result = $fileHelper->exists('themes'.DS.$theme.DS.'images'.DS.'footer.'.$this->extensions[$i]);
            if($result['result'] !== FALSE) {
                $dimensions = @getimagesize($result['file']);
                if($dimensions !== FALSE and count($dimensions) >= 2) {

                    list($width, $height) = $dimensions;
                    $ratio = round(($width / $height) * 1000) / 1000;
                    $width = 600;
                    $height = (int)($width / $ratio);

                    $footer = array(
                        'alignment' => 'center',
                        'type' => 'footer',
                        'text' => null,
                        'image' => array(
                            'src' => $fileHelper->url('footer.'.$this->extensions[$i], 'themes'.DS.$theme.DS.'images'),
                            'width' => $width,
                            'height' => $height,
                            'url' => null,
                            'alt' => __('Footer', WYSIJA),
                            'alignment' => 'center'
                        )
                    );
                }
            }
        }

        $divider = NULL;
        for($i = 0; $i < count($this->extensions); $i++) {

            $result = $fileHelper->exists('themes'.DS.$theme.DS.'images'.DS.'divider.'.$this->extensions[$i]);
            if($result['result'] !== FALSE) {
                $dimensions = @getimagesize($result['file']);
                if($dimensions !== FALSE and count($dimensions) >= 2) {

                    list($width, $height) = $dimensions;
                    $ratio = round(($width / $height) * 1000) / 1000;
                    $width = 564;
                    $height = (int)($width / $ratio);

                    $divider = array(
                        'type' => 'divider',
                        'src' => $fileHelper->url('divider.'.$this->extensions[$i], 'themes'.DS.$theme.DS.'images'),
                        'width' => $width,
                        'height' => $height
                    );
                }
            }
        }
        return array(
            'header' => $header,
            'footer' => $footer,
            'divider' => $divider
        );
    }
    function getDivider($theme = 'default') {
        $divider = NULL;
        if($theme === 'default') {
            $dividersHelper =& WYSIJA::get('dividers', 'helper');
            $divider = $dividersHelper->getDefault();
        } else {

            $fileHelper =& WYSIJA::get('file', 'helper');
            for($i = 0; $i < count($this->extensions); $i++) {

                $result = $fileHelper->exists('themes'.DS.$theme.DS.'images'.DS.'divider.'.$this->extensions[$i]);
                if($result['result'] !== FALSE) {
                    $dimensions = @getimagesize($result['file']);
                    if($dimensions !== FALSE and count($dimensions) >= 2) {

                        list($width, $height) = $dimensions;
                        $ratio = round(($width / $height) * 1000) / 1000;
                        $width = 564;
                        $height = (int)($width / $ratio);

                        $divider = array(
                            'src' => $fileHelper->url('divider.'.$this->extensions[$i], 'themes'.DS.$theme.DS.'images'),
                            'width' => $width,
                            'height' => $height
                        );
                    }
                }
            }
        }
        return $divider;
    }
    
    function installTheme($ZipfileResult,$manual=false){
        $helperF=&WYSIJA::get('file','helper');
        if(!@file_exists($ZipfileResult)){
            
            $dirtemp=$helperF->makeDir();
            $dirtemp=str_replace("/",DS,$dirtemp);
            
            $tempzipfile=$dirtemp.$_REQUEST['theme_key'].'.zip';
            $fp = fopen($tempzipfile, 'w');
            fwrite($fp, $ZipfileResult);
            fclose($fp);
        }else $tempzipfile=$ZipfileResult;

        
        $dirtheme=$helperF->makeDir('themes');
        if(!$dirtheme){
            $upload_dir = wp_upload_dir();
            $this->error(sprintf(__('The folder "%1$s" is not writable, please change the access rights to this folder so that Wysija can setup itself properly.',WYSIJA),$upload_dir['basedir'])."<a target='_blank' href='http://codex.wordpress.org/Changing_File_Permissions'>".__('Read documentation',WYSIJA)."</a>");
            return false;
        }
        $timecreated=time();
        $dirthemetemp=$helperF->makeDir('temp'.DS.'temp_'.$timecreated,0777);
        $zipclass=&WYSIJA::get('zip','helper');
        if(!$zipclass->unzip_wp($tempzipfile,$dirthemetemp)) {
            $this->error("Error while decompressing archive.");
            $helperF->rrmdir($dirthemetemp);
            return false;
        }
        
        $files = scandir($dirthemetemp);
        foreach($files as $filename){
            if(!in_array($filename, array('.','..','.DS_Store','Thumbs.db')) && !is_dir($dirthemetemp.DS.$filename)){

                $this->error('In your zip there should be one folder only, with the content of your theme within.');
                $helperF->rrmdir($dirthemetemp);
                return false;
            }else{
                if(!in_array($filename, array('.','..','.DS_Store','Thumbs.db')))    $theme_key=$filename;
            }
        }
         if(!$theme_key){
            $this->error('There was an error while unzipping the file :'.$tempzipfile.' to the folder: '.$dirthemetemp);
            return false;
        }
        unlink($tempzipfile);

        if($manual && !isset($_REQUEST['overwriteexistingtheme']) && file_exists($dirtheme.DS.$theme_key)){
            $this->error(sprintf(__('A theme called %1$s exists already. To overwrite it, tick the corresponding checkbox before uploading.',WYSIJA),'<strong>'.$theme_key.'</strong>'),1);
            return false;
        }
        
        $result=true;
            $listoffilestocheck=array($theme_key,'style.css');
            foreach($listoffilestocheck as $keyindex=> $fileexist){
                if($keyindex==0)    $testfile=$listoffilestocheck[0];
                else    $testfile=$listoffilestocheck[0].DS.$fileexist;
                if($manual){
                    if(!file_exists($dirthemetemp.DS.$testfile)){

                        if($keyindex==0)    $this->error('Missing directory :'.$testfile);
                        else    $this->error('Missing file :'.$dirthemetemp.DS.$testfile);
                        $result=false;
                    }
                }
            }

        
        if($result){

            $helperF->rcopy($dirthemetemp.DS.$listoffilestocheck[0],$dirtheme.DS.$listoffilestocheck[0]);
            $this->notice(sprintf(__('The theme %1$s has been installed on your site.',WYSIJA),'<strong>'.$theme_key.'</strong>'));
        }else{
            $this->error(__("We could not install your theme. It appears it's not in the valid format.",WYSIJA),1);
        }

        $helperF->rrmdir($dirthemetemp);
        return $result;
    }

    function delete($themekey){
        $helperF=&WYSIJA::get('file','helper');
        $dirtheme=$helperF->makeDir('themes'.DS.$themekey);
        $helperF->rrmdir($dirtheme);
        if(!file_exists($dirtheme)){
            $this->notice(sprintf(__('Theme %1$s has been deleted.',WYSIJA),'<strong>'.$themekey.'</strong>'));
            return true;
        }else{
            $this->error(sprintf(__('Theme %1$s could not be deleted.',WYSIJA),'<strong>'.$themekey.'</strong>'));
            return false;
        }
    }
}