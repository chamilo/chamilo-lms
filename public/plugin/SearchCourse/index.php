<?php
/* For licensing terms, see /license.txt */

if (!function_exists('api_get_path')) {
    require_once __DIR__.'/../../main/inc/global.inc.php';
}

require_once __DIR__.'/lib/search_course_plugin.class.php';
require_once __DIR__.'/lib/register_course_widget.class.php';
require_once __DIR__.'/lib/search_course_widget.class.php';

$plugin = SearchCoursePlugin::create();
$widget = SearchCourseWidget::factory();

$isDirectRequest = isset($_SERVER['SCRIPT_FILENAME'])
    && realpath((string) $_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__);

$isRenderingOwnFullPage = defined('SEARCH_COURSE_FULL_PAGE_RENDERING')
    && SEARCH_COURSE_FULL_PAGE_RENDERING;

if (!$plugin->isEnabled()) {
    if ($isDirectRequest && !$isRenderingOwnFullPage) {
        api_not_allowed(true);
    }

    return;
}

if ($isRenderingOwnFullPage) {
    return;
}

if (!$isDirectRequest) {
    echo $widget->renderBlock();

    return;
}

define('SEARCH_COURSE_FULL_PAGE_RENDERING', true);

$content = '
    <style>
        body.search-course-full-page [data-search-course-region-block="1"] {
            display: none !important;
        }
    </style>
    <script>
        document.body.classList.add("search-course-full-page");
    </script>';
$content .= $widget->run();

$template = new Template($plugin->get_title());
$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
