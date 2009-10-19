<?php //$id: $
/**
 * Script that displays the footer frame for lp_view.php
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */
//flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;
require_once('back_compat.inc.php');
include_once('../inc/reduced_header.inc.php');
Display::display_footer();
?>