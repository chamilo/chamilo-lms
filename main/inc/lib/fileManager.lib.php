<?php
/* For licensing terms, see /license.txt */

/**
 *    This is the file manage library for Chamilo.
 *    Include/require it in your code to use its functionality.
 * @package chamilo.library
 */

/**
This class contains functions that you can access statically.

FileManager::list_all_directories($path)
FileManager::list_all_files($dir_array)
FileManager::compat_load_file($file_name)
FileManager::set_default_settings($upload_path, $filename, $filetype="file", $glued_table, $default_visibility='v')

@author Roan Embrechts
@version 1.1, July 2004
 * @package chamilo.library
 */
class FileManager
{
    /**
     * Update the file or directory path in the document db document table
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - action (string) - action type require : 'delete' or 'update'
     * @param  - old_path (string) - old path info stored to change
     * @param  - new_path (string) - new path info to substitute
     * @desc Update the file or directory path in the document db document table
     *
     */
    static function update_db_info($action, $old_path, $new_path = '')
    {
        $dbTable = Database::get_course_table(TABLE_DOCUMENT);
        $course_id = api_get_course_int_id();

        /* DELETE */
        if ($action == 'delete') {

            $old_path = Database::escape_string($old_path);
            $to_delete = "WHERE c_id = $course_id AND path LIKE BINARY '".$old_path."' OR path LIKE BINARY '".$old_path."/%'";
            $query = "DELETE FROM $dbTable ".$to_delete;

            $result = Database::query("SELECT id FROM $dbTable ".$to_delete);

            if (Database::num_rows($result)) {
                require_once api_get_path(INCLUDE_PATH).'../metadata/md_funcs.php';
                $mdStore = new mdstore(true); // create if needed

                $md_type = (substr($dbTable, -13) == 'scormdocument') ? 'Scorm' : 'Document';

                while ($row = Database::fetch_array($result)) {
                    $eid = $md_type.'.'.$row['id'];
                    $mdStore->mds_delete($eid);
                    $mdStore->mds_delete_offspring($eid);
                }
            }
        }

        /* UPDATE */

        if ($action == 'update') {
            if ($new_path[0] == '.') {
                $new_path = substr($new_path, 1);
            }
            $new_path = str_replace('//', '/', $new_path);

            // Attempt to update	- tested & working for root	dir
            $new_path = Database::escape_string($new_path);
            $query = "UPDATE $dbTable
            SET path = CONCAT('".$new_path."', SUBSTRING(path, LENGTH('".$old_path."')+1) )
            WHERE c_id = $course_id AND path LIKE BINARY '".$old_path."' OR path LIKE BINARY '".$old_path."/%'";
        }
        Database::query($query);
    }

    /**
     * Cheks a file or a directory actually exist at this location
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - file_path (string) - path of the presume existing file or dir
     * @return - boolean TRUE if the file or the directory exists
     *           boolean FALSE otherwise.
     */
    static function check_name_exist($file_path)
    {
        clearstatcache();
        $save_dir = getcwd();
        if (!is_dir(dirname($file_path))) {
            return false;
        }
        chdir(dirname($file_path));
        $file_name = basename($file_path);

        if (file_exists($file_name)) {
            chdir($save_dir);

            return true;
        } else {
            chdir($save_dir);

            return false;
        }
    }

    /**
     * Deletes a file or a directory
     *
     * @author - Hugues Peeters
     * @param  - $file (String) - the path of file or directory to delete
     * @return - bolean - true if the delete succeed
     *           bolean - false otherwise.
     * @see    - delete() uses check_name_exist() and removeDir() functions
     */
    static function my_delete($file)
    {
        if (self::check_name_exist($file)) {
            if (is_file($file)) { // FILE CASE
                unlink($file);

                return true;
            } elseif (is_dir($file)) { // DIRECTORY CASE
                self::removeDir($file);

                return true;
            }
        } else {
            return false; // no file or directory to delete
        }
    }

    /**
     * Removes a directory recursively
     *
     * @returns true if OK, otherwise false
     *
     * @author Amary <MasterNES@aol.com> (from Nexen.net)
     * @author Olivier Brouckaert <oli.brouckaert@skynet.be>
     *
     * @param string    $dir        directory to remove
     */
    static function removeDir($dir)
    {
        if (!@$opendir = opendir($dir)) {
            return false;
        }

        while ($readdir = readdir($opendir)) {
            if ($readdir != '..' && $readdir != '.') {
                if (is_file($dir.'/'.$readdir)) {
                    if (!@unlink($dir.'/'.$readdir)) {
                        return false;
                    }
                } elseif (is_dir($dir.'/'.$readdir)) {
                    if (!self::removeDir($dir.'/'.$readdir)) {
                        return false;
                    }
                }
            }
        }

        closedir($opendir);

        if (!@rmdir($dir)) {
            return false;
        }

        return true;
    }


    /**
     * Return true if folder is empty
     * @author : hubert.borderiou@grenet.fr
     * @param string $in_folder : folder path on disk
     * @return 1 if folder is empty, 0 otherwise
     */

    static function folder_is_empty($in_folder)
    {
        $tab_folder_content = scandir($in_folder);
        $folder_is_empty = 0;
        if ((count($tab_folder_content) == 2 && in_array(".", $tab_folder_content) && in_array(
            "..",
            $tab_folder_content
        )) || (count($tab_folder_content) < 2)
        ) {
            $folder_is_empty = 1;
        }

        return $folder_is_empty;
    }


    /**
     * Renames a file or a directory
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - $file_path (string) - complete path of the file or the directory
     * @param  - $new_file_name (string) - new name for the file or the directory
     * @return - boolean - true if succeed
     *         - boolean - false otherwise
     * @see    - rename() uses the check_name_exist() and php2phps() functions
     */
    static function my_rename($file_path, $new_file_name)
    {

        $save_dir = getcwd();
        $path = dirname($file_path);
        $old_file_name = basename($file_path);

        $new_file_name = replace_dangerous_char($new_file_name);

        // If no extension, take the old one
        if ((strpos($new_file_name, '.') === false) && ($dotpos = strrpos($old_file_name, '.'))) {
            $new_file_name .= substr($old_file_name, $dotpos);
        }

        // Note: still possible: 'xx.yy' -rename-> '.yy' -rename-> 'zz'
        // This is useful for folder names, where otherwise '.' would be sticky

        // Extension PHP is not allowed, change to PHPS
        $new_file_name = self::php2phps($new_file_name);

        if ($new_file_name == $old_file_name) {
            return $old_file_name;
        }

        if (strtolower($new_file_name) != strtolower($old_file_name) && self::check_name_exist(
            $path.'/'.$new_file_name
        )
        ) {
            return false;
        }
        // On a Windows server, it would be better not to do the above check
        // because it succeeds for some new names resembling the old name.
        // But on Unix/Linux the check must be done because rename overwrites.

        chdir($path);
        $res = rename($old_file_name, $new_file_name) ? $new_file_name : false;
        chdir($save_dir);

        return $res;
    }

