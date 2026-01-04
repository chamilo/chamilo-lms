<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\SkillLevelProfile;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;

/**
 * Add a skill Level.
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$em = Database::getManager();
$profiles = $em->getRepository(SkillLevelProfile::class)->findAll();
$list = $em->getRepository(Level::class)->findAll();

$listAction = api_get_self();

$action = '';
if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit', 'delete', 'add_level'])) {
    $action = $_GET['action'];
}

$id = isset($_GET['id']) ? $_GET['id'] : '';

$item = null;
if (!empty($id)) {
    /** @var Level $item */
    $item = $em->getRepository(Level::class)->find($id);
    if (!$item) {
        api_not_allowed();
    }
}

$form = new FormValidator('level', 'GET', api_get_self().'?action='.$action.'&id='.$id);
$form->addText('title', get_lang('Name'));
$form->addText('short_title', get_lang('Short name'));
$form->addSelectFromCollection('profile_id', get_lang('Profile'), $profiles);
$form->addHidden('action', $action);
$form->addHidden('id', $id);
$form->addButtonSave(get_lang('Save'));

if (!empty($item)) {
    $form->setDefaults([
        'title' => $item->getTitle(),
        'short_title' => $item->getShortTitle(),
        'profile_id' => $item->getProfile()->getId(),
    ]);
}

$formToDisplay = '';

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'skills/skill.php', 'name' => get_lang('Manage skills levels')];
$interbreadcrumb[] = ['url' => api_get_self(), 'name' => get_lang('Skill level')];

$tpl = new Template('');
// Active tab for the shared header navigation.
$tpl->assign('current_tab', 'levels');

switch ($action) {
    case 'add':
        $formToDisplay = $form->returnForm();
        if ($form->validate()) {
            $values = $form->exportValues();
            if (isset($values['profile_id']) && !empty($values['profile_id'])) {
                $profile = $em->getRepository(SkillLevelProfile::class)->find($values['profile_id']);
                if ($profile) {
                    $item = new Level();
                    $item->setTitle($values['title']);
                    $item->setShortTitle($values['short_title']);
                    $item->setProfile($profile);
                    $em->persist($item);
                    $em->flush();
                    Display::addFlash(Display::return_message(get_lang('Added')));
                } else {
                    Display::addFlash(Display::return_message(get_lang('Added')));
                }
            } else {
                Display::addFlash(Display::return_message(get_lang('You need to create a skill profile')));
            }
            header('Location: '.$listAction);
            exit;
        }
        $toolbarAction = Display::url(
            Display::getMdiIcon(ObjectIcon::LIST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List')),
            $listAction,
            ['title' => get_lang('List')]
        );
        break;
    case 'edit':
        $formToDisplay = $form->returnForm();
        $toolbarAction = Display::url(
            Display::getMdiIcon(ObjectIcon::LIST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List')),
            $listAction,
            ['title' => get_lang('List')]
        );

        if ($form->validate()) {
            $values = $form->exportValues();

            $item->setTitle($values['title']);
            $item->setShortTitle($values['short_title']);
            $profile = $em->getRepository(SkillLevelProfile::class)->find($values['profile_id']);
            if ($profile) {
                $item->setProfile($profile);
            }

            $em->persist($item);
            $em->flush();
            header('Location: '.$listAction);
            exit;
        }
        break;
    case 'delete':
        $toolbarAction = Display::url(
            Display::getMdiIcon(ObjectIcon::LIST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List')),
            $listAction,
            ['title' => get_lang('List')]
        );
        if ($item) {
            $em->remove($item);
            $em->flush();
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }
        header('Location: '.$listAction);
        exit;

    default:
        $toolbarAction = '';

}

$tpl->assign('form', $formToDisplay);
$tpl->assign('list', $list);
$templateName = $tpl->get_template('skills/skill_level.tpl');
$contentTemplate = $tpl->fetch($templateName);
$tpl->assign('actions', Display::toolbarAction('toolbar', [$toolbarAction]));
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
