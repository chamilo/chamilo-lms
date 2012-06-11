<?php
/* For licensing terms, see /license.txt */

exit;

require_once 'main/inc/global.inc.php';

//Source language do not change
$dir = api_get_path(SYS_CODE_PATH).'lang/english';

//Translate this language
$to_dir = api_get_path(SYS_CODE_PATH).'lang/spanish';

$save_dir_path = api_get_path(SYS_CODE_PATH).'locale/es_ES';

//The new po files will be saved in  $dir.'/LC_MESSAGES/';

///data/workspaces/tutorial/portal/lang/de_DE/LC_MESSAGES/portal.po

if (!is_dir($save_dir_path)) {
    mkdir($save_dir_path);  
    mkdir($save_dir_path.'/LC_MESSAGES');
}
   
if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            $info = pathinfo($file);
            if ($info['extension'] != 'php') continue;
            
            echo "filename: $file : filetype: " . filetype($dir.'/'.$file) . "<br >";
            
            $translations = array();
            $filename = $dir.'/'.$file;                        
            $po = file($filename);              
            if (!file_exists($filename) || !file_exists($to_dir.'/'.$file)) {
                continue;
            }
            
            foreach ($po as $line) {
                $pos = strpos($line, '=');                
                if ($pos) {
                    $variable = (substr($line, 1, $pos-1));
                    $variable = trim($variable);
                    require $filename;
                    $my_variable_in_english = $$variable;
                    require $to_dir.'/'.$file;
                    $my_variable = $$variable;       
                    $translations[] = array('msgid' =>$my_variable_in_english, 'msgstr' =>$my_variable);
                }                   
            }
            //var_dump($translations);
            $info['filename'] = explode('.', $info['filename']);
            $info['filename'] = $info['filename'][0];
            $new_po_file = $save_dir_path.'/LC_MESSAGES/'.$info['filename'].'.po';
            var_dump($new_po_file);
            
            $fp = fopen($new_po_file, 'w');  
            var_dump($fp);
            foreach($translations as $item) {   
                $line = 'msgid "'.addslashes($item['msgid']).'"'."\n";
                $line .= 'msgstr "'.addslashes($item['msgstr']).'"'."\n\n";                
                fwrite($fp, $line);
            }
            fclose($fp);            
        }
        closedir($dh);
    }
}