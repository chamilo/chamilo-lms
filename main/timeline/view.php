<?php
/* For licensing terms, see /license.txt */
/**
    @author Julio Montoya <gugli100@gmail.com> BeezNest 2011
 *	@package chamilo.timeline
 */
require_once __DIR__.'/../inc/global.inc.php';

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Timeline')];
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Listing')];

$timeline = new Timeline();
if (empty($_GET['id'])) {
    api_not_allowed();
}
$url = $timeline->get_url($_GET['id']);
$item = $timeline->get($_GET['id']);
$interbreadcrumb[] = ['url' => '#', 'name' => $item['headline']];

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/timeline/timeline.css');
$htmlHeadXtra[] = api_get_js('timeline/timeline-min.js');
$htmlHeadXtra[] = '
<script>
	$(function() {
		var timeline = new VMM.Timeline();
		timeline.init("'.$url.'");
	});
</script>';
$content = '<div class="timeline-example"><div id="timeline"></div></div>';

$tpl = new Template($tool_name);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
