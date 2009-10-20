<?php # $Id: fileManage.lib.php 21531 2009-06-20 16:03:29Z ivantcholakov $

/* vim: set expandtab tabstop=4 shiftwidth=4:
===============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	more copyrights held by individual contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
===============================================================================
*/

/**
==============================================================================
*	This is the file manage library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================
*/

/**
 * Update the file or directory path in the document db document table
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - action (string) - action type require : 'delete' or 'update'
 * @param  - oldPath (string) - old path info stored to change
 * @param  - newPath (string) - new path info to substitute
 * @desc Update the file or directory path in the document db document table
 *
 */

function update_db_info($action, $oldPath, $newPath="")
{
	global $dbTable; // table 'document'  // RH: see below

	/* DELETE */

	if ($action == "delete")
	{
		/*  // RH: metadata, update 2004/08/23
		    these two lines replaced by new code below:
        	$query = "DELETE FROM `$dbTable`
    		WHERE path='".$oldPath."' OR path LIKE '".$oldPath."/%'";
        */
        $to_delete = "WHERE path LIKE BINARY '".$oldPath."' OR path LIKE BINARY '".$oldPath."/%'";
        $query = "DELETE FROM $dbTable " . $to_delete;

        $result = Database::query("SELECT id FROM $dbTable " . $to_delete);

        if (Database::num_rows($result))
        {
            require_once(api_get_path(INCLUDE_PATH) . "../metadata/md_funcs.php");
            $mdStore = new mdstore(TRUE);  // create if needed

            $mdType = (substr($dbTable, -13) == 'scormdocument') ?
                'Scorm' : 'Document';

            while ($row = Database::fetch_array($result))
            {
                $eid = $mdType . '.' . $row['id'];
                $mdStore->mds_delete($eid);
                $mdStore->mds_delete_offspring($eid);
            }
        }
	}

	/* UPDATE */

	if ($action == "update")
	{
		//Display::display_normal_message("newPath = $newPath");
		if ($newPath[0] == ".") $newPath = substr($newPath,1);
		$newPath = str_replace('//','/',$newPath);
		//Display::display_normal_message("character 0 = " . $newPath[0] . " 1=" . $newPath[1]);
		//Display::display_normal_message("newPath = $newPath");

		//older broken version
		//$query = "UPDATE `$dbTable`
		//SET path = CONCAT('".$newPath."', SUBSTRING(path, LENGTH('".$oldPath."')+1) )
		//WHERE path = '".$oldPath."' OR path LIKE '".$oldPath."/%'";

		//attempt to update	- tested & working for root	dir
		$query = "UPDATE $dbTable
		SET path = CONCAT('".$newPath."', SUBSTRING(path, LENGTH('".$oldPath."')+1) )
		WHERE path LIKE BINARY '".$oldPath."' OR path LIKE BINARY '".$oldPath."/%'";
	}
	//echo $query;
	//error_log($query,0);
	Database::query($query,__FILE__,__LINE__);
	//Display::display_normal_message("query = $query");
}

//------------------------------------------------------------------------------

/**
 * Cheks a file or a directory actually exist at this location
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - filePath (string) - path of the presume existing file or dir
 * @return - boolean TRUE if the file or the directory exists
 *           boolean FALSE otherwise.
 */

function check_name_exist($filePath)
{
	clearstatcache();
	$save_dir = getcwd();
	if(!is_dir(dirname($filePath)))
	{
		return false;
	}
	chdir ( dirname($filePath) );
	$fileName = basename ($filePath);

	if (file_exists( $fileName ))
	{
		chdir($save_dir);
		return true;
	}
	else
	{
		chdir($save_dir);
		return false;
	}
}


/**
 * Delete a file or a directory
 *
 * @author - Hugues Peeters
 * @param  - $file (String) - the path of file or directory to delete
 * @return - bolean - true if the delete succeed
 *           bolean - false otherwise.
 * @see    - delete() uses check_name_exist() and removeDir() functions
 */

