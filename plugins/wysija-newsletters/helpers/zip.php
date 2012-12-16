<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_zip extends WYSIJA_object{
    function WYSIJA_help_zip(){
    }
    
    function unzip($temp_file_addr, $to){
        $filesystem = WP_Filesystem();
        $dounzip = unzip_file($temp_file_addr, $to);
        if ( is_wp_error($dounzip) ) {

            $error = $dounzip->get_error_code();
            $data = $dounzip->get_error_data($error);
            $this->error($dounzip->get_error_message());
            return false;
        }
        return true;
    }
    
    function unzip_wp($file, $to){
        $filesystem = WP_Filesystem();

	@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
	$to = str_replace("/",DS,$to);
	if (class_exists('ZipArchive')) return $this->_unzip_file_ziparchive($file, $to);

	return $this->_unzip_file_pclzip($file, $to);
    }
    
    
    function _unzip_file_ziparchive($file, $to) {
        
        global $wp_filesystem;
        $z = new ZipArchive();

        $zopen = $z->open($file, 4); // -- ZIPARCHIVE::CHECKCONS = 4
        if ($zopen !== true){
            $this->error("Archive is not of a correct format!");
            return false;
        }
        $z->extractTo($to); 
        $z->close();
        return true;
    }
    
    function _unzip_file_pclzip($file, $to) {
            global $wp_filesystem;

            if ( ini_get('mbstring.func_overload') && function_exists('mb_internal_encoding') ) {
                    $previous_encoding = mb_internal_encoding();
                    mb_internal_encoding('ISO-8859-1');
            }
            if(file_exists(ABSPATH . 'wp-admin/includes/class-pclzip.php')) require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
            $archive = new PclZip($file);
            $archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
            if ( isset($previous_encoding) )
                    mb_internal_encoding($previous_encoding);

            if ( !is_array($archive_files) ){
                $this->error("Archive is not of a correct format!");
                return false;
            }

            if ( 0 == count($archive_files) ){
                $this->error("Archive is empty!");
                return false;
            }

            foreach ( $archive_files as $file ) {
                $filedest=str_replace("/",DS,$to . $file['filename']);
                if ( $file['folder']){
                    $to=str_replace("/",DS,$to);
                    if(file_exists($to))  chmod($to,0777);

                    if(is_dir($to) ){

                        if(!mkdir($filedest,0777)){
                            $this->error('cannot created folder : '.$filedest);
                            $to=dirname($to).DS;
                            $filedest=str_replace("/",DS,$to . $file['filename']);
                            if(!mkdir($filedest,0777)) {
                                $this->error('Still cannot created folder : '.$filedest);
                                return false;
                            }
                        }
                    }
                    if(file_exists($filedest))  chmod($filedest,0777);
                    continue;
                }
                if ( '__MACOSX/' === substr($file['filename'], 0, 9) ) // Don't extract the OS X-created __MACOSX directory files
                        continue;
                if ( ! $wp_filesystem->put_contents( $filedest, $file['content'], 0644) ){

                    if ( ! ($fp = @fopen($filedest, 'w')) )
                            return false;
                    @fwrite($fp, $file['content']);
                    @fclose($fp);
                    if(!file_exists($filedest)){
                        $this->error('Could not copy file : '. $filedest);
                        return false;
                    }
                }            
            }
            return true;
    }
}
