<?php

/* For licensing terms, see /license.txt */

$course_plugin = 'teams';

require_once __DIR__.'/config.php';

api_protect_course_script(true);

$plugin = TeamsPlugin::create();
if (!$plugin->isEnabledForCurrentAccessUrl()) {
    api_not_allowed(true);
}

$course = api_get_course_entity();
if (null === $course) {
    api_not_allowed(true);
}

$target = api_get_path(WEB_PATH).'conference/teams/course';
$cidReq = api_get_cidreq();

if ('' !== $cidReq) {
    $target .= '?'.$cidReq;
}

api_location($target);
