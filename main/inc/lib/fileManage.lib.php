<?php
/* For licensing terms, see /license.txt */
/**
 *	This is the file manage library for Chamilo.
 *	Include/require it in your code to use its functionality.
 *	@package chamilo.library
 */

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
function update_db_info($action, $old_path, $new_path = '') {
    $dbTable = Database::get_course_table(TABLE_DOCUMENT);
    $course_id = api_get_course_int_id();
    
	/* DELETE */
	if ($action == 'delete') {
		/*  // RH: metadata, update 2004/08/23
		    these two lines replaced by new code below:
        	$query = "DELETE FROM $dbTable
    		WHERE path='".$old_path."' OR path LIKE '".$old_path."/%'";
        */
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
		//Display::display_normal_message("new_path = $new_path");
		if ($new_path[0] == '.') $new_path = substr($new_path, 1);
		$new_path = str_replace('//', '/', $new_path);
		//Display::display_normal_message("character 0 = " . $new_path[0] . " 1=" . $new_path[1]);
		//Display::display_normal_message("new_path = $new_path");

		// Older broken version
		//$query = "UPDATE $dbTable
		//SET path = CONCAT('".$new_path."', SUBSTRING(path, LENGTH('".$old_path."')+1) )
		//WHERE path = '".$old_path."' OR path LIKE '".$old_path."/%'";

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
function check_name_exist($file_path) {
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
function my_delete($file) {
	if (check_name_exist($file)) {
		if (is_file($file)) { // FILE CASE
			unlink($file);
			return true;
		} elseif (is_dir($file)) { // DIRECTORY CASE
			removeDir($file);
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
function removeDir($dir) {
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
				if (!removeDir($dir.'/'.$readdir)) {
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

function folder_is_empty($in_folder) {
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
function my_rename($file_path, $new_file_name) {

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

	if (strtolower($new_file_name) != strtolower($old_file_name) && check_name_exist($path.'/'.$new_file_name)) {
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
function move($source, $target) {

	if (check_name_exist($source)) {
		$file_name = basename($source);

		if (check_name_exist($target.'/'.$file_name)) {
			return false;
		} else {
			/* File case */
			if (is_file($source)) {
				copy($source , $target.'/'.$file_name);
				unlink($source);
				return true;
			}
			/* Directory case */
			elseif (is_dir($source)) {
				// Check to not copy the directory inside itself
				if (ereg('^'.$source.'/', $target.'/')) { // TODO: ereg() function is deprecated in PHP 5.3
					return false;
				} else {
					copyDirTo($source, $target);
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
function copyDirTo($orig_dir_path, $destination, $move = true) {

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
					copyDirTo($this_dir, $destination_trail, $move); // Recursivity
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
function index_dir($path) {
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
                $sub_dir_array = index_dir($dir_array[$i]); // Function recursivity
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
function index_and_sort_dir($path) {
	$dir_list = index_dir($path);
	if ($dir_list) {
		natsort($dir_list);
		return $dir_list;
	}
	return false;
}


/**
 * Builds a html form listing all directories of a given directory
 *
 */
function form_dir_list($source_type, $source_component, $command, $base_work_dir) {

	$dir_list = index_and_sort_dir($base_work_dir);
	$dialog_box .= "<form action=\"".api_get_self()."\" method=\"post\">\n" ;
	$dialog_box .= "<input type=\"hidden\" name=\"".$source_type."\" value=\"".$source_component."\">\n" ;
	$dialog_box .= get_lang('Move').' '.$source_component.' '.get_lang('To');
	$dialog_box .= "<select name=\"".$command."\">\n" ;
	$dialog_box .= "<option value=\"\" style=\"color:#999999\">".get_lang('Root')."\n";

	$bwdLen = strlen($base_work_dir) ;	// base directories lenght, used under

	/* build html form inputs */
	if ($dir_list) {
		while (list( , $path_value) = each($dir_list) ) {
			$path_value = substr ( $path_value , $bwdLen );		// Truncates cunfidential informations confidentielles
			$dirname = basename ($path_value);					// Extracts $path_value directory name du nom

			/* compute de the display tab */

			$tab = "";											// $tab reinitialisation
			$depth = substr_count($path_value, '/');			// The number of nombre '/' indicates the directory deepness

			for ($h = 0; $h < $depth; $h++) {
				$tab .= "&nbsp;&nbsp;";
			}
			$dialog_box .= "<option value=\"$path_value\">$tab>$dirname\n";
		}
	}

	$dialog_box .= "</select>\n";
	$dialog_box .= "<input type=\"submit\" value=\"".get_lang('Ok')."\">";
	$dialog_box .= "</form>\n";

	return $dialog_box;
}

/**
 * Extracting extention of a filename
 *
 * @returns array
 * @param 	string	$filename 		filename
 */
function getextension($filename) {
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
function dirsize($root, $recursive = true) {
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

/*	CLASS FileManager */

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
		Returns a list of all directories, except the base dir,
		of the current course. This function uses recursion.

		Convention: the parameter $path does not end with a slash.

		@author Roan Embrechts
		@version 1.0.1
	*/
	function list_all_directories($path) {

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



} //end class FileManager





/*	DEPRECATED FUNCTIONS */

/**
 * Like in Java, creates the directory named by this abstract pathname,
 * including any necessary but nonexistent parent directories.
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @author Christophe Gesche <gesche@ipm.ucl.ac.be>
 *
 * @param  string $path - path to create
 * @param  string $mode - directory permission (default is '770')
 *
 * @return boolean TRUE if succeeds FALSE otherwise
 */
function mkdirs($path, $mode = '0770') {
	if (file_exists($path)) {
		return false;
	} else {
		FileManager :: mkdirs(dirname($path), $mode);
	 	//mkdir($path, $mode);
		return true;
	}
}

/**
 * @deprecated 06-FEB-2010. The function mkdir() is able to create directories recursively.
 * @link http://php.net/manual/en/function.mkdir.php
 *
 * to create missing directory in a gived path
 *
 * @returns a resource identifier or FALSE if the query was not executed correctly.
 * @author KilerCris@Mail.com original function from  php manual
 * @author Christophe Gesch� gesche@ipm.ucl.ac.be Claroline Team
 * @since  28-Aug-2001 09:12
 * @param 	sting	$path 		wanted path
 * @param 	boolean	$verbose	fix if comments must be printed
 * @param 	string	$mode		fix if chmod is same of parent or default (Note: This misterious parameter is not used).
 * Note 	string  $langCreatedIn string used to say "create in"
 */
function mkpath($path, $verbose = false, $mode = 'herit') {
	global $langCreatedIn, $_configuration;

	$path = str_replace('/', "\\", $path);
	$dirs = explode("\\", $path);

	$path = $dirs[0];

	if ($verbose) {
		echo '<ul>';
	}

	for ($i = 1; $i < sizeof($dirs); $i++) {
		$path .= '/'.$dirs[$i];

		if (ereg('^'.$path, $_configuration['root_sys']) && strlen($path) < strlen($_configuration['root_sys'])) {
			continue;
		}

		if (!is_dir($path)) {
			$ret = mkdir($path, api_get_permissions_for_new_directories());

			if ($ret) {
				if ($verbose) {
					echo '<li><strong>'.basename($path).'</strong><br />'.$langCreatedIn.'<br /><strong>'.realpath($path.'/..').'</strong></li>';
				}
			} else {
				if ($verbose) {
					echo '</ul>error : '.$path.' not created';
				}
				$ret = false;
				break;
			}
		}
	}

	if ($verbose) {
		echo '</ul>';
	}

	return $ret;
}
