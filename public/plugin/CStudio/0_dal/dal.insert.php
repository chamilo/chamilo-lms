<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;
use PhpZip\ZipFile;

/**
 * chamidoc plugin\CStudio\0_dal\dal.insert.php
 * /sites/formation.chamilo-studio.com/plugin/CStudio/0_dal
 * /sites/formation.chamilo-studio.com/app/courses/CHAMILOSENDBOX/scorm.
 *
 * @author Damien Renou <rxxxx.dxxxxx@gmail.com>
 *
 * @version 18/05/2024
 *
 * @param mixed $title
 * @param mixed $userId
 * @param mixed $showlog
 */
function insertNewProject($title, $userId, $showlog = false)
{
    $em = Database::getManager();

    $idurl = api_get_current_access_url_id();
    $dirPlug = Container::getParameter('kernel.project_dir').'/public/plugin/CStudio';

    $date = new DateTime();
    $dateStr = $date->format('d/m/Y');
    $folderFSName = 'teachcs-'.$date->format('Ymd').'-'.uuid(5);

    $templateFiles = ['imsmanifest.xml', 'index.html', 'jq.js', 'api.js'];
    $zipFile = new ZipFile();

    foreach ($templateFiles as $templateFile) {
        $source = $dirPlug.'/resources/'.$templateFile;

        if (!file_exists($source)) {
            if ($showlog) {
                echo '<span style="color:red;">'.$source.' not found</span><br>';
            }

            if ('imsmanifest.xml' === $templateFile) {
                return 0;
            }

            continue;
        }

        $zipFile->addFromString($templateFile, file_get_contents($source));
    }

    $tmpZip = Container::getCacheDir().'/cstudio_'.uniqid();
    $zipFile->saveAsFile($tmpZip);
    $zipFile->close();

    $proximity = !empty($_REQUEST['content_proximity']) ? strip_tags($_REQUEST['content_proximity']) : 'local';
    $maker = !empty($_REQUEST['content_maker']) ? strip_tags($_REQUEST['content_maker']) : 'Scorm';

    $oScorm = new scorm();
    $result = $oScorm->import_package(
        ['tmp_name' => $tmpZip, 'name' => "$folderFSName.zip"],
        '',
        [],
        false,
        null,
        false
    );

    @unlink($tmpZip);

    if (!$result || empty($oScorm->manifestToString)) {
        if ($showlog) {
            echo '<span style="color:red;">Error: import_package failed</span><br>';
        }

        return 0;
    }

    if ($showlog) {
        echo '<span style="color:green;">SCORM folder created in Flysystem</span><br>';
    }

    $oScorm->parse_manifest();
    $lp = $oScorm->import_manifest(api_get_course_int_id(), 1);

    if (!$lp) {
        if ($showlog) {
            echo '<span style="color:red;">Error: import_manifest failed</span><br>';
        }

        return 0;
    }

    $lp->setContentLocal($proximity)->setContentMaker($maker);
    $em->persist($lp);
    $em->flush();

    $objectId = $lp->getIid();

    if ($objectId > 0) {
        $em->getConnection()->insert('plugin_oel_tools_teachdoc', [
            'title' => $title,
            'date_create' => $dateStr,
            'id_parent' => 0,
            'id_user' => $userId,
            'type_base' => 0,
            'lp_id' => $objectId,
            'local_folder' => $folderFSName,
            'order_lst' => 1,
            'type_node' => 1,
            'colors' => 'white-chami.css',
            'quizztheme' => '',
            'base_html' => '',
            'base_css' => '',
            'gpscomps' => '',
            'gpsstyle' => '',
            'id_url' => $idurl,
            'recent_save' => 0,
            'options' => '',
        ]);
    } else {
        if ($showlog) {
            echo '<span style="color:red;">Error: lp_id = '.$objectId.'</span><br>';
        }
    }

    return $objectId;
}

/**
 * This method insert a log in the table plugin_oel_tools_logs
 *  $type_log= 1 : Creator activity
 *  $type_log= 2 : Export activity
 *  $type_log= 3 : Learner activity.
 *
 * @param mixed $title
 * @param mixed $id_project
 * @param mixed $id_page
 * @param mixed $logs
 * @param mixed $type_log
 * @param mixed $result
 */
function insertOelToolsLog($title, $id_project, $id_page, $logs, $type_log = 1, $result = 1): void
{
    $VDB = new VirtualDatabase();
    $table = 'plugin_oel_tools_logs';

    $date = new DateTime();
    $userId = $VDB->w_api_get_user_id();

    $params = [
        'id_user' => $userId,
        'id_project' => $id_project,
        'id_page' => $id_page,
        'type_log' => $type_log,
        'title' => $title,
        'logs' => $logs,
        'result' => $result,
        'date_create' => idate('U'),
        'send_xapi' => 0,
    ];
    $VDB->insert($table, $params);
}
