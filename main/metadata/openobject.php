<?php /*                                 <!-- Dokeos metadata/openobject.php -->
                                                             <!-- 2004/08/27 -->

<!-- Copyright (C) 2004 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

*/

/**
============================================================================== 
*	Dokeos Metadata: general script for opening an object
*
*   URL parameters:
*   - eid=  entry-id = object-id = type.identifier, e.g. 'Document.12';
*
*	@package dokeos.metadata
============================================================================== 
*/


require("md_funcs.php");
// name of the language file that needs to be included 
/*
$language_file = 'Whatever'; 
*/
require("../inc/global.inc.php");
$this_section=SECTION_COURSES;

getpar('EID', 'Entry IDentifier');
if (!($dotpos = strpos(EID, '.'))) give_up('No . in ' . EID);

require('md_' . strtolower(substr(EID, 0, $dotpos)) . '.php');
$mdObj = new mdobject($_course, substr(EID, $dotpos + 1));

header('Location: ' . $mdObj->mdo_url);

exit;
?>
