<?php
/* For licensing terms, see /license.txt */

/**
 *	This is the file manage library for Chamilo.
 *	Include/require it in your code to use its functionality.
 *	@package chamilo.library
 */

/**
	This class contains functions that you can access statically.

	FileManager::list_all_directories($path)
	FileManager::list_all_files($dir_array)
	FileManager::compat_load_file($file_name)
	FileManager::set_default_settings($upload_path, $filename, $filetype="file", $glued_table, $default_visibility='v')

	@author Roan Embrechts
	@version 1.1, July 2004
 *	@package chamilo.library
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
    static function update_db_info($action, $old_path, $new_path = '') {
        $dbTable = Database::get_course_table(TABLE_DOCUMENT);
        $course_id = api_get_course_int_id();

        /* DELETE */
        if ($action == 'delete') {

            $old_path = Database::escape_string($old_path);
            $to_delete = "WHERE c_id = $course_id AND path LIKE BINARY '".$old_path."' OR path LIKE BINARY '".$old_path."/%'";
            $query = "DELETE FROM $dbTable " . $to_delete;

            $result = Database::query("SELECT id FROM $dbTable " . $to_delete);

            if (Database::num_rows($result)) {
                require_once api_get_path(INCLUDE_PATH).'../metadata/md_funcs.php';
                $mdStore = new mdstore(TRUE);  // create if needed

                $md_type = (substr($dbTable, -13) == 'scormdocument') ? 'Scorm' : 'Document';

                while ($row = Database::fetch_array($result)) {
                    $eid = $md_type . '.' . $row['id'];
                    $mdStore->mds_delete($eid);
                    $mdStore->mds_delete_offspring($eid);
                }
            }
        }

        /* UPDATE */

        if ($action == 'update') {
            if ($new_path[0] == '.') $new_path = substr($new_path, 1);
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
    static function check_name_exist($file_path) {
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
    static function my_delete($file) {
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
     * @param string	$dir		directory to remove
     */
    static function removeDir($dir) {
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

    static function folder_is_empty($in_folder) {
        $tab_folder_content = scandir($in_folder);
        $folder_is_empty = 0;
        if ((count($tab_folder_content) == 2 && in_array(".", $tab_folder_content) && in_array("..", $tab_folder_content)) || (count($tab_folder_content) < 2)) {
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
    static function my_rename($file_path, $new_file_name) {

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
        $new_file_name = php2phps($new_file_name);

        if ($new_file_name == $old_file_name) {
            return $old_file_name;
        }

        if (strtolower($new_file_name) != strtolower($old_file_name) && self::check_name_exist($path.'/'.$new_file_name)) {
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
    static function move($source, $target) {

        if (self::check_name_exist($source)) {
            $file_name = basename($source);

            if (self::check_name_exist($target.'/'.$file_name)) {
                return false;
            } else {
                /* File case */
                if (is_file($source)) {
                    copy($source , $target.'/'.$file_name);
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
    static function copyDirTo($orig_dir_path, $destination, $move = true) {

        $save_dir = getcwd();
        // Extract directory name - create it at destination - update destination trail
        $dir_name = basename($orig_dir_path);
        if (is_dir($orig_dir_path)) {
            mkdir($destination.'/'.$dir_name, api_get_permissions_for_new_directories());
            $destination_trail = $destination.'/'.$dir_name;
            if (is_dir($destination)) {
                chdir ($orig_dir_path) ;
                $handle = opendir($orig_dir_path);

                while ($element = readdir($handle)) {
                    if ($element == '.' || $element == '..') {
                        continue; // Skip the current and parent directories
                    } elseif (is_file($element)) {
                        copy($element, $destination_trail.'/'.$element);

                        if ($move) {
                            unlink($element) ;
                        }
                    } elseif (is_dir($element)) {
                        $dir_to_copy[] = $orig_dir_path.'/'.$element;
                    }
                }

                closedir($handle) ;

                if (sizeof($dir_to_copy) > 0) {
                    foreach ($dir_to_copy as $this_dir) {
                        self::copyDirTo($this_dir, $destination_trail, $move); // Recursivity
                    }
                }

                if ($move) {
                    rmdir($orig_dir_path) ;
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
    static function index_dir($path) {
        $dir_array = array();
        $save_dir = getcwd();
        if (is_dir($path)){
            chdir($path);
            $handle = opendir($path);
            // Reads directory content end record subdirectoies names in $dir_array
            if ($handle !== false) {
                while ($element = readdir($handle)) {
                    if ($element == '.' || $element == '..') continue; // Skip the current and parent directories
                    if (is_dir($element)) $dir_array[] = $path.'/'.$element;
                }
                closedir($handle) ;
            }
            // Recursive operation if subdirectories exist
            $dir_number = sizeof($dir_array);
            if ($dir_number > 0) {
                for ($i = 0 ; $i < $dir_number ; $i++) {
                    $sub_dir_array = self::index_dir($dir_array[$i]); // Function recursivity
                    $dir_array  =  array_merge((array)$dir_array, (array)$sub_dir_array); // Data merge
                }
            }
        }
        chdir($save_dir) ;
        return $dir_array ;
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
    static function index_and_sort_dir($path) {
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
     * @param 	string	$filename 		filename
     */
    static function getextension($filename) {
        $bouts = explode('.', $filename);
        return array(array_pop($bouts), implode('.', $bouts));
    }

    /**
     * Calculation size of a directory
     *
     * @returns integer size
     * @param 	string	$path path to size
     * @param 	boolean $recursive if true , include subdir in total
     */
    static function dirsize($root, $recursive = true) {
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
	static function list_all_directories($path) {

		$result_array = array();
		if (is_dir($path)) {
			$save_dir = getcwd();
			chdir($path);
			$handle = opendir($path);
			while ($element = readdir($handle)) {
				if ($element == '.' || $element == '..') continue; // Skip the current and parent directories
				if (is_dir($element)) {
					$dir_array[] = $path.'/'.$element;
				}
			}
			closedir($handle);
			// Recursive operation if subdirectories exist
			$dir_number = sizeof($dir_array);
			if ($dir_number > 0) {
				for ($i = 0 ; $i < $dir_number ; $i++) {
					$sub_dir_array = FileManager::list_all_directories($dir_array[$i]); // Function recursivity
					if (is_array($dir_array) && is_array($sub_dir_array)) {
						$dir_array  =  array_merge($dir_array, $sub_dir_array); // Data merge
					}
				}
			}
			$result_array  =  $dir_array;
			chdir($save_dir) ;
		}
		return $result_array ;
	}

	/**
		This function receives a list of directories.
		It returns a list of all files in these directories

		@author Roan Embrechts
		@version 1.0
	*/
	function list_all_files($dir_array) {

		$element_array = array();
		if (is_dir($dir_array)) {

			$save_dir = getcwd();
			foreach ($dir_array as $directory) {
				chdir($directory);
				$handle = opendir($directory);
			   	while ($element = readdir($handle)) {
					if ($element == '.' || $element == '..' || $element == '.htaccess') continue; // Skip the current and parent directories
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
	function compat_load_file($file_name) {
		$buffer = '';
		if (file_exists($file_name)) {
			$fp = fopen($file_name, 'rb');
			$buffer = fread ($fp, filesize($file_name));
			fclose ($fp);
		}
		return $buffer;
	}

	/**
	 * Adds file/folder to document table in database
	 * improvement from set_default_settings (see below):
	 * take all info from function parameters
	 * no global variables needed
	 *
	 * NOTE $glued_table should already have backticks around it
	 * (get it from the database library, and it is done automatically)
	 *
	 * @param	path, filename, filetype,
				$glued_table, default_visibility

	 * action:	Adds an entry to the document table with the default settings.
	 * @author	Olivier Cauberghe <olivier.cauberghe@ugent.be>
	 * @author	Roan Embrechts
	 * @version 1.2
	 */
	function set_default_settings($upload_path, $filename, $filetype = 'file', $glued_table, $default_visibility = 'v') {
		if (!$default_visibility) $default_visibility = 'v';

		// Make sure path is not wrongly formed
		$upload_path = !empty($upload_path) ? "/$upload_path" : '';

		$endchar = substr($filename, strlen($filename) - 1, 1);
		if ($endchar == "\\" || $endchar == '/') {
			$filename = substr($filename, 0, strlen($filename) - 1);
		}

		$full_file_name = $upload_path.'/'.$filename;
		//$upload_path = str_replace("//", '/', $upload_path);
		$full_file_name = str_replace("//", '/', $full_file_name);

		$sql_query = "SELECT count(*) as number_existing FROM $glued_table WHERE path='$full_file_name'";
		$sql_result = Database::query($sql_query);
		$result = Database::fetch_array($sql_result);
		// Determine which query to execute
		if ($result['number_existing'] > 0) {
			// Entry exists, update
			$query = "UPDATE $glued_table SET path='$full_file_name',visibility='$default_visibility', filetype='$filetype' WHERE path='$full_file_name'";
		} else {
			// No entry exists, create new one
			$query = "INSERT INTO $glued_table (path,visibility,filetype) VALUES('$full_file_name','$default_visibility','$filetype')";
		}
		Database::query($query);
	}

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
}