<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = LearningCalendarPlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$calendarId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$item = $plugin->getCalendar($calendarId);
$plugin->protectCalendar($item);

$tokenSessionKey = 'learning_calendar_users_token_'.$calendarId;
if (empty($_SESSION[$tokenSessionKey])) {
    $_SESSION[$tokenSessionKey] = bin2hex(random_bytes(16));
}
$secToken = $_SESSION[$tokenSessionKey];

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $postedToken = isset($_POST['sec_token']) ? (string) $_POST['sec_token'] : '';

    if (!hash_equals($secToken, $postedToken)) {
        api_not_allowed(true);
    }

    $formAction = isset($_POST['form_action']) ? (string) $_POST['form_action'] : '';

    switch ($formAction) {
        case 'assign_user':
            $identifier = isset($_POST['user_identifier']) ? trim((string) $_POST['user_identifier']) : '';
            $user = $plugin->findUserForCalendarAssignment($identifier);

            if (empty($user)) {
                Display::addFlash(Display::return_message($plugin->get_lang('UserNotFound'), 'warning'));
                header('Location: '.api_get_self().'?id='.$calendarId);
                exit;
            }

            $status = $plugin->assignUserToCalendar($calendarId, (int) $user['id']);

            if ('added' === $status) {
                Display::addFlash(Display::return_message($plugin->get_lang('UserAssignedToCalendar')));
            } elseif ('moved' === $status) {
                Display::addFlash(Display::return_message($plugin->get_lang('UserMovedToCalendar')));
            } elseif ('already' === $status) {
                Display::addFlash(Display::return_message($plugin->get_lang('UserAlreadyAssignedToThisCalendar'), 'warning'));
            } else {
                Display::addFlash(Display::return_message($plugin->get_lang('CalendarUserAssignmentFailed'), 'error'));
            }

            header('Location: '.api_get_self().'?id='.$calendarId);
            exit;

        case 'remove_user':
            $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

            if (!empty($userId)) {
                $plugin->deleteAllCalendarFromUser($calendarId, $userId);
                Display::addFlash(Display::return_message($plugin->get_lang('UserRemovedFromCalendar')));
            }

            header('Location: '.api_get_self().'?id='.$calendarId);
            exit;
    }
}

$template = new Template($plugin->get_lang('LearningCalendar'));
$users = $plugin->getUsersPerCalendar($calendarId);

