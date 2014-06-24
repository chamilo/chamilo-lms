<?php
/* For licensing terms, see /license.txt */
/**
*   @package chamilo.admin
*/

// name of the language file that needs to be included
$language_file = array('admin','registration');

// resetting the course id
$cidReset = true;

// including some necessary files
//require_once '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'usergroups.php','name' => get_lang('Classes'));

// Database Table Definitions

// setting the name of the tool
$tool_name=get_lang('SubscribeUsersToClass');

$add_type = 'multiple';
if(isset($_REQUEST['add_type']) && $_REQUEST['add_type']!=''){
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$htmlHeadXtra[] = '
<script>
function checked_in_no_group(checked) {
    $("#add_users_to_usergroup").submit();
}

</script>';


$form_sent  = 0;
$errorMsg   = '';

$extra_field_list= UserManager::get_extra_fields();
$new_field_list = array();
if (is_array($extra_field_list)) {
    foreach ($extra_field_list as $extra_field) {
        //if is enabled to filter and is a "<select>" field type
        if ($extra_field[8]==1 && $extra_field[2]==4 ) {
            $new_field_list[] = array('name'=> $extra_field[3], 'variable'=>$extra_field[1], 'data'=> $extra_field[9]);
        }
    }
}

$usergroup = new UserGroup();
$id = intval($_GET['id']);
$first_letter_user = '';
/*
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent              = $_POST['form_sent'];
    $elements_posted        = $_POST['elements_in_name'];
    $first_letter_user      = $_POST['firstLetterUser'];

    if (!is_array($elements_posted)) {
        $elements_posted=array();
    }
    if ($form_sent == 1) {
        //added a parameter to send emails when registering a user
        //$usergroup->subscribe_users_to_usergroup($id, $elements_posted);
        header('Location: usergroups.php');
        exit;
    }
}*/


//Filter by Extra Fields
$use_extra_fields = false;
if (is_array($extra_field_list)) {
    if (is_array($new_field_list) && count($new_field_list)>0 ) {
        foreach ($new_field_list as $new_field) {
            $varname = 'field_'.$new_field['variable'];
            if (UserManager::is_extra_field_available($new_field['variable'])) {
                if (isset($_POST[$varname]) && $_POST[$varname]!='0') {
                    $use_extra_fields = true;
                    $extra_field_result[]= UserManager::get_extra_user_data_by_value($new_field['variable'], $_POST[$varname]);
                }
            }
        }
    }
}

if ($use_extra_fields) {
    $final_result = array();
    if (count($extra_field_result)>1) {
        for($i=0;$i<count($extra_field_result)-1;$i++) {
            if (is_array($extra_field_result[$i+1])) {
                $final_result  = array_intersect($extra_field_result[$i],$extra_field_result[$i+1]);
            }
        }
    } else {
        $final_result = $extra_field_result[0];
    }
}
$data       = $usergroup->get($id);
$list_in    = $usergroup->get_users_by_usergroup($id);
$list_all   = $usergroup->get_users_by_usergroup();

$order = array('lastname');
if (api_is_western_name_order()) {
    $order = array('firstname');
}

$elements_not_in = $elements_in = array();
$complete_user_list = UserManager::get_user_list(array(), $order);

if (!empty($complete_user_list)) {
    foreach($complete_user_list as $item) {
        if ($use_extra_fields) {
            if (!in_array($item['user_id'], $final_result)) {
                continue;
            }
        }
        if ($item['status'] == 6 ) continue; //avoid anonymous users

        if (in_array($item['user_id'], $list_in)) {
            $person_name = api_get_person_name($item['firstname'], $item['lastname']).' ('.$item['username'].')';
            $elements_in[$item['user_id']] = $person_name;
        }
    }
}

$user_with_any_group = isset($_REQUEST['user_with_any_group']) && !empty($_REQUEST['user_with_any_group']) ? true : false;

if ($user_with_any_group) {
    $user_list = UserManager::get_user_list_like(array('lastname' => $first_letter_user), $order, true);
    $new_user_list = array();
    foreach ($user_list as $item) {
        if (!in_array($item['user_id'], $list_all)) {
            $new_user_list[] = $item;
        }
    }
    $user_list = $new_user_list;
} else {
    $user_list = UserManager::get_user_list_like(array('lastname' => $first_letter_user), $order, true);
}

if (!empty($user_list)) {
    foreach($user_list as $item) {
        if ($use_extra_fields) {
            if (!in_array($item['user_id'], $final_result)) {
                continue;
            }
        }
        if ($item['status'] == 6 ) continue; //avoid anonymous users
        $person_name = api_get_person_name($item['firstname'], $item['lastname']).' ('.$item['username'].')';
        if (in_array($item['user_id'], $list_in)) {
            //$elements_in[$item['user_id']] = $person_name;
        } else {
            $elements_not_in[$item['user_id']] = $person_name;
        }
    }
}

$add_type == 'unique' ? true : false;
Display::display_header($tool_name);
if ($add_type == 'multiple') {
    $link_add_type_unique = '<a href="'.api_get_self().'?add_type=unique">'.Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'</a>';
    $link_add_type_multiple = Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple');
} else {
    $link_add_type_unique = Display::return_icon('single.gif').get_lang('SessionAddTypeUnique');
    $link_add_type_multiple = '<a href="'.api_get_self().'?add_type=multiple">'.Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').'</a>';
}

echo '<div class="actions">';
echo '<a href="usergroups.php">'.Display::return_icon('back.png',get_lang('Back'), array(), ICON_SIZE_MEDIUM).'</a>';
echo '<a href="usergroup_user_import.php">'.Display::return_icon('import_csv.png',get_lang('Import'), array(), ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$form = new FormValidator('add_users_to_usergroup', 'post', api_get_self().'?id='.$id);

$form->addElement('hidden', 'id', $id);
$form->addElement('hidden', 'form_sent', '1');
$form->addElement('hidden', 'add_type', null);

$userList = array();
if (!empty($complete_user_list)) {
    foreach ($complete_user_list as $user) {
        $userList[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
    }
}

$form->addDoubleMultipleSelect('user_groups', get_lang('GroupTutors'), $userList);
$form->addElement('checkbox', 'user_with_any_group', null, get_lang('UsersRegisteredInAnyGroup'), array('onchange' => 'checked_in_no_group(this.checked);'));

$form->addElement('button', 'submit', get_lang('SubscribeUsersToClass'));

$defaults = array(
    'user_groups' => array_keys($elements_in)
);

$form->setDefaults($defaults);
$form->display();

if ($form->validate()) {
    $values  = $form->getSubmitValues();
    $users = $values['user_groups'];
    $usergroup->subscribe_users_to_usergroup($id, $users);
    header('Location: usergroups.php');
    exit;
}

Display::display_footer();
