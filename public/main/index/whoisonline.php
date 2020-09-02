<?php
/* For licensing terms, see /license.txt */

/**
 * Who is online list.
 */
if (!isset($_GET['cidReq'])) {
    $cidReset = true;
}

require_once '../../main/inc/global.inc.php';

if (isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
    api_protect_course_script(true);
}

$this_section = SECTION_SOCIAL;
$social_right_content = '';
$whoisonline_list = '';
$social_search = '';
$userId = api_get_user_id();
$access = accessToWhoIsOnline();

if (!$access) {
    api_not_allowed(true);
}

if (isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
    $user_list = who_is_online_in_this_course(
        0,
        MAX_ONLINE_USERS,
        api_get_user_id(),
        api_get_setting('time_limit_whosonline'),
        $_GET['cidReq']
    );
} else {
    $user_list = who_is_online(0, MAX_ONLINE_USERS);
}

if ($user_list) {
    if (!isset($_GET['id'])) {
        if ('true' == api_get_setting('allow_social_tool')) {
            if (!api_is_anonymous()) {
                $query = isset($_GET['q']) ? $_GET['q'] : null;
                $social_search = UserManager::getSearchForm($query);
            }
        }
        $social_right_content .= SocialManager::display_user_list($user_list);
    }
}

$whoisonline_list .= SocialManager::display_user_list($user_list);

if (isset($_GET['id'])) {
    if ('true' == api_get_setting('allow_social_tool') && !api_is_anonymous()) {
        header("Location: ".api_get_path(WEB_CODE_PATH)."social/profile.php?u=".intval($_GET['id']));
        exit;
    }
}

$tpl = new Template(get_lang('Online users list'));

if ('true' === api_get_setting('allow_social_tool') && !api_is_anonymous()) {
    $tpl->assign('whoisonline', $whoisonline_list);
    $tpl->assign('social_search', $social_search);
} else {
    $tpl->assign('whoisonline', $social_right_content);
}

$social_layout = $tpl->get_template('social/whoisonline.tpl');
$tpl->display($social_layout);