$toolbarActions = [];
$toolbarActions[] = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, $plugin->get_lang('BackToMySpace')),
    api_get_path(WEB_CODE_PATH).'my_space/index.php'
);
$toolbarActions[] = Display::url(
    Display::getMdiIcon('format-list-bulleted', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List')),
    api_get_path(WEB_PLUGIN_PATH).'LearningCalendar/start.php'
);

$postUrl = api_get_self().'?id='.$calendarId;
$escapedPostUrl = Security::remove_XSS($postUrl);
$escapedToken = htmlspecialchars((string) $secToken, ENT_QUOTES, 'UTF-8');
$assignLabel = Security::remove_XSS($plugin->get_lang('AssignUser'));
$userIdentifierLabel = Security::remove_XSS($plugin->get_lang('UserIdentifier'));
$userIdentifierHelp = Security::remove_XSS($plugin->get_lang('UserIdentifierHelp'));

$content = '<div class="space-y-6">';
$content .= '<section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">';
$content .= '<div class="text-sm font-semibold uppercase tracking-wide text-primary">'.
    Security::remove_XSS($plugin->get_lang('LearningCalendar')).'</div>';
$content .= '<h1 class="mt-1 text-2xl font-semibold text-gray-90">'.Security::remove_XSS($item['title']).'</h1>';
$content .= '<p class="mt-2 text-sm text-gray-50">'.Security::remove_XSS($plugin->get_lang('CalendarUsersDescription')).'</p>';
$content .= '</section>';

$content .= '<section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">';
$content .= '<div class="mb-4 flex items-center gap-2">';
$content .= '<span class="mdi mdi-account-multiple-plus ch-tool-icon" aria-hidden="true"></span>';
$content .= '<h2 class="text-lg font-semibold text-gray-90">'.Security::remove_XSS($plugin->get_lang('AssignUsers')).'</h2>';
$content .= '</div>';
$content .= '<p class="mb-4 text-sm text-gray-50">'.Security::remove_XSS($plugin->get_lang('AssignUserToCalendarHelp')).'</p>';
$content .= '<form method="post" action="'.$escapedPostUrl.'" class="flex flex-col gap-4 md:flex-row md:items-end">';
$content .= '<input type="hidden" name="sec_token" value="'.$escapedToken.'">';
$content .= '<input type="hidden" name="form_action" value="assign_user">';
$content .= '<div class="flex-1">';
$content .= '<label class="mb-1 block text-sm font-semibold text-gray-70" for="user_identifier">'.$userIdentifierLabel.'</label>';
$content .= '<input id="user_identifier" name="user_identifier" type="text" required class="w-full rounded border border-gray-25 px-3 py-2 text-sm" placeholder="'.$userIdentifierLabel.'">';
$content .= '<p class="mt-1 text-xs text-gray-50">'.$userIdentifierHelp.'</p>';
$content .= '</div>';
$content .= '<button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40">';
$content .= '<span class="mdi mdi-account-plus" aria-hidden="true"></span>';
$content .= '<span>'.$assignLabel.'</span>';
$content .= '</button>';
$content .= '</form>';
$content .= '</section>';

$content .= '<section class="rounded-2xl border border-gray-25 bg-white shadow-sm">';
$content .= '<div class="border-b border-gray-25 px-6 py-4">';
$content .= '<h2 class="text-lg font-semibold text-gray-90">'.get_lang('Users').'</h2>';
$content .= '</div>';

if (empty($users)) {
    $content .= '<div class="p-8 text-center text-gray-50">'.get_lang('No data available').'</div>';
} else {
    $content .= '<div class="overflow-x-auto">';
    $content .= '<table class="w-full text-left text-sm">';
    $content .= '<thead class="bg-gray-15 text-xs uppercase tracking-wide text-gray-50">';
    $content .= '<tr>';
    $content .= '<th class="px-6 py-3">'.get_lang('First name').'</th>';
    $content .= '<th class="px-6 py-3">'.get_lang('Last name').'</th>';
    $content .= '<th class="px-6 py-3">'.get_lang('Username').'</th>';
    $content .= '<th class="px-6 py-3 text-right">'.get_lang('Actions').'</th>';
    $content .= '</tr>';
    $content .= '</thead><tbody class="divide-y divide-gray-25">';
    foreach ($users as $user) {
        $userId = (int) ($user['user_id'] ?? 0);
        $removeLabel = Security::remove_XSS($plugin->get_lang('RemoveFromCalendar'));
        $removeConfirm = htmlspecialchars(
            json_encode((string) $plugin->get_lang('RemoveUserConfirm')),
            ENT_QUOTES,
            'UTF-8'
        );

        $content .= '<tr>';
        $content .= '<td class="px-6 py-3 font-medium text-gray-90">'.Security::remove_XSS($user['firstname'] ?? '').'</td>';
        $content .= '<td class="px-6 py-3 text-gray-70">'.Security::remove_XSS($user['lastname'] ?? '').'</td>';
        $content .= '<td class="px-6 py-3 text-gray-50">'.Security::remove_XSS($user['username'] ?? '').'</td>';
        $content .= '<td class="px-6 py-3 text-right">';
        $content .= '<form method="post" action="'.$escapedPostUrl.'" class="inline-block" onsubmit="return confirm('.$removeConfirm.');">';
        $content .= '<input type="hidden" name="sec_token" value="'.$escapedToken.'">';
        $content .= '<input type="hidden" name="form_action" value="remove_user">';
        $content .= '<input type="hidden" name="user_id" value="'.$userId.'">';
        $content .= '<button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-danger/30 bg-white text-danger shadow-sm transition hover:bg-danger/10 focus:outline-none focus:ring-2 focus:ring-danger/40" title="'.$removeLabel.'" aria-label="'.$removeLabel.'">';
        $content .= '<span class="mdi mdi-delete ch-tool-icon text-danger" aria-hidden="true"></span>';
        $content .= '<span class="sr-only">'.$removeLabel.'</span>';
        $content .= '</button>';
        $content .= '</form>';
        $content .= '</td>';
        $content .= '</tr>';
    }
    $content .= '</tbody></table></div>';
}
$content .= '</section>';
$content .= '</div>';

$template->assign('actions', Display::toolbarAction('toolbar-calendar-users', $toolbarActions));
$template->assign('content', $content);
$template->display_one_col_template();
