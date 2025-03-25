<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;
use Chamilo\CoreBundle\Component\Utils\ActionIcon;

/**
 * Main page for the group module.
 * This script displays the general group settings,
 * and a list of groups with buttons to view, edit...
 *
 * @author Thomas Depraetere, Hugues Peeters, Christophe Gesche: initial versions
 * @author Bert Vanderkimpen, improved self-unsubscribe for cvs
 * @author Patrick Cool, show group comment under the group name
 * @author Roan Embrechts, initial self-unsubscribe code, code cleaning, virtual course support
 * @author Bart Mollet, code cleaning, use of Display-library, list of courseAdmin-tools, use of GroupManager
 * @author Isaac Flores, code cleaning and improvements
 */
require_once __DIR__.'/../inc/global.inc.php';

$is_allowed_in_course = api_is_allowed_in_course();
$userId = api_get_user_id();
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GROUP;
$course_id = api_get_course_int_id();
$sessionId = api_get_session_id();

// Notice for unauthorized people.
api_protect_course_script(true, false, 'group');

$htmlHeadXtra[] = '<script>
$(function() {
    var i;
	for (i=0; i<$(".actions").length; i++) {
		if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" ||
		    $(".actions:eq("+i+")").html()==null
		) {
			$(".actions:eq("+i+")").hide();
		}
	}
});
 </script>';
$nameTools = get_lang('Groups management');

/*
 * Self-registration and un-registration
 */
$my_group_id = $_GET['group_id'] ?? null;
$categoryId = $_GET['category_id'] ?? null;
$my_get_id1 = isset($_GET['id1']) ? Security::remove_XSS($_GET['id1']) : null;
$my_get_id2 = isset($_GET['id2']) ? Security::remove_XSS($_GET['id2']) : null;

$groupRepo = Container::getGroupRepository();
$groupEntity = null;
if (!empty($my_group_id)) {
    $groupEntity = $groupRepo->find($my_group_id);
}

$currentUrl = api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq();
$groupInfo = GroupManager::get_group_properties($my_group_id);

if (isset($_GET['action']) && $is_allowed_in_course) {
    switch ($_GET['action']) {
        case 'set_visible':
            if ($groupEntity && api_is_allowed_to_edit()) {
                GroupManager::setVisible($groupEntity);
                Display::addFlash(Display::return_message(get_lang('Item updated')));
                header("Location: $currentUrl");
                exit;
            }

            break;
        case 'set_invisible':
            if ($groupEntity && api_is_allowed_to_edit()) {
                GroupManager::setInvisible($groupEntity);
                Display::addFlash(Display::return_message(get_lang('Item updated')));
                header("Location: $currentUrl");
                exit;
            }

            break;
        case 'self_reg':
            if (GroupManager::is_self_registration_allowed($userId, $groupEntity)) {
                GroupManager::subscribeUsers($userId, $groupEntity);
                Display::addFlash(Display::return_message(get_lang('You are now a member of this group.')));
                header("Location: $currentUrl");
                exit;
            } else {
                Display::addFlash(Display::return_message(get_lang('Error')));
                header("Location: $currentUrl");
                exit;
            }

            break;
        case 'self_unreg':
            if (GroupManager::is_self_unregistration_allowed($userId, $groupEntity)) {
                GroupManager::subscribeUsers($userId, $groupEntity);
                Display::addFlash(Display::return_message(get_lang('You\'re now unsubscribed.')));
                header("Location: $currentUrl");
                exit;
            }

            break;
    }
}

/*
 * Group-admin functions
 */
if (api_is_allowed_to_edit(false, true)) {
    // Post-actions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_selected':
                if (is_array($_POST['group'])) {
                    foreach ($_POST['group'] as $myGroupId) {
                        $group = $groupRepo->find($myGroupId);
                        GroupManager::deleteGroup($group);
                    }

                    Display::addFlash(Display::return_message(get_lang('All selected groups have been deleted')));
                    header("Location: $currentUrl");
                    exit;
                }

                break;
            case 'empty_selected':
                if (is_array($_POST['group'])) {
                    foreach ($_POST['group'] as $myGroupId) {
                        GroupManager::unsubscribeAllUsers($myGroupId);
                    }

                    Display::addFlash(Display::return_message(get_lang('All selected groups are now empty')));
                    header("Location: $currentUrl");
                    exit;
                }

                break;
            case 'fill_selected':
                if (is_array($_POST['group'])) {
                    foreach ($_POST['group'] as $myGroupId) {
                        $group = $groupRepo->find($myGroupId);
                        GroupManager::fillGroupWithUsers($group);
                    }
                    Display::addFlash(Display::return_message(get_lang('All selected groups have been filled')));
                    header("Location: $currentUrl");
                    exit;
                }

                break;
        }
    }

    // Get-actions
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'swap_cat_order':
                GroupManager::swap_category_order($my_get_id1, $my_get_id2);
                Display::addFlash(Display::return_message(get_lang('The category order was changed')));
                header("Location: $currentUrl");
                exit;

                break;
            case 'delete_one':
                GroupManager::deleteGroup($groupEntity);
                Display::addFlash(Display::return_message(get_lang('Group deleted')));
                header("Location: $currentUrl");
                exit;

                break;
            case 'fill_one':
                GroupManager::fillGroupWithUsers($groupEntity);
                Display::addFlash(
                    Display::return_message(
                        get_lang('Groups have been filled (or completed) by users present in the \'Users\' list.')
                    )
                );
                header("Location: $currentUrl");
                exit;

                break;
            case 'delete_category':
                if (empty($sessionId)) {
                    GroupManager::delete_category($categoryId);
                    Display::addFlash(
                        Display::return_message(get_lang('The category has been deleted.'))
                    );
                    header("Location: $currentUrl");
                    exit;
                }

                break;
        }
    }
}

