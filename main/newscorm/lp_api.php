<?php
/* For licensing terms, see /license.txt */

/**
 * DEPRECATED (temporarily left here)
 * Script that displays the footer frame for lp_view.php
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

require_once 'back_compat.inc.php';
//require_once 'lp_comm.common.php'; // xajax functions
//$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/')."\n";
//$htmlHeadXtra[] = '<script language="javascript">var myxajax = window.parent.oxajax;</script>';
include_once '../inc/reduced_header.inc.php';
?>
<body>
<!--div id="clickme" style="border: 1px solid black; width:10px; height:7px;" onclick="myxajax.xajax_get_statuses();"></div-->
</body>
</html>