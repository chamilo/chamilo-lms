<?php

/* For licensing terms, see /license.txt */

/**
 * Add a skill Profile
 *
 * @package chamilo.skill
 */

$cidReset = true;
require_once '../inc/global.inc.php';

api_protect_admin_script();
$em = Database::getManager();
$list = $em->getRepository('ChamiloSkillBundle:Profile')->findAll();

$listAction = api_get_self();

$action =  '';
if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit', 'delete', 'move_up', 'move_down'])) {
    $action = $_GET['action'];
}

$id = isset($_GET['id']) ? $_GET['id'] : '';

$item = null;
if (!empty($id)) {
    $item = $em->getRepository('ChamiloSkillBundle:Profile')->find($id);
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
    $form->setDefaults(['name' => $item->getName()]);
}
$formToDisplay = $form->returnForm();

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'skill.php', 'name' => get_lang('ManageSkillsLevels'));
$interbreadcrumb[] = array ('url' =>  api_get_self(), 'name' => get_lang('SkillProfile'));

$tpl = new Template($action);
switch ($action) {
    case 'move_up':
        /** @var \Chamilo\SkillBundle\Entity\Level $item */
        $item = $em->getRepository('ChamiloSkillBundle:Level')->find($_GET['level_id']);

        $position = $item->getPosition();

        if (!empty($position)) {
            $item->setPosition($position-1);
        }
        $em->persist($item);
        $em->flush();
        header('Location: '.$listAction);
        exit;
        break;
    case 'move_down':
        /** @var \Chamilo\SkillBundle\Entity\Level $item */
        $item = $em->getRepository('ChamiloSkillBundle:Level')->find($_GET['level_id']);

        $position = $item->getPosition();

        $item->setPosition($position+1);

        $em->persist($item);
        $em->flush();
        header('Location: '.$listAction);
        exit;
        break;
    case 'add':
        $tpl->assign('form', $formToDisplay);
        if ($form->validate()) {
            $values = $form->exportValues();
            $item = new \Chamilo\SkillBundle\Entity\Profile();
            $item->setName($values['name']);
            $em->persist($item);
            $em->flush();
            header('Location: '.$listAction);
            exit;
        }
        $tpl->assign('actions', Display::url(get_lang('List'), $listAction));
        break;
    case 'edit':
        $tpl->assign('form', $formToDisplay);
        $tpl->assign('actions', Display::url(get_lang('List'), $listAction));

        if ($form->validate()) {
            $values = $form->exportValues();
            $item->setName($values['name']);
            $em->persist($item);
            $em->flush();
            header('Location: '.$listAction);
            exit;
        }

        break;
    case 'delete':
        $tpl->assign('actions', Display::url(get_lang('List'), $listAction));
        $em->remove($item);
        $em->flush();
        header('Location: '.$listAction);
        exit;

        break;
    default:
        $tpl->assign('actions', Display::url(get_lang('Add'), api_get_self().'?action=add'));
}

$tpl->assign('list', $list);
$templateName = $tpl->get_template('admin/skill_profile.tpl');
$contentTemplate = $tpl->fetch($templateName);
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
