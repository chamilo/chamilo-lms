<?php

/* Dokeos and compilatio's includes */
require ('../../inc/global.inc.php');
require_once ("../../work/work.lib.php");
require_once('../../inc/lib/document.lib.php');
require_once('../../inc/lib/fileDisplay.lib.php');
require_once ('compilatio.class.php');
require_once ('config.php');

/*parameters SOAP use*/
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('default_socket_timeout', '1000');
$coursId = api_get_course_int_id();

/* Variables */
$typeMessage = 0;
$errorCodeNotValid = get_lang('compilatioError') . get_lang('documentCodeNotValid');
$errorLoadError = get_lang('compilatioLoadError') . get_lang('compilatioContactAdmin');

/*message to the user for be patient*/
$msgWait = get_lang('PleaseWaitThisCouldTakeAWhile');

/* if we have to upload severals documents*/
if(isset($_REQUEST['type']) && $_REQUEST['type']=="multi"){
    $docs = preg_split("/a/", $_REQUEST['doc']);
    for ($k=0 ; $k < sizeof($docs)-1 ; $k++)	{
        /* We have to modify the timeout server for send the heavy files */
        set_time_limit(600);
        $documentId = 0;
        if (intval($docs[$k])) {
            $documentId = $docs[$k];
        }
        /**
         * File problem in the url field that no longer have the file extension,
         * Compilatio's server refuse the files
         * we renames in the FS and the database with the file extension that is found in the title field
         */
        compilatioUpdateWorkDocument($documentId, $coursId);

        $compTable = Database::get_course_table(TABLE_PLAGIARISM);
        $compilatioQuery = "SELECT compilatio_id FROM "
            . $compTable
            . " WHERE id_doc="
            . $documentId
            . " AND c_id="
            . $coursId;
        $compiSqlResult = Database::query($compilatioQuery);
        $compi = Database::fetch_object($compiSqlResult);
        if (isset($compi->compilatio_id)) {
            /*The document is already in Compilatio, we do nothing*/

        } else {
            $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
            $query = "SELECT * FROM "
                . $workTable
                . " WHERE id='"
                . $documentId
                . "' AND c_id="
                . $coursId;
            $sqlResult = Database::query($query);
            $doc = Database::fetch_object($sqlResult);
            if ($doc) {
                /*We load the document in compilatio through the webservice */
                $currentCourseRepositoryWeb = api_get_path(WEB_COURSE_PATH) . $_course["path"] . "/";
                $WrkUrl = $currentCourseRepositoryWeb . $doc->url;
                $LocalWrkUrl = api_get_path(SYS_COURSE_PATH) . $_course["path"] . "/" . $doc->url;
                $LocalWrkTitle = $doc->title;
                $mime = typeMime($LocalWrkTitle);
                $compilatio = new compilatio(
                    $compilatioParameter['key'],
                    $compilatioParameter['$urlsoap'],
                    $compilatioParameter['proxy_host'],
                    $compilatioParameter['proxy_port']);

                if ($compilatioParameter['mode_transport'] == 'wget') {
                    /*Compilatio's server recover tjre file throught wget like this:
                    username:password@http://somedomain.com/reg/remotefilename.tar.gz */
                    if (strlen($compilatioParameter['wget_uri']) > 2)  {
                        $filename = ereg_replace("/$",
                                "",
                                $compilatioParameter['wget_uri'])
                            . "/"
                            . $_course["path"]
                            . "/"
                            . $doc->url;
                    } else {
                        $filename = $WrkUrl;
                    }
                    if (strlen($compilatioParameter['wget_login']) > 2) {
                        $filename = $compilatioParameter['wget_login']
                            . ":"
                            . $compilatioParameter['wget_password']
                            . "@"
                            . $filename;
                    }
                    $mime = "text/plain";
                    $compilatioId = $compilatio->sendDoc($doc->title, '', $filename, $mime, 'get_url');
                } else {
                    /* we use strictly the SOAP for the data trasmission */
                    $pieces = explode("/", $doc->url);
                    $nbPieces = count($pieces);
                    $filename = $pieces[$nbPieces - 1];
                    $compilatioId = $compilatio->sendDoc($doc->title,
                        '',
                        $filename,
                        $mime,
                        file_get_contents($LocalWrkUrl)
                    );
                }
                /*we associate in the database the document chamilo to the document compilatio*/
                $compTable = Database::get_course_table(TABLE_PLAGIARISM);
                $sql4 = "INSERT INTO "
                    . $compTable
                    . " (c_id, id_doc, compilatio_id) VALUES ("
                    . $coursId
                    . ", '"
                    . $doc->id
                    . "','"
                    . $compilatioId
                    . "')";
                /*we verify that the docmuent's id is an hash_md5*/

                if (isMd5($compilatioId)) {
                    /*We insert the document into the DB*/
                    Database::query($sql4);
                } else {
                    $typeMessage = 1;
                }
                if (isMd5($compilatioId)) {
                    /*the uploading is good, we return an compilatio's id_doc*/
                    $soapRes = $compilatio->startAnalyse($compilatioId);
                } else {
                    $typeMessage = 2;
                }
            }
        }
    }

    if ($typeMessage == 1) {
        $message = $errorCodeNotValid;
        Display::display_error_message($message);
    }elseif($typeMessage == 2){
        $message = $errorLoadError;
        Display::display_error_message($message);
    }else{
        $message = $msgWait;
        Display::display_confirmation_message($message);
    }

} else {
    //non multiple

	$documentId = $_GET['doc'];
	compilatioUpdateWorkDocument($documentId, $coursId);
	$workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
	$query = "SELECT * FROM "
        . $workTable
        . " WHERE id='"
        . $documentId
        . "' AND c_id="
        . $coursId;
	$sqlResult = Database::query($query);
	$doc = Database::fetch_object($sqlResult);
	$currentCourseRepositoryWeb = api_get_path(WEB_COURSE_PATH) . $_course["path"] . "/";
	$WrkUrl = $currentCourseRepositoryWeb . $doc->url;
	$WrkTitle = $doc->title;
	$LocalWrkUrl = api_get_path(SYS_COURSE_PATH).$_course["path"] . "/" . $doc->url;
	$mime = typeMime($WrkTitle);
	$compilatio = new compilatio(
        $compilatioParameter['key'],
        $compilatioParameter['$urlsoap'],
        $compilatioParameter['proxy_host'],
        $compilatioParameter['proxy_port']);
	if ($compilatioParameter['mode_transport'] == 'wget') {

        if (strlen($compilatioParameter['wget_uri']) > 2) {
            $filename = ereg_replace("/$",
                    "",
                    $compilatioParameter['wget_uri'])
                    . "/"
                    . $_course["path"]
                    . "/"
                    . $doc->title;
        } else {
            $filename = $WrkUrl;
        }
        if (strlen($compilatioParameter['wget_login']) > 2) {
            $filename = $compilatioParameter['wget_login']
                . ":"
                . $compilatioParameter['wget_password']
                . "@"
                . $filename;
        }
        $mime = "text/plain";
        $compilatioId = $compilatio->sendDoc($doc->title, '', $filename, $mime, 'get_url');
    } else {
		$pieces = explode("/", $doc->url);
		$nbPieces = count($pieces);
		$filename = $pieces[$nbPieces-1];
        $compilatioId = $compilatio->sendDoc($doc->title, '', $filename, $mime, file_get_contents($LocalWrkUrl));
    }
	$compTable = Database::get_course_table(TABLE_PLAGIARISM);

	$sql4 = "INSERT INTO "
        . $compTable
        . "(c_id, id_doc, compilatio_id) VALUES ("
        . $coursId
        . ", '"
        . $doc->id
        . "','"
        . $compilatioId
        . "')";
	if (isMd5($compilatioId)) {
        Database::query($sql4);
	} else {
		$sql5 = "INSERT INTO "
            . $compTable
            . " (c_id, id_doc,compilatio_id) VALUES ("
            . $coursId
            . ", '"
            . $doc->id
            ."','error')";
        Database::query($sql5);

	}
	if (isMd5($compilatioId)) {
        $soapRes = $compilatio->startAnalyse($compilatioId);
    } else {
        $typeMessage = 2;
    }

    if ($typeMessage == 1) {
        $message = $errorCodeNotValid;
        Display::display_error_message($message);
    }elseif($typeMessage == 2){
        $message = $errorLoadError;
        Display::display_error_message($message);
    }else{
        $message = $msgWait;
        Display::display_confirmation_message($message);
	}

}

