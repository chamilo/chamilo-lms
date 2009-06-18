<?php
/* For licensing terms, see /dokeos_license.txt */

// including the global dokeos file
require_once '../inc/global.inc.php';
require_once '../glossary/glossary.class.php';

// notice for unauthorized people.
api_protect_course_script(true);

/*
 * search a term and return description from a glossary
 */
 $glossary_id=Security::remove_XSS($_POST['glossary_id']);
$glossary_description=GlossaryManager::get_glossary_term_by_glossary_id($glossary_id);

 if (isset($_POST['glossary_id']) && $_POST['glossary_id']==strval(intval($_POST['glossary_id']))) {
  	echo api_xml_http_response_encode($glossary_description);	
 } else {
 	echo '';
 }
