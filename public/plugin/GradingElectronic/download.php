<?php

/* For licensing terms, see /license.txt */

require_once '../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

$gradingElectronic = GradingElectronicPlugin::create();
$gradingElectronic->downloadFromRequest();
