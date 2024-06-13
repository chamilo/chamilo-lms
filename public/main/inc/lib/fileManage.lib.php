<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Filesystem\Filesystem;

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
 * Get a list of all PHP (.php) files in a given directory. Includes .tpl files.
 */
function getAllPhpFiles(string $base_path, bool $includeStatic = false): array
{
    $list = scandir($base_path);
    $files = [];
    $extensionsArray = ['.php', '.tpl', '.html.twig'];
    if ($includeStatic) {
        $extensionsArray[] = 'html';
        $extensionsArray[] = '.htm';
        $extensionsArray[] = '.css';
    }
    foreach ($list as $item) {
        if ('.' == substr($item, 0, 1)) {
            continue;
        }
        $special_dirs = []; // Modify this array as needed
        if (in_array($base_path . $item . '/', $special_dirs)) {
            continue;
        }
        if (is_dir($base_path . $item)) {
            $files = array_merge($files, getAllPhpFiles($base_path . $item . '/', $includeStatic));
        } else {
            foreach ($extensionsArray as $extension) {
                if (substr($item, -strlen($extension)) == $extension) {
                    $files[] = $base_path . $item;
                    break;
                }
            }
        }
    }

    return $files;
}
