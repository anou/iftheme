<?php
/*
if ( !function_exists('sys_get_temp_dir')) {
  function sys_get_temp_dir() {
      if( $temp=getenv('TMP') )        return $temp;
      if( $temp=getenv('TEMP') )        return $temp;
      if( $temp=getenv('TMPDIR') )    return $temp;
      $temp=tempnam(__FILE__,'');
      if (file_exists($temp)) {
          unlink($temp);
          return dirname($temp);
      }
      return null;
  }
}
*/  
function icl_troubleshooting_dumpdb(){
    
    if($_GET['nonce'] == wp_create_nonce('dbdump') && is_admin() &&  current_user_can('manage_options')){
    
        ini_set('memory_limit','128M');

        $dump = _icl_ts_mysqldump(DB_NAME);
        $gzdump = gzencode($dump, 9);
        
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=" . DB_NAME . ".sql.gz");
        //header("Content-Encoding: gzip");
        header("Content-Length: ". strlen($gzdump));
        
        echo $gzdump;
        exit;
    }
}




function _icl_ts_mysqldump($mysql_database)
{
    global $wpdb;
    $upload_folder = wp_upload_dir();
    $dump_tmp_file = $upload_folder['path'] . '/' . '__icl_mysqldump.sql';
    
    $fp = @fopen($dump_tmp_file, 'w');        
    if(!$fp){
        $fp = fopen('php://output', 'w');        
        ob_start();
    }
    
    $sql="SHOW TABLES LIKE '".str_replace('_','\_',$wpdb->prefix)."%';";
    
    $result= mysql_query($sql);
    if( $result)
    {
        while( $row= mysql_fetch_row($result))
        {       
            //_icl_ts_mysqldump_table_structure($row[0]);
            //_icl_ts_mysqldump_table_data($row[0]);
            _icl_ts_backup_table($row[0], 0, $fp);            
        }
    }
    else
    {
        echo "/* no tables in $mysql_database */\n";
    }
    mysql_free_result($result);
    fclose ($fp);
    
    
    if(file_exists($dump_tmp_file)){
        $data = file_get_contents($dump_tmp_file);
        @unlink($dump_tmp_file);    
    }else{
        $data = ob_get_contents();
        ob_end_clean();
    }
    
    return $data ;
}

if ( ! defined('ROWS_PER_SEGMENT') ) define('ROWS_PER_SEGMENT', 100);

function _icl_ts_stow($query_line, $fp) {
    if(! @fwrite($fp, $query_line,strlen($query_line)))
        die(__('Error writing query:','sitepress') . '  ' . $query_line);
}
 
function _icl_ts_backquote($a_name) {
    if (!empty($a_name) && $a_name != '*') {
        if (is_array($a_name)) {
            $result = array();
            reset($a_name);
            while(list($key, $val) = each($a_name)) 
                $result[$key] = '`' . $val . '`';
            return $result;
        } else {
            return '`' . $a_name . '`';
        }
    } else {
        return $a_name;
    }
} 
      