function my_delete($file)
{
	if ( check_name_exist($file) )
	{
		if ( is_file($file) ) // FILE CASE
		{
			unlink($file);
			return true;
		}

		elseif ( is_dir($file) ) // DIRECTORY CASE
		{
			removeDir($file);
			return true;
		}
	}
	else
	{
		return false; // no file or directory to delete
	}

}

//------------------------------------------------------------------------------

/**
 * removes a directory recursively
 *
 * @returns true if OK, otherwise false
 *
 * @author Amary <MasterNES@aol.com> (from Nexen.net)
 * @author Olivier Brouckaert <oli.brouckaert@skynet.be>
 *
 * @param string	$dir		directory to remove
 */

function removeDir($dir)
{
	if(!@$opendir = opendir($dir))
	{
		return false;
	}

	while($readdir = readdir($opendir))
	{
		if($readdir != '..' && $readdir != '.')
		{
			if(is_file($dir.'/'.$readdir))
			{
				if(!@unlink($dir.'/'.$readdir))
				{
					return false;
				}
			}
			elseif(is_dir($dir.'/'.$readdir))
			{
				if(!removeDir($dir.'/'.$readdir))
				{
					return false;
				}
			}
		}
	}

	closedir($opendir);

	if(!@rmdir($dir))
	{
		return false;
	}

	return true;
}

//------------------------------------------------------------------------------

/**
 * Rename a file or a directory
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - $filePath (string) - complete path of the file or the directory
 * @param  - $newFileName (string) - new name for the file or the directory
 * @return - boolean - true if succeed
 *         - boolean - false otherwise
 * @see    - rename() uses the check_name_exist() and php2phps() functions
 */

function my_rename($filePath, $newFileName)
{
	$save_dir = getcwd();
	$path        = dirname($filePath);
	$oldFileName = basename($filePath);

	$newFileName = replace_dangerous_char($newFileName);

	// If no extension, take the old one
	if ((strpos($newFileName, '.') === FALSE)
		&& ($dotpos = strrpos($oldFileName, '.')))
	{
		$newFileName .= substr($oldFileName, $dotpos);
	}

	// Note: still possible: 'xx.yy' -rename-> '.yy' -rename-> 'zz'
	// This is useful for folder names, where otherwise '.' would be sticky

	// Extension PHP is not allowed, change to PHPS
	$newFileName = php2phps($newFileName);

	if ($newFileName == $oldFileName) return $oldFileName;

	if (strtolower($newFileName) != strtolower($oldFileName) && check_name_exist($path."/".$newFileName)) return false;
	// On a Windows server, it would be better not to do the above check
	// because it succeeds for some new names resembling the old name.
	// But on Unix/Linux the check must be done because rename overwrites.

	chdir($path);
	$res = rename($oldFileName, $newFileName) ? $newFileName : false;
	chdir($save_dir);
	return $res;
}

//------------------------------------------------------------------------------


/**
 * Move a file or a directory to an other area
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - $source (String) - the path of file or directory to move
 * @param  - $target (String) - the path of the new area
 * @return - bolean - true if the move succeed
 *           bolean - false otherwise.
 * @see    - move() uses check_name_exist() and copyDirTo() functions
 */


function move($source, $target)
{
	if ( check_name_exist($source) )
	{
		$fileName = basename($source);

		if ( check_name_exist($target."/".$fileName) )
		{
			return false;
		}
		else
		{	/* File case */
			if ( is_file($source) )
			{
				copy($source , $target."/".$fileName);
				unlink($source);
				return true;
			}
			/* Directory case */
			elseif (is_dir($source))
			{
				// check to not copy the directory inside itself
				if (ereg("^".$source."/", $target."/"))
				{
					return false;
				}
				else
				{
					copyDirTo($source, $target);
					return true;
				}
			}
		}
	}
	else
	{
		return false;
	}

}

//------------------------------------------------------------------------------


