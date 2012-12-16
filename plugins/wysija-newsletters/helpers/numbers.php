<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_numbers extends WYSIJA_object{
    function WYSIJA_help_numbers(){
    }
    
    function format_number($int) {

        $int = (int)(0 + str_replace(',', '', $int));

        if(!is_numeric($int)){ return false;}

        if($int>1000000000000){ 
                    return round(($int/1000000000000),2).' trillion';
        }elseif($int>1000000000){ 
                    return round(($int/1000000000),2).' billion';
        }elseif($int>1000000){ 
                    return round(($int/1000000),2).' million';
        }elseif($int>1000){ 
            return round(($int/1000),2).' thousand';
        }
        return number_format($int);
    }
    function get_max_file_upload(){
        $u_bytes = ini_get( 'upload_max_filesize' );
        $p_bytes = ini_get( 'post_max_size' );
        $data=array();
        $data['maxbytes']=$this->return_bytes(min($u_bytes, $p_bytes));
        $data['maxmegas'] = apply_filters( 'upload_size_limit', min($u_bytes, $p_bytes), $u_bytes, $p_bytes );
        $data['maxchars'] =(int)floor(($p_bytes*1024*1024)/200);
        return $data;
    }
    function return_bytes($size_str)
    {
        switch (substr ($size_str, -1))
        {
            case 'M': case 'm': return (int)$size_str * 1048576;
            case 'K': case 'k': return (int)$size_str * 1024;
            case 'G': case 'g': return (int)$size_str * 1073741824;
            default: return $size_str;
        }
    }
}