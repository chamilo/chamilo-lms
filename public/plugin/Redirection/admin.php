<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

api_protect_admin_script();

$plugin = RedirectionPlugin::create();
$url = api_get_path(WEB_PLUGIN_PATH).'Redirection/admin.php';
$backUrl = api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins&plugin_tab=all';

if ('delete' === ($_GET['action'] ?? '') && isset($_GET['id'])) {
    RedirectionPlugin::delete((int) $_GET['id']);
    Display::addFlash(Display::return_message(get_lang('Deleted')));
    header('Location: '.$url);
    exit;
}

$form = new FormValidator('add', 'post', api_get_self());
$form->addSelectAjax(
    'user_id',
    get_lang('User'),
    [],
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
        'id' => 'user_id',
    ]
);
$form->addText('url', 'URL');
$form->addButtonSave(get_lang('Add'));

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $result = RedirectionPlugin::insert(
        (int) ($values['user_id'] ?? 0),
        (string) ($values['url'] ?? '')
    );

    if ($result) {
        Display::addFlash(Display::return_message(get_lang('Added')));
    } else {
        Display::addFlash(Display::return_message(get_lang('Error'), 'warning'));
    }

    header('Location: '.$url);
    exit;
}

$list = RedirectionPlugin::getAll();

$content = '<section class="space-y-6">';

$content .= '<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">';
$content .= '<div>';
$content .= '<p class="text-xs font-semibold uppercase tracking-wide text-primary">'.Security::remove_XSS(get_lang('Plugin')).'</p>';
$content .= '<h1 class="mt-1 text-2xl font-bold text-gray-90">'.Security::remove_XSS(get_lang('Redirection')).'</h1>';
$content .= '<p class="mt-2 text-sm text-gray-50">'.
    Security::remove_XSS(get_lang('Configure a URL where selected users will be redirected after login.')).
    '</p>';
$content .= '</div>';
$content .= '<a class="inline-flex items-center justify-center rounded-lg border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-gray-90 shadow-sm hover:bg-gray-15" href="'.Security::remove_XSS($backUrl).'">';
$content .= '<span class="mdi mdi-arrow-left mr-2" aria-hidden="true"></span>'.Security::remove_XSS(get_lang('Back to plugins'));
$content .= '</a>';
$content .= '</div>';

$content .= '<div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">';
$content .= '<div class="mb-5 border-b border-gray-20 pb-4">';
$content .= '<h2 class="text-lg font-semibold text-gray-90">'.Security::remove_XSS(get_lang('Add redirection')).'</h2>';
$content .= '<p class="mt-1 text-sm text-gray-50">'.Security::remove_XSS(get_lang('Choose a user and define the destination URL.')).'</p>';
$content .= '</div>';
$content .= $form->returnForm();
$content .= '<div class="mt-3 rounded-lg border border-info bg-support-1 px-4 py-3 text-sm text-support-4">'.
    Security::remove_XSS(get_lang('Accepted URL formats: internal paths starting with /, or full http:// and https:// URLs.')).
    '</div>';
$content .= '</div>';

$content .= '<div class="rounded-2xl border border-gray-25 bg-white shadow-sm">';
$content .= '<div class="flex flex-col gap-2 border-b border-gray-25 px-6 py-4 md:flex-row md:items-center md:justify-between">';
$content .= '<div>';
$content .= '<h2 class="text-lg font-semibold text-gray-90">'.Security::remove_XSS(get_lang('Configured redirections')).'</h2>';
$content .= '<p class="mt-1 text-sm text-gray-50">'.Security::remove_XSS(get_lang('Users listed here will be redirected after a successful login.')).'</p>';
$content .= '</div>';
$content .= '<span class="inline-flex w-fit items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">'.
    count($list).' '.Security::remove_XSS(get_lang('Redirections')).
    '</span>';
$content .= '</div>';

if (empty($list)) {
    $content .= '<div class="px-6 py-10 text-center">';
    $content .= '<div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-support-2 text-primary">';
    $content .= '<span class="mdi mdi-arrow-decision-outline text-2xl" aria-hidden="true"></span>';
    $content .= '</div>';
    $content .= '<p class="text-sm font-semibold text-gray-90">'.Security::remove_XSS(get_lang('No redirection configured')).'</p>';
    $content .= '<p class="mt-1 text-sm text-gray-50">'.Security::remove_XSS(get_lang('Add a user redirection using the form above.')).'</p>';
    $content .= '</div>';
} else {
    $content .= '<div class="overflow-x-auto">';
    $content .= '<table class="min-w-full divide-y divide-gray-25 text-sm">';
    $content .= '<thead class="bg-gray-15">';
    $content .= '<tr>';
    $content .= '<th class="px-6 py-3 text-left font-semibold text-gray-90">'.Security::remove_XSS(get_lang('User')).'</th>';
    $content .= '<th class="px-6 py-3 text-left font-semibold text-gray-90">URL</th>';
    $content .= '<th class="px-6 py-3 text-right font-semibold text-gray-90">'.Security::remove_XSS(get_lang('Actions')).'</th>';
    $content .= '</tr>';
    $content .= '</thead>';
    $content .= '<tbody class="divide-y divide-gray-20 bg-white">';

    foreach ($list as $item) {
        $userInfo = api_get_user_info((int) $item['user_id']);
        $userName = get_lang('Unknown');

        if (!empty($userInfo)) {
            $userName = $userInfo['complete_name_with_username'].' - '.$item['user_id'];
        }

        $deleteUrl = $url.'?action=delete&id='.(int) $item['id'];
        $itemUrl = (string) $item['url'];

        $content .= '<tr class="hover:bg-gray-15">';
        $content .= '<td class="px-6 py-4 align-top">';
        $content .= '<div class="font-semibold text-gray-90">'.Security::remove_XSS($userName).'</div>';
        $content .= '</td>';
        $content .= '<td class="px-6 py-4 align-top">';
        $content .= '<a class="break-all text-primary hover:underline" href="'.Security::remove_XSS($itemUrl).'" target="_blank" rel="noopener noreferrer">'.
            Security::remove_XSS($itemUrl).
            '</a>';
        $content .= '</td>';
        $content .= '<td class="px-6 py-4 text-right align-top">';
        $content .= '<a class="inline-flex items-center justify-center rounded-lg bg-danger px-3 py-2 text-sm font-semibold text-danger-button-text shadow-sm hover:opacity-90" href="'.Security::remove_XSS($deleteUrl).'" onclick="return confirm(\''.Security::remove_XSS(get_lang('Are you sure?')).'\');">';
        $content .= '<span class="mdi mdi-delete mr-2" aria-hidden="true"></span>'.Security::remove_XSS(get_lang('Delete'));
        $content .= '</a>';
        $content .= '</td>';
        $content .= '</tr>';
    }

    $content .= '</tbody>';
    $content .= '</table>';
    $content .= '</div>';
}

$content .= '</div>';
$content .= '</section>';

$tpl = new Template(
    get_lang('Redirection'),
    true,
    true,
    false,
    false,
    false
);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
