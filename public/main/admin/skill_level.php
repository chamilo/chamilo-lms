<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\Profile;

/**
 * Add a skill Level.
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$em = Database::getManager();
$profiles = $em->getRepository(Profile::class)->findAll();
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
$form->addText('name', get_lang('Name'));
$form->addText('short_name', get_lang('Short name'));
$form->addSelectFromCollection('profile_id', get_lang('Profile'), $profiles);
$form->addHidden('action', $action);
$form->addHidden('id', $id);
$form->addButtonSave(get_lang('Save'));

if (!empty($item)) {
    $form->setDefaults([
        'name' => $item->getName(),
        'short_name' => $item->getShortName(),
        'profile_id' => $item->getProfile()->getId(),
    ]);
}

$formToDisplay = '';

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/skill.php', 'name' => get_lang('Manage skills levels')];
$interbreadcrumb[] = ['url' => api_get_self(), 'name' => get_lang('Skill level')];

switch ($action) {
    case 'add':
        $formToDisplay = $form->returnForm();
        if ($form->validate()) {
            $values = $form->exportValues();
            if (isset($values['profile_id']) && !empty($values['profile_id'])) {
                $profile = $em->getRepository(Profile::class)->find($values['profile_id']);
                if ($profile) {
                    $item = new Level();
                    $item->setName($values['name']);
                    $item->setShortName($values['short_name']);
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
            Display::return_icon(
                'list_badges.png',
                get_lang('List'),
                null,
                ICON_SIZE_MEDIUM
            ),
            $listAction,
            ['title' => get_lang('List')]
        );
        break;
    case 'edit':
        $formToDisplay = $form->returnForm();
        $toolbarAction = Display::url(
            Display::return_icon(
                'list_badges.png',
                get_lang('List'),
                null,
                ICON_SIZE_MEDIUM
            ),
            $listAction,
            ['title' => get_lang('List')]
        );

        if ($form->validate()) {
            $values = $form->exportValues();

            $item->setName($values['name']);
            $item->setShortName($values['short_name']);
            $profile = $em->getRepository(Profile::class)->find($values['profile_id']);
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
            Display::return_icon(
                'list_badges.png',
                get_lang('List'),
                null,
                ICON_SIZE_MEDIUM
            ),
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

        break;
    default:
        $toolbarAction = Display::url(
            Display::return_icon(
                'add.png',
                get_lang('Add'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_self().'?action=add',
            ['title' => get_lang('Add')]
        );
}

$tpl = new Template($action);
$tpl->assign('form', $formToDisplay);
$tpl->assign('list', $list);
$templateName = $tpl->get_template('admin/skill_level.tpl');
$contentTemplate = $tpl->fetch($templateName);
$tpl->assign('actions', Display::toolbarAction('toolbar', [$toolbarAction]));
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
