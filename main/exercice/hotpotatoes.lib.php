<?php
/* For licensing terms, see /license.txt */

/**
 * Code library for HotPotatoes integration.
 * @package chamilo.exercise
 * @author Istvan Mandak (original author)
 */

$dbTable = Database::get_course_table(TABLE_DOCUMENT); // TODO: This is a global variable with too simple name, conflicts are possible. Better eliminate it. Correct the test unit too.


/**
 * Creates a hotpotato directory.
 *
 * If a directory of that name already exists, don't create any. If a file of that name exists, remove it and create a directory.
 * @param   string      Wanted path
 * @return  boolean     Always true so far
 */
function hotpotatoes_init($base_work_dir) {
    //global $_course, $_user;
    $document_path = $base_work_dir.'/';
    if (!is_dir($document_path)) {
        if (is_file($document_path)) {
            @unlink($document_path);
        }
        @mkdir($document_path, api_get_permissions_for_new_directories());
        return true;
    } else {
        return false;
    }
    //why create a .htaccess here?
    //if (!is_file($document_path.".htacces"))
    //{
    //        if (!($fp = fopen($document_path.".htaccess", "w"))) {
    //    }
    //    $str = "order deny,allow\nallow from all";
    //    if (!fwrite($fp,$str)) { }
    //}
}

/**
 * Gets the title of the quizz file given as parameter.
 * @param   string    File name
 * @param   string    File path
 * @return  string    The exercise title
 */
function GetQuizName($fname, $fpath) {
    $title = GetComment($fname);
    if (trim($title) == '') {
        if (file_exists($fpath.$fname)) {
            if (!($fp = @fopen($fpath.$fname, 'r'))) {
                //die('Could not open Quiz input.');
                return basename($fname);
            }

            $contents = @fread($fp, filesize($fpath.$fname));
            @fclose($fp);

            $title = api_get_title_html($contents);
        }
    }
    if ($title == '') {
        $title = basename($fname);
    }
    return (string)$title;
}

/**
 * Gets the comment about a file from the corresponding database record.
 * @param   string    File path
 * @return  string    Comment from the database record
 * Added conditional to the table if is empty.
 */
function GetComment($path, $course_code = '') {
    global $dbTable;    
    $course_info = api_get_course_info($course_code);            
    $path = Database::escape_string($path);
    $query = "SELECT comment FROM $dbTable WHERE c_id = {$course_info['real_id']} AND path='$path'";
    $result = Database::query($query);
    while ($row = Database::fetch_array($result)) {
        return $row[0];
    }
    return '';
}

/**
 * Sets the comment in the database for a particular path.
 * @param    string    File path
 * @param    string    Comment to set
 * @return   string    Result of the database operation (Database::query will output some message directly on error anyway)
 */
function SetComment($path, $comment) {
    global $dbTable;
    $path = Database::escape_string($path);
    $comment = Database::escape_string($comment);
    $course_id = api_get_course_int_id();
    $query = "UPDATE $dbTable SET comment='$comment' WHERE $course_id AND path='$path'";
    $result = Database::query($query);
    return "$result";
}

/**
 * Reads the file contents into a string.
 * @param    string    Urlencoded path
 * @return   string    The file contents or false on security error
 */
function ReadFileCont($full_file_path) {
    if (Security::check_abs_path(dirname($full_file_path).'/', api_get_path(SYS_COURSE_PATH))) {
        if (is_file($full_file_path)) {
            if (!($fp = fopen(urldecode($full_file_path), 'r'))) {
                return '';
            }
            $contents = fread($fp, filesize($full_file_path));
            fclose($fp);
            return $contents;
        }
    }
    return false;
}

/**
 * Writes the file contents into the given filepath.
 * @param    string    Urlencoded path
 * @param    string    The file contents
 * @return   boolean   True on success, false on security error
 */
function WriteFileCont($full_file_path, $content) {
    // Check if this is not an attack, trying to get into other directories or something like that.
    global $_course;
    if (Security::check_abs_path(dirname($full_file_path).'/', api_get_path(SYS_COURSE_PATH).$_course['path'].'/')) {
        // Check if this is not an attack, trying to upload a php file or something like that.
        if (basename($full_file_path) != Security::filter_filename(basename($full_file_path))) { return false; }
        if (!($fp = fopen(urldecode($full_file_path), 'w'))) {
            //die('Could not open Quiz input.');
        }
        fwrite($fp, $content);
        fclose($fp);
        return true;
    }
    return false;
}

