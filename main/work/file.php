<?php

Use Model\StudentPublication;
Use Model\Course;

/**
 * 	Return either 
 * 
 *      - one work item (file)
 *      - several work items (files) zipped together
 * 
 * Used to transfer files to another application through http.
 * 
 * Script parameters:
 * 
 *      - id        id(s) of the work item id=1 or id=1,2,4  
 *      - cidReq    course code
 * 
 * Note this script enables key authentication so access with a key token is possible.
 * 
 * @package chamilo.document
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
require_once __DIR__ . '/../inc/autoload.inc.php';
KeyAuth::enable();

require_once __DIR__ . '/../inc/global.inc.php';

$has_access = api_protect_course_script();
if (!$has_access) {
    exit;
}

session_cache_limiter('none');

$ids = Request::get('id', '');
$ids = $ids ? explode(',', $ids) : array();

$course = Course::current();

/**
 * No files requested. We make sure we return 404 error to tell the client
 * that the call failed. 
 */
if (count($ids) == 0 || empty($course)) {
    Response::not_found();
}

/**
 * One file/folder requested.
 */
if (count($ids) == 1) {
    $id = reset($ids);
    $pub = StudentPublication::get_by_id($course, $id);
    if (empty($pub)) {
        Response::not_found();
    }

    $has_access = $pub->is_accessible();
    if (!$has_access) {
        Response::not_found();
    }

    if ($pub->is_file()) {
        event_download(Uri::here());
        DocumentManager::file_send_for_download($pub->get_absolute_path(), false, $pub->get_title());
        exit;
    }

    /**
     * one folder requested 
     */
    $items = array();
    $children = $pub->get_children();
    foreach ($children as $child) {
        if ($child->is_accessible()) {
            $items[] = $child;
        }
    }
    if (count($items) == 0) {
        Response::not_found();
    }

    $zip = Chamilo::temp_zip();
    foreach ($items as $item) {
        $path = $item->get_absolute_path();
        $title = $item->get_title();
        $zip->add($path, $title);
    }
    event_download(Uri::here());
    DocumentManager::file_send_for_download($zip->get_path(), false, $pub->get_title() . '.zip');
}

/**
 * Several files requested. In this case we zip them together. 
 */
$items = array();
foreach ($ids as $id) {
    $pub = StudentPublication::get_by_id($course, $id);
    if (!$pub->is_accessible()) {
        break;
    }
    if ($pub->is_file()) {
        $items[] = $pub;
    }
    /**
     * We ignore folders 
     */
}

/**
 * Requested files may not be accessible. 
 */
if (count($items) == 0) {
    Response::not_found();
}

/**
 * Zip files together. 
 */
$zip = Chamilo::temp_zip();
foreach ($items as $item) {
    $path = $item->get_absolute_path();
    $title = $item->get_title();
    $zip->add($path, $title);
}

/**
 * Send file for download 
 */
event_download(Uri::here());
DocumentManager::file_send_for_download($zip->get_path(), false, get_lang('StudentPublications') . '.zip');