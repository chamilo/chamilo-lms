<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
// Avoid auto-closing the session in global.inc.php because of api_is_platform_admin() call
const KEEP_SESSION_OPEN = true;
require_once __DIR__.'/../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'get_promotions':
        $careerId = isset($_REQUEST['career_id']) ? (int) $_REQUEST['career_id'] : 0;
        $career = new Promotion();
        $promotions = $career->get_all_promotions_by_career_id($careerId);
        echo json_encode($promotions);

        break;
}
