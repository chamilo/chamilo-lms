<?php

/* For licensing terms, see /license.txt */

/**
 * Command to export certificates for a gradebook categori in course (or course session).
 *
 * <code>php main/gradebook/cli/export_all_certificated.php COURSE_CODE SESSION_ID CATEGORY_ID [USER_IDS]</code>
 */
require_once __DIR__.'/../../inc/global.inc.php';

if (PHP_SAPI !== 'cli') {
    exit(get_lang('NotAllowed'));
}

$courseCode = $argv[1];
$sessionId = $argv[2];
$categoryId = $argv[3];
$userList = isset($argv[4]) ? explode(',', $argv[4]) : [];

$date = api_get_utc_datetime(null, false, true);

$pdfName = 'certs_'.$courseCode.'_'.$sessionId.'_'.$categoryId.'_'.$date->format('Y-m-d');

$finalFile = api_get_path(SYS_ARCHIVE_PATH)."$pdfName.pdf";

if (file_exists($finalFile)) {
    unlink(api_get_path(SYS_ARCHIVE_PATH)."$pdfName.pdf");
}

Category::exportAllCertificates($categoryId, $userList, $courseCode, true, $pdfName);
