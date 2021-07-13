<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;

/**
 * Download script for course info.
 */
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

if (isset($_GET['archive_path'])) {
    $archive_path = api_get_path(SYS_ARCHIVE_PATH);
} else {
    $archive_path = CourseArchiver::getBackupDir();
}
$archive_file = isset($_GET['archive']) ? $_GET['archive'] : null;
$archive_file = str_replace(['..', '/', '\\'], '', $archive_file);

list($extension) = getextension($archive_file);

if (empty($extension) || !file_exists($archive_path.$archive_file)) {
    exit;
}

$extension = strtolower($extension);
$content_type = '';

if (in_array($extension, ['xml', 'csv', 'imscc']) &&
    (api_is_platform_admin(true) || api_is_drh())
) {
    $content_type = 'application/force-download';
} elseif ('zip' === $extension && $_cid && (api_is_platform_admin(true) || api_is_course_admin())) {
    $content_type = 'application/force-download';
}

if (empty($content_type)) {
    api_not_allowed(true);
}
if (Security::check_abs_path($archive_path.$archive_file, $archive_path)) {
    DocumentManager::file_send_for_download(
        $archive_path.$archive_file,
        true,
        $archive_file
    );
    exit;
} else {
    api_not_allowed(true);
}
