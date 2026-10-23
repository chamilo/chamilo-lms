<?php

/* For licensing terms, see /license.txt */

/**
 * The platform-wide tracking reports moved to the global reporting admin view.
 * Kept only to redirect old links to the matching report there.
 */
require_once __DIR__.'/../inc/global.inc.php';

$sections = [
    'coaches' => '/trainers',
    'user' => '/users',
    'session' => '/sessions',
    'course' => '/courses',
    'company' => '/organization',
    'learningPath' => '/learning-path-authors',
    'learningPathByItem' => '/learning-path-item-authors',
    'accessoverview' => '/accesses',
];

$display = (string) ($_GET['display'] ?? '');

header('Location: '.api_get_path(WEB_PATH).'reporting/admin'.($sections[$display] ?? ''));
exit;
