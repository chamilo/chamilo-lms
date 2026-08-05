<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

$plugin = CourseBlockPlugin::create();

if (!$plugin->isEnabled()) {
    return;
}

/*
 * CourseBlock is rendered through CourseBlockPlugin::renderRegion() by the
 * plugin region controller for course-aware regions.
 *
 * Do not echo the same content from this legacy entry point, otherwise the
 * region output is duplicated: once by AppPlugin::loadRegion() and once by
 * renderRegion().
 */
return;
