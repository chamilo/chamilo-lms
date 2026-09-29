<?php

/* For licensing terms, see /license.txt */

/**
 * Legacy entry point — redirects to the Vue SPA page.
 * Kept as a deprecation stub so bookmarks and external links keep working.
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$section = isset($_GET['section']) ? trim((string) $_GET['section']) : '';
$target = '/admin/system-status';
if ('' !== $section) {
    $target .= '?section='.urlencode($section);
}

header('Location: '.$target);
exit;
