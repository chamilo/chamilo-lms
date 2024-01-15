<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\ObjectIcon;
use Chamilo\CoreBundle\Component\Utils\ToolIcon;

if ($currentUserId == $user->getId()) {
    if ($allowEdit) {
        $actions[] = Display::url(
            Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add')),
            $baseUrl.'action=add_item'
        );
        $actions[] = Display::url(
            Display::getMdiIcon(ObjectIcon::FOLDER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add category')),
            $baseUrl.'action=add_category'
        );
        $actions[] = Display::url(
            Display::getMdiIcon(ToolIcon::SETTINGS, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Preview')),
            $baseUrl.'preview=&user='.$user->getId()
        );
    } else {
        $actions[] = Display::url(
            Display::getMdiIcon(ToolIcon::SETTINGS, 'ch-tool-icon-disabled', null, ICON_SIZE_MEDIUM, get_lang('Preview')),
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
