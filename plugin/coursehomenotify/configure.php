<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

use Chamilo\PluginBundle\Entity\CourseHomeNotify\Notification;

$plugin = CourseHomeNotifyPlugin::create();
$courseId = api_get_course_int_id();

if (
    empty($courseId) ||
    'true' !== $plugin->get(CourseHomeNotifyPlugin::SETTING_ENABLED)
) {
    api_not_allowed(true);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

$course = api_get_course_entity($courseId);

$em = Database::getManager();
/** @var Notification $notification */
$notification = $em
    ->getRepository('ChamiloPluginBundle:CourseHomeNotify\Notification')
    ->findOneBy(['course' => $course]);

$actionLinks = '';

if ($notification) {
    $actionLinks = Display::url(
        Display::return_icon('delete.png', $plugin->get_lang('DeleteNotification'), [], ICON_SIZE_MEDIUM),
        api_get_self().'?'.api_get_cidreq().'&action=delete'
    );

    if ('delete' === $action) {
        $em->remove($notification);
        $em->flush();

        Display::addFlash(
            Display::return_message($plugin->get_lang('NotificationDeleted'), 'success')
        );

        header('Location: '.api_get_course_url());
        exit;
    }
} else {
    $notification = new Notification();
}

$form = new FormValidator('frm_course_home_notify');
$form->addHeader($plugin->get_lang('AddNotification'));
$form->applyFilter('title', 'trim');
$form->addHtmlEditor('content', get_lang('Content'), true, false, ['ToolbarSet' => 'Minimal']);
$form->addUrl(
    'expiration_link',
    [$plugin->get_lang('ExpirationLink'), $plugin->get_lang('ExpirationLinkHelp')],
    false,
    ['placeholder' => 'https://']
);
$form->addButtonSave(get_lang('Save'));

if ($form->validate()) {
    $values = $form->exportValues();

    $notification
        ->setContent($values['content'])
        ->setExpirationLink($values['expiration_link'])
        ->setCourse($course)
        ->setHash(md5(uniqid()));

    $em->persist($notification);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('NotificationAdded'), 'success')
    );

    header('Location: '.api_get_course_url());
    exit;
}

if ($notification) {
    $form->setDefaults(
        [
            'content' => $notification->getContent(),
            'expiration_link' => $notification->getExpirationLink(),
        ]
    );
}

$template = new Template($plugin->get_title());
$template->assign('header', $plugin->get_title());

if ($actionLinks) {
    $template->assign('actions', Display::toolbarAction('course-home-notify-actions', ['', $actionLinks]));
}

$template->assign('content', $form->returnForm());
$template->display_one_col_template();
