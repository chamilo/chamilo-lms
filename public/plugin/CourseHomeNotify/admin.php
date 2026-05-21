<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = CourseHomeNotifyPlugin::create();

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('PlatformAdmin'),
];

$title = $plugin->get_title();

$content = Display::page_subheader($title);
$content .= Display::return_message(
    $plugin->get_comment(),
    'info',
    false
);

$content .= '<div class="card">';
$content .= '<div class="card-body">';
$content .= '<p>This plugin is configured per course, not from the global plugin administration page.</p>';
$content .= '<p>To configure a course notification, open the target course as a teacher or administrator, then go to the course settings page and use the "'
    .$plugin->get_lang('SetNotification')
    .'" option.</p>';
$content .= '<p>The notification will be displayed on the Vue course homepage when the plugin is active and the course has a configured notification.</p>';
$content .= '</div>';
$content .= '</div>';

$template = new Template($title);
$template->assign('header', $title);
$template->assign('content', $content);
$template->display_one_col_template();
