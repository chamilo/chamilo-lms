<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\CourseHomeNotify\Entity\Notification;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users(true);
api_protect_course_script(true);

$plugin = CourseHomeNotifyPlugin::create();
$courseId = api_get_course_int_id();

if (
    empty($courseId) ||
    !$plugin->isEnabled() ||
    !api_is_allowed_to_edit()
) {
    api_not_allowed(true);
}

$course = api_get_course_entity($courseId);

if (!$course) {
    api_not_allowed(true);
}

$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';

$em = Database::getManager();
/** @var Notification|null $notification */
$notification = $em
    ->getRepository(Notification::class)
    ->findOneBy(['course' => $course]);

if ('delete' === $action) {
    if ($notification) {
        $em->remove($notification);
        $em->flush();

        Display::addFlash(
            Display::return_message($plugin->get_lang('NotificationDeleted'), 'success')
        );
    }

    header('Location: '.api_get_self().'?'.api_get_cidreq());
    exit;
}

$existingNotification = $notification instanceof Notification;

if (!$notification) {
    $notification = new Notification();
}

$form = new FormValidator(
    'frm_course_home_notify',
    'post',
    api_get_self().'?'.api_get_cidreq()
);

$form->addHtml(
    '<div class="mb-6 rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="mb-2 text-caption font-semibold uppercase tracking-wide text-primary">'
                    .Security::remove_XSS($plugin->get_title()).
                '</p>
                <h2 class="m-0 text-h3 font-semibold text-gray-90">'
                    .$plugin->get_lang('CourseNotice').
                '</h2>
                <p class="mt-2 max-w-3xl text-body-2 text-gray-50">'
                    .$plugin->get_comment().
                '</p>
            </div>
            <div class="flex flex-wrap gap-2">'
);

$form->addHtml(
    Display::toolbarButton(
        get_lang('Back'),
        api_get_path(WEB_CODE_PATH).'course_info/infocours.php?'.api_get_cidreq(),
        'arrow-left',
        'plain'
    )
);

if ($existingNotification) {
    $form->addHtml(
        Display::toolbarButton(
            $plugin->get_lang('DeleteNotification'),
            api_get_self().'?'.api_get_cidreq().'&action=delete',
            'delete',
            'danger',
            [
                'onclick' => "return confirm('".addslashes(Security::remove_XSS(get_lang('Please confirm your choice')))."');",
            ]
        )
    );
}

$form->addHtml(
    '       </div>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <h3 class="m-0 text-body-1 font-semibold text-gray-90">'.$plugin->get_lang('SetNotification').'</h3>
            <p class="mt-2 text-body-2 text-gray-50">'.$plugin->get_lang('ExpirationLinkHelp').'</p>
        </div>'
);

$form->addHtmlEditor(
    'content',
    get_lang('Content'),
    true,
    false,
    [
        'ToolbarSet' => 'Minimal',
    ]
);

$form->addUrl(
    'expiration_link',
    [
        $plugin->get_lang('ExpirationLink'),
        $plugin->get_lang('ExpirationLinkHelp'),
    ],
    false,
    [
        'placeholder' => 'https://',
    ]
);

$form->addHtml(
    '<div class="mt-6 flex justify-end">'
);
$form->addButtonSave(get_lang('Save'));
$form->addHtml(
    '</div></div>'
);

if ($form->validate()) {
    $values = $form->exportValues();
    $content = isset($values['content']) ? (string) $values['content'] : '';
    $expirationLink = isset($values['expiration_link']) ? trim((string) $values['expiration_link']) : '';

    $notification
        ->setContent($content)
        ->setExpirationLink($expirationLink)
        ->setCourse($course);

    if ('' === $notification->getHash()) {
        $notification->setHash(md5(uniqid('', true)));
    }

    $em->persist($notification);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('NotificationAdded'), 'success')
    );

    header('Location: '.api_get_self().'?'.api_get_cidreq());
    exit;
}

$form->setDefaults(
    [
        'content' => $notification->getContent(),
        'expiration_link' => $notification->getExpirationLink(),
    ]
);

$template = new Template($plugin->get_title());
$template->assign('header', $plugin->get_title());
$template->assign('content', $form->returnForm());
$template->display_one_col_template();
