<?php

/* For licensing terms, see /license.txt */

/**
 * This script contains the server part of the xajax interaction process. The client part is located
 * in lp_api.php or other api's.
 * This is a first attempt at using xajax and AJAX in general, so the code might be a bit unsettling.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';

echo ScormApi::switchItem(
    $_REQUEST['lid'],
    $_REQUEST['uid'],
    $_REQUEST['vid'],
    $_REQUEST['iid'],
    $_REQUEST['next']
);