/**
 * Move a directory and its content to an other area
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - $origDirPath (String) - the path of the directory to move
 * @param  - $destination (String) - the path of the new area
 * @return - no return !!
 */

function copyDirTo($origDirPath, $destination, $move=true)
{
	$save_dir=getcwd();
	// extract directory name - create it at destination - update destination trail
	$dirName = basename($origDirPath);
	mkdir ($destination."/".$dirName, 0775);
	$destinationTrail = $destination."/".$dirName;

	chdir ($origDirPath) ;
	$handle = opendir($origDirPath);

	while ($element = readdir($handle) )
	{
		if ( $element == "." || $element == "..")
		{
			continue; // skip the current and parent directories
		}
		elseif ( is_file($element) )
		{
			copy($element, $destinationTrail."/".$element);

			if($move)
			{
				unlink($element) ;
			}
		}
		elseif ( is_dir($element) )
		{
			$dirToCopy[] = $origDirPath."/".$element;
		}
	}

	closedir($handle) ;

	if ( sizeof($dirToCopy) > 0)
	{
		foreach($dirToCopy as $thisDir)
		{
			copyDirTo($thisDir, $destinationTrail, $move);	// recursivity
		}
	}

	if($move)
	{
		rmdir ($origDirPath) ;
	}
	chdir($save_dir);

}

//------------------------------------------------------------------------------


/* NOTE: These functions batch is used to automatically build HTML forms
 * with a list of the directories contained on the course Directory.
 *
 * From a thechnical point of view, form_dir_lists calls sort_dir wich calls index_dir
 */

/**
 * Indexes all the directories and subdirectories
 * contented in a given directory
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - path (string) - directory path of the one to index
 * @return - an array containing the path of all the subdirectories
 */

function index_dir($path)
{
	$save_dir = getcwd();
	chdir($path);
	$handle = opendir($path);

	// reads directory content end record subdirectoies names in $dir_array
	while ($element = readdir($handle) )
	{
		if ( $element == "." || $element == "..") continue;	// skip the current and parent directories
		if ( is_dir($element) )	 $dirArray[] = $path."/".$element;
	}

	closedir($handle) ;

	// recursive operation if subdirectories exist
	$dirNumber = sizeof($dirArray);
	if ( $dirNumber > 0 )
	{
		for ($i = 0 ; $i < $dirNumber ; $i++ )
		{
			$subDirArray = index_dir( $dirArray[$i] ) ;			    // function recursivity
			$dirArray  =  array_merge( (array)$dirArray , (array)$subDirArray );	// data merge
		}
	}

	chdir($save_dir) ;

	return $dirArray ;

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

function index_and_sort_dir($path)
{
	$dir_list = index_dir($path);

	if ($dir_list)
	{
		//sort($dir_list);
		natsort($dir_list);

		return $dir_list;
	}
	else
	{
		return false;
	}
}


/**
 * build an html form listing all directories of a given directory
 *
 */

function form_dir_list($sourceType, $sourceComponent, $command, $baseWorkDir)
{

	$dirList = index_and_sort_dir($baseWorkDir);

	$dialogBox .= "<form action=\"".api_get_self()."\" method=\"post\">\n" ;
	$dialogBox .= "<input type=\"hidden\" name=\"".$sourceType."\" value=\"".$sourceComponent."\">\n" ;
	$dialogBox .= get_lang('Move').' '.$sourceComponent.' '.get_lang('To');
	$dialogBox .= "<select name=\"".$command."\">\n" ;
	$dialogBox .= "<option value=\"\" style=\"color:#999999\">".get_lang('Root')."\n";

	$bwdLen = strlen($baseWorkDir) ;	// base directories lenght, used under

	/* build html form inputs */

	if ($dirList)
	{
		while (list( , $pathValue) = each($dirList) )
		{

			$pathValue = substr ( $pathValue , $bwdLen );		// truncate cunfidential informations confidentielles
			$dirname = basename ($pathValue);					// extract $pathValue directory name du nom

			/* compute de the display tab */

			$tab = "";										// $tab reinitialisation
			$depth = substr_count($pathValue, "/");			// The number of nombre '/' indicates the directory deepness

			for ($h=0; $h<$depth; $h++)
			{
				$tab .= "&nbsp;&nbsp;";
			}
			$dialogBox .= "<option value=\"$pathValue\">$tab>$dirname\n";
		}
	}

	$dialogBox .= "</select>\n";
	$dialogBox .= "<input type=\"submit\" value=\"".get_lang('Ok')."\">";
	$dialogBox .= "</form>\n";

	return $dialogBox;
}

//------------------------------------------------------------------------------

/**
 * to create missing directory in a gived path
 *
 * @returns a resource identifier or FALSE if the query was not executed correctly.
 * @author KilerCris@Mail.com original function from  php manual
 * @author Christophe Gesch� gesche@ipm.ucl.ac.be Claroline Team
 * @since  28-Aug-2001 09:12
 * @param 	sting	$path 		wanted path
 * @param 	boolean	$verbose	fix if comments must be printed
 * @param 	string	$mode		fix if chmod is same of parent or default
 * @global 	string  $langCreatedIn string to say "create in"
 */
function mkpath($path, $verbose = false, $mode = "herit")
{
	global $langCreatedIn, $_configuration;

	$path=str_replace("/","\\",$path);
	$dirs=explode("\\",$path);

	$path=$dirs[0];

	if($verbose)
	{
		echo "<UL>";
	}

    $perm = api_get_setting('permissions_for_new_directories');
    $perm = octdec(!empty($perm)?$perm:'0770');

	for($i=1;$i < sizeof($dirs);$i++)
	{
		$path.='/'.$dirs[$i];

		if(ereg('^'.$path,$_configuration['root_sys']) && strlen($path) < strlen($_configuration['root_sys']))
		{
			continue;
		}

		if(!is_dir($path))
		{
			$ret=mkdir($path,$perm);

			if($ret)
			{
				if($verbose)
				{
					echo '<li><strong>'.basename($path).'</strong><br>'.$langCreatedIn.'<br><strong>'.realpath($path.'/..').'</strong></li>';
				}
			}
			else
			{
				if($verbose)
				{
					echo '</UL>error : '.$path.' not created';
				}

				$ret=false;

				break;
			}
		}
	}

	if($verbose)
	{
		echo '</UL>';
	}

	return $ret;
}

/**
 * to extract the extention of the filename
 *
 * @returns array
 * @param 	string	$filename 		filename
 */
function getextension($filename)
{
	$bouts = explode(".", $filename);
	return array(array_pop($bouts), implode(".", $bouts));
}

/**
 * to compute the size of the directory
 *
 * @returns integer size
 * @param 	string	$path path to size
 * @param 	boolean $recursive if true , include subdir in total
 */
function dirsize($root,$recursive=true)
{
	$dir=@opendir($root);

	$size=0;

	while($file=@readdir($dir))
	{
		if(!in_array($file,array('.','..')))
		{
			if(is_dir($root.'/'.$file))
			{
				$size+=$recursive?dirsize($root.'/'.$file):0;
			}
			else
			{
				$size+=@filesize($root.'/'.$file);
			}
		}
	}

	@closedir($dir);

	return $size;
}

/*
===============================================================
	CLASS FileManager
===============================================================
*/

/**
	This class contains functions that you can access statically.

	FileManager::list_all_directories($path)
	FileManager::list_all_files($dir_array)
	FileManager::compat_load_file($file_name)
	FileManager::set_default_settings($upload_path, $filename, $filetype="file", $glued_table, $default_visibility='v')

	@author Roan Embrechts
	@version 1.1, July 2004
*/
class FileManager
{

	/**
	---------------------------------------------------------------
		Returns a list of all directories, except the base dir,
		of the current course. This function uses recursion.

		Convention: the parameter $path does not end with a slash.

		@author Roan Embrechts
		@version 1.0.1
	---------------------------------------------------------------
	*/
	function list_all_directories($path)
	{
		$save_dir = getcwd();
		chdir($path);
		$handle = opendir($path);
		while ($element = readdir($handle) )
		{
			if ( $element == "." || $element == "..") continue;	// skip the current and parent directories
			if ( is_dir($element) )
			{
				$dirArray[] = $path."/".$element;
			}

		}
		closedir($handle) ;
		// recursive operation if subdirectories exist
		$dirNumber = sizeof($dirArray);
		if ( $dirNumber > 0 )
		{
			for ($i = 0 ; $i < $dirNumber ; $i++ )
			{
				$subDirArray = FileManager::list_all_directories( $dirArray[$i] ) ;			    // function recursivity
				$dirArray  =  array_merge( $dirArray , $subDirArray ) ;	// data merge
			}
		}
		$resultArray  =  $dirArray;
		chdir($save_dir) ;
		return $resultArray ;
	}


	/**
	===============================================================
		This function receives a list of directories.
		It returns a list of all files in these directories

		@author Roan Embrechts
		@version 1.0
	===============================================================
	*/
	function list_all_files($dirArray)
	{
		$save_dir = getcwd();
		foreach ($dirArray as $directory)
		{
			chdir($directory);
			$handle = opendir($directory);

			while ($element = readdir($handle) )
			{
				if ( $element == "." || $element == ".." || $element == '.htaccess') continue;	// skip the current and parent directories
				if ( ! is_dir($element) )
				{
					$elementArray[] = $directory."/".$element;
				}
			}
			closedir($handle) ;
			chdir("..") ;
		}
		chdir($save_dir);
		return $elementArray;
	}


	/**
		Load contents of file $filename into memory
		and return them as a string.
		Function kept for compatibility with older PHP versions.
		Function is binary safe (is needed on Windows)
	*/
	function compat_load_file($file_name)
	{
		$fp = fopen($file_name, "rb");
		$buffer = fread ($fp, filesize ($file_name));
		fclose ($fp);
		//api_display_debug_info(htmlentities($buffer));
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

	* @action	Adds an entry to the document table with the default settings.
	* @author	Olivier Cauberghe <olivier.cauberghe@ugent.be>
	* @author	Roan Embrechts
	* @version 1.2
	*/
	function set_default_settings($upload_path, $filename, $filetype="file", $glued_table, $default_visibility='v')
	{
		if (!$default_visibility) $default_visibility="v";

		//make sure path is not wrongly formed
		if( strlen($upload_path) ) $upload_path = "/$upload_path";
		else $upload_path = "";

		$endchar=substr($filename,strlen($filename)-1,1);
		if($endchar=="\\" || $endchar=="/")
		{
			$filename=substr($filename,0,strlen($filename)-1);
		}

		$full_file_name = $upload_path."/".$filename;
		//$upload_path = str_replace("//", "/", $upload_path);
		$full_file_name = str_replace("//", "/", $full_file_name);

		$sql_query = "SELECT count(*) as number_existing FROM $glued_table WHERE path='$full_file_name'";
		//api_display_debug_info($sql_query);
		$sql_result = Database::query($sql_query,__FILE__,__LINE__);
		$result = Database::fetch_array($sql_result);

		//determine which query to execute
		if( $result["number_existing"] > 0 )
		{
			//entry exists, update
			$query="UPDATE $glued_table SET path='$full_file_name',visibility='$default_visibility', filetype='$filetype' WHERE path='$full_file_name'";
		}
		else
		{
			//no entry exists, create new one
			$query="INSERT INTO $glued_table (path,visibility,filetype) VALUES('$full_file_name','$default_visibility','$filetype')";
		}
		Database::query($query,__FILE__,__LINE__);
	}
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
	function mkdirs($path, $mode = '0770')
	{
		if (file_exists($path))
		{
			return false;
		}
		else
		{
			FileManager :: mkdirs(dirname($path), $mode);
		 	//mkdir($path, $mode);
			return true;
		}
	}

} //end class FileManager

?>
