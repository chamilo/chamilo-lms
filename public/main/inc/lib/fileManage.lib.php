<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Filesystem\Filesystem;

/**
 * This is the file manage library for Chamilo.
 * Include/require it in your code to use its functionality.
 */

/**
 * Cheks a file or a directory actually exist at this location.
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 *
 * @param string $file_path Path of the presume existing file or dir
 *
 * @return bool TRUE if the file or the directory exists or FALSE otherwise
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
 * Deletes a file or a directory.
 *
 * @author - Hugues Peeters
 *
 * @param  $file (String) - the path of file or directory to delete
 *
 * @return bool - true if the delete succeed, false otherwise
 *
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
    }

    return false; // no file or directory to delete
}

/**
 * Removes a directory recursively.
 *
 * @returns true if OK, otherwise false
 *
 * @author Amary <MasterNES@aol.com> (from Nexen.net)
 * @author Olivier Brouckaert <oli.brouckaert@skynet.be>
 *
 * @param string $dir directory to remove
 */
function removeDir($dir)
{
    if (!@$opendir = opendir($dir)) {
        return false;
    }

    while ($readdir = readdir($opendir)) {
        if ('..' != $readdir && '.' != $readdir) {
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
 * Moves a file or a directory to an other area.
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 *
 * @param string $source      the path of file or directory to move
 * @param string $target      the path of the new area
 * @param bool   $forceMove   Whether to force a move or to make a copy (safer but slower) and then delete the original
 * @param bool   $moveContent In some cases (including migrations), we need to move the *content* and not the folder itself
 *
 * @return bool true if the move succeed, false otherwise
 *
 * @see move() uses check_name_exist() and copyDirTo() functions
 */
function move($source, $target, $forceMove = true, $moveContent = false)
{
    $target = realpath($target); // remove trailing slash
    $source = realpath($source);
    if (check_name_exist($source)) {
        $file_name = basename($source);
        // move onto self illegal: mv a/b/c a/b/c or mv a/b/c a/b
        if (0 === strcasecmp($target, dirname($source))) {
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
                    copy($source, $target.'/'.$file_name);
                    unlink($source);
                }
            } else {
                copy($source, $target.'/'.$file_name);
                unlink($source);
            }

            return true;
        } elseif (is_dir($source)) {
            // move dir down will cause loop: mv a/b/ a/b/c/ not legal
            if (0 == strncasecmp($target, $source, strlen($source))) {
                return false;
            }
            /* Directory */
            if ($forceMove && !$isWindowsOS && $canExec) {
                if ($moveContent) {
                    $base = basename($source);
                    $out = [];
                    $retVal = -1;
                    exec('mv '.$source.'/* '.$target.'/'.$base, $out, $retVal);
                    if (0 !== $retVal) {
                        return false; // mv should return 0 on success
                    }
                    exec('rm -rf '.$source);
                } else {
                    $out = [];
                    $retVal = -1;
                    exec("mv $source $target", $out, $retVal);
                    if (0 !== $retVal) {
                        error_log("Chamilo error fileManage.lib.php: mv $source $target\n");

                        return false; // mv should return 0 on success
                    }
                }
            } else {
                return copyDirTo($source, $target);
            }

            return true;
        }
    }

    return false;
}

/**
 * Moves a directory and its content to an other area.
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 *
 * @param string $source      the path of the directory to move
 * @param string $destination the path of the new area
 * @param bool   $move        Whether we want to remove the source at the end
 *
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
 * Copy a directory and its directories (not files) to an other area.
 *
 * @param string $source      the path of the directory to move
 * @param string $destination the path of the new area
 *
 * @return bool false on error
 */
function copyDirWithoutFilesTo($source, $destination)
{
    $fs = new Filesystem();

    if (!is_dir($source)) {
        return false;
    }

    if (!$fs->exists($destination)) {
        $fs->mkdir($destination);
    }

    $dirIterator = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);

    /** @var \SplFileInfo $item */
    foreach ($iterator as $item) {
        if ($item->isFile()) {
            continue;
        }

        $newDir = $destination.'/'.$item->getFilename();

        if (!$fs->exists($newDir)) {
            $fs->mkdir($destination.'/'.$item->getFilename());
        }
    }

    return true;
}

/**
 * Get a list of all PHP (.php) files in a given directory. Includes .tpl files.
 *
 * @param string $base_path     The base path in which to find the corresponding files
 * @param bool   $includeStatic Include static .html, .htm and .css files
 *
 * @return array
 */
function getAllPhpFiles($base_path, $includeStatic = false)
{
    $list = scandir($base_path);
    $files = [];
    $extensionsArray = ['.php', '.tpl'];
    if ($includeStatic) {
        $extensionsArray[] = 'html';
        $extensionsArray[] = '.htm';
        $extensionsArray[] = '.css';
    }
    foreach ($list as $item) {
        if ('.' == substr($item, 0, 1)) {
            continue;
        }
        $special_dirs = [api_get_path(SYS_TEST_PATH), api_get_path(SYS_COURSE_PATH), api_get_path(SYS_LANG_PATH), api_get_path(SYS_ARCHIVE_PATH)];
        if (in_array($base_path.$item.'/', $special_dirs)) {
            continue;
        }
        if (is_dir($base_path.$item)) {
            $files = array_merge($files, getAllPhpFiles($base_path.$item.'/', $includeStatic));
        } else {
            //only analyse php files
            $sub = substr($item, -4);
            if (in_array($sub, $extensionsArray)) {
                $files[] = $base_path.$item;
            }
        }
    }
    $list = null;

    return $files;
}
