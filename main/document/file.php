<?php

Use Model\Document;
Use Model\Course;

/**
 * 	Return either 
 * 
 *      - one document
 *      - several documents (file and/or folders) zipped together
 * 
 * Used to transfer files to another application through http.
 * 
 * Script parameters:
 * 
 *      - id        id(s) of the document id=1 or id=1,2,4  
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
 * One file requested. In this case we return the file itself. 
 */
if (count($ids) == 1) {
    $id = reset($ids);
    $doc = Document::get_by_id($course, $id);
    if (empty($doc)) {
        Response::not_found();
    }

    if ($doc->is_file()) {
        $has_access = $doc->is_accessible();
        if (!$has_access) {
            Response::not_found();
        }

        event_download(Uri::here());
        DocumentManager::file_send_for_download($doc);
        exit;
    }
}

/**
 * Several files requested. In this case we zip them together. 
 */
$files = array();
$folders = array();
foreach ($ids as $id) {
    $doc = Document::get_by_id($course, $id);
    if (!$doc->is_accessible()) {
        break;
    }
    if ($doc->is_file()) {
        $files[] = $doc;
    }
    if ($doc->is_folder()) {
        $folders[] = $doc;
    }
}

$requested_folders = $folders;

/**
 * Note that if a parent folder is hidden children should not be accesible 
 * even if they are visible. It is therefore not sufficient to check document 
 * visibility. 
 */
while ($folders) {
    $items = $folders;
    $folders = array();
    foreach ($items as $item) {
        $children = $item->get_children();
        foreach ($children as $child) {
            if (!$child->is_accessible()) {
                break;
            }
            if ($child->is_file()) {
                $files[] = $child;
            }
            if ($child->is_folder()) {
                $folders[] = $child;
            }
        }
    }
}

$folders = $requested_folders;

/**
 * Requested files may not be accessible. 
 */
if (count($files) == 0) {
    Response::not_found();
}

$root_dir = '';
$items = array_merge($folders, $files);
foreach ($items as $item) {
    $path = $item->get_absolute_path();
    $path = realpath($path);
    $dir = dirname($path);

    if (empty($root_dir) || strlen($root_dir) > strlen($dir)) {
        $root_dir = $dir;
    }
}

/**
 * Zip files together. 
 */
$temp_zip_path = Chamilo::temp_file('zip');
$zip_folder = new PclZip($temp_zip_path);
foreach ($files as $file) {
    if (empty($root_dir)) {
        $root_dir = dirname($file);
    }
    $file = (string) $file;
    $zip_folder->add($file, PCLZIP_OPT_REMOVE_PATH, $root_dir);
}

/**
 * Send file for download 
 */
event_download(Uri::here());
DocumentManager::file_send_for_download($temp_zip_path, false, get_lang('Documents') . '.zip');