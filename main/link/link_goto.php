<?php
/* For licensing terms, see /license.txt */

/**
* This page is used to launch an event when a user clicks
* on a page linked in a course.
* - It gets name of URL
* - It calls the event function
* - It redirects the user to the linked page
*
* Need the liens.id, user.user_id et cours.code when called
* ?link_id=$myrow[0]&link_url=$myrow[1]
* url is given to avoid a new select
*
* @author Thomas Depraetere, Hugues Peeters, Christophe Gesch� - original versions
* @package chamilo.link
*/

/*	INIT SECTION */

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

$link_url = html_entity_decode(Security::remove_XSS($_GET['link_url']));
$link_id = intval($_GET['link_id']);

// Launch event
event_link($link_id);

header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                                     // HTTP/1.0
header("Location: $link_url");

// To be sure that the script stops running after the redirection
exit;
