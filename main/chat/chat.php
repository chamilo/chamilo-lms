<?php
/* For licensing terms, see /license.txt */

define('CHAMILO_LOAD_WYSIWYG', false);

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

Event::event_access_tool(TOOL_CHAT);

// View
$externalCSS = [
    'jquery-emojiarea/jquery.emojiarea.css',
    'jquery-textcomplete/jquery.textcomplete.css',
    'emojione/css/emojione.min.css',
    'emojione/css/autocomplete.css',
    'highlight/styles/github.css'
];

foreach ($externalCSS as $css) {
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_JS_PATH).$css);
}

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_CSS_PATH).'chat.css');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_CSS_PATH).'markdown.css');

$externalJS = [
    'highlight/highlight.pack.js',
    'jquery-textcomplete/jquery.textcomplete.js',
    'emojione/js/emojione.min.js',
    'jquery-emojiarea/jquery.emojiarea.js'
];

foreach ($externalJS as $js) {
    $htmlHeadXtra[] = api_get_js($js);
}

$iconList = [];

foreach (Emojione\Emojione::$shortcode_replace as $key => $icon) {
    if (!in_array($key, CourseChatUtils::getEmojisToInclude())) {
        continue;
    }

    $iconList[$key] = strtoupper($icon).'.png';
}

$view = new Template(get_lang('Chat'), false, false, false, true, false);
$view->assign('icons', $iconList);
$view->assign('emoji_strategy', CourseChatUtils::getEmojiStrategry());
$view->assign('emoji_smile', \Emojione\Emojione::toImage(':smile:'));

$template = $view->get_template('chat/chat.tpl');
$content = $view->fetch($template);

$view->assign('content', $content);
$view->display_no_layout_template();
