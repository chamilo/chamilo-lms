<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = Justification::create();
$allowSessionAdmins = $plugin->canSessionAdminsManageUsers();

api_protect_admin_script($allowSessionAdmins);

$tool = 'justification';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$userId = isset($_REQUEST['user_id']) ? (int) $_REQUEST['user_id'] : 0;

switch ($action) {
    case 'delete':
        if (!Security::check_token('get')) {
            api_not_allowed(true);
        }

        Security::clear_token();

        if ($plugin->deleteUserJustification($id)) {
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }

        header('Location: '.api_get_self().'?user_id='.$userId);
        exit;
}

$tpl = new Template($tool);
$fields = [];
$tpl->assign('list', []);
$tpl->assign('user_info', null);

$form = new FormValidator('search', 'get');
$form->addHeader(get_lang('Search'));
$form->addSelectAjax(
    'user_id',
    get_lang('User'),
    [],
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
    ]
);
$form->addButtonSearch(get_lang('Search'));
$tpl->assign('form', $form->returnForm());

if ($form->validate()) {
    $userId = (int) $form->getSubmitValue('user_id');
}

if ($userId) {
    $tpl->assign('user_info', api_get_user_info($userId));
    $list = $plugin->getUserJustificationList($userId);
    if ($list) {
        foreach ($list as &$item) {
            if ($item['date_validity'] < api_get_local_time()) {
                $item['date_validity'] = Display::label($item['date_validity'], 'warning');
            }
            $item['justification'] = $plugin->getJustification($item['justification_document_id']);
            $fileLabel = basename((string) $item['file_path']);
            if ('' === $fileLabel) {
                $fileLabel = $plugin->get_lang('DownloadFile');
            }

            $item['file_path'] = Display::url(
                $fileLabel,
                $plugin->getUserJustificationDownloadUrl((int) $item['id']),
                ['target' => '_blank']
            );
        }
    }
    if (empty($list)) {
        Display::addFlash(Display::return_message($plugin->get_lang('NoJustificationFound')));
    }
    $tpl->assign('list', $list);
}

$tpl->assign('user_id', $userId);
$tpl->assign('token', Security::get_token());
$content = $tpl->fetch('Justification/view/justification_user_list.tpl');

switch ($action) {
    case 'edit':
        $userJustification = $plugin->getUserJustification($id);
        if (empty($userJustification)) {
            api_not_allowed(true);
        }

        $userInfo = api_get_user_info($userJustification['user_id']);
        $form = new FormValidator('edit', 'post', api_get_self().'?a=edit&id='.$id.'&user_id='.$userId);
        $form->addHeader($userInfo['complete_name']);
        $element = $form->addDatePicker('date_validity', $plugin->get_lang('ValidityDate'));
        $element->setValue($userJustification['date_validity']);
        $form->addButtonUpdate(get_lang('Update'));
        $form->setDefaults($userJustification);
        $content = '
<section class="w-full space-y-6">
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                <span class="mdi mdi-calendar-edit text-2xl"></span>
            </div>
            <div>
                <h2 class="text-2xl font-semibold text-gray-90">'.$plugin->get_lang('EditUserJustification').'</h2>
                <p class="text-sm text-gray-50">'.$plugin->get_lang('EditUserJustificationHelp').'</p>
            </div>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        '.$form->returnForm().'
    </div>
</section>';

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $date = Database::escape_string($values['date_validity']);
            $sql = "UPDATE ".Justification::TABLE_DOCUMENT_REL_USER."
                    SET date_validity = '$date'
                    WHERE id = $id";
            Database::query($sql);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header('Location: '.api_get_self().'?user_id='.$userId);
            exit;
        }
        break;
}

$actionLinks = Display::toolbarButton(
    $plugin->get_lang('Back'),
    api_get_path(WEB_PLUGIN_PATH).'Justification/list.php',
    'arrow-left',
    'primary'
);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

$tpl->assign('content', $content);
$tpl->display_one_col_template();
