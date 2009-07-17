<?php
/* For licensing terms, see /dokeos_license.txt */

// including the global dokeos file
require_once '../inc/global.inc.php';
require_once '../glossary/glossary.class.php';
/*
 * search a term and return description from a glossary
 */
global $charset; 
$glossary_id=Security::remove_XSS($_POST['glossary_id']);
$glossary_description_by_id=GlossaryManager::get_glossary_term_by_glossary_id($glossary_id);
$glossary_data=GlossaryManager::get_glossary_terms();
$my_glossary_name=Security::remove_XSS($_POST['glossary_name']);
$my_glossary_name=api_convert_encoding($my_glossary_name,$charset,'UTF-8');
$my_glossary_name=trim($my_glossary_name);

$glossary_description=GlossaryManager::get_glossary_term_by_glossary_name($my_glossary_name);
$glossary_all_data=array();
foreach ($glossary_data as $glossary_index=>$glossary_value) {
	$glossary_all_data[]=$glossary_value['id'].'__|__|'.$glossary_value['name'];
}
$glossary_all_data=implode('[|.|_|.|-|.|]',$glossary_all_data);

//replace image path
$path_image=api_get_path(WEB_COURSE_PATH).api_get_course_path();
$path_image_search='../../courses/'.api_get_course_path();
//get_glossary_terms
 if (isset($_POST['glossary_id']) && $_POST['glossary_id']==strval(intval($_POST['glossary_id']))) {
		$glossary_description_by_id=str_replace($path_image_search,$path_image,$glossary_description_by_id); 	
  	echo api_xml_http_response_encode($glossary_description_by_id);	
 } elseif (isset($_POST['glossary_data']) && $_POST['glossary_data']=='true') {
   	echo api_xml_http_response_encode($glossary_all_data);	
 } elseif(isset($_POST['glossary_name'])) {
 	if (is_null($glossary_description)) {
 		echo api_xml_http_response_encode(get_lang('NoResults'));
 	} else {
		$glossary_description=str_replace($path_image_search,$path_image,$glossary_description);
 	 	echo api_xml_http_response_encode($glossary_description);	
 	}
 	
 } else {
 	echo api_xml_http_response_encode(get_lang('NoResults'));
 }