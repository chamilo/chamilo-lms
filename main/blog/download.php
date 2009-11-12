<?php // $Id: download.php 12218 2007-05-01 18:27:14Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	info@dokeos.com
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
api_protect_course_script(true);

$doc_url = $_GET['file'];
//change the '&' that got rewritten to '///' by mod_rewrite back to '&'
$doc_url = str_replace('///', '&', $doc_url);
//still a space present? it must be a '+' (that got replaced by mod_rewrite)
$doc_url = str_replace(' ', '+', $doc_url);
$doc_url = str_replace('/..', '', $doc_url); //echo $doc_url;

if (! isset($_course))
{
	api_not_allowed(true);
}
$full_file_name = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/blog/'.$doc_url;

//if the rewrite rule asks for a directory, we redirect to the course view
if (is_dir($full_file_name))
{
	//remove last slash if present
	while ($doc_url{$dul = strlen($doc_url)-1}=='/') $doc_url = substr($doc_url,0,$dul);
	//create the path
	$document_explorer = api_get_path(WEB_COURSE_PATH).api_get_course_path(); // home course path
	//redirect
	header('Location: '.$document_explorer);
}

$tbl_blogs_attachment 	= Database::get_course_table(TABLE_BLOGS_ATTACHMENT);

// launch event
event_download($doc_url);

$sql = 'SELECT filename FROM '.$tbl_blogs_attachment.' WHERE path LIKE BINARY "'.$doc_url.'"';

$result= Database::query($sql, __FILE__, __LINE__);
$row= Database::fetch_array($result);
DocumentManager::file_send_for_download($full_file_name,TRUE, $row['filename']);
exit;
?>