Display::display_header(get_lang('Groups'));
Display::display_introduction_section(TOOL_GROUP);

$actionsLeft = '';
if (api_is_allowed_to_edit(false, true)) {
    $actionsLeft .= '<a href="group_creation.php?'.api_get_cidreq().'">'.
        Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Create new group(s)')).'</a>';

    if (empty($sessionId) && 'true' === api_get_setting('allow_group_categories')) {
        $actionsLeft .= '<a href="group_category.php?'.api_get_cidreq().'&action=add_category">'.
            Display::getMdiIcon(ActionIcon::CREATE_FOLDER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add category')).'</a>';
    }

    $actionsLeft .= '<a href="import.php?'.api_get_cidreq().'&action=import">'.
        Display::getMdiIcon(ActionIcon::IMPORT_ARCHIVE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Import')).'</a>';

    $actionsLeft .= '<a href="group_overview.php?'.api_get_cidreq().'&action=export_all&type=csv">'.
        Display::getMdiIcon(ActionIcon::EXPORT_CSV, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('CSV export')).'</a>';

    $actionsLeft .= '<a href="group_overview.php?'.api_get_cidreq().'&action=export_all&type=xls">'.
        Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Excel export')).'</a>';

    $actionsLeft .= '<a href="group_overview.php?'.api_get_cidreq().'&action=export_pdf">'.
        Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to PDF')).'</a>';

    $actionsLeft .= '<a href="group_overview.php?'.api_get_cidreq().'">'.
        Display::getMdiIcon('view-dashboard', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Groups overview')).'</a>';
}

$actionsRight = GroupManager::getSearchForm();
$toolbar = Display::toolbarAction('toolbar-groups', [$actionsLeft, $actionsRight]);
$categories = GroupManager::get_categories();

echo $toolbar;
echo UserManager::getUserSubscriptionTab(3);

/*  List all categories */
if ('true' === api_get_setting('allow_group_categories')) {
    if (empty($categories)) {
        $defaultCategoryId = GroupManager::create_category(
            get_lang('Default groups')
        );
        $defaultCategory = GroupManager::get_category($defaultCategoryId);
        $categories = [$defaultCategory];
    }
    $course = api_get_course_entity();
    foreach ($categories as $index => $category) {
        $categoryId = $category['iid'];
        $groupList = GroupManager::get_group_list(
            $categoryId,
            $course,
            null,
            null,
            false,
            null,
            true
        );
        $groupToShow = GroupManager::processGroups($groupList, $categoryId);

        if (empty($categoryId) && empty($groupList)) {
            continue;
        }

        $label = Display::label(count($groupList).' '.get_lang('Groups'), 'info');
        $actions = null;
        if (api_is_allowed_to_edit(false, true) && !empty($categoryId) && empty($sessionId)) {
            // Edit
            $actions .= '<a
                href="group_category.php?'.api_get_cidreq().'&id='.$categoryId.'" title="'.get_lang('Edit').'">'.
                Display::getMdiIcon('pencil', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit this category')).'</a>';

            // Delete
            $actions .= Display::url(
                Display::getMdiIcon(ActionIcon::DELETE, count($categories) == 1 ? 'ch-tool-icon-disabled' : 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')),
                'group.php?'.api_get_cidreq().'&action=delete_category&category_id='.$categoryId,
                count($categories) == 1 ? [
                    'onclick' => 'javascript:alert('."'".addslashes(api_htmlentities(get_lang('You cannot delete the last category'), ENT_QUOTES))."'".'); return false;',
                ] : [
                    'onclick' => 'javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES))."'".')) return false;',
                ]
            );
            // Move
            if (0 != $index) {
                $actions .= ' <a
                    href="group.php?'.api_get_cidreq().'&action=swap_cat_order&id1='.$categoryId.'&id2='.$categories[$index - 1]['iid'].'">'.
                    Display::getMdiIcon(ActionIcon::UP, 'ch-tool-icon', null, ICON_SIZE_SMALL).'</a>';
            }
            if ($index != count($categories) - 1) {
                $actions .= ' <a
                    href="group.php?'.api_get_cidreq().'&action=swap_cat_order&id1='.$categoryId.'&id2='.$categories[$index + 1]['iid'].'">'.
                    Display::getMdiIcon(ActionIcon::DOWN, 'ch-tool-icon', null, ICON_SIZE_SMALL).'</a>';
            }
        }

        $header = Display::page_header(
            Security::remove_XSS($category['title'].' '.$label.' '),
            null,
            'h4',
            false
        );

        echo Display::toolbarAction('category_'.$categoryId, [$header.$actions]);
        echo $category['description'];
        echo $groupToShow;
    }
} else {
    echo GroupManager::processGroups(
        GroupManager::get_group_list(
            null,
            null,
            null,
            null,
            false,
            null,
            true
        )
    );
}

if (!isset($_GET['origin']) || 'learnpath' !== $_GET['origin']) {
    Display::display_footer();
}

Session::write('_gid', 0);
