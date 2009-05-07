<?php // $Id: download.php 20388 2009-05-07 12:38:12Z spyroux $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	Copyright (c) Roan Embrechts
	Copyright (c) Sergio A. Kessler aka "sak"

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This file is responsible for  passing requested documents to the browser.
*	Html files are parsed to fix a few problems with URLs,
*	but this code will hopefully be replaced soon by an Apache URL
*	rewrite mechanism.
*
*	@package dokeos.document
==============================================================================
*/

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/
/* file_html_dynamic_parsing removed */
/* other functions updated and moved to lib/document.lib.php */

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

session_cache_limiter('none');

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include(api_get_path(LIBRARY_PATH).'document.lib.php');

$doc_url = $_GET['doc_url'];
//change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $doc_url);
//still a space present? it must be a '+' (that got replaced by mod_rewrite)
$doc_url = str_replace(' ', '+', $doc_url);
$doc_url = str_replace('/..', '', $doc_url); //echo $doc_url;


// dealing with image included into survey: when users receive a link towards a 
// survey while not being authenticated on the plateform. 
// the administrator should probably be able to disable this code through admin
// inteface
$refer_script = strrchr($_SERVER["HTTP_REFERER"],'/');
if (substr($refer_script,0,15) == "/fillsurvey.php") {
	$invitation = substr(strstr($refer_script, 'invitationcode='),15);
	$course = strstr($refer_script, 'course=');
	$course = substr($course, 7, strpos($course, '&')-7);
	include ("../survey/survey.download.inc.php");
	$_course = check_download_survey($course, $invitation, $doc_url);
	$_course['path']=$_course['directory'];
} else {
	//protection
	api_protect_course_script();

	include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');

	if (! isset($_course))
	{
		api_not_allowed(true);
	}


	//if the rewrite rule asks for a directory, we redirect to the document explorer
	if(is_dir(api_get_path(SYS_COURSE_PATH).$_course['path']."/document".$doc_url)) 
	{
		//remove last slash if present
		//$doc_url = ($doc_url{strlen($doc_url)-1}=='/')?substr($doc_url,0,strlen($doc_url)-1):$doc_url; 
		//mod_rewrite can change /some/path/ to /some/path// in some cases, so clean them all off (Ren�)
		while ($doc_url{$dul = strlen($doc_url)-1}=='/') $doc_url = substr($doc_url,0,$dul);
		//group folder?
		$gid_req = ($_GET['gidReq'])?'&gidReq='.$_GET['gidReq']:'';
		//create the path
		$document_explorer = api_get_path(WEB_CODE_PATH).'document/document.php?curdirpath='.urlencode($doc_url).'&cidReq='.$_GET['cidReq'].$gid_req;
		//redirect
		header('Location: '.$document_explorer);
	}

	// launch event
	event_download($doc_url);

}

$sys_course_path = api_get_path(SYS_COURSE_PATH);
$full_file_name = $sys_course_path.$_course['path'].'/document'.$doc_url;

// check visibility of document and paths
$is_allowed_to_edit = api_is_allowed_to_edit();
if (!$is_allowed_to_edit &&
    !DocumentManager::is_visible($doc_url, $_course)){
       echo "document not visible"; //api_not_allowed backbutton won't work
       exit; // you shouldn't be here anyway
}

DocumentManager::file_send_for_download($full_file_name);

?>
