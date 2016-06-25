<?php
/* For licensing terms, see /license.txt */

/**
 * Class MoodleImport
 *
 * @author José Loguercio <jose.loguercio@beeznest.com>
 * @package chamilo.library
 */

class MoodleImport
{
    /**
     * @param resource $file *.* mbz file moodle course backup
     * @return bool
     */
    public function readMoodleFile($file)
    {
        if (is_file($file) && is_readable($file) && ($xml = @file_get_contents($file))) {
            $package = new PclZip($file);
            $packageContent = $package->listContent();
            return $packageContent;
        }
    }
}