<?php
/* For licensing terms, see /license.txt */


class Cc13Entities extends CcEntities 
{

    public function get_external_xml($identifier) {
        $xpath = Cc1p3Convert::newx_path(Cc1p3Convert::$manifest, Cc1p3Convert::$namespaces);
        $files = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@identifier="' .
                 $identifier . '"]/imscc:file/@href');
        $response = empty($files) || ($files->length == 0) ? '' : $files->item(0)->nodeValue;
        return $response;
    }

    protected function get_all_files () {
        
        $permDirs = api_get_permissions_for_new_directories();
        
        $all_files = array();
        $xpath = Cc1p3Convert::newx_path(Cc1p3Convert::$manifest, Cc1p3Convert::$namespaces);
        foreach (Cc1p3Convert::$restypes as $type) {
            $files = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@type="' .
                                    $type . '"]/imscc:file/@href');
            if (empty($files) || ($files->length == 0)) {
                continue;
            }
            foreach ($files as $file) {
                //omit html files
                //this is a bit too simplistic
                $ext = strtolower(pathinfo($file->nodeValue, PATHINFO_EXTENSION));
                if (in_array($ext, array('html', 'htm', 'xhtml'))) {
                    continue;
                }
                $all_files[] = $file->nodeValue;
            }
            unset($files);
        }

        //are there any labels?
        $xquery = "//imscc:item/imscc:item/imscc:item[imscc:title][not(@identifierref)]";
        $labels = $xpath->query($xquery);
        if (!empty($labels) && ($labels->length > 0)) {
            $tname = 'course_files';
            $dpath = Cc1p3Convert::$path_to_manifest_folder . DIRECTORY_SEPARATOR . $tname;
            $rfpath = 'files.gif';
            $fpath = $dpath . DIRECTORY_SEPARATOR . 'files.gif';
            if (!file_exists($dpath)) {
                mkdir($dpath, $permDirs, true);
            }
            $all_files[] = $rfpath;
        }
        $all_files = empty($all_files) ? '' : $all_files;

        return $all_files;
    }

}