function _icl_ts_backup_table($table, $segment = 'none', $fp) {
        global $wpdb;

        $table_structure = $wpdb->get_results("DESCRIBE $table");        
        if(($segment == 'none') || ($segment == 0)) {
            _icl_ts_stow("\n\n", $fp);
            _icl_ts_stow("DROP TABLE IF EXISTS " . _icl_ts_backquote($table) . ";\n", $fp);
            // Table structure
            _icl_ts_stow("\n\n", $fp);
            $create_table = $wpdb->get_results("SHOW CREATE TABLE $table", ARRAY_N);
            _icl_ts_stow($create_table[0][1] . ' ;', $fp);
            _icl_ts_stow("\n\n", $fp);
        }
        
        if(($segment == 'none') || ($segment >= 0)) {
            $defs = array();
            $ints = array();
            foreach ($table_structure as $struct) {
                if ( (0 === strpos($struct->Type, 'tinyint')) ||
                    (0 === strpos(strtolower($struct->Type), 'smallint')) ||
                    (0 === strpos(strtolower($struct->Type), 'mediumint')) ||
                    (0 === strpos(strtolower($struct->Type), 'int')) ||
                    (0 === strpos(strtolower($struct->Type), 'bigint')) ) {
                        $defs[strtolower($struct->Field)] = ( null === $struct->Default ) ? 'NULL' : $struct->Default;
                        $ints[strtolower($struct->Field)] = "1";
                }
            }
            
            
            // Batch by $row_inc
            
            if($segment == 'none') {
                $row_start = 0;
                $row_inc = ROWS_PER_SEGMENT;
            } else {
                $row_start = $segment * ROWS_PER_SEGMENT;
                $row_inc = ROWS_PER_SEGMENT;
            }
            
            do {    
                $table_data = $wpdb->get_results("SELECT * FROM $table LIMIT {$row_start}, {$row_inc}", ARRAY_A);

                $entries = 'INSERT INTO ' . _icl_ts_backquote($table) . ' VALUES (';    
                //    \x08\\x09, not required
                $search = array("\x00", "\x0a", "\x0d", "\x1a");
                $replace = array('\0', '\n', '\r', '\Z');
                if($table_data) {
                    foreach ($table_data as $row) {
                        $values = array();
                        foreach ($row as $key => $value) {
                            if ($ints[strtolower($key)]) {
                                // make sure there are no blank spots in the insert syntax,
                                // yet try to avoid quotation marks around integers
                                $value = ( null === $value || '' === $value) ? $defs[strtolower($key)] : $value;
                                $values[] = ( '' === $value ) ? "''" : $value;
                            } else {
                                $values[] = "'" . str_replace($search, $replace, $wpdb->escape($value)) . "'";
                            }
                        }
                        _icl_ts_stow(" \n" . $entries . implode(', ', $values) . ');', $fp);
                    }
                    $row_start += $row_inc;
                }
            } while((count($table_data) > 0) and ($segment=='none'));
        }
        
        if(($segment == 'none') || ($segment < 0)) {
            // Create footer/closing comment in SQL-file
            _icl_ts_stow("\n", $fp);
        }
    } // end backup_table()  
    

    
function icl_reset_wpml($blog_id = false){
    global $wpdb;
    
    if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'resetwpml'){
        check_admin_referer( 'resetwpml' );    
    }
    
    if(empty($blog_id)){
        $blog_id = isset($_POST['id']) ? $_POST['id'] : $wpdb->blogid;
    }
    
    define('ICL_IS_WPML_RESET', true);
      
    if($blog_id || !function_exists('is_multisite') || !is_multisite()){

        if(function_exists('is_multisite') && is_multisite()){
            switch_to_blog($blog_id);
        }
        
        $icl_tables = array(
            $wpdb->prefix . 'icl_languages',
            $wpdb->prefix . 'icl_languages_translations',
            $wpdb->prefix . 'icl_translations',
            $wpdb->prefix . 'icl_translation_status',    
            $wpdb->prefix . 'icl_translate_job',    
            $wpdb->prefix . 'icl_translate',    
            $wpdb->prefix . 'icl_locale_map',
            $wpdb->prefix . 'icl_flags',
            $wpdb->prefix . 'icl_content_status',
            $wpdb->prefix . 'icl_core_status',
            $wpdb->prefix . 'icl_node',
            $wpdb->prefix . 'icl_strings',
            $wpdb->prefix . 'icl_string_translations',
            $wpdb->prefix . 'icl_string_status',
            $wpdb->prefix . 'icl_string_positions',
            $wpdb->prefix . 'icl_message_status',
            $wpdb->prefix . 'icl_reminders',    
        );
                
        foreach($icl_tables as $icl_table){
            mysql_query("DROP TABLE IF EXISTS " . $icl_table);
        }
        
        delete_option('icl_sitepress_settings');
        delete_option('icl_sitepress_version');
        delete_option('_icl_cache');
        delete_option('_icl_admin_option_names');
        delete_option('wp_icl_translators_cached');
        delete_option('WPLANG');   
         
        $wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
        if(!isset($wpmu_sitewide_plugins[ICL_PLUGIN_FOLDER.'/sitepress.php'])){
            deactivate_plugins(basename(ICL_PLUGIN_PATH) . '/sitepress.php');
            $ra = get_option('recently_activated');
            $ra[basename(ICL_PLUGIN_PATH) . '/sitepress.php'] = time();
            update_option('recently_activated', $ra);        
        }else{
            update_option('_wpml_inactive', true);
        }
        
        
        if(isset($_REQUEST['submit'])){            
            wp_redirect(network_admin_url('admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/network.php&updated=true&action=resetwpml'));
            exit();
        }
        
        if(function_exists('is_multisite') && is_multisite()){
            restore_current_blog(); 
        }
        
    }
}

?>
