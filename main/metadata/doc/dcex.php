<?php

require_once("../metadata/md_funcs.php"); $mdStore = new mdstore(TRUE);
require_once(api_get_path(LIBRARY_PATH) . 'xmd.lib.php');
require_once(api_get_path(LIBRARY_PATH) . 'xht.lib.php');
require_once('../metadata/md_document.php');

$mdObj = new mdobject($_course, $docId);  // e.g. '12'

// Fetch example:
if (is_array($dcelems = $mdStore->mds_get_dc_elements($mdObj)))
	echo '<div>', htmlspecialchars($dcelems['Identifier']), ': ', 
	    htmlspecialchars($dcelems['Description']), '</div>';

// Store example:
$langMdCopyright = 'Provided the source is acknowledged';
$mdStore->mds_put_dc_elements($mdObj, array('Description'=>time()));
?>