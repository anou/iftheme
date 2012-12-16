<?php
defined('WYSIJA') or die('Restricted access');
require_once(dirname(__FILE__).DS.'file.php');
class WYSIJA_help_images extends WYSIJA_help_file{
    function WYSIJA_help_images(){
    }
    function getList($template="default"){
        $foldersTocheck="themes".DS.$template.DS."img".DS."public";
        $url="themes/$template/img/public";
        $imagesallowed=array("jpg","png","jpeg");
        $listed=array();
        $path=$this->getUploadDir($foldersTocheck);
        
        if(file_exists($path)){
           $files = scandir($path);
            $i=1;
            foreach($files as $filename){
                if(!in_array($filename, array('.','..',".DS_Store","Thumbs.db"))){
                    if(preg_match('/.*\.('.implode($imagesallowed,'|').')/',$filename,$match)){
                       $res=getimagesize($path.$filename);
                        $listed["tmpl-".$template.$i]=array(
                           'path'=>$path.$filename,
                           'width'=>$res[0],
                           'height'=>$res[1],
                           'url'=>$this->url($filename,$url),
                           'thumb_url'=>$this->url($filename,$url),
                           'identifier'=>"tmpl-".$template.$i,
                           );
                       $i++;
                    }
                }
            }
            return $listed; 
        }
    }
}
