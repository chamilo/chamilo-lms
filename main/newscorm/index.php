<?php //$id: $
/**
 * Redirection script
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */

//flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;

require('back_compat.inc.php');
header('location: lp_controller.php?'.api_get_cidReq().'&action=list');
?>