/**
 * function for show and recovery the extension from a file
 *
 * @param $docId
 * @param $coursId
 * @return string
 */
function workDocExtension($docId, $coursId) {
    $dbTitle = getWorkTitle($docId, $coursId);
    $res = getFileExtension($dbTitle);
    return $res;
}

function getFileExtension($filename) {
    $res = "";
    preg_match("/.*\.([^.]+)/", $filename, $dbTitle);
    if (count($dbTitle) > 1) {
        $res = $dbTitle[1];
    }
    return $res;
}

function getWorkTitle($docId, $coursId) {
    $res = "";
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $sql = "SELECT title FROM " . $workTable . " WHERE c_id=" . $coursId . " AND id=" . $docId;
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $data = Database::fetch_array($res);
        $res = $data['title'];
    }
    return $res;
}


function getFilename($txt) {
    $res = $txt;
    preg_match("|.*/([^/]+)|", $txt, $urlList);
    if (count($urlList) > 0) {
        $res = $urlList[1];
    }
    return $res;
}

function getWorkFolder($txt) {
    $res = "";
    preg_match("|(.*/)[^/]+|", $txt, $urlList);
    if (count($urlList) > 0) {
        $res = $urlList[1];
    }
    return $res;
}

function getShortFilename($txt) {
    $res = $txt;
    if (strlen($txt) > 10) {
        $res = substr($txt, 0, 10);
    }
    return $res;
}

function compilatioUpdateWorkDocument($docId, $coursId) {
    global $_course;
    $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    $extensionFile = workDocExtension($docId, $coursId);
    $urlFile = get_work_path($docId);
    $filename = getFilename($urlFile);
    $work_folder = getWorkFolder($urlFile);
    $urlFile_ext = getFileExtension($urlFile);
    $coursePath = api_get_path(SYS_COURSE_PATH) . $_course["path"] . "/";
    $workTitle = getWorkTitle($docId, $coursId);

    if ($extensionFile != "" && $urlFile_ext == "") {
        /*rename the files in the FS whit the extension*/
        $shortFilename = $filename;
        $cleanWorkTitle = api_replace_dangerous_char($workTitle);
        $newestFilename = $shortFilename . "_" . $cleanWorkTitle;
        rename($coursePath . $urlFile, $coursePath . $work_folder . $newestFilename);
        /*rename the db's input with the extension*/
        $sql = "UPDATE "
            . $workTable
            . " SET url='"
            . $work_folder
            . $newestFilename
            . "' WHERE c_id="
            . $coursId
            . " AND id="
            . $docId;
        $res = Database::query($sql);
    }
}
?>