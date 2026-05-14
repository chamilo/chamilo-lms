<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$mailTemplate = new MailTemplateManager();
$action = $_REQUEST['action'] ?? 'list';
$allowedActions = ['add', 'edit', 'delete', 'set_default', 'list'];

if (!in_array($action, $allowedActions, true)) {
    $action = 'list';
}

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$content = '';

$redirectToList = static function (): never {
    header('Location: '.api_get_self());
    exit;
};

$backButton = static function (): string {
    return '<a
        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-25 bg-white text-primary shadow-sm transition hover:bg-gray-15"
        href="'.api_get_self().'"
        title="'.api_htmlentities(get_lang('Back')).'"
    >'.
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')).
    '</a>';
};

$renderFormPage = static function (string $formHtml) use ($backButton): string {
    return '
        <section class="w-full px-4 py-6">
            <div class="mb-4 flex items-center gap-2">
                '.$backButton().'
            </div>
            <div class="mb-5">
                <h1 class="text-2xl font-semibold text-gray-90">'.get_lang('Mail templates').'</h1>
            </div>
            <div class="w-full rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
                '.$formHtml.'
            </div>
        </section>
    ';
};

switch ($action) {
    case 'add':
        $url = api_get_self().'?action=add';
        $form = $mailTemplate->returnForm($url, 'add');
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => Security::get_existing_token()]);

        if ($form->validate()) {
            if (!Security::check_token('post')) {
                Display::addFlash(Display::return_message(get_lang('Invalid token'), 'error'));
                $redirectToList();
            }

            $values = $form->exportValues();
            $values['template'] = $values['email_template'] ?? '';
            $values['author_id'] = api_get_user_id();
            $values['url_id'] = api_get_current_access_url_id();
            $values['default_template'] = isset($values['default_template']) ? (int) $values['default_template'] : 0;
            $values['system'] = 0;

            $res = $mailTemplate->save($values);
            if ($res) {
                Display::addFlash(Display::return_message(get_lang('Item added'), 'confirm'));
                Security::clear_token();
                $redirectToList();
            }

            Display::addFlash(Display::return_message(get_lang('Unable to save the item'), 'error'));
            $redirectToList();
        }

        $content .= $renderFormPage($form->returnForm());

        break;
    case 'edit':
        if (empty($id)) {
            Display::addFlash(Display::return_message(get_lang('Invalid id'), 'error'));
            $redirectToList();
        }

        $url = api_get_self().'?action=edit&id='.$id;
        $form = $mailTemplate->returnForm($url, 'edit');
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => Security::get_existing_token()]);

        if ($form->validate()) {
            if (!Security::check_token('post')) {
                Display::addFlash(Display::return_message(get_lang('Invalid token'), 'error'));
                $redirectToList();
            }

            $values = $form->exportValues();
            $values['id'] = $id;
            $values['template'] = $values['email_template'] ?? '';

            $res = $mailTemplate->update($values);
            if ($res) {
                Display::addFlash(Display::return_message(get_lang('Item updated').': '.$values['title'], 'confirm'));
                Security::clear_token();
                $redirectToList();
            }

            Display::addFlash(Display::return_message(get_lang('Unable to save the item'), 'error'));
            $redirectToList();
        }

        $content .= $renderFormPage($form->returnForm());

        break;
    case 'delete':
        if (!Security::check_token('get')) {
            Display::addFlash(Display::return_message(get_lang('Invalid token'), 'error'));
            $redirectToList();
        }

        if (!empty($id)) {
            $mailTemplate->delete($id);
            Display::addFlash(Display::return_message(get_lang('Deleted'), 'confirm'));
            Security::clear_token();
        }

        $redirectToList();

        break;
    case 'set_default':
        if (!Security::check_token('get')) {
            Display::addFlash(Display::return_message(get_lang('Invalid token'), 'error'));
            $redirectToList();
        }

        if (!empty($id) && $mailTemplate->setDefault($id)) {
            Display::addFlash(Display::return_message(get_lang('Updated'), 'confirm'));
            Security::clear_token();
            $redirectToList();
        }

        Display::addFlash(Display::return_message(get_lang('Unable to save the item'), 'error'));
        $redirectToList();

        break;
    case 'list':
    default:
        $content = $mailTemplate->display();

        break;
}

$template = new Template();
$template->assign('content', $content);
$template->display_one_col_template();
