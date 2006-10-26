<?php // $Id: download.php 9246 2006-09-25 13:24:53Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

session_cache_limiter('public');

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');

$archivePath=api_get_path(SYS_PATH).$archiveDirName.'/';
$archiveFile=$_GET['archive'];

$archiveFile=str_replace(array('..','/','\\'),'',$archiveFile);

list($extension)=getextension($archiveFile);

if(empty($extension) || !file_exists($archivePath.$archiveFile))
{
	exit();
}

$content_type='';

if(in_array($extension,array('xml','csv')) && $is_platformAdmin)
{
	$content_type='application/force-download';
}
elseif($extension == 'zip' && $_cid && $is_courseAdmin)
{
	$content_type='application/force-download';
}

if(empty($content_type))
{
	not_allowed();
}

header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: public');
header('Pragma: no-cache');

header('Content-Type: '.$content_type);
header('Content-Length: '.filesize($archivePath.$archiveFile));
header('Content-Disposition: attachment; filename='.$archiveFile);

readfile($archivePath.$archiveFile);
?>