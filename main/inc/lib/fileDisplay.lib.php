<?php
/* See license terms in /license.txt */
/**
*	This is the file display library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package chamilo.library
*/
/**
 * Code
 */
/*	GENERIC FUNCTIONS : FOR OLDER PHP VERSIONS */
if ( ! function_exists('array_search') ) {
    /**
     * Searches haystack for needle and returns the key
     * if it is found in the array, FALSE otherwise.
     *
     * Natively implemented in PHP since 4.0.5 version.
     * This function is intended for previous version.
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - needle (mixed)
     * @param  - haystack (array)
     * @return - array key or FALSE
     *
     * @see    - http://www.php.net/array_search
     */
    function array_search($needle, $haystack)
    {
        while (list($key, $val) = each($haystack))
            if ($val == $needle)
                return $key;
        return false;
    }
}

/* FILE DISPLAY FUNCTIONS */
/**
 * Define the image to display for each file extension.
 * This needs an existing image repository to work.
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - $file_name (string) - Name of a file
 * @return - The gif image to chose
 */
function choose_image($file_name)
{
    static $type, $image;

    /* TABLES INITIALISATION */
    if (!$type || !$image)
    {
        $type['word'      ] = array('doc', 'dot',  'rtf', 'mcw',  'wps', 'psw', 'docm', 'docx', 'dotm',  'dotx');
        $type['web'       ] = array('htm', 'html', 'htx', 'xml',  'xsl',  'php', 'xhtml');
        $type['image'     ] = array('gif', 'jpg',  'png', 'bmp',  'jpeg', 'tif', 'tiff');
        $type['image_vect'] = array('svg','svgz');
        $type['audio'     ] = array('wav', 'mid',  'mp2', 'mp3',  'midi', 'sib', 'amr', 'kar', 'oga','au','wma');
		$type['video'     ] = array('mp4', 'mov',  'rm',  'pls',  'mpg',  'mpeg', 'm2v', 'm4v', 'flv', 'f4v', 'avi', 'wmv', 'asf', '3gp','ogv','ogg','ogx','webm');
        $type['excel'     ] = array('xls', 'xlt',  'xls', 'xlt', 'pxl', 'xlsx', 'xlsm', 'xlam', 'xlsb', 'xltm', 'xltx');
        $type['compressed'] = array('zip', 'tar',  'rar', 'gz');
        $type['code'      ] = array('js',  'cpp',  'c',   'java', 'phps', 'jsp', 'asp', 'aspx', 'cfm');
        $type['acrobat'   ] = array('pdf');
        $type['powerpoint'] = array('ppt', 'pps', 'pptm', 'pptx', 'potm', 'potx', 'ppam', 'ppsm', 'ppsx');
        $type['flash'     ] = array('fla', 'swf');
        $type['text'      ] = array('txt','log');
        $type['oo_writer' ] = array('odt', 'ott', 'sxw', 'stw');
        $type['oo_calc'   ] = array('ods', 'ots', 'sxc', 'stc');
        $type['oo_impress'] = array('odp', 'otp', 'sxi', 'sti');
        $type['oo_draw'   ] = array('odg', 'otg', 'sxd', 'std');
        $type['epub'      ] = array('epub');
		$type['java'      ] = array('class','jar');
		$type['freemind'  ] = array('mm');

        $image['word'      ] = 'word.gif';
        $image['web'       ] = 'file_html.gif';
        $image['image'     ] = 'file_image.gif';
        $image['image_vect'] = 'file_svg.png';
        $image['audio'     ] = 'file_sound.gif';
        $image['video'     ] = 'film.gif';
        $image['excel'     ] = 'excel.gif';
        $image['compressed'] = 'file_zip.gif';
        $image['code'      ] = 'icons/22/mime_code.png';
        $image['acrobat'   ] = 'file_pdf.gif';
        $image['powerpoint'] = 'powerpoint.gif';
        $image['flash'     ] = 'file_flash.gif';
        $image['text'      ] = 'icons/22/mime_text.png';
        $image['oo_writer' ] = 'file_oo_writer.gif';
        $image['oo_calc'   ] = 'file_oo_calc.gif';
        $image['oo_impress'] = 'file_oo_impress.gif';
        $image['oo_draw'   ] = 'file_oo_draw.gif';
        $image['epub'      ] = 'file_epub.gif';
		$image['java'      ] = 'file_java.png';
		$image['freemind'  ] = 'file_freemind.png';
    }

    /* FUNCTION CORE */
    $extension = array();
    if (!is_array($file_name)) {
        if (preg_match('/\.([[:alnum:]]+)(\?|$)/', $file_name, $extension)) {
            $extension[1] = strtolower($extension[1]);

            foreach ($type as $generic_type => $extension_list)
            {
                if (in_array($extension[1], $extension_list))
                {
                    return $image[$generic_type];
                }
            }
        }
    }
    return 'defaut.gif';
}


