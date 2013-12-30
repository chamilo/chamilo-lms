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
$linkId = intval($_GET['link_id']);

require_once api_get_path(LIBRARY_PATH).'link.lib.php';
$linkInfo = get_link_info($linkId);
if ($linkInfo['target'] == '_in_header') {
    $tpl = $app['template'];
    $url = $linkInfo['url'];

    $interbreadcrumb[] = array('url' => 'link.php', 'name' => get_lang('Links'));

    $frame = '<iframe name="page" onload="javascript:resizeIframe(this);" style="width:100%;frameBorder:0px; height:500px" src="'.$url.'">
             </iframe>';
    $js = "<script>
    function resizeIframe(obj) {
        /*var body = obj.contentWindow.document.body;
        var height =$(obj, top.document).height();
        console.log(height);
        console.log(jQuery('iframe',top.document).height());
        obj.style.height = height;*/
    }
    </script>";
    $tpl->assign('content', $js.$frame);
    $tpl->display_one_col_template();
} else {



    // Launch event
    event_link($linkId);

    header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");                                     // HTTP/1.0
    header("Location: $link_url");

    // To be sure that the script stops running after the redirection
    exit;
}