/**
 * Gets the name of an img whose path is given (without directories or extensions).
 * @param    string    An image tag (<img src="...." ...>)
 * @return   string    The image file name or an empty string
 */
function GetImgName($imgtag) {
    // Select src tag from img tag.
    $match = array();
    //preg_match('/(src=(["\'])1.*(["\'])1)/i', $imgtag, $match);            //src
    preg_match('/src(\s)*=(\s)*[\'"]([^\'"]*)[\'"]/i', $imgtag, $match); //get the img src as contained between " or '
    //list($key, $srctag) = each($match);
    $src = $match[3];
    //$src = substr($srctag, 5, (strlen($srctag) - 7));
    if (stristr($src, 'http') === false) {
    // Valid or invalid image name.
        if ($src == '') {
            return '';
        } else {
            $tmp_src = basename($src) ;
            if ($tmp_src == '') {
                return $src;
            } else {
                return $tmp_src;
            }
        }
    } else {
        // The img tag contained "http", which means it is probably external. Ignore it.
        return '';
    }
}

/**
 * Gets the source path of an image tag.
 * @param    string    An image tag
 * @return   string    The image source or ""
 */
function GetSrcName($imgtag) {
    // Select src tag from img tag.
    $match = array();
    preg_match("|(src=\".*\" )|U", $imgtag, $match);            //src
    list($key, $srctag) = each($match);
    $src = substr($srctag, 5, (strlen($srctag) - 7));
    if (stristr($src, 'http') === false) {
    // valid or invalid image name
        return $src;
    } else {
        return '';
    }
}

/**
 * Gets the image parameters from an image path.
 * @param    string       File name
 * @param    string       File path
 * @param    reference    Reference to a list of image parameters (emptied, then used to return results)
 * @param    reference    Reference to a counter of images (emptied, then used to return results)
 */
function GetImgParams($fname, $fpath, &$imgparams, &$imgcount) {
    // Select img tags from context.
    $imgparams = array();
    //phpinfo();
    $contents = ReadFileCont("$fpath"."$fname");
    $matches = array();
    preg_match_all('(<img .*>)', $contents, $matches);
    $imgcount = 0;
    while (list($int, $match) = each($matches)) {
        // Each match consists of a key and a value.
        while (list($key, $imgtag) = each($match)) {
            $imgname = GetImgName($imgtag);
            if ($imgname != '' && !in_array($imgname, $imgparams)) {
                array_push($imgparams, $imgname);    // name (+ type) of the images in the html test
                $imgcount = $imgcount + 1;            // number of images in the html test
            }
        }
    }
}

/**
 * Generates a list of hidden fields with the image params given as parameter to this function.
 * @param    array    List of image parameters
 * @return   string   String containing the hidden parameters built from the list given
 */
function GenerateHiddenList($imgparams) {
    $list = '';
    if (is_array($imgparams)) {
        while (list($int, $string) = each($imgparams)) {
            $list .= "<input type=\"hidden\" name=\"imgparams[]\" value=\"$string\" />\n";
        }
    }
    return $list;
}

/**
 * Searches for a node in the given array.
 * @param    reference    Reference to the array to search
 * @param    string       Node we are looking for in the array
 * @return   mixed        Node name or false if not found
 */
function myarraysearch(&$array, $node) {
    $match = false;
    $tmp_array = array();
    for ($i = 0; $i < count($array); $i++) {
        if (!strcmp($array[$i], $node)) {
            $match = $node;
        } else {
            array_push($tmp_array, $array[$i]);
        }
    }
    $array = $tmp_array;
    return $match;
}

/**
 * Searches an image name into an array.
 * @param    reference        Reference to an array to search
 * @param    string           String to look for
 * @return   mixed            String given if found, false otherwise
 * @uses     myarraysearch    This function is just an additional layer on the myarraysearch() function
 */
