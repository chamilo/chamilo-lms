<?php // $Id: download.php 9246 2006-09-25 13:24:53Z bmol $
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

session_cache_limiter('public');

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include(api_get_path(LIBRARY_PATH).'document.lib.php');

// IMPORTANT to avoid caching of documents
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Cache-Control: public');
header('Pragma: no-cache');

//protection
api_protect_course_script();

$doc_url = $_GET['doc_url'];
//change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $doc_url);
//still a space present? it must be a '+' (that got replaced by mod_rewrite)
$doc_url = str_replace(' ', '+', $doc_url);
$doc_url = str_replace('/..', '', $doc_url); //echo $doc_url;

include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');

if (! isset($_course))
{
	api_not_allowed();
}


//if the rewrite rule asks for a directory, we redirect to the document explorer
if(is_dir(api_get_path(SYS_COURSE_PATH).$_course['path']."/document".$doc_url)) 
{
	//remove last slash if present
	//$doc_url = ($doc_url{strlen($doc_url)-1}=='/')?substr($doc_url,0,strlen($doc_url)-1):$doc_url; 
	//mod_rewrite can change /some/path/ to /some/path// in some cases, so clean them all off (René)
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

$sys_course_path = api_get_path(SYS_COURSE_PATH);
$full_file_name = $sys_course_path.$_course['path'].'/document'.$doc_url;

DocumentManager::file_send_for_download($full_file_name);
?>