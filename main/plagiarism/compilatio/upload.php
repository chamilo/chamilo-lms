<?php

/* For licensing terms, see /license.txt */

require_once '../../inc/global.inc.php';
require_once '../../work/work.lib.php';

ini_set('soap.wsdl_cache_enabled', 0);
ini_set('default_socket_timeout', '1000');

api_set_more_memory_and_time_limits();

$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info();
$compilatio = new Compilatio();

/* if we have to upload severals documents*/
if (isset($_REQUEST['type']) && 'multi' === $_REQUEST['type']) {
    $docs = explode('a', $_REQUEST['doc']);
    for ($k = 0; $k < count($docs) - 1; $k++) {
        $documentId = 0;
        if (!isset($docs[$k])) {
            $documentId = (int) $docs[$k];
        }

        /**
         * File problem in the url field that no longer have the file extension,
         * Compilatio's server refuse the files
         * we renames in the FS and the database with the file extension that is found in the title field.
         */
        compilatioUpdateWorkDocument($documentId, $courseId);

        $compilatioId = $compilatio->getCompilatioId($documentId, $courseId);
        if (empty($compilatioId)) {
            $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
            $query = "SELECT * FROM $workTable WHERE id= $documentId AND c_id= $courseId";
            $sqlResult = Database::query($query);
            $doc = Database::fetch_object($sqlResult);
            if ($doc) {
                /*We load the document in compilatio through the webservice */
                $currentCourseRepositoryWeb = api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/';
                $WrkUrl = $currentCourseRepositoryWeb.$doc->url;
                $LocalWrkUrl = $courseInfo['course_sys_path'].$doc->url;
                $mime = DocumentManager::file_get_mime_type($doc->title);
                if ('wget' === $compilatio->getTransportMode()) {
                    /*Compilatio's server recover tjre file throught wget like this:
                    username:password@http://somedomain.com/reg/remotefilename.tar.gz */
                    if (strlen($compilatio->getWgetUri()) > 2) {
                        $filename = preg_replace(
                                '/$',
                                '',
                                $compilatio->getWgetUri()
                            ).'/'.$courseInfo['path'].'/'.$doc->url;
                    } else {
                        $filename = $WrkUrl;
                    }
                    if (strlen($compilatio->getWgetLogin()) > 2) {
                        $filename = $compilatio->getWgetLogin().':'.$compilatio->getWgetPassword().'@'.$filename;
                    }
                    $mime = 'text/plain';
                    $compilatioId = $compilatio->sendDoc($doc->title, '', $filename, $mime, 'get_url');
                } else {
                    /* we use strictly the SOAP for the data trasmission */
                    $pieces = explode('/', $doc->url);
                    $nbPieces = count($pieces);
                    $filename = $pieces[$nbPieces - 1];
                    $compilatioId = $compilatio->sendDoc(
                        $doc->title,
                        '',
                        $filename,
                        $mime,
                        file_get_contents($LocalWrkUrl)
                    );
                }
                /*we associate in the database the document chamilo to the document compilatio*/
                /*we verify that the docmuent's id is an hash_md5*/
                if (Compilatio::isMd5($compilatioId)) {
                    $compilatio->saveDocument($courseId, $doc->id, $compilatioId);
                    $soapRes = $compilatio->startAnalyse($compilatioId);
                }
            }
        }
    }
} else {
    $documentId = isset($_GET['doc']) ? $_GET['doc'] : 0;
    sendDocument($documentId, $courseInfo);
}

