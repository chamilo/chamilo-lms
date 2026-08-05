<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/config.php';

api_protect_course_script(true);
api_block_anonymous_users();

$legal = CourseLegalPlugin::create();

if (!$legal->isEnabled()) {
    api_not_allowed(true);
}

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$url = api_get_self().'?'.api_get_cidreq();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$courseEntity = api_get_course_entity();

$form = new FormValidator('plugin', 'post', $url);
$form->addElement('hidden', 'session_id', $sessionId);
$form->addElement('hidden', 'c_id', $courseId);
$form->addElement(
    'checkbox',
    'activate_legal',
    null,
    get_lang('Enable legal terms')
);
$form->addHtml('<p class="mb-4 text-body-2 text-gray-70">'.get_lang('Show a legal notice when entering the course').'</p>');

$form->addHtml('
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <span class="mdi mdi-file-document-check-outline ch-tool-icon text-primary"></span>
                    <h2 class="m-0 text-h3 font-semibold text-gray-90">'.$legal->get_lang('CourseLegal').'</h2>
                </div>
                <p class="m-0 text-body-2 text-gray-70">
                    Configure the legal agreement that learners must accept before accessing this course.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                '.Display::toolbarButton(
                    get_lang('User list'),
                    api_get_path(WEB_PLUGIN_PATH).'CourseLegal/user_list.php?'.api_get_cidreq(),
                    'account-check-outline',
                    'secondary'
                ).'
                '.Display::toolbarButton(
                    get_lang('Back'),
                    api_get_path(WEB_CODE_PATH).'course_info/infocours.php?'.api_get_cidreq(),
                    'arrow-left',
                    'plain'
                ).'
            </div>
        </div>
        <div class="space-y-4">
');

$form->addHtmlEditor(
    'content',
    get_lang('Text'),
    true,
    false,
    ['ToolbarSet' => 'TermsAndConditions']
);

$form->addElement('file', 'uploaded_file', get_lang('File'));
$file = $legal->getCurrentFile($courseId, $sessionId);

if (!empty($file)) {
    $form->addElement('label', get_lang('Current file'), $file);
}

$form->addElement('checkbox', 'delete_file', null, $legal->get_lang('DeleteFile'));
$form->addElement('checkbox', 'remove_previous_agreements', null, $legal->get_lang('RemoveAllUserAgreements'));
$form->addElement('radio', 'warn_users_by_email', null, $legal->get_lang('NoSendWarning'), 1);
$form->addElement('radio', 'warn_users_by_email', $legal->get_lang('WarnAllUsersByEmail'), $legal->get_lang('SendOnlyWarning'), 2);
$form->addElement('radio', 'warn_users_by_email', null, $legal->get_lang('SendAgreementFile'), 3);

$form->addHtml('
        </div>
    </div>
');

$form->addButtonSave(get_lang('Save'));

$defaults = $legal->getData($courseId, $sessionId);
$defaults['warn_users_by_email'] = 1;
$defaults['activate_legal'] = $courseEntity && $courseEntity->getActivateLegal() ? 1 : 0;
$form->setDefaults($defaults);

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $file = $_FILES['uploaded_file'] ?? [];
    $deleteFile = $values['delete_file'] ?? false;

    if ($courseEntity) {
        $courseEntity->setActivateLegal(!empty($values['activate_legal']) ? 1 : 0);
        $entityManager = Database::getManager();
        $entityManager->persist($courseEntity);
        $entityManager->flush();
    }

    $legal->save($values, $file, $deleteFile);
    header('Location: '.$url);
    exit;
}

Display::display_header($legal->get_lang('CourseLegal'));
$form->display();
Display::display_footer();
