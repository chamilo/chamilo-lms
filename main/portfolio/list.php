<?php
/* For licensing terms, see /license.txt */

if ($currentUserId == $user->getId()) {
    if ($allowEdit) {
        $actions[] = Display::url(
            Display::return_icon('add.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
            $baseUrl.'action=add_item'
        );
        $actions[] = Display::url(
            Display::return_icon('folder.png', get_lang('AddCategory'), [], ICON_SIZE_MEDIUM),
            $baseUrl.'action=add_category'
        );
        $actions[] = Display::url(
            Display::return_icon('shared_setting.png', get_lang('Preview'), [], ICON_SIZE_MEDIUM),
            $baseUrl.'preview=&user='.$user->getId()
        );
    } else {
        $actions[] = Display::url(
            Display::return_icon('shared_setting_na.png', get_lang('Preview'), [], ICON_SIZE_MEDIUM),
            $baseUrl
        );
    }
}

$form = new FormValidator('a');
$form->addUserAvatar('user', get_lang('User'), 'medium');
$form->setDefaults(['user' => $user]);

$criteria = ['user' => $user];

if (!$allowEdit) {
    $criteria['isVisible'] = true;
}

$categories = $em
    ->getRepository('ChamiloCoreBundle:PortfolioCategory')
    ->findBy($criteria);

if ($course) {
    $criteria['course'] = $course;
    $criteria['session'] = $session;
}

$criteria['category'] = null;

$items = $em
    ->getRepository('ChamiloCoreBundle:Portfolio')
    ->findBy($criteria);

$template = new Template(null, false, false, false, false, false, false);
$template->assign('user', $user);
$template->assign('course', $course);
$template->assign('session', $session);
$template->assign('allow_edit', $allowEdit);
$template->assign('portfolio', $categories);
$template->assign('uncategorized_items', $items);
$layout = $template->get_template('portfolio/list.html.twig');
$content = $template->fetch($layout);
