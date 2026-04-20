<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

/*
 * This file contains the functions used by the Chamidoc plugin.
 *
 * @version 18/05/2024
 */
if (isset($_GET['id']) || isset($_GET['act'])) {
    require_once '../../0_dal/dal.global_lib.php';

    require_once '../../0_dal/dal.vdatabase.php';
    $VDB = new VirtualDatabase();

    require_once '../inc/functions.php';

    require_once '../../0_dal/dal.save.php';

    $idTopPage = get_int_from('id');

    if (false == oel_ctr_rights($idTopPage)) {
        echo 'error';

        exit;
    }

    $localFolder = get_local_folder($idTopPage);
    $cPathCache = 'CStudio/editor/img_cache/'.strtolower($localFolder);

    $pluginFileSystem = Container::getPluginsFileSystem();

    $action = get_int_from('act');
    $titleTerm = get_string_from('term');

    // Create term glossary file
    if (1 == $action) {
        $idTerm = uuid(10);

        if ($pluginFileSystem->fileExists($cPathCache.'/'.$idTerm.'.xml')) {
            $idTerm = uuid(11);
        }

        $def1 = get_string_from('def1');
        $def2 = get_string_from('def2');

        if (-1 != $idTopPage && '' != $idTerm && '' != $titleTerm) {
            $userId = $VDB->w_api_get_user_id();

            if (!$pluginFileSystem->directoryExists($cPathCache)) {
                $pluginFileSystem->createDirectory($cPathCache);
            }
            if (!$pluginFileSystem->fileExists($cPathCache.'/'.$idTerm.'.xml')) {
                $xmlstr = '<?xml version="1.0" encoding="UTF-8" ?>';
                $xmlstr .= '<terms><term>';
                $xmlstr .= '<w><![CDATA['.clean_term_split($titleTerm).']]></w>';
                $xmlstr .= '<d><![CDATA['.clean_term_split($def1).']]></d>';
                $xmlstr .= '<d2><![CDATA['.clean_term_split($def2).']]></d2>';
                $xmlstr .= '</term></terms>';
                $pluginFileSystem->write($cPathCache.'/'.$idTerm.'.xml', $xmlstr);
            }
        }
    }

    // Update term glossary file
    if (3 == $action) {
        $idTerm = basename(get_string_from('idterm'));
        $idTerm = preg_match('/^[a-z0-9]+$/', $idTerm) ? $idTerm : '';

        if ('' !== $idTerm && $pluginFileSystem->fileExists($cPathCache.'/'.$idTerm.'.xml')) {
            $def1 = get_string_from('def1');
            $def2 = get_string_from('def2');

            if (-1 != $idTopPage && '' != $titleTerm) {
                $xmlstr = '<?xml version="1.0" encoding="UTF-8" ?>';
                $xmlstr .= '<terms><term>';
                $xmlstr .= '<w><![CDATA['.clean_term_split($titleTerm).']]></w>';
                $xmlstr .= '<d><![CDATA['.clean_term_split($def1).']]></d>';
                $xmlstr .= '<d2><![CDATA['.clean_term_split($def2).']]></d2>';
                $xmlstr .= '</term></terms>';
                $pluginFileSystem->write($cPathCache.'/'.$idTerm.'.xml', $xmlstr);
            }
        }
    }

    // Delete term glossary file
    if (100 == $action) {
        $idTerm = basename(get_string_from('idterm'));
        $idTerm = preg_match('/^[a-z0-9]+$/', $idTerm) ? $idTerm : '';

        if ('' !== $idTerm && $pluginFileSystem->fileExists($cPathCache.'/'.$idTerm.'.xml')) {
            $pluginFileSystem->delete($cPathCache.'/'.$idTerm.'.xml');

            if (!$pluginFileSystem->fileExists($cPathCache.'/'.$idTerm.'.xml')) {
                echo 'OK';
            } else {
                echo 'error';
            }
        }
    }

    // Load all terms glossary files
    if (2 == $action) {
        $glossaryRender = 'var glossaryRender = new Array();'."\n";

        $ind = 0;
        $dataTX = '';

        if ($pluginFileSystem->directoryExists($cPathCache)) {
            foreach ($pluginFileSystem->listContents($cPathCache, false) as $item) {
                if (!$item->isFile()) {
                    continue;
                }

                $file = basename($item->path());

                if (!str_contains($file, '.xml')) {
                    continue;
                }

                $nam = str_replace('.xml', '', $file);
                $nam = str_replace('@', '-', $nam);

                $xmlContent = $pluginFileSystem->read($item->path());
                $xml = simplexml_load_string($xmlContent);

                $Bw = $xml->term[0]->w;
                $Bd = $xml->term[0]->d;
                $Bd2 = $xml->term[0]->d2;

                $dataTX .= $nam.'@'.$Bw.'@'.$Bd.'@'.$Bd2.'|';

                $glossaryRender .= 'glossaryRender.push({w:'.json_encode((string) $Bw).',';
                $glossaryRender .= 'd:'.json_encode((string) $Bd).',';
                $glossaryRender .= 'd2:'.json_encode((string) $Bd2)."});\n";

                $ind++;
            }
        }

        $pluginFileSystem->write($cPathCache.'/gloss.js', $glossaryRender);

        if ($ind > 0) {
            echo $dataTX;
        }
    }
} else {
    echo 'error';
}
