<?php
/* For licensing terms, see /license.txt */

/**
 * This script contains the server part of the xajax interaction process.
 * This script, in particular, enables the process of SCO's initialization. It
 * resets the JavaScript values for each SCO to the current LMS status.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

require_once __DIR__.'/lp_initialize_item.inc.php';

echo initialize_item(
    $_POST['lid'],
    $_POST['uid'],
    $_POST['vid'],
    $_POST['iid']
);
