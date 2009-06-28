<?php
/* For licensing terms, see /dokeos_license.txt */

// including the global dokeos file
require_once '../inc/global.inc.php';
require_once '../glossary/glossary.class.php';
/*
 * search a term and return description from a glossary
 */
 $glossary_id=Security::remove_XSS($_POST['glossary_id']);
$glossary_description=GlossaryManager::get_glossary_term_by_glossary_id($glossary_id);
$glossary_data=GlossaryManager::get_glossary_terms();

$glossary_all_data=array();
foreach ($glossary_data as $glossary_index=>$glossary_value) {
	$glossary_all_data[]=$glossary_value['id'].'__|__|'.$glossary_value['name'];
}

$glossary_all_data=implode('[|.|_|.|-|.|]',$glossary_all_data);

//get_glossary_terms
 if (isset($_POST['glossary_id']) && $_POST['glossary_id']==strval(intval($_POST['glossary_id']))) {
  	echo api_xml_http_response_encode($glossary_description);	
 } elseif (isset($_POST['glossary_data']) && $_POST['glossary_data']=='true') {
   	echo api_xml_http_response_encode($glossary_all_data);	
 } else {
 	echo '';
 }