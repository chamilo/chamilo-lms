<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

// Deprecated file left here to redirect old links to the forum tool in Vue. Should be removed before the next major version.
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$url = getForumToolUrl(api_get_course_int_id(), api_get_session_id(), api_get_group_id());
if ('' === $url) {
    api_not_allowed(true);
}

header('Location: '.$url);
exit;