function CheckImageName(&$imgparams, $string) {
    $checked = myarraysearch($imgparams, $string);
    return $checked;
}

/**
 * Replaces an image tag by ???
 * @param    string    The content to replace
 * @return   string    The modified content
 */
function ReplaceImgTag($content) {
    $newcontent = $content;
    $matches = array();
    preg_match_all('(<img .*>)', $content, $matches);
    $imgcount = 0;
    while (list($int, $match) = each($matches)) {
        while (list($key, $imgtag) = each($match)) {
            $imgname = GetSrcName($imgtag);
            if ($imgname == '') {}                                // Valid or invalid image name.
            else {

                $prehref = $imgname;
                $posthref = basename($imgname);
                $newcontent = str_replace($prehref, $posthref, $newcontent);
            }
        }
    }
    return $newcontent;
}

/**
 * Fills the folder name up to a certain length with "0".
 * @param    string    Original folder name
 * @param    integer   Length to reach
 * @return   string    Modified folder name
 */
function FillFolderName($name, $nsize) {
    $str = '';
    for ($i = 0; $i < $nsize - strlen($name); $i++) {
        $str .= '0';
    }
    $str .= $name;
    return $str;
}

/**
 * Generates the HotPotato folder tree.
 * @param    string    Folder path
 * @return   string    Folder name (modified)
 */
function GenerateHpFolder($folder) {
    $filelist = array();
    if ($dir = @opendir($folder)) {
        while (($file = readdir($dir)) !== false) {
            if ($file != '.') {
                if ($file != '..') {
                    $full_name = $folder.'/'.$file;
                    if (is_dir($full_name)) {
                        $filelist[] = $file;
                    }
               }
            }
        }
    }
    $w = 0;
    do {
        $name = FillFolderName(mt_rand(1, 99999), 6);
        $checked = myarraysearch($filelist, $name);
        // As long as we find the name in the array, continue looping. As soon as we have a new element, quit.
        if ($checked) { $w = 1;    }
        else { $w = 0; }
    } while ($w == 1);

    return $name;
}

/**
 * Gets the folder name (strips down path).
 * @param    string    Path
 * @return   string    Folder name stripped down
 */
function GetFolderName($fname) {
    $name = explode('/', $fname);
    $name = $name[sizeof($name) - 2];
    return $name;
}

/**
 * Gets the folder path (withouth the name of the folder itself) ?
 * @param    string    Path
 * @return   string    Path stripped down
 */
function GetFolderPath($fname) {
    $str = '';
    $name = explode('/', $fname);
    for ($i = 0; $i < sizeof($name) - 1; $i++) {
        $str = $str.$name[$i].'/';
    }
    return $str;
}

/**
 * Checks if there are subfolders.
 * @param    string    Path
 * @return   integer   1 if a subfolder was found, 0 otherwise
 */
function CheckSubFolder($path) {
    $folder = GetFolderPath($path);
    $dflag = 0;
    if ($dir = @opendir($folder)) {
        while (($file = readdir($dir)) !== false) {
            if ($file != '.') {
                if ($file != '..') {
                    $full_name = $folder.'/'.$file;
                    if (is_dir($full_name)) {
                        $dflag = 1;    // first directory
                    }
                }
            }
        }
    }
    return $dflag;
}

/**
 * Hotpotato Garbage Collector
 * @param    string     Path
 * @param    integer    Flag
 * @param    integer    User id
 * @return   void       No return value, but echoes results
 */
function HotPotGCt($folder, $flag, $user_id) {
    // Garbage Collector
    $filelist = array();
    if ($dir = @opendir($folder)) {
        while (($file = readdir($dir)) !== false) {
            if ($file != '.') {
                if ($file != '..') {
                    $full_name = $folder.'/'.$file;
                    if (is_dir($full_name)) {
                        HotPotGCt($folder.'/'.$file, $flag, $user_id);
                    } else {
                        $filelist[] = $file;
                    }
               }
            }
        }
        closedir($dir);
    }
    while (list($key, $val) = each($filelist)) {
        if (stristr($val, $user_id.'.t.html')) {
            if ($flag == 1) {
                my_delete($folder.'/'.$val);
            } else {
                echo $folder.'/'.$val.'<br />';
            }
        }
    }
}