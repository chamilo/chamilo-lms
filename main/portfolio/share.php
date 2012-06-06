<?php

/**
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
require_once __DIR__ . '/../inc/global.inc.php';

$has_access = api_protect_course_script();
if (!$has_access) {
    exit;
}

Portfolio::controller()->run();