function sendDocument($documentId, $courseInfo)
{
    $courseId = $courseInfo['real_id'];

    compilatioUpdateWorkDocument($documentId, $courseId);
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $query = "SELECT * FROM $workTable
              WHERE id = $documentId AND c_id= $courseId";
    $sqlResult = Database::query($query);
    $doc = Database::fetch_object($sqlResult);
    $currentCourseRepositoryWeb = api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/';
    $documentUrl = $currentCourseRepositoryWeb.$doc->url;

    $filePath = $courseInfo['course_sys_path'].$doc->url;
    $mime = DocumentManager::file_get_mime_type($doc->title);

    $compilatio = new Compilatio();
    if ('wget' === $compilatio->getTransportMode()) {
        if (strlen($compilatio->getWgetUri()) > 2) {
            $filename = preg_replace('/$', '', $compilatio->getWgetUri()).'/'.$courseInfo['path'].'/'.$doc->title;
        } else {
            $filename = $documentUrl;
        }
        if (strlen($compilatio->getWgetLogin()) > 2) {
            $filename = $compilatio->getWgetLogin().':'.$compilatio->getWgetPassword().'@'.$filename;
        }
        $compilatioId = $compilatio->sendDoc($doc->title, '', $filename, 'text/plain', 'get_url');
    } else {
        $pieces = explode('/', $doc->url);
        $nbPieces = count($pieces);
        $filename = $pieces[$nbPieces - 1];
        $compilatioId = $compilatio->sendDoc($doc->title, '', $filename, $mime, file_get_contents($filePath));
    }

    if (Compilatio::isMd5($compilatioId)) {
        $compilatio->saveDocument($courseId, $doc->id, $compilatioId);
        $compilatio->startAnalyse($compilatioId);
        echo Display::return_message(get_lang('Uploaded'));
    } else {
        echo Display::return_message(get_lang('Error'), 'error');
    }
}

/**
 * function for show and recovery the extension from a file.
 *
 * @param $docId
 * @param $courseId
 *
 * @return string
 */
function workDocExtension($docId, $courseId)
{
    $dbTitle = getWorkTitle($docId, $courseId);
    $res = getFileExtension($dbTitle);

    return $res;
}

function getFileExtension($filename)
{
    $res = '';
    preg_match("/.*\.([^.]+)/", $filename, $dbTitle);
    if (count($dbTitle) > 1) {
        $res = $dbTitle[1];
    }

    return $res;
}

function getWorkTitle($docId, $courseId)
{
    $docId = (int) $docId;
    $courseId = (int) $courseId;

    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $sql = "SELECT title FROM $workTable
            WHERE c_id= $courseId AND id = $docId";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $data = Database::fetch_array($res);
        $res = $data['title'];
    }

    return $res;
}

function getFilename($txt)
{
    $res = $txt;
    preg_match('|.*/([^/]+)|', $txt, $urlList);
    if (count($urlList) > 0) {
        $res = $urlList[1];
    }

    return $res;
}

function getWorkFolder($txt)
{
    $res = '';
    preg_match('|(.*/)[^/]+|', $txt, $urlList);
    if (count($urlList) > 0) {
        $res = $urlList[1];
    }

    return $res;
}

function getShortFilename($txt)
{
    $res = $txt;
    if (strlen($txt) > 10) {
        $res = substr($txt, 0, 10);
    }

    return $res;
}

function compilatioUpdateWorkDocument($docId, $courseId)
{
    $_course = api_get_course_info();

    $docId = (int) $docId;
    $courseId = (int) $courseId;

    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $extensionFile = workDocExtension($docId, $courseId);
    $urlFile = get_work_path($docId);
    $filename = getFilename($urlFile);
    $work_folder = getWorkFolder($urlFile);
    $urlFile_ext = getFileExtension($urlFile);
    $coursePath = $_course['course_sys_path'];
    $workTitle = getWorkTitle($docId, $courseId);

    if ($extensionFile != '' && $urlFile_ext == '') {
        /* Rename the files in the FS with the extension */
        $shortFilename = $filename;
        $cleanWorkTitle = api_replace_dangerous_char($workTitle);
        $newestFilename = $shortFilename.'_'.$cleanWorkTitle;
        rename($coursePath.$urlFile, $coursePath.$work_folder.$newestFilename);
        /*rename the db's input with the extension*/
        $sql = "UPDATE $workTable SET url='".$work_folder.$newestFilename."'
                WHERE c_id=$courseId AND id=$docId";
        Database::query($sql);
    }
}
