<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_readme extends WYSIJA_object{
    var $changelog=array();
    function WYSIJA_help_readme(){
    }
    function scan($file=false){
        if(!$file) $file=WYSIJA_DIR.'readme.txt';
        $handle=fopen($file, 'r');
        $content = fread ($handle, filesize ($file));
        fclose($handle);
        $exploded=explode('== Changelog ==', $content);
        $explodedVersions=explode("\n=", $exploded[1]);
        foreach($explodedVersions as $key=> $version){
            if(!trim($version)) unset($explodedVersions[$key]);
        }
        foreach($explodedVersions as $key=> $version){
            $versionNumber='';
            foreach(explode("\n", $version) as $key =>$commentedLine){
                if($key==0){

                    $expldoedvnumber=explode(' - ',$commentedLine);
                    $versionNumber=trim($expldoedvnumber[0]);
                }else{

                    if(!isset($this->changelog[$versionNumber])) $this->changelog[$versionNumber]=array();
                    if(trim($commentedLine))    $this->changelog[$versionNumber][]= str_replace('* ', '', $commentedLine);
                }
            }
        }
    }
}