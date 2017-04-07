<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Filesystem\Filesystem;

/**
 *	This is the file manage library for Chamilo.
 *	Include/require it in your code to use its functionality.
 *	@package chamilo.library
 */

/**
 * Cheks a file or a directory actually exist at this location
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param string $file_path Path of the presume existing file or dir
 * @return boolean TRUE if the file or the directory exists or FALSE otherwise.
 */
function check_name_exist($file_path)
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
 * @param  $file (String) - the path of file or directory to delete
 * @return boolean - true if the delete succeed, false otherwise.
 * @see    - delete() uses check_name_exist() and removeDir() functions
 */
function my_delete($file)
{
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
function removeDir($dir)
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
 * @author hubert.borderiou@grenet.fr
 * @param string $in_folder folder path on disk
 * @return int 1 if folder is empty, 0 otherwise
*/
function folder_is_empty($in_folder)
{
    $folder_is_empty = 0;
    if (is_dir($in_folder)) {
        $tab_folder_content = scandir($in_folder);
        if ((count($tab_folder_content) == 2 &&
            in_array(".", $tab_folder_content) &&
            in_array("..", $tab_folder_content)
            ) ||
            (count($tab_folder_content) < 2)
        ) {
            $folder_is_empty = 1;
        }
    }

    return $folder_is_empty;
}

/**
 * Renames a file or a directory
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  string $file_path complete path of the file or the directory
 * @param  string $new_file_name new name for the file or the directory
 * @return boolean true if succeed, false otherwise
 * @see rename() uses the check_name_exist() and php2phps() functions
 */
function my_rename($file_path, $new_file_name) {

	$save_dir = getcwd();
	$path = dirname($file_path);
	$old_file_name = basename($file_path);
	$new_file_name = api_replace_dangerous_char($new_file_name);

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
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  string $source the path of file or directory to move
 * @param  string $target the path of the new area
 * @param  bool $forceMove Whether to force a move or to make a copy (safer but slower) and then delete the original
 * @param	bool $moveContent In some cases (including migrations), we need to move the *content* and not the folder itself
 * @return bool true if the move succeed, false otherwise.
 * @see move() uses check_name_exist() and copyDirTo() functions
 */
function move($source, $target, $forceMove = true, $moveContent = false)
{
    $target = realpath($target); // remove trailing slash
    $source = realpath($source);
    if (check_name_exist($source)) {
        $file_name = basename($source);
        // move onto self illegal: mv a/b/c a/b/c or mv a/b/c a/b
        if (strcasecmp($target, dirname($source)) === 0) {
               return false;
        }
        $isWindowsOS = api_is_windows_os();
        $canExec = function_exists('exec');

        /* File case */
        if (is_file($source)) {
            if ($forceMove) {
                if (!$isWindowsOS && $canExec) {
                    exec('mv '.$source.' '.$target.'/'.$file_name);
                } else {
                    // Try copying
                    copy($source, $target . '/' . $file_name);
                    unlink($source);
                }
            } else {
                copy($source, $target . '/' . $file_name);
                unlink($source);
            }
            return true;
        } elseif (is_dir($source)) {
            // move dir down will cause loop: mv a/b/ a/b/c/ not legal
            if (strncasecmp($target, $source, strlen($source)) == 0) {
                return false;
            }
            /* Directory */
            if ($forceMove && !$isWindowsOS && $canExec) {
                if ($moveContent) {
                    $base = basename($source);
                    $out = []; $retVal = -1;
                    exec('mv '.$source.'/* '.$target.'/'.$base, $out, $retVal);
                    if ($retVal !== 0) {
                        return false; // mv should return 0 on success
                    }
                    exec('rm -rf '.$source);
                } else {
                    $out = []; $retVal = -1;
                    exec("mv $source $target", $out, $retVal);
                    if ($retVal !== 0) {
                        error_log("Chamilo error fileManage.lib.php: mv $source $target\n");
                        return false; // mv should return 0 on success
                    }
                }
            } else {
                return copyDirTo($source, $target);
            }
            return true;
        }
    } else {
        return false;
    }
}

/**
 * Moves a directory and its content to an other area
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param string $source the path of the directory to move
 * @param string $destination the path of the new area
 * @return bool false on error
 */
function copyDirTo($source, $destination, $move = true)
{
    $fs = new Filesystem();
    if (is_dir($source)) {
        $fs->mkdir($destination);
        if (!is_dir($destination)) {
            error_log("Chamilo copyDirTo cannot mkdir $destination\n");
            return false; // could not create destination dir
        }
        $fs->mirror($source, $destination);
        if ($move) {
            $fs->remove($source);
        }
    }
    return true;
}

/**
 * Extracting extension of a filename
 *
 * @returns array
 * @param 	string	$filename 		filename
 */
function getextension($filename)
{
	$bouts = explode('.', $filename);
	return array(array_pop($bouts), implode('.', $bouts));
}

/**
 * Calculation size of a directory
 *
 * @returns integer size
 * @param 	string	$root path of dir to measure
 * @param 	boolean $recursive if true , include subdirectory in total
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
