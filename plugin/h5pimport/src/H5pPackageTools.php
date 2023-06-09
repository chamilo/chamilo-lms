<?php

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Symfony\Component\Filesystem\Filesystem;

class H5pPackageTools
{

    /**
     * Help read JSON from the archive
     *
     * @param string $file
     * @param bool $assoc
     * @return mixed JSON content if valid or FALSE for invalid
     */
    public static function getJson(string $file, bool $assoc = false)
    {

        $fs = new Filesystem();
        $json = false;

        if ($fs->exists($file)) {

            $contents = '';
            $fileContent = fopen($file, "r");
            while (!feof($fileContent)) {
                $contents .= fread($fileContent, 2);
            }

            // Decode the data
            $json = json_decode($contents, $assoc);
            if ($json === null) {
                // JSON cannot be decoded or the recursion limit has been reached.
                return false;
            }
        }

        return $json;
    }

}