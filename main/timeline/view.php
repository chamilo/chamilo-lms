<?php
/* For licensing terms, see /license.txt */
/**	
	@author Julio Montoya <gugli100@gmail.com> BeezNest 2011
*	@package chamilo.timeline
*/

// name of the language file that needs to be included
$language_file = array ('registration','admin');
require_once '../inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH).'timeline.lib.php';  

$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('Timeline'));
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('Listing'));

$timeline = new Timeline();
if (empty($_GET['id'])) {
    api_not_allowed();
}
$url = $timeline->get_url($_GET['id']);
$item = $timeline->get($_GET['id']);
$interbreadcrumb[]=array('url' => '#','name' => $item['headline']);

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/timeline/timeline.css');
$htmlHeadXtra[] = api_get_js('timeline/timeline-min.js');
$htmlHeadXtra[] = '
<script>
	$(document).ready(function() {
		var timeline = new VMM.Timeline();
		timeline.init("'.$url.'");			
	});
</script>';
$content = '<div class="timeline-example"><div id="timeline"></div></div>';

$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
