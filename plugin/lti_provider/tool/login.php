<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../src/LtiProvider.php';
use Packback\Lti1p3;

LtiProvider::create()->login($_REQUEST);