    /**
     * Moves a file or a directory to an other area
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - $source (String) - the path of file or directory to move
     * @param  - $target (String) - the path of the new area
     * @return - bolean - true if the move succeed
     *           bolean - false otherwise.
     * @see    - move() uses check_name_exist() and copyDirTo() functions
     */
    static function move($source, $target)
    {

        if (self::check_name_exist($source)) {
            $file_name = basename($source);

            if (self::check_name_exist($target.'/'.$file_name)) {
                return false;
            } else {
                /* File case */
                if (is_file($source)) {
                    copy($source, $target.'/'.$file_name);
                    unlink($source);

                    return true;
                } elseif (is_dir($source)) {
                    /* Directory case */
                    // Check to not copy the directory inside itself
                    if (ereg('^'.$source.'/', $target.'/')) { // TODO: ereg() function is deprecated in PHP 5.3
                        return false;
                    } else {
                        self::copyDirTo($source, $target);

                        return true;
                    }
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Moves a directory and its content to an other area
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - $orig_dir_path (string) - the path of the directory to move
     * @param  - $destination (string) - the path of the new area
     * @return - no return
     */
    static function copyDirTo($orig_dir_path, $destination, $move = true)
    {

        $save_dir = getcwd();
        // Extract directory name - create it at destination - update destination trail
        $dir_name = basename($orig_dir_path);
        if (is_dir($orig_dir_path)) {
            mkdir($destination.'/'.$dir_name, api_get_permissions_for_new_directories());
            $destination_trail = $destination.'/'.$dir_name;
            if (is_dir($destination)) {
                chdir($orig_dir_path);
                $handle = opendir($orig_dir_path);

                while ($element = readdir($handle)) {
                    if ($element == '.' || $element == '..') {
                        continue; // Skip the current and parent directories
                    } elseif (is_file($element)) {
                        copy($element, $destination_trail.'/'.$element);

                        if ($move) {
                            unlink($element);
                        }
                    } elseif (is_dir($element)) {
                        $dir_to_copy[] = $orig_dir_path.'/'.$element;
                    }
                }

                closedir($handle);

                if (sizeof($dir_to_copy) > 0) {
                    foreach ($dir_to_copy as $this_dir) {
                        self::copyDirTo($this_dir, $destination_trail, $move); // Recursivity
                    }
                }

                if ($move) {
                    rmdir($orig_dir_path);
                }
                chdir($save_dir);
            }
        }
    }

    /* NOTE: These functions batch is used to automatically build HTML forms
     * with a list of the directories contained on the course Directory.
     *
     * From a thechnical point of view, form_dir_lists calls sort_dir wich calls index_dir
     */

    /**
     * Gets all the directories and subdirectories
     * contented in a given directory
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - path (string) - directory path of the one to index
     * @return - an array containing the path of all the subdirectories
     */
    static function index_dir($path)
    {
        $dir_array = array();
        $save_dir = getcwd();
        if (is_dir($path)) {
            chdir($path);
            $handle = opendir($path);
            // Reads directory content end record subdirectoies names in $dir_array
            if ($handle !== false) {
                while ($element = readdir($handle)) {
                    if ($element == '.' || $element == '..') {
                        continue;
                    } // Skip the current and parent directories
                    if (is_dir($element)) {
                        $dir_array[] = $path.'/'.$element;
                    }
                }
                closedir($handle);
            }
            // Recursive operation if subdirectories exist
            $dir_number = sizeof($dir_array);
            if ($dir_number > 0) {
                for ($i = 0; $i < $dir_number; $i++) {
                    $sub_dir_array = self::index_dir($dir_array[$i]); // Function recursivity
                    $dir_array = array_merge((array)$dir_array, (array)$sub_dir_array); // Data merge
                }
            }
        }
        chdir($save_dir);

        return $dir_array;
    }

    /**
     * Indexes all the directories and subdirectories
     * contented in a given directory, and sort them alphabetically
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - path (string) - directory path of the one to index
     * @return - an array containing the path of all the subdirectories sorted
     *           false, if there is no directory
     * @see    - index_and_sort_dir uses the index_dir() function
     */
    static function index_and_sort_dir($path)
    {
        $dir_list = self::index_dir($path);
        if ($dir_list) {
            natsort($dir_list);

            return $dir_list;
        }

        return false;
    }


    /**
     * Extracting extention of a filename
     *
     * @returns array
     * @param     string    $filename         filename
     */
    static function getextension($filename)
    {
        $bouts = explode('.', $filename);

        return array(array_pop($bouts), implode('.', $bouts));
    }

    /**
     * Calculation size of a directory
     *
     * @returns integer size
     * @param     string    $path path to size
     * @param     boolean $recursive if true , include subdir in total
     */
    static function dirsize($root, $recursive = true)
    {
        $dir = @opendir($root);
        $size = 0;
        while ($file = @readdir($dir)) {
            if (!in_array($file, array('.', '..'))) {
                if (is_dir($root.'/'.$file)) {
                    $size += $recursive ? dirsize($root.'/'.$file) : 0;
                } else {
                    $size += @filesize($root.'/'.$file);
                }
            }
        }
        @closedir($dir);

        return $size;
    }

    /**
    Returns a list of all directories, except the base dir,
    of the current course. This function uses recursion.

    Convention: the parameter $path does not end with a slash.

    @author Roan Embrechts
    @version 1.0.1
     */
    static function list_all_directories($path)
    {

        $result_array = array();
        if (is_dir($path)) {
            $save_dir = getcwd();
            chdir($path);
            $handle = opendir($path);
            while ($element = readdir($handle)) {
                if ($element == '.' || $element == '..') {
                    continue;
                } // Skip the current and parent directories
                if (is_dir($element)) {
                    $dir_array[] = $path.'/'.$element;
                }
            }
            closedir($handle);
            // Recursive operation if subdirectories exist
            $dir_number = sizeof($dir_array);
            if ($dir_number > 0) {
                for ($i = 0; $i < $dir_number; $i++) {
                    $sub_dir_array = self::list_all_directories($dir_array[$i]); // Function recursivity
                    if (is_array($dir_array) && is_array($sub_dir_array)) {
                        $dir_array = array_merge($dir_array, $sub_dir_array); // Data merge
                    }
                }
            }
            $result_array = $dir_array;
            chdir($save_dir);
        }

        return $result_array;
    }

    /**
    This function receives a list of directories.
    It returns a list of all files in these directories

    @author Roan Embrechts
    @version 1.0
     */
    function list_all_files($dir_array)
    {

        $element_array = array();
        if (is_dir($dir_array)) {

            $save_dir = getcwd();
            foreach ($dir_array as $directory) {
                chdir($directory);
                $handle = opendir($directory);
                while ($element = readdir($handle)) {
                    if ($element == '.' || $element == '..' || $element == '.htaccess') {
                        continue;
                    } // Skip the current and parent directories
                    if (!is_dir($element)) {
                        $element_array[] = $directory.'/'.$element;
                    }
                }
                closedir($handle);
                chdir('..');
                chdir($save_dir);
            }
        }

        return $element_array;
    }

    /**
    Loads contents of file $filename into memory and returns them as a string.
    Function kept for compatibility with older PHP versions.
    Function is binary safe (is needed on Windows)
     */
    function compat_load_file($file_name)
    {
        $buffer = '';
        if (file_exists($file_name)) {
            $fp = fopen($file_name, 'rb');
            $buffer = fread($fp, filesize($file_name));
            fclose($fp);
        }

        return $buffer;
    }

    function choose_image($file_name)
    {
        static $type, $image;

        /* TABLES INITIALISATION */
        if (!$type || !$image) {
            $type['word'] = array('doc', 'dot', 'rtf', 'mcw', 'wps', 'psw', 'docm', 'docx', 'dotm', 'dotx');
            $type['web'] = array('htm', 'html', 'htx', 'xml', 'xsl', 'php', 'xhtml');
            $type['image'] = array('gif', 'jpg', 'png', 'bmp', 'jpeg', 'tif', 'tiff');
            $type['image_vect'] = array('svg', 'svgz');
            $type['audio'] = array('wav', 'mid', 'mp2', 'mp3', 'midi', 'sib', 'amr', 'kar', 'oga', 'au', 'wma');
            $type['video'] = array(
                'mp4',
                'mov',
                'rm',
                'pls',
                'mpg',
                'mpeg',
                'm2v',
                'm4v',
                'flv',
                'f4v',
                'avi',
                'wmv',
                'asf',
                '3gp',
                'ogv',
                'ogg',
                'ogx',
                'webm'
            );
            $type['excel'] = array('xls', 'xlt', 'xls', 'xlt', 'pxl', 'xlsx', 'xlsm', 'xlam', 'xlsb', 'xltm', 'xltx');
            $type['compressed'] = array('zip', 'tar', 'rar', 'gz');
            $type['code'] = array('js', 'cpp', 'c', 'java', 'phps', 'jsp', 'asp', 'aspx', 'cfm');
            $type['acrobat'] = array('pdf');
            $type['powerpoint'] = array('ppt', 'pps', 'pptm', 'pptx', 'potm', 'potx', 'ppam', 'ppsm', 'ppsx');
            $type['flash'] = array('fla', 'swf');
            $type['text'] = array('txt', 'log');
            $type['oo_writer'] = array('odt', 'ott', 'sxw', 'stw');
            $type['oo_calc'] = array('ods', 'ots', 'sxc', 'stc');
            $type['oo_impress'] = array('odp', 'otp', 'sxi', 'sti');
            $type['oo_draw'] = array('odg', 'otg', 'sxd', 'std');
            $type['epub'] = array('epub');
            $type['java'] = array('class', 'jar');
            $type['freemind'] = array('mm');

            $image['word'] = 'word.gif';
            $image['web'] = 'file_html.gif';
            $image['image'] = 'file_image.gif';
            $image['image_vect'] = 'file_svg.png';
            $image['audio'] = 'file_sound.gif';
            $image['video'] = 'film.gif';
            $image['excel'] = 'excel.gif';
            $image['compressed'] = 'file_zip.gif';
            $image['code'] = 'icons/22/mime_code.png';
            $image['acrobat'] = 'file_pdf.gif';
            $image['powerpoint'] = 'powerpoint.gif';
            $image['flash'] = 'file_flash.gif';
            $image['text'] = 'icons/22/mime_text.png';
            $image['oo_writer'] = 'file_oo_writer.gif';
            $image['oo_calc'] = 'file_oo_calc.gif';
            $image['oo_impress'] = 'file_oo_impress.gif';
            $image['oo_draw'] = 'file_oo_draw.gif';
            $image['epub'] = 'file_epub.gif';
            $image['java'] = 'file_java.png';
            $image['freemind'] = 'file_freemind.png';
        }

        /* FUNCTION CORE */
        $extension = array();
        if (!is_array($file_name)) {
            if (preg_match('/\.([[:alnum:]]+)(\?|$)/', $file_name, $extension)) {
                $extension[1] = strtolower($extension[1]);

                foreach ($type as $generic_type => $extension_list) {
                    if (in_array($extension[1], $extension_list)) {
                        return $image[$generic_type];
                    }
                }
            }
        }

        return 'defaut.gif';
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
     * Calculates the total size of a directory by adding the sizes (that
     * are stored in the database) of all files & folders in this directory.
     *
     * @param     string  $path
     * @param     boolean $can_see_invisible
     * @return     Total size
     */
    static function get_total_folder_size($path, $can_see_invisible = false)
    {
        $table_itemproperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table_document = Database::get_course_table(TABLE_DOCUMENT);
        $tool_document = TOOL_DOCUMENT;

        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $session_condition = api_get_session_condition($session_id, true, true, 'props.id_session');

        $visibility_rule = ' props.visibility '.($can_see_invisible ? '<> 2' : '= 1');

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

    /**
     * Changes the file name extension from .php to .phps
     * Useful for securing a site.
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - file_name (string) name of a file
     *
     * @return string the filename phps'ized
     */
    static function php2phps($file_name)
    {
        return preg_replace('/\.(php.?|phtml.?)(\.){0,1}.*$/i', '.phps', $file_name);
    }

    /**
     * Renames .htaccess & .HTACCESS to htaccess.txt
     *
     * @param string $filename
     * @return string
     */
    static function htaccess2txt($filename)
    {
        return str_replace(array('.htaccess', '.HTACCESS'), array('htaccess.txt', 'htaccess.txt'), $filename);
    }

    /**
     * This function executes our safety precautions
     * more functions can be added
     *
     * @param string $filename
     * @return string
     * @see FileManager::php2phps()
     * @see htaccess2txt()
     */
    static function disable_dangerous_file($filename)
    {
        return self::htaccess2txt(self::php2phps($filename));
    }

    /**
     * This function generates a unique name for a file on a given location. Filenames are changed to name_#.ext
     *
     * @param string $path
     * @param string $name
     * @return new unique name
     */
    static function unique_name($path, $name)
    {
        $ext = substr(strrchr($name, '.'), 0);
        $name_no_ext = substr($name, 0, strlen($name) - strlen(strstr($name, $ext)));
        $n = 0;
        $unique = '';
        while (file_exists($path.$name_no_ext.$unique.$ext)) {
            $unique = '_'.++$n;
        }

        return $name_no_ext.$unique.$ext;
    }

    /**
     * Returns the name without extension, used for the title
     *
     * @param string $name
     * @return name without the extension
     */
    static function get_document_title($name)
    {
        // If they upload .htaccess...
        $name = self::disable_dangerous_file($name);
        $ext = substr(strrchr($name, '.'), 0);

        return addslashes(substr($name, 0, strlen($name) - strlen(strstr($name, $ext))));
    }

    /**
     * This function checks if the upload succeeded
     *
     * @param array $uploaded_file ($_FILES)
     * @param bool $show_output
     *
     * @return true if upload succeeded
     */
    static function process_uploaded_file($uploaded_file, $show_output = true)
    {
        // Checking the error code sent with the file upload.

        switch ($uploaded_file['error']) {
            case 1:
                // The uploaded file exceeds the upload_max_filesize directive in php.ini.
                if ($show_output) {
                    Display::display_error_message(
                        get_lang('UplExceedMaxServerUpload').ini_get('upload_max_filesize')
                    );
                }

                return false;
            case 2:
                // The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
                // Not used at the moment, but could be handy if we want to limit the size of an upload (e.g. image upload in html editor).
                $max_file_size = intval($_POST['MAX_FILE_SIZE']);
                if ($show_output) {
                    Display::display_error_message(get_lang('UplExceedMaxPostSize').format_file_size($max_file_size));
                }

                return false;
            case 3:
                // The uploaded file was only partially uploaded.
                if ($show_output) {
                    Display::display_error_message(get_lang('UplPartialUpload').' '.get_lang('PleaseTryAgain'));
                }

                return false;
            case 4:
                // No file was uploaded.
                if ($show_output) {
                    Display::display_error_message(get_lang('UplNoFileUploaded').' '.get_lang('UplSelectFileFirst'));
                }

                return false;
        }

        if (!file_exists($uploaded_file['tmp_name'])) {
            // No file was uploaded.
            if ($show_output) {
                Display::display_error_message(get_lang('UplUploadFailed'));
            }

            return false;
        }

        if (file_exists($uploaded_file['tmp_name'])) {
            $filesize = filesize($uploaded_file['tmp_name']);
            if (empty($filesize)) {
                // No file was uploaded.
                if ($show_output) {
                    Display::display_error_message(get_lang('UplUploadFailedSizeIsZero'));
                }

                return false;
            }
        }

        $course_id = api_get_course_id();
        //Checking course quota if we are in a course

        if (!empty($course_id)) {
            $max_filled_space = DocumentManager::get_course_quota();
            // Check if there is enough space to save the file
            if (!DocumentManager::enough_space($uploaded_file['size'], $max_filled_space)) {
                if ($show_output) {
                    Display::display_error_message(get_lang('UplNotEnoughSpace'));
                }

                return false;
            }
        }

        // case 0: default: We assume there is no error, the file uploaded with success.
        return true;
    }

    /**
     * This function does the save-work for the documents.
     * It handles the uploaded file and adds the properties to the database
     * If unzip=1 and the file is a zipfile, it is extracted
     * If we decide to save ALL kinds of documents in one database,
     * we could extend this with a $type='document', 'scormdocument',...
     *
     * @param array $_course
     * @param array $uploaded_file ($_FILES)
     * @param string $base_work_dir
     * @param string $upload_path
     * @param int $user_id
     * @param int $to_group_id, 0 for everybody
     * @param int $to_user_id, NULL for everybody
     * @param int $unzip 1/0
     * @param string $what_if_file_exists overwrite, rename or warn if exists (default)
     * @param boolean Optional output parameter. So far only use for unzip_uploaded_document function. If no output wanted on success, set to false.
     * @return path of the saved file
     */
    static function handle_uploaded_document(
        $_course,
        $uploaded_file,
        $base_work_dir,
        $upload_path,
        $user_id,
        $to_group_id = 0,
        $to_user_id = null,
        $unzip = 0,
        $what_if_file_exists = '',
        $output = true
    ) {
        if (!$user_id) {
            die('Not a valid user.');
        }
        // Strip slashes
        $uploaded_file['name'] = stripslashes($uploaded_file['name']);
        // Add extension to files without one (if possible)
        $uploaded_file['name'] = self::add_ext_on_mime($uploaded_file['name'], $uploaded_file['type']);
        $current_session_id = api_get_session_id();

        //Just in case process_uploaded_file is not called
        $max_filled_space = DocumentManager::get_course_quota();

        // Check if there is enough space to save the file
        if (!DocumentManager::enough_space($uploaded_file['size'], $max_filled_space)) {
            if ($output) {
                Display::display_error_message(get_lang('UplNotEnoughSpace'));
            }

            return false;
        }

        // If the want to unzip, check if the file has a .zip (or ZIP,Zip,ZiP,...) extension
        if ($unzip == 1 && preg_match('/.zip$/', strtolower($uploaded_file['name']))) {
            return self::unzip_uploaded_document(
                $uploaded_file,
                $upload_path,
                $base_work_dir,
                $max_filled_space,
                $output,
                $to_group_id
            );
            //display_message('Unzipping file');
        } elseif ($unzip == 1 && !preg_match(
            '/.zip$/',
            strtolower($uploaded_file['name'])
        )
        ) { // We can only unzip ZIP files (no gz, tar,...)
            if ($output) {
                Display::display_error_message(get_lang('UplNotAZip')." ".get_lang('PleaseTryAgain'));
            }

            return false;
        } else {
            // Clean up the name, only ASCII characters should stay. (and strict)
            $clean_name = replace_dangerous_char($uploaded_file['name'], 'strict');

            // No "dangerous" files
            $clean_name = self::disable_dangerous_file($clean_name);

            if (!self::filter_extension($clean_name)) {
                if ($output) {
                    Display::display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
                }

                return false;
            } else {
                // Extension is good
                //echo '<br />clean name = '.$clean_name;
                //echo '<br />upload_path = '.$upload_path;
                // If the upload path differs from / (= root) it will need a slash at the end
                if ($upload_path != '/') {
                    $upload_path = $upload_path.'/';
                }
                //echo '<br />upload_path = '.$upload_path;
                $file_path = $upload_path.$clean_name;
                //echo '<br />file path = '.$file_path;
                // Full path to where we want to store the file with trailing slash
                $where_to_save = $base_work_dir.$upload_path;
                // At least if the directory doesn't exist, tell so
                if (!is_dir($where_to_save)) {
                    if ($output) {
                        Display::display_error_message(get_lang('DestDirectoryDoesntExist').' ('.$upload_path.')');
                    }

                    return false;
                }
                //echo '<br />where to save = '.$where_to_save;
                // Full path of the destination
                $store_path = $where_to_save.$clean_name;
                //echo '<br />store path = '.$store_path;
                // Name of the document without the extension (for the title)
                $document_name = self::get_document_title($uploaded_file['name']);
                // Size of the uploaded file (in bytes)
                $file_size = $uploaded_file['size'];

                $files_perm = api_get_permissions_for_new_files();

                // What to do if the target file exists
                switch ($what_if_file_exists) {
                    // Overwrite the file if it exists
                    case 'overwrite':
                        // Check if the target file exists, so we can give another message
                        $file_exists = file_exists($store_path);
                        if (@move_uploaded_file($uploaded_file['tmp_name'], $store_path)) {
                            chmod($store_path, $files_perm);
                            if ($file_exists) {
                                // UPDATE DATABASE
                                $document_id = DocumentManager::get_document_id($_course, $file_path);

                                if (is_numeric($document_id)) {
                                    // Update filesize
                                    self::update_existing_document($_course, $document_id, $uploaded_file['size']);

                                    // Update document item_property
                                    api_item_property_update(
                                        $_course,
                                        TOOL_DOCUMENT,
                                        $document_id,
                                        'DocumentUpdated',
                                        $user_id,
                                        $to_group_id,
                                        $to_user_id,
                                        null,
                                        null,
                                        $current_session_id
                                    );

                                    //Redo visibility
                                    api_set_default_visibility(TOOL_DOCUMENT, $document_id);
                                }
                                // If the file is in a folder, we need to update all parent folders
                                self::item_property_update_on_folder($_course, $upload_path, $user_id);
                                // Display success message with extra info to user
                                if ($output) {
                                    Display::display_confirmation_message(
                                        get_lang('UplUploadSucceeded').'<br />'.$file_path.' '.get_lang(
                                            'UplFileOverwritten'
                                        ),
                                        false
                                    );
                                }

                                return $file_path;
                            } else {
                                // Put the document data in the database
                                $document_id = self::add_document(
                                    $_course,
                                    $file_path,
                                    'file',
                                    $file_size,
                                    $document_name,
                                    null,
                                    0,
                                    true
                                );
                                if ($document_id) {
                                    // Put the document in item_property update
                                    api_item_property_update(
                                        $_course,
                                        TOOL_DOCUMENT,
                                        $document_id,
                                        'DocumentAdded',
                                        $user_id,
                                        $to_group_id,
                                        $to_user_id,
                                        null,
                                        null,
                                        $current_session_id
                                    );
                                }
                                // If the file is in a folder, we need to update all parent folders
                                self::item_property_update_on_folder($_course, $upload_path, $user_id);
                                // Display success message to user
                                if ($output) {
                                    Display::display_confirmation_message(
                                        get_lang('UplUploadSucceeded').'<br />'.$file_path,
                                        false
                                    );
                                }

                                return $file_path;
                            }
                        } else {
                            if ($output) {
                                Display::display_error_message(get_lang('UplUnableToSaveFile'));
                            }

                            return false;
                        }
                        break;

                    // Rename the file if it exists
                    case 'rename':
                        $new_name = unique_name($where_to_save, $clean_name);
                        $store_path = $where_to_save.$new_name;
                        $new_file_path = $upload_path.$new_name;

                        if (@move_uploaded_file($uploaded_file['tmp_name'], $store_path)) {

                            chmod($store_path, $files_perm);

                            // Put the document data in the database
                            $document_id = self::add_document(
                                $_course,
                                $new_file_path,
                                'file',
                                $file_size,
                                $document_name,
                                null,
                                0,
                                true
                            );
                            if ($document_id) {
                                // Update document item_property
                                api_item_property_update(
                                    $_course,
                                    TOOL_DOCUMENT,
                                    $document_id,
                                    'DocumentAdded',
                                    $user_id,
                                    $to_group_id,
                                    $to_user_id,
                                    null,
                                    null,
                                    $current_session_id
                                );
                            }
                            // If the file is in a folder, we need to update all parent folders
                            self::item_property_update_on_folder($_course, $upload_path, $user_id);

                            // Display success message to user
                            if ($output) {
                                Display::display_confirmation_message(
                                    get_lang('UplUploadSucceeded').'<br />'.get_lang('UplFileSavedAs').$new_file_path,
                                    false
                                );
                            }

                            return $new_file_path;
                        } else {
                            if ($output) {
                                Display::display_error_message(get_lang('UplUnableToSaveFile'));
                            }

                            return false;
                        }
                        break;

                    // Only save the file if it doesn't exist or warn user if it does exist
                    default:
                        if (file_exists($store_path)) {
                            if ($output) {
                                Display::display_error_message($clean_name.' '.get_lang('UplAlreadyExists'));
                            }
                        } else {
                            if (@move_uploaded_file($uploaded_file['tmp_name'], $store_path)) {
                                chmod($store_path, $files_perm);

                                // Put the document data in the database
                                $document_id = self::add_document(
                                    $_course,
                                    $file_path,
                                    'file',
                                    $file_size,
                                    $document_name,
                                    null,
                                    0,
                                    true
                                );

                                if ($document_id) {
                                    // Update document item_property
                                    api_item_property_update(
                                        $_course,
                                        TOOL_DOCUMENT,
                                        $document_id,
                                        'DocumentAdded',
                                        $user_id,
                                        $to_group_id,
                                        $to_user_id,
                                        null,
                                        null,
                                        $current_session_id
                                    );
                                }
                                // If the file is in a folder, we need to update all parent folders
                                self::item_property_update_on_folder($_course, $upload_path, $user_id);

                                // Display success message to user
                                if ($output) {
                                    Display::display_confirmation_message(
                                        get_lang('UplUploadSucceeded').'<br />'.$file_path,
                                        false
                                    );
                                }

                                return $file_path;
                            } else {
                                if ($output) {
                                    Display::display_error_message(get_lang('UplUnableToSaveFile'));
                                }

                                return false;
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * Checks if there is enough place to add a file on a directory
     * on the base of a maximum directory size allowed
     * deprecated: use enough_space instead!
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - file_size (int) - size of the file in byte
     * @param  - dir (string) - Path of the directory
     *           whe the file should be added
     * @param  - max_dir_space (int) - maximum size of the diretory in byte
     * @return - boolean true if there is enough space,
     *                boolean false otherwise
     *
     * @see    - enough_size() uses  dir_total_space() function
     */
    static function enough_size($file_size, $dir, $max_dir_space)
    {
        if ($max_dir_space) {
            $already_filled_space = self::dir_total_space($dir);
            if (($file_size + $already_filled_space) > $max_dir_space) {
                return false;
            }
        }

        return true;
    }


    /**
     * Computes the size already occupied by a directory and is subdirectories
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  - dir_path (string) - size of the file in byte
     * @return - int - return the directory size in bytes
     */
    static function dir_total_space($dir_path)
    {

        $save_dir = getcwd();
        chdir($dir_path);
        $handle = opendir($dir_path);

        while ($element = readdir($handle)) {
            if ($element == '.' || $element == '..') {
                continue; // Skip the current and parent directories
            }
            if (is_file($element)) {
                $sumSize += filesize($element);
            }
            if (is_dir($element)) {
                $dirList[] = $dir_path.'/'.$element;
            }
        }

        closedir($handle);

        if (sizeof($dirList) > 0) {
            foreach ($dirList as $j) {
                $sizeDir = self::dir_total_space($j); // Recursivity
                $sumSize += $sizeDir;
            }
        }
        chdir($save_dir); // Return to initial position
        return $sumSize;
    }


    /**
     * Tries to add an extension to files without extension
     * Some applications on Macintosh computers don't add an extension to the files.
     * This subroutine try to fix this on the basis of the MIME type sent
     * by the browser.
     *
     * Note : some browsers don't send the MIME Type (e.g. Netscape 4).
     *        We don't have solution for this kind of situation
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @author - Bert Vanderkimpen
     * @param  - file_name (string) - Name of the file
     * @param  - file_type (string) - Type of the file
     * @return - file_name (string)
     */
    static function add_ext_on_mime($file_name, $file_type)
    {

        // Check whether the file has an extension AND whether the browser has sent a MIME Type

        if (!preg_match('/^.*\.[a-zA-Z_0-9]+$/', $file_name) && $file_type) {

            // Build a "MIME-types / extensions" connection table

            static $mime_type = array();

            $mime_type[] = 'application/msword';
            $extension[] = '.doc';
            $mime_type[] = 'application/rtf';
            $extension[] = '.rtf';
            $mime_type[] = 'application/vnd.ms-powerpoint';
            $extension[] = '.ppt';
            $mime_type[] = 'application/vnd.ms-excel';
            $extension[] = '.xls';
            $mime_type[] = 'application/pdf';
            $extension[] = '.pdf';
            $mime_type[] = 'application/postscript';
            $extension[] = '.ps';
            $mime_type[] = 'application/mac-binhex40';
            $extension[] = '.hqx';
            $mime_type[] = 'application/x-gzip';
            $extension[] = 'tar.gz';
            $mime_type[] = 'application/x-shockwave-flash';
            $extension[] = '.swf';
            $mime_type[] = 'application/x-stuffit';
            $extension[] = '.sit';
            $mime_type[] = 'application/x-tar';
            $extension[] = '.tar';
            $mime_type[] = 'application/zip';
            $extension[] = '.zip';
            $mime_type[] = 'application/x-tar';
            $extension[] = '.tar';
            $mime_type[] = 'text/html';
            $extension[] = '.html';
            $mime_type[] = 'text/plain';
            $extension[] = '.txt';
            $mime_type[] = 'text/rtf';
            $extension[] = '.rtf';
            $mime_type[] = 'img/gif';
            $extension[] = '.gif';
            $mime_type[] = 'img/jpeg';
            $extension[] = '.jpg';
            $mime_type[] = 'img/png';
            $extension[] = '.png';
            $mime_type[] = 'audio/midi';
            $extension[] = '.mid';
            $mime_type[] = 'audio/mpeg';
            $extension[] = '.mp3';
            $mime_type[] = 'audio/x-aiff';
            $extension[] = '.aif';
            $mime_type[] = 'audio/x-pn-realaudio';
            $extension[] = '.rm';
            $mime_type[] = 'audio/x-pn-realaudio-plugin';
            $extension[] = '.rpm';
            $mime_type[] = 'audio/x-wav';
            $extension[] = '.wav';
            $mime_type[] = 'video/mpeg';
            $extension[] = '.mpg';
            $mime_type[] = 'video/mpeg4-generic';
            $extension[] = '.mp4';
            $mime_type[] = 'video/quicktime';
            $extension[] = '.mov';
            $mime_type[] = 'video/x-msvideo';
            $extension[] = '.avi';
            $mime_type[] = 'video/x-ms-wmv';
            $extension[] = '.wmv';
            $mime_type[] = 'video/x-flv';
            $extension[] = '.flv';
            $mime_type[] = 'image/svg+xml';
            $extension[] = '.svg';
            $mime_type[] = 'image/svg+xml';
            $extension[] = '.svgz';
            $mime_type[] = 'video/ogg';
            $extension[] = '.ogv';
            $mime_type[] = 'audio/ogg';
            $extension[] = '.oga';
            $mime_type[] = 'application/ogg';
            $extension[] = '.ogg';
            $mime_type[] = 'application/ogg';
            $extension[] = '.ogx';
            $mime_type[] = 'application/x-freemind';
            $extension[] = '.mm';
            $mime_type[] = 'application/vnd.ms-word.document.macroEnabled.12';
            $extension[] = '.docm';
            $mime_type[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $extension[] = '.docx';
            $mime_type[] = 'application/vnd.ms-word.template.macroEnabled.12';
            $extension[] = '.dotm';
            $mime_type[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
            $extension[] = '.dotx';
            $mime_type[] = 'application/vnd.ms-powerpoint.template.macroEnabled.12';
            $extension[] = '.potm';
            $mime_type[] = 'application/vnd.openxmlformats-officedocument.presentationml.template';
            $extension[] = '.potx';
            $mime_type[] = 'application/vnd.ms-powerpoint.addin.macroEnabled.12';
            $extension[] = '.ppam';
            $mime_type[] = 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12';
            $extension[] = '.ppsm';
            $mime_type[] = 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
            $extension[] = '.ppsx';
            $mime_type[] = 'application/vnd.ms-powerpoint.presentation.macroEnabled.12';
            $extension[] = '.pptm';
            $mime_type[] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            $extension[] = '.pptx';
            $mime_type[] = 'application/vnd.ms-excel.addin.macroEnabled.12';
            $extension[] = '.xlam';
            $mime_type[] = 'application/vnd.ms-excel.sheet.binary.macroEnabled.12';
            $extension[] = '.xlsb';
            $mime_type[] = 'application/vnd.ms-excel.sheet.macroEnabled.12';
            $extension[] = '.xlsm';
            $mime_type[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $extension[] = '.xlsx';
            $mime_type[] = 'application/vnd.ms-excel.template.macroEnabled.12';
            $extension[] = '.xltm';
            $mime_type[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';
            $extension[] = '.xltx';

            // Test on PC (files with no extension get application/octet-stream)
            //$mime_type[] = 'application/octet-stream';      $extension[] = '.ext';

            // Check whether the MIME type sent by the browser is within the table

            foreach ($mime_type as $key => & $type) {
                if ($type == $file_type) {
                    $file_name .= $extension[$key];
                    break;
                }
            }

            unset($mime_type, $extension, $type, $key); // Delete to eschew possible collisions
        }

        return $file_name;
    }


    /**
     * Manages all the unzipping process of an uploaded document
     * This uses the item_property table for properties of documents
     *
     * @author Hugues Peeters <hugues.peeters@claroline.net>
     * @author Bert Vanderkimpen
     *
     * @param  array  $uploaded_file - follows the $_FILES Structure
     * @param  string $upload_path   - destination of the upload.
     *                                This path is to append to $base_work_dir
     * @param  string $base_work_dir  - base working directory of the module
     * @param  int $max_filled_space  - amount of bytes to not exceed in the base
     *                                working directory
     * @param        boolean    Output switch. Optional. If no output not wanted on success, set to false.
     *
     * @return boolean true if it succeeds false otherwise
     */
    static function unzip_uploaded_document(
        $uploaded_file,
        $upload_path,
        $base_work_dir,
        $max_filled_space,
        $output = true,
        $to_group_id = 0
    ) {
        global $_course;
        global $_user;
        global $to_user_id;
        global $to_group_id;

        $zip_file = new PclZip($uploaded_file['tmp_name']);

        // Check the zip content (real size and file extension)

        $zip_content_array = (array)$zip_file->listContent();
        $real_filesize = 0;
        foreach ($zip_content_array as & $this_content) {
            $real_filesize += $this_content['size'];
        }

        if (!DocumentManager::enough_space($real_filesize, $max_filled_space)) {
            Display::display_error_message(get_lang('UplNotEnoughSpace'));

            return false;
        }

        // It happens on Linux that $upload_path sometimes doesn't start with '/'
        if ($upload_path[0] != '/') {
            $upload_path = '/'.$upload_path;
        }

        /*	Uncompressing phase */

        // Get into the right directory
        $save_dir = getcwd();
        chdir($base_work_dir.$upload_path);
        // We extract using a callback function that "cleans" the path
        //@todo check if this works
        $unzipping_state = $zip_file->extract(
            PCLZIP_CB_PRE_EXTRACT,
            'FileManager::clean_up_files_in_zip',
            PCLZIP_OPT_REPLACE_NEWER
        );
        // Add all documents in the unzipped folder to the database
        self::add_all_documents_in_folder_to_database(
            $_course,
            $_user['user_id'],
            $base_work_dir,
            $upload_path == '/' ? '' : $upload_path,
            $to_group_id
        );

        //Display::display_normal_message(get_lang('UplZipExtractSuccess'));
        return true;
    }

    /**
     * This function is a callback function that is used while extracting a zipfile
     * http://www.phpconcept.net/pclzip/man/en/index.php?options-pclzip_cb_pre_extract
     *
     * @param $p_event
     * @param $p_header
     * @return 1 (If the function returns 1, then the extraction is resumed)
     */
    function clean_up_files_in_zip($p_event, &$p_header)
    {
        $res = self::clean_up_path($p_header['filename']);

        return $res;
    }

    /**
     * This function cleans up a given path
     * by eliminating dangerous file names and cleaning them
     *
     * @param string $path
     * @return $path
     * @see FileManager::disable_dangerous_file()
     * @see replace_dangerous_char()
     */
    static function clean_up_path(&$path)
    {
        // Split the path in folders and files
        $path_array = explode('/', $path);
        // Clean up every foler and filename in the path
        foreach ($path_array as $key => & $val) {
            // We don't want to lose the dots in ././folder/file (cfr. zipfile)
            if ($val != '.') {
                $val = self::disable_dangerous_file(replace_dangerous_char($val));
            }
        }
        // Join the "cleaned" path (modified in-place as passed by reference)
        $path = implode('/', $path_array);
        $res = self::filter_extension($path);

        return $res;
    }

    /**
     * Checks if the file is dangerous, based on extension and/or mimetype.
     * The list of extensions accepted/rejected can be found from
     * api_get_setting('upload_extensions_exclude') and api_get_setting('upload_extensions_include')
     * @param    string     filename passed by reference. The filename will be modified if filter rules say so! (you can include path but the filename should look like 'abc.html')
     * @return    int        0 to skip file, 1 to keep file
     */
    static function filter_extension(&$filename)
    {

        if (substr($filename, -1) == '/') {
            return 1; // Authorize directories
        }
        $blacklist = api_get_setting('upload_extensions_list_type');
        if ($blacklist != 'whitelist') { // if = blacklist
            $extensions = explode(';', strtolower(api_get_setting('upload_extensions_blacklist')));

            $skip = api_get_setting('upload_extensions_skip');
            $ext = strrchr($filename, '.');
            $ext = substr($ext, 1);
            if (empty($ext)) {
                return 1; // We're in blacklist mode, so accept empty extensions
            }
            if (in_array(strtolower($ext), $extensions)) {
                if ($skip == 'true') {
                    return 0;
                } else {
                    $new_ext = api_get_setting('upload_extensions_replace_by');
                    $filename = str_replace('.'.$ext, '.'.$new_ext, $filename);

                    return 1;
                }
            } else {
                return 1;
            }
        } else {
            $extensions = split(';', strtolower(api_get_setting('upload_extensions_whitelist')));
            $skip = api_get_setting('upload_extensions_skip');
            $ext = strrchr($filename, '.');
            $ext = substr($ext, 1);
            if (empty($ext)) {
                return 1; // Accept empty extensions
            }
            if (!in_array(strtolower($ext), $extensions)) {
                if ($skip == 'true') {
                    return 0;
                } else {
                    $new_ext = api_get_setting('upload_extensions_replace_by');
                    $filename = str_replace('.'.$ext, '.'.$new_ext, $filename);

                    return 1;
                }
            } else {
                return 1;
            }
        }
    }

    /**
     * Adds a new document to the database
     *
     * @param array $_course
     * @param string $path
     * @param string $filetype
     * @param int $filesize
     * @param string $title
     *
     * @return id if inserted document
     */
    static function add_document(
        $_course,
        $path,
        $filetype,
        $filesize,
        $title,
        $comment = null,
        $readonly = 0,
        $save_visibility = true,
        $group_id = null
    ) {
        $session_id = api_get_session_id();
        $readonly = intval($readonly);
        $comment = Database::escape_string($comment);
        $path = Database::escape_string($path);
        $filetype = Database::escape_string($filetype);
        $filesize = intval($filesize);
        $title = Database::escape_string(htmlspecialchars($title));
        $c_id = $_course['real_id'];

        $table_document = Database::get_course_table(TABLE_DOCUMENT);
        $sql = "INSERT INTO $table_document (c_id, path, filetype, size, title, comment, readonly, session_id)
	        VALUES ($c_id, '$path','$filetype','$filesize','$title', '$comment', $readonly, $session_id)";

        if (Database::query($sql)) {
            $document_id = Database::insert_id();
            if ($document_id) {
                if ($save_visibility) {
                    api_set_default_visibility($document_id, TOOL_DOCUMENT, $group_id);
                }
            }

            return $document_id;
        } else {
            return false;
        }
    }

    /**
     * Updates an existing document in the database
     * as the file exists, we only need to change the size
     *
     * @param array $_course
     * @param int $document_id
     * @param int $filesize
     * @param int $readonly
     *
     * @return boolean true /false
     */
    static function update_existing_document($_course, $document_id, $filesize, $readonly = 0)
    {
        $document_table = Database::get_course_table(TABLE_DOCUMENT);
        $document_id = intval($document_id);

        $filesize = intval($filesize);
        $readonly = intval($readonly);
        $course_id = $_course['real_id'];

        $sql = "UPDATE $document_table SET size = '$filesize' , readonly = '$readonly'
			WHERE c_id = $course_id AND id = $document_id";
        if (Database::query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * This function updates the last_edit_date, last edit user id on all folders in a given path
     *
     * @param array $_course
     * @param string $path
     * @param int $user_id
     */
    static function item_property_update_on_folder($_course, $path, $user_id)
    {
        //display_message("Start update_lastedit_on_folder");
        // If we are in the root, just return... no need to update anything
        if ($path == '/') {
            return;
        }

        $user_id = intval($user_id);

        // If the given path ends with a / we remove it
        $endchar = substr($path, strlen($path) - 1, 1);
        if ($endchar == '/') {
            $path = substr($path, 0, strlen($path) - 1);
        }

        $TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

        // Get the time
        $time = date('Y-m-d H:i:s', time());

        // Det all paths in the given path
        // /folder/subfolder/subsubfolder/file
        // if file is updated, subsubfolder, subfolder and folder are updated

        $exploded_path = explode('/', $path);
        $course_id = api_get_course_int_id();
        foreach ($exploded_path as $key => & $value) {
            // We don't want a slash before our first slash
            if ($key != 0) {
                $newpath .= '/'.$value;

                //echo 'path= '.$newpath.'<br />';
                // Select ID of given folder
                $folder_id = DocumentManager::get_document_id($_course, $newpath);

                if ($folder_id) {
                    $sql = "UPDATE $TABLE_ITEMPROPERTY SET lastedit_date='$time',lastedit_type='DocumentInFolderUpdated', lastedit_user_id='$user_id'
						WHERE c_id = $course_id AND tool='".TOOL_DOCUMENT."' AND ref='$folder_id'";
                    Database::query($sql);
                }
            }
        }
    }

    /**
     * Returns the directory depth of the file.
     *
     * @author    Olivier Cauberghe <olivier.cauberghe@ugent.be>
     * @param    path+filename eg: /main/document/document.php
     * @return    The directory depth
     */
    static function get_levels($filename)
    {
        $levels = explode('/', $filename);
        if (empty($levels[count($levels) - 1])) {
            unset($levels[count($levels) - 1]);
        }

        return count($levels);
    }

    /**
     * Retrieves the image path list in a html file
     *
     * @author Hugues Peeters <hugues.peeters@claroline.net>
     * @param  string $html_file
     * @return array -  images path list
     */
    static function search_img_from_html($html_file)
    {

        $img_path_list = array();

        if (!$fp = fopen($html_file, 'r')) {
            return;
        }

        // Aearch and store occurences of the <img> tag in an array
        $size_file = (filesize($html_file) === 0) ? 1 : filesize($html_file);
        if (isset($fp) && $fp !== false) {
            $buffer = fread($fp, $size_file);
            if (strlen($buffer) >= 0 && $buffer !== false) {
                //
            } else {
                die('<center>Can not read file.</center>');
            }
        } else {
            die('<center>Can not read file.</center>');
        }
        $matches = array();
        if (preg_match_all('~<[[:space:]]*img[^>]*>~i', $buffer, $matches)) {
            $img_tag_list = $matches[0];
        }

        fclose($fp);
        unset($buffer);

        // Search the image file path from all the <IMG> tag detected

        if (sizeof($img_tag_list) > 0) {
            foreach ($img_tag_list as & $this_img_tag) {
                if (preg_match('~src[[:space:]]*=[[:space:]]*[\"]{1}([^\"]+)[\"]{1}~i', $this_img_tag, $matches)) {
                    $img_path_list[] = $matches[1];
                }
            }
            $img_path_list = array_unique($img_path_list); // Remove duplicate entries
        }

        return $img_path_list;
    }

    /**
     * Creates a new directory trying to find a directory name
     * that doesn't already exist
     * (we could use unique_name() here...)
     *
     * @author  Hugues Peeters <hugues.peeters@claroline.net>
     * @author  Bert Vanderkimpen
     * @param   array   $_course current course information
     * @param   int     $user_id current user id
     * @param   string  $desiredDirName complete path of the desired name
     * @param   string  The visible name of the directory
     * @param   int     Visibility (0 for invisible, 1 for visible, 2 for deleted)
     * @return  string  actual directory name if it succeeds,
     *          boolean false otherwise
     */
    static function create_unexisting_directory(
        $_course,
        $user_id,
        $session_id,
        $to_group_id,
        $to_user_id,
        $base_work_dir,
        $desired_dir_name,
        $title = null,
        $visibility = ''
    ) {
        $nb = '';
        // add numerical suffix to directory if another one of the same number already exists
        while (file_exists($base_work_dir.$desired_dir_name.$nb)) {
            $nb += 1;
        }

        if ($title == null) {
            $title = basename($desired_dir_name);
        }
        $course_id = $_course['real_id'];

        if (mkdir($base_work_dir.$desired_dir_name.$nb, api_get_permissions_for_new_directories(), true)) {
            // Check if pathname already exists inside document table
            $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
            $sql = "SELECT path FROM $tbl_document WHERE c_id = $course_id AND path='".$desired_dir_name.$nb."'";

            $rs = Database::query($sql);
            if (Database::num_rows($rs) == 0) {
                $document_id = self::add_document(
                    $_course,
                    $desired_dir_name.$nb,
                    'folder',
                    0,
                    $title,
                    null,
                    0,
                    true,
                    $to_group_id
                );
                if ($document_id) {
                    // Update document item_property
                    if ($visibility !== '') {
                        $visibilities = array(0 => 'invisible', 1 => 'visible', 2 => 'delete');
                        api_item_property_update(
                            $_course,
                            TOOL_DOCUMENT,
                            $document_id,
                            $visibilities[$visibility],
                            $user_id,
                            $to_group_id,
                            $to_user_id,
                            null,
                            null,
                            $session_id
                        );
                    } else {
                        api_item_property_update(
                            $_course,
                            TOOL_DOCUMENT,
                            $document_id,
                            'FolderCreated',
                            $user_id,
                            $to_group_id,
                            $to_user_id,
                            null,
                            null,
                            $session_id
                        );
                    }

                    return $desired_dir_name.$nb;
                }
            } else {
                //This means the folder NOT exist in the filesystem (now this was created) but there is a record in the Database
                return $desired_dir_name.$nb;
            }
        } else {
            return false;
        }
    }

    /**
     * Handles uploaded missing images
     *
     * @author Hugues Peeters <hugues.peeters@claroline.net>
     * @author Bert Vanderkimpen
     * @param array $_course
     * @param array $uploaded_file_collection - follows the $_FILES Structure
     * @param string $base_work_dir
     * @param string $missing_files_dir
     * @param int $user_id
     * @param int $max_filled_space
     */
    static function move_uploaded_file_collection_into_directory(
        $_course,
        $uploaded_file_collection,
        $base_work_dir,
        $missing_files_dir,
        $user_id,
        $to_group_id,
        $to_user_id,
        $max_filled_space
    ) {
        $number_of_uploaded_images = count($uploaded_file_collection['name']);
        for ($i = 0; $i < $number_of_uploaded_images; $i++) {
            $missing_file['name'] = $uploaded_file_collection['name'][$i];
            $missing_file['type'] = $uploaded_file_collection['type'][$i];
            $missing_file['tmp_name'] = $uploaded_file_collection['tmp_name'][$i];
            $missing_file['error'] = $uploaded_file_collection['error'][$i];
            $missing_file['size'] = $uploaded_file_collection['size'][$i];

            $upload_ok = self::process_uploaded_file($missing_file);
            if ($upload_ok) {
                $new_file_list[] = self::handle_uploaded_document(
                    $_course,
                    $missing_file,
                    $base_work_dir,
                    $missing_files_dir,
                    $user_id,
                    $to_group_id,
                    $to_user_id,
                    $max_filled_space,
                    0,
                    'overwrite'
                );
            }
            unset($missing_file);
        }

        return $new_file_list;
    }

    /**
     * Opens the old html file and replace the src path into the img tag
     * This also works for files in subdirectories.
     * @param $original_img_path is an array
     * @param $new_img_path is an array
     */
    static function replace_img_path_in_html_file($original_img_path, $new_img_path, $html_file)
    {
        global $_course;

        // Open the file

        $fp = fopen($html_file, 'r');
        $buffer = fread($fp, filesize($html_file));


        // Fix the image tags

        for ($i = 0, $fileNb = count($original_img_path); $i < $fileNb; $i++) {
            $replace_what = $original_img_path[$i];
            // We only need the directory and the filename /path/to/file_html_files/missing_file.gif -> file_html_files/missing_file.gif
            $exploded_file_path = explode('/', $new_img_path[$i]);
            $replace_by = $exploded_file_path[count($exploded_file_path) - 2].'/'.$exploded_file_path[count(
                $exploded_file_path
            ) - 1];
            $buffer = str_replace($replace_what, $replace_by, $buffer);
        }

        $new_html_content .= $buffer;

        @fclose($fp);

        // Write the resulted new file

        if (!$fp = fopen($html_file, 'w')) {
            return;
        }

        if (!fwrite($fp, $new_html_content)) {
            return;
        }
    }

    /**
     * Checks the extension of a file, if it's .htm or .html
     * we use search_img_from_html to get all image paths in the file
     *
     * @param string $file
     * @return array paths
     * @see check_for_missing_files() uses search_img_from_html()
     */
    static function check_for_missing_files($file)
    {
        if (strrchr($file, '.') == '.htm' || strrchr($file, '.') == '.html') {
            $img_file_path = self::search_img_from_html($file);

            return $img_file_path;
        }

        return false;
    }

    /**
     * This function builds a form that asks for the missing images in a html file
     * maybe we should do this another way?
     *
     * @param array $missing_files
     * @param string $upload_path
     * @param string $file_name
     * @return string the form
     */
    static function build_missing_files_form($missing_files, $upload_path, $file_name)
    {
        // Do we need a / or not?
        $added_slash = ($upload_path == '/') ? '' : '/';
        $folder_id = DocumentManager::get_document_id(api_get_course_info(), $upload_path);
        // Build the form
        $form = "<p><strong>".get_lang('MissingImagesDetected')."</strong></p>"
            ."<form method=\"post\" action=\"".api_get_self()."\" enctype=\"multipart/form-data\">"
            // Related_file is the path to the file that has missing images
            ."<input type=\"hidden\" name=\"related_file\" value=\"".$upload_path.$added_slash.$file_name."\" />"
            ."<input type=\"hidden\" name=\"upload_path\" value=\"".$upload_path."\" />"
            ."<input type=\"hidden\" name=\"id\" value=\"".$folder_id."\" />"
            ."<table border=\"0\">";
        foreach ($missing_files as & $this_img_file_path) {
            $form .= "<tr>"
                ."<td>".basename($this_img_file_path)." : </td>"
                ."<td>"
                ."<input type=\"file\" name=\"img_file[]\"/>"
                ."<input type=\"hidden\" name=\"img_file_path[]\" value=\"".$this_img_file_path."\" />"
                ."</td>"
                ."</tr>";
        }
        $form .= "</table>"
            ."<button type='submit' name=\"cancel_submit_image\" value=\"".get_lang(
            'Cancel'
        )."\" class=\"cancel\">".get_lang('Cancel')."</button>"
            ."<button type='submit' name=\"submit_image\" value=\"".get_lang('Ok')."\" class=\"save\">".get_lang(
            'Ok'
        )."</button>"
            ."</form>";

        return $form;
    }

    /**
     * This recursive function can be used during the upgrade process form older versions of Chamilo
     * It crawls the given directory, checks if the file is in the DB and adds it if it's not
     *
     * @param string $base_work_dir
     * @param string $current_path, needed for recursivity
     */
    static function add_all_documents_in_folder_to_database(
        $_course,
        $user_id,
        $base_work_dir,
        $current_path = '',
        $to_group_id = 0
    ) {
        $current_session_id = api_get_session_id();
        $path = $base_work_dir.$current_path;
        // Open dir
        $handle = opendir($path);
        if (is_dir($path)) {
            // Run trough
            while ($file = readdir($handle)) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                $completepath = "$path/$file";
                // Directory?
                if (is_dir($completepath)) {
                    $title = self::get_document_title($file);
                    $safe_file = replace_dangerous_char($file);
                    @rename($path.'/'.$file, $path.'/'.$safe_file);
                    // If we can't find the file, add it
                    if (!DocumentManager::get_document_id($_course, $current_path.'/'.$safe_file)) {
                        $document_id = self::add_document($_course, $current_path.'/'.$safe_file, 'folder', 0, $title);
                        api_item_property_update(
                            $_course,
                            TOOL_DOCUMENT,
                            $document_id,
                            'DocumentAdded',
                            $user_id,
                            $to_group_id,
                            null,
                            null,
                            null,
                            $current_session_id
                        );
                        //echo $current_path.'/'.$safe_file.' added!<br />';
                    }
                    // Recursive
                    self::add_all_documents_in_folder_to_database(
                        $_course,
                        $user_id,
                        $base_work_dir,
                        $current_path.'/'.$safe_file,
                        $to_group_id
                    );
                } else {
                    //Rename
                    $safe_file = self::disable_dangerous_file(replace_dangerous_char($file, 'strict'));
                    @rename($base_work_dir.$current_path.'/'.$file, $base_work_dir.$current_path.'/'.$safe_file);
                    $document_id = DocumentManager::get_document_id($_course, $current_path.'/'.$safe_file);
                    if (!$document_id) {
                        $title = self::get_document_title($file);
                        $size = filesize($base_work_dir.$current_path.'/'.$safe_file);
                        $document_id = self::add_document(
                            $_course,
                            $current_path.'/'.$safe_file,
                            'file',
                            $size,
                            $title
                        );
                        api_item_property_update(
                            $_course,
                            TOOL_DOCUMENT,
                            $document_id,
                            'DocumentAdded',
                            $user_id,
                            $to_group_id,
                            null,
                            null,
                            null,
                            $current_session_id
                        );
                        //echo $current_path.'/'.$safe_file.' added!<br />';
                    } else {
                        api_item_property_update(
                            $_course,
                            TOOL_DOCUMENT,
                            $document_id,
                            'DocumentUpdated',
                            $user_id,
                            $to_group_id,
                            null,
                            null,
                            null,
                            $current_session_id
                        );
                    }
                }
            }
        }
    }
}