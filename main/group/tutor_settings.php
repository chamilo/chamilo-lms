<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays an area where teachers can edit the group properties and member list.
 *	Groups are also often called "teams" in the Dokeos code.
 *
 *	@author various contributors
 *	@author Roan Embrechts (VUB), partial code cleanup, initial virtual course support
 *	@package chamilo.group
 *	@todo course admin functionality to create groups based on who is in which course (or class).
 */

/*	INIT SECTION */

// Name of the language file that needs to be included
$language_file = 'group';

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

$group_id = api_get_group_id();
$current_group = GroupManager :: get_group_properties($group_id);

$nameTools = get_lang('EditGroup');
$interbreadcrumb[] = array ('url' => 'group.php', 'name' => get_lang('Groups'));
$interbreadcrumb[] = array ('url' => 'group_space.php?'.api_get_cidReq(), 'name' => $current_group['name']);

$is_group_member = GroupManager :: is_tutor_of_group(api_get_user_id(), $group_id);

if (!api_is_allowed_to_edit(false, true) && !$is_group_member) {
    api_not_allowed(true);
}

/*	FUNCTIONS */

/**
 *  List all users registered to the course
 */
function search_members_keyword($firstname, $lastname, $username, $official_code, $keyword)
{
    if (api_strripos($firstname, $keyword) !== false ||
        api_strripos($lastname, $keyword) !== false ||
        api_strripos($username, $keyword) !== false ||
        api_strripos($official_code, $keyword) !== false
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Function to sort users after getting the list in the DB.
 * Necessary because there are 2 or 3 queries. Called by usort()
 */
function sort_users($user_a, $user_b)
{
    if (api_sort_by_first_name()) {
        $cmp = api_strcmp($user_a['firstname'], $user_b['firstname']);
        if ($cmp !== 0) {
            return $cmp;
        } else {
            $cmp = api_strcmp($user_a['lastname'], $user_b['lastname']);
            if ($cmp !== 0) {
                return $cmp;
            } else {
                return api_strcmp($user_a['username'], $user_b['username']);
            }
        }
    } else {
        $cmp = api_strcmp($user_a['lastname'], $user_b['lastname']);
        if ($cmp !== 0) {
            return $cmp;
        } else {
            $cmp = api_strcmp($user_a['firstname'], $user_b['firstname']);
            if ($cmp !== 0) {
                return $cmp;
            } else {
                return api_strcmp($user_a['username'], $user_b['username']);
            }
        }
    }
}


/*	MAIN CODE */

$htmlHeadXtra[] = '<script>
$(document).ready( function() {
    $("#max_member").on("focus", function() {
        $("#max_member_selected").attr("checked", true);
    });
});
 </script>';

// Build form
$form = new FormValidator('group_edit', 'post', api_get_self().'?'.api_get_cidreq());
$form->addElement('hidden', 'action');

// Group tutors
$group_tutor_list = GroupManager :: get_subscribed_tutors($current_group['id']);
$selected_tutors = array();
foreach ($group_tutor_list as $index => $user) {
    $selected_tutors[] = $user['user_id'];
}

$complete_user_list = GroupManager :: fill_groups_list($current_group['id']);
$possible_users = array();
if (!empty($complete_user_list)) {
    usort($complete_user_list, 'sort_users');

    foreach ($complete_user_list as $index => $user) {
        $possible_users[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')';
    }
}

$group_tutors_element = $form->addElement('advmultiselect', 'group_tutors', get_lang('GroupTutors'), $possible_users, 'style="width: 280px;"');
$group_tutors_element->setElementTemplate('
{javascript}
<table{class}>
<!-- BEGIN label_2 --><tr><th>{label_2}</th><!-- END label_2 -->
<!-- BEGIN label_3 --><th>&nbsp;</th><th>{label_3}</th></tr><!-- END label_3 -->
<tr>
  <td valign="top">{unselected}</td>
  <td align="center">{add}<br /><br />{remove}</td>
  <td valign="top">{selected}</td>
</tr>
</table>
');

$group_tutors_element->setButtonAttributes('add', array('class' => 'btn arrowr'));
$group_tutors_element->setButtonAttributes('remove', array('class' => 'btn arrowl'));

// submit button
$form->addElement('style_submit_button', 'submit', get_lang('SaveSettings'), 'class="save"');

if ($form->validate()) {
    $values = $form->exportValues();

    // Storing the tutors (we first remove all the tutors and then add only those who were selected)
    GroupManager :: unsubscribe_all_tutors($current_group['id']);
    if (isset ($_POST['group_tutors']) && count($_POST['group_tutors']) > 0) {
        GroupManager :: subscribe_tutors($values['group_tutors'], $current_group['id']);
    }

    // Returning to the group area (note: this is inconsistent with the rest of chamilo)
    $cat = GroupManager::get_category_from_group($current_group['id']);
    if (isset($_POST['group_members']) && count($_POST['group_members']) > $max_member && $max_member != GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
        header('Location: group.php?'.api_get_cidreq(true, false).'&action=warning_message&msg='.get_lang('GroupTooMuchMembers'));
    } else {
        header('Location: group.php?'.api_get_cidreq(true, false).'&action=success_message&msg='.get_lang('GroupSettingsModified').'&category='.$cat['id']);
    }
    exit;
}

$defaults = $current_group;
$defaults['group_tutors'] = $selected_tutors;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$defaults['action'] = $action;

if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
    $keyword_name = Security::remove_XSS($_GET['keyword']);
    echo '<br/>'.get_lang('SearchResultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

Display :: display_header($nameTools, 'Group');

//@todo fix this
if (isset($_GET['show_message_warning'])) {
    echo Display::display_warning_message($_GET['show_message_warning']);
}

if (isset($_GET['show_message_sucess'])) {
    echo Display::display_normal_message($_GET['show_message_sucess']);
}

$form->setDefaults($defaults);
echo GroupManager::getSettingBar('tutor');
$form->display();

Display :: display_footer();
