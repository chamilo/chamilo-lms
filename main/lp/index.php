<?php
/* For licensing terms, see /license.txt */
/**
 * Redirection script.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

require_once __DIR__.'/../inc/global.inc.php';
header('location: lp_controller.php?'.api_get_cidreq().'&action=list');
exit;
