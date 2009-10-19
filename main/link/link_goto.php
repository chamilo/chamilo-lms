<?php // $Id: link_goto.php 22201 2009-07-17 19:57:03Z cfasanando $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

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
* This page is used to launch an event when a user clicks
* on a page linked in a course.
* - It gets name of URL
* - It calls the event function
* - It redirects the user to the linked page
*                                                               |
* Need the liens.id, user.user_id et cours.code when called
* ?link_id=$myrow[0]&link_url=$myrow[1]                           |
* url is given to avoid a new select
*
* @author Thomas Depraetere, Hugues Peeters, Christophe Gesch� - original versions
* @package dokeos.link
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$link_url = Security::remove_XSS($_GET['link_url']);
$link_id = Security::remove_XSS($_GET['link_id']);

// launch event
event_link($link_id);

header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                                     // HTTP/1.0
header("Location: $link_url");

//to be sure that the script stops running after the redirection
exit;

?>