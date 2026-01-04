<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\SkillLevelProfile;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;


/**
 * Add a skill Profile.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();
$em = Database::getManager();
$list = $em->getRepository(SkillLevelProfile::class)->findAll();

$listAction = api_get_self();

$action = '';
if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit', 'delete', 'move_up', 'move_down'])) {
    $action = $_GET['action'];
}

$id = isset($_GET['id']) ? $_GET['id'] : '';

$item = null;
if (!empty($id)) {
    $item = $em->getRepository(SkillLevelProfile::class)->find($id);
    if (!$item) {
        api_not_allowed();
    }
}

$form = new FormValidator('Profile', 'GET', api_get_self().'?action='.$action.'&id='.$id);
$form->addText('name', get_lang('Name'));
$form->addHidden('action', $action);
$form->addHidden('id', $id);
$form->addButtonSave(get_lang('Save'));

if (!empty($item)) {
    $form->setDefaults(['name' => $item->getTitle()]);
}
$formToDisplay = $form->returnForm();

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'skills/skill.php', 'name' => get_lang('Manage skills levels')];
$interbreadcrumb[] = ['url' => api_get_self(), 'name' => get_lang('Skill profile')];

$toolbar = null;

$tpl = new Template('');
// Active tab for the shared header navigation.
$tpl->assign('current_tab', 'profiles');

switch ($action) {
    case 'move_up':
        /** @var Level $item */
        $item = $em->getRepository(Level::class)->find($_GET['level_id']);
        if ($item) {
            $position = $item->getPosition();
            if (!empty($position)) {
                $item->setPosition($position - 1);
            }
            $em->persist($item);
            $em->flush();
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        }

        header('Location: '.$listAction);
        exit;

    case 'move_down':
        /** @var Level $item */
        $item = $em->getRepository(Level::class)->find($_GET['level_id']);
        if ($item) {
            $position = $item->getPosition();
            $item->setPosition($position + 1);
            $em->persist($item);
            $em->flush();
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        }

        header('Location: '.$listAction);
        exit;

    case 'add':
        $tpl->assign('form', $formToDisplay);
        if ($form->validate()) {
            $values = $form->exportValues();
            $item = new SkillLevelProfile();
            $item->setTitle($values['name']);
            $em->persist($item);
            $em->flush();

            Display::addFlash(Display::return_message(get_lang('Added')));
            header('Location: '.$listAction);
            exit;
        }
        $toolbar = Display::url(
            Display::getMdiIcon(ObjectIcon::LIST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List')),
            $listAction,
            ['title' => get_lang('List')]
        );
        break;

    case 'edit':
        $tpl->assign('form', $formToDisplay);
        $toolbar = Display::url(
            Display::getMdiIcon(ObjectIcon::LIST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List')),
            $listAction,
            ['title' => get_lang('List')]
        );

        if ($form->validate()) {
            $values = $form->exportValues();
            $item->setTitle($values['name']);
            $em->persist($item);
            $em->flush();
            Display::addFlash(Display::return_message(get_lang('Update successful')));
            header('Location: '.$listAction);
            exit;
        }
        break;

    case 'delete':
        $toolbar = Display::url(
            Display::getMdiIcon(ObjectIcon::LIST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List')),
            $listAction,
            ['title' => get_lang('List')]
        );

        try {
            $em->remove($item);
            $em->flush();
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        } catch (Exception $e) {
            Display::addFlash(Display::return_message(get_lang('Delete error'), 'error'));
        }
        header('Location: '.$listAction);
        exit;

    default:
        $toolbar = '';
}

$tpl->assign('list', $list);
$templateName = $tpl->get_template('skills/skill_profile.tpl');
$contentTemplate = $tpl->fetch($templateName);

if ($toolbar) {
    $tpl->assign('actions', Display::toolbarAction('toolbar', [$toolbar]));
}

$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
