<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Skill;

/**
 * This script manages the skills, levels and profiles assignments.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
api_protect_admin_script();
$em = Database::getManager();
$profiles = $em->getRepository('ChamiloSkillBundle:Profile')->findAll();
$list = $em->getRepository('ChamiloCoreBundle:Skill')->findAll();

$listAction = api_get_self();
$toolbarAction = '';

$action = '';
if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit', 'delete'])) {
    $action = $_GET['action'];
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : '';

$item = null;
if (!empty($id)) {
    /** @var Skill $item */
    $item = $em->getRepository('ChamiloCoreBundle:Skill')->find($id);
    if (!$item) {
        api_not_allowed();
    }
}

$form = new FormValidator('Skill', 'GET', api_get_self().'?action='.$action.'&id='.$id);
$form->addSelectFromCollection('profile_id', get_lang('Profile'), $profiles, null, true);
$form->addHidden('action', $action);
$form->addHidden('id', $id);
$form->addButtonSave(get_lang('Update'));

if (!empty($item)) {
    $profile = $item->getProfile();
    if ($profile) {
        $form->setDefaults(
            [
                'profile_id' => $item->getProfile()->getId(),
            ]
        );
    }
    $form->addHeader($item->getName());
}
$formToDisplay = $form->returnForm();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => api_get_self(), 'name' => get_lang('ManageSkillsLevels')];

$tpl = new Template($action);
switch ($action) {
    case 'edit':
        $tpl->assign('form', $formToDisplay);
        $toolbarAction = Display::toolbarAction('toolbar', [Display::url(get_lang('List'), $listAction)]);

        if ($form->validate()) {
            $values = $form->exportValues();
            $profile = $em->getRepository('ChamiloSkillBundle:Profile')->find($values['profile_id']);
            if ($profile) {
                $item->setProfile($profile);
                $em->persist($item);
                $em->flush();
                Display::addFlash(Display::return_message(get_lang('Updated')));
            }
            header('Location: '.$listAction);
            exit;
        }
        break;
    case 'delete':
        $toolbarAction = Display::toolbarAction('toolbar', [Display::url(get_lang('List'), $listAction)]);

        $em->remove($item);
        $em->flush();
        header('Location: '.$listAction);
        exit;

        break;
    default:
        break;
}

$tpl->assign('list', $list);
$view = $tpl->get_template('admin/skill.tpl');
$contentTemplate = $tpl->fetch($view);
$tpl->assign('actions', $toolbarAction);
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
