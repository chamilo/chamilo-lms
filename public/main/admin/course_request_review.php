<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

require_once __DIR__.'/../inc/lib/course_request_admin.inc.php';

course_request_admin_list(COURSE_REQUEST_PENDING);
