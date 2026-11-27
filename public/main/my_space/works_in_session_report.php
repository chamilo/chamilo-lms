<?php

/* For licensing terms, see /license.txt */

/**
 * The works in session report moved to the global reporting.
 * Kept only to redirect old links there, with the selected session.
 */
require_once __DIR__.'/../inc/global.inc.php';

$sessionId = (int) ($_GET['session'] ?? 0);

header('Location: /reporting/admin/works-in-session'.($sessionId > 0 ? '?sessionId='.$sessionId : ''));
exit;
