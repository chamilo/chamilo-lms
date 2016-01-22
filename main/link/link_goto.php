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
require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

$linkId = intval($_GET['link_id']);

$linkInfo = Link::get_link_info($linkId);
$linkUrl = html_entity_decode(Security::remove_XSS($linkInfo['url']));
// Launch event
Event::event_link($linkId);

header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                                     // HTTP/1.0
header("Location: $linkUrl");
exit;