/**
 * Transform a UNIX time stamp in human readable format date.
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - $date - UNIX time stamp
 * @return - A human readable representation of the UNIX date
 */
function format_date($date)
{
    return date('d.m.Y', $date);
}

/**
 * Transform the file path to a URL.
 *
 * @param  - $file_path (string) - Relative local path of the file on the hard disk
 * @return - Relative url
 */
function format_url($file_path)
{
    $path_component = explode('/', $file_path);

    $path_component = array_map('rawurlencode', $path_component);

    return implode('/', $path_component);
}

/**
 * Get the most recent time the content of a folder was changed.
 *
 * @param  - $dir_name (string)   - Path of the dir on the hard disk
 * @param  - $do_recursive (bool) - Traverse all folders in the folder?
 * @return - Time the content of the folder was changed
 */
function recent_modified_file_time($dir_name, $do_recursive = true)
{

    $dir = dir($dir_name);
    $last_modified = 0;
    $return = 0;
    if (is_dir($dir)) {
        while(($entry = $dir->read()) !== false)
        {
            if ($entry != '.' && $entry != '..')
                continue;

            if (!is_dir($dir_name.'/'.$entry))
                $current_modified = filemtime($dir_name.'/'.$entry);
            elseif ($do_recursive)
                $current_modified = recent_modified_file_time($dir_name.'/'.$entry, true);

            if ($current_modified > $last_modified)
                $last_modified = $current_modified;
        }

        $dir->close();
        //prevents returning 0 (for empty directories)
        $return = ($last_modified == 0) ? filemtime($dir_name) : $last_modified;
    }
    return $return;
}

/**
 * Get the total size of a directory.
 *
 * @param  - $dir_name (string) - Path of the dir on the hard disk
 * @return - Total size in bytes
 */
function folder_size($dir_name)
{
    $size = 0;

    if ($dir_handle = opendir($dir_name))
    {
        while (($entry = readdir($dir_handle)) !== false)
        {
            if($entry == '.' || $entry == '..')
                continue;

            if(is_dir($dir_name.'/'.$entry))
                $size += folder_size($dir_name.'/'.$entry);
            else
                $size += filesize($dir_name.'/'.$entry);
        }

        closedir($dir_handle);
    }

    return $size;
}

/**
 * Calculates the total size of a directory by adding the sizes (that
 * are stored in the database) of all files & folders in this directory.
 *
 * @param 	string  $path
 * @param 	boolean $can_see_invisible
 * @return 	Total size
 */
function get_total_folder_size($path, $can_see_invisible = false) {
    $table_itemproperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $table_document 	= Database::get_course_table(TABLE_DOCUMENT);
    $tool_document 		= TOOL_DOCUMENT;
    
    $course_id 			= api_get_course_int_id();
    $session_id         = api_get_session_id();
    $session_condition  = api_get_session_condition($session_id, true, true, 'props.id_session');

    $visibility_rule = ' props.visibility ' . ($can_see_invisible ? '<> 2' : '= 1');
    
    $sql = "SELECT SUM(table1.size) FROM (
                SELECT size FROM $table_itemproperty AS props, $table_document AS docs
                WHERE 	docs.c_id 	= $course_id AND                        
                        docs.id 	= props.ref AND 
                        docs.path LIKE '$path/%' AND
                        props.c_id 	= $course_id AND
                        props.tool 	= '$tool_document' AND                            
                        $visibility_rule  
                        $session_condition 
                GROUP BY ref
            ) as table1";
    
    $result = Database::query($sql);    
    if ($result && Database::num_rows($result) != 0) {
        $row = Database::fetch_row($result);
        return $row[0] == null ? 0 : $row[0];
    } else {
        return 0;
    }
}