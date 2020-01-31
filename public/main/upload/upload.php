<?php
/* For licensing terms, see /license.txt */

/**
 * Action controller for the upload process. The display scripts (web forms) redirect
 * the process here to do what needs to be done with each file.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
require_once __DIR__.'/../inc/global.inc.php';

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

require 'upload.scorm.php';
