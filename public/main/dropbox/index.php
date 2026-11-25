<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

// The tool is served by the Vue views (/resources/dropbox). This stub only keeps old links
// (e.g. learning path items of type dropbox) working by redirecting to them.
$dropboxCourse = api_get_course_entity();
if (null === $dropboxCourse) {
    api_not_allowed(true);
}
header('Location: '.api_get_path(WEB_PATH).'resources/dropbox/'.$dropboxCourse->getResourceNode()->getId().'/?'.api_get_cidreq());
exit;
