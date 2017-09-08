<?php

use ChamiloSession as Session;

/*
 * filesave.php
 * To be used with ext-server_opensave.js for SVG-edit
 *
 * Licensed under the Apache License, Version 2
 *
 * Copyright(c) 2010 Alexis Deveria
 *
 * Integrate svg-edit with Chamilo
 * @author Juan Carlos Raña Trabado
 * @since 25/september/2010
*/

require_once '../../../../../inc/global.inc.php';

// Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();

if (!isset($_POST['output_svg']) && !isset($_POST['output_png'])) {
    api_not_allowed();
}

$file = '';
$suffix = isset($_POST['output_svg']) ? 'svg' : 'png';

if (isset($_POST['filename']) && strlen($_POST['filename']) > 0) {
    $file = $_POST['filename'];
} else {
    $file = 'image';
}

if ($suffix == 'svg') {
    $mime = 'image/svg+xml';
    $contents = rawurldecode($_POST['output_svg']);
} else {
    $mime = 'image/png';
    $contents = $_POST['output_png'];
    $pos = (strpos($contents, 'base64,') + 7);
    $contents = base64_decode(substr($contents, $pos));
}

//get SVG-Edit values
$filename = $file;//from svg-edit
$extension = $suffix;// from svg-edit
$content = $contents;//from svg-edit
$title = Database::escape_string(str_replace('_', ' ', $filename));
$_course = api_get_course_info();
$relativeUrlPath = Session::read('draw_dir');
$_course = api_get_course_info();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();
$groupInfo = GroupManager::get_group_properties($groupId);
$dirBaseDocuments = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
$saveDir = $dirBaseDocuments.$relativeUrlPath;

// a bit title security
$filename = addslashes(trim($filename));
$filename = Security::remove_XSS($filename);
$filename = api_replace_dangerous_char($filename);
$filename = disable_dangerous_file($filename);

// a bit extension
if ($suffix != 'svg' && $suffix != 'png') {
    die();
}
$drawFileNameFromSession = Session::read('draw_file');

//checks if the file exists, then rename the new
if (file_exists($saveDir.'/'.$filename.'.'.$extension) &&
    empty($drawFileNameFromSession)
) {
    $message = get_lang('FileExistsChangeToSave');
    $params = array(
        'message' => $message,
        'url' => ''
    );
    echo json_encode($params);
    exit;
} else {
    $drawFileName = $filename.'.'.$extension;
    $title = $title.'.'.$extension;
}

$documentPath = $saveDir.'/'.$drawFileName;

//add new document to disk
file_put_contents($documentPath, $contents);

if (empty($drawFileNameFromSession)) {
    //add document to database
    $doc_id = add_document(
        $_course,
        $relativeUrlPath.'/'.$drawFileName,
        'file',
        filesize($documentPath),
        $title
    );
    api_item_property_update(
        $_course,
        TOOL_DOCUMENT,
        $doc_id,
        'DocumentAdded',
        api_get_user_id(),
        $groupInfo,
        null,
        null,
        null,
        $sessionId
    );
} else {
    if ($drawFileNameFromSession == $drawFileName) {
        $document_id = DocumentManager::get_document_id(
            $_course,
            $relativeUrlPath.'/'.$drawFileName
        );
        update_existing_document(
            $_course,
            $document_id,
            filesize($documentPath),
            null
        );
        api_item_property_update(
            $_course,
            TOOL_DOCUMENT,
            $document_id,
            'DocumentUpdated',
            api_get_user_id(),
            $groupInfo,
            null,
            null,
            null,
            $sessionId
        );
    } else {
        //add a new document
        $doc_id = add_document(
            $_course,
            $relativeUrlPath.'/'.$drawFileName,
            'file',
            filesize($documentPath),
            $title
        );
        api_item_property_update(
            $_course,
            TOOL_DOCUMENT,
            $doc_id,
            'DocumentAdded',
            api_get_user_id(),
            $groupInfo,
            null,
            null,
            null,
            $sessionId
        );
    }
}

//clean sessions and add messages and return to current document list
Session::erase('draw_dir');
Session::erase('draw_file');

if ($suffix != 'png') {
    if ($relativeUrlPath == '') {
        $relativeUrlPath = '/';
    };
    $url = api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq().'&curdirpath='.urlencode($relativeUrlPath);
    $message = get_lang('FileSavedAs').': '.$title;
} else {
    $url = '';
    $message = get_lang('FileExportAs').': '.$title;
}

$params = array(
    'message' => $message,
    'url' => $url
);
echo json_encode($params);
exit;
