<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the OeL plugin.
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;

if (isset($_POST['id']) || isset($_GET['id'])) {
    if (
        (isset($_POST['bh'], $_POST['bc']))
        || (isset($_GET['bh'], $_GET['bc']))
    ) {
        require_once '../../0_dal/dal.global_lib.php';

        require_once '../../0_dal/dal.vdatabase.php';
        $VDB = new VirtualDatabase();

        require_once '../inc/functions.php';

        require_once '../../0_dal/dal.save.php';

        require_once '../../0_dal/dal.insert.php';

        require_once '../../0_dal/dal.chamidoc_object.php';

        $idPage = get_int_from('id');

        if (false == oel_ctr_rights($idPage)) {
            echo 'KO';

            exit;
        }

        $baseHtml = get_string_direct_from('bh');
        $baseCss = get_string_direct_from('bc');
        $logsactions = get_string_direct_from('logsactions');
        $redir = get_int_from('r');

        if ('' != $logsactions) {
            if (oel_ctr_options('ALC')) {
                $id_top = get_top_page_id($idPage);
                // oel_add_dev_logs('inserOelToolsLog("save",'.$id_project.'",'.$idPage.'","'.$logsactions.'",1);');
                if (0 == $id_top) {
                    $id_top = $idPage;
                }
                insertOelToolsLog('interacted', $id_top, $idPage, $logsactions, 1, 1);
            }
        }

        if ('' != $baseCss && '' != $baseHtml) {
            oel_tools_save_element_compo($baseHtml, $baseCss, $idPage);

            $date = new DateTime();

            $dateStr = $date->format('Y-m-j-H');

            $localFolderH = get_local_folder($idPage).'-'.$idPage;

            $fileSystem = Container::getPluginsFileSystem();
            $historyCacheDirName = 'CStudio/editor/history_cache';

            if (!$fileSystem->directoryExists("$historyCacheDirName/$localFolderH")) {
                $fileSystem->createDirectory("$historyCacheDirName/$localFolderH");
            }

            if (!$fileSystem->fileExists("$historyCacheDirName/$localFolderH/$dateStr.html")) {
                $fileSystem->write(
                    "$historyCacheDirName/$localFolderH/$dateStr.html",
                    $baseHtml
                );
                $fileSystem->write(
                    "$historyCacheDirName/$localFolderH/$dateStr.css",
                    $baseCss
                );

                $localFolder = get_local_folder($idPage);
                $imgCacheDirName = 'CStudio/editor/img_cache';

                $projectCacheDirName = "$imgCacheDirName/".strtolower($localFolder);

                if (!$fileSystem->directoryExists($projectCacheDirName)) {
                    $fileSystem->createDirectory($projectCacheDirName);
                }

                $imgCacheDirName .= '/'.strtolower($localFolder).'/customcode.css';

                if (!$fileSystem->fileExists($imgCacheDirName)) {
                    $fileSystem->write($imgCacheDirName, '/* code css */');
                }
            }

            if (0 == $redir) {
                echo ' - Saved ';
            }
        }

        if (0 == $redir) {
            echo ' no redirect ';
        } else {
            if (isset($_GET['pt'])) {
                $idPageTop = get_int_from('pt');
                $lp_id = get_lp_Id($idPageTop);
                $courseSysPage = '';
                if ('chamil' == $VDB->engine) {
                    $cidReq = api_get_cidreq_params(
                        getCourseIdFromLp($lp_id),
                    );
                    $courseSysPage = $VDB->w_get_path(WEB_PATH).'main/lp/lp_controller.php?'.$cidReq;
                    $courseSysPage .= '&gradebook=0&origin=&action=view&lp_id='.$lp_id.'&isStudentView=true&teachdoc=edit';
                }

                echo $courseSysPage;
            } else {
                echo 'error ...';
            }
        }
    }
} else {
    echo 'no id';
}
