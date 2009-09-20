<?php // $Id: download.php 21562 2009-06-22 10:38:38Z ivantcholakov $
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

require '../inc/global.inc.php';
$this_section = SECTION_COURSES;

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

$archive_path = api_get_path(SYS_ARCHIVE_PATH);
$archive_file = $_GET['archive'];

$archive_file = str_replace(array('..', '/', '\\'), '', $archive_file);

list($extension) = getextension($archive_file);

if (empty($extension) || !file_exists($archive_path.$archive_file)) {
	exit();
}

$content_type = '';

if (in_array(strtolower($extension), array('xml','csv')) && api_is_platform_admin(true)) {
	$content_type = 'application/force-download';
}
elseif (strtolower($extension) == 'zip' && $_cid && $is_courseAdmin) {
	$content_type = 'application/force-download';
}

if (empty($content_type)) {
	api_not_allowed(true);
}

header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: public');
header('Pragma: no-cache');

header('Content-Type: '.$content_type);
header('Content-Length: '.filesize($archive_path.$archive_file));
header('Content-Disposition: attachment; filename='.$archive_file);

readfile($archive_path.$archive_file);
