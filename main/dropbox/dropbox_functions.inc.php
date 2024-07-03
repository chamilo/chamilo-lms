<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file contains additional dropbox functions. Initially there were some
 * functions in the init files also but I have moved them over
 * to one file -- Patrick Cool <patrick.cool@UGent.be>, Ghent University.
 *
 * @author Julio Montoya adding c_id support
 */
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<script>
function setFocus(){
    $("#category_title").focus();
}
$(function() {
    setFocus();
});
</script>';

/**
 * This function is a wrapper function for the multiple actions feature.
 *
 * @return string|null If there is a problem, return a string message, otherwise nothing
 *
 * @author   Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version  march 2006
 */
function handle_multiple_actions()
{
    $_user = api_get_user_info();
    $is_courseAdmin = api_is_course_admin();
    $is_courseTutor = api_is_course_tutor();

    // STEP 1: are we performing the actions on the received or on the sent files?
    if ($_POST['action'] == 'delete_received' || $_POST['action'] == 'download_received') {
        $part = 'received';
    } elseif ($_POST['action'] == 'delete_sent' || $_POST['action'] == 'download_sent') {
        $part = 'sent';
    }

    // STEP 2: at least one file has to be selected. If not we return an error message
    $ids = isset($_GET['id']) ? $_GET['id'] : [];
    if (count($ids) > 0) {
        $checked_file_ids = $_POST['id'];
    } else {
        foreach ($_POST as $key => $value) {
            if (strstr($value, $part.'_') && $key != 'view_received_category' && $key != 'view_sent_category') {
                $checked_files = true;
                $checked_file_ids[] = intval(substr($value, strrpos($value, '_')));
            }
        }
    }
    $checked_file_ids = $_POST['id'];

    if (!is_array($checked_file_ids) || count($checked_file_ids) == 0) {
        return get_lang('CheckAtLeastOneFile');
    }

    // Deleting
    if ($_POST['action'] == 'delete_received' || $_POST['action'] == 'delete_sent') {
        $dropboxfile = new Dropbox_Person($_user['user_id'], $is_courseAdmin, $is_courseTutor);
        foreach ($checked_file_ids as $key => $value) {
            if ($_GET['view'] == 'received') {
                $dropboxfile->deleteReceivedWork($value);
                $message = get_lang('ReceivedFileDeleted');
            }
            if ($_GET['view'] == 'sent' || empty($_GET['view'])) {
                $dropboxfile->deleteSentWork($value);
                $message = get_lang('SentFileDeleted');
            }
        }

        return $message;
    }

    // moving
    if (strstr($_POST['action'], 'move_')) {
        // check move_received_n or move_sent_n command
        if (strstr($_POST['action'], 'received')) {
            $part = 'received';
            $to_cat_id = str_replace('move_received_', '', $_POST['action']);
        } else {
            $part = 'sent';
            $to_cat_id = str_replace('move_sent_', '', $_POST['action']);
        }

        foreach ($checked_file_ids as $value) {
            store_move($value, $to_cat_id, $part);
        }

        return get_lang('FilesMoved');
    }

    // STEP 3D: downloading
    if ($_POST['action'] == 'download_sent' || $_POST['action'] == 'download_received') {
        zip_download($checked_file_ids);
    }
}

/**
 * Get conf settings.
 *
 * @return array
 */
function getDropboxConf()
{
    return Session::read('dropbox_conf');
}

/**
 * This function deletes a dropbox category.
 *
 * @todo give the user the possibility what needs to be done with the files
 * in this category: move them to the root, download them as a zip, delete them
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function delete_category($action, $id, $user_id = null)
{
    $course_id = api_get_course_int_id();
    $is_courseAdmin = api_is_course_admin();
    $is_courseTutor = api_is_course_tutor();

    if (empty($user_id)) {
        $user_id = api_get_user_id();
    }

    $cat = get_dropbox_category($id);
    if (count($cat) == 0) {
        return false;
    }

    if ($cat['user_id'] != $user_id && !api_is_platform_admin($user_id)) {
        return false;
    }

    // an additional check that might not be necessary
    if ($action == 'deletereceivedcategory') {
        $sentreceived = 'received';
        $entries_table = Database::get_course_table(TABLE_DROPBOX_POST);
        $id_field = 'file_id';
        $return_message = get_lang('ReceivedCatgoryDeleted');
    } elseif ($action == 'deletesentcategory') {
        $sentreceived = 'sent';
        $entries_table = Database::get_course_table(TABLE_DROPBOX_FILE);
        $id_field = 'id';
        $return_message = get_lang('SentCatgoryDeleted');
    } else {
        return get_lang('Error');
    }

    // step 1: delete the category
    $sql = "DELETE FROM ".Database::get_course_table(TABLE_DROPBOX_CATEGORY)."
            WHERE c_id = $course_id AND cat_id='".intval($id)."' AND $sentreceived='1'";
    Database::query($sql);

    // step 2: delete all the documents in this category
    $sql = "SELECT * FROM ".$entries_table."
            WHERE c_id = $course_id AND cat_id='".intval($id)."'";
    $result = Database::query($sql);

    while ($row = Database::fetch_array($result)) {
        $dropboxfile = new Dropbox_Person($user_id, $is_courseAdmin, $is_courseTutor);
        if ($action == 'deletereceivedcategory') {
            $dropboxfile->deleteReceivedWork($row[$id_field]);
        }
        if ($action == 'deletesentcategory') {
            $dropboxfile->deleteSentWork($row[$id_field]);
        }
    }

    return $return_message;
}

/**
 * Displays the form to move one individual file to a category.
 *
 *@ return html code of the form that appears in a message box.
 *
 * @author Julio Montoya - function rewritten
 */
function display_move_form(
    $part,
    $id,
    $target,
    $extra_params,
    $viewReceivedCategory,
    $viewSentCategory,
    $view
) {
    $form = new FormValidator(
        'form1',
        'post',
        api_get_self().'?view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&'.$extra_params
    );
    $form->addElement('header', get_lang('MoveFileTo'));
    $form->addElement('hidden', 'id', intval($id));
    $form->addElement('hidden', 'part', Security::remove_XSS($part));

    $options = ['0' => get_lang('Root')];
    foreach ($target as $category) {
        $options[$category['cat_id']] = $category['cat_name'];
    }
    $form->addElement('select', 'move_target', get_lang('MoveFileTo'), $options);
    $form->addButtonMove(get_lang('MoveFile'), 'do_move');
    $form->display();
}

/**
 * This function moves a file to a different category.
 *
 * @param int    $id     the id of the file we are moving
 * @param int    $target the id of the folder we are moving to
 * @param string $part   are we moving a received file or a sent file?
 *
 * @return string string
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function store_move($id, $target, $part)
{
    $_user = api_get_user_info();
    $course_id = api_get_course_int_id();

    if ((isset($id) && $id != '') &&
        (isset($target) && $target != '') &&
        (isset($part) && $part != '')
    ) {
        if ($part == 'received') {
            $sql = "UPDATE ".Database::get_course_table(TABLE_DROPBOX_POST)."
                    SET cat_id = ".intval($target)."
                    WHERE c_id = $course_id AND dest_user_id = ".intval($_user['user_id'])."
                    AND file_id = ".intval($id)."";
            Database::query($sql);
            $return_message = get_lang('ReceivedFileMoved');
        }
        if ($part == 'sent') {
            $sql = "UPDATE ".Database::get_course_table(TABLE_DROPBOX_FILE)."
                    SET cat_id = ".intval($target)."
                    WHERE
                        c_id = $course_id AND
                        uploader_id = ".intval($_user['user_id'])." AND
                        id = ".intval($id);
            Database::query($sql);
            $return_message = get_lang('SentFileMoved');
        }
    } else {
        $return_message = get_lang('NotMovedError');
    }

    return $return_message;
}

/**
 * This function retrieves all dropbox categories and returns them as an array.
 *
 * @param $filter default '', when we need only the categories of the sent or the received part
 *
 * @return array
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function get_dropbox_categories($filter = '')
{
    $course_id = api_get_course_int_id();
    $_user = api_get_user_info();
    $return_array = [];

    $session_id = api_get_session_id();
    $condition_session = api_get_session_condition($session_id);

    $sql = "SELECT * FROM ".Database::get_course_table(TABLE_DROPBOX_CATEGORY)."
            WHERE c_id = $course_id AND user_id='".$_user['user_id']."' $condition_session";

    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        if (($filter == 'sent' && $row['sent'] == 1) ||
            ($filter == 'received' && $row['received'] == 1) || $filter == ''
        ) {
            $return_array[$row['cat_id']] = $row;
        }
    }

    return $return_array;
}

/**
 * Get a dropbox category details.
 *
 * @param int The category ID
 *
 * @return array The details of this category
 */
function get_dropbox_category($id)
{
    $course_id = api_get_course_int_id();
    $id = (int) $id;

    if (empty($id)) {
        return [];
    }

    $sql = "SELECT * FROM ".Database::get_course_table(TABLE_DROPBOX_CATEGORY)."
            WHERE c_id = $course_id AND cat_id='".$id."'";
    $res = Database::query($sql);
    if ($res === false) {
        return [];
    }
    $row = Database::fetch_assoc($res);

    return $row;
}

/**
 * This functions stores a new dropboxcategory.
 *
 * @var it might not seem very elegant if you create a category in sent
 *         and in received with the same name that you get two entries in the
 *         dropbox_category table but it is the easiest solution. You get
 *         cat_name | received | sent | user_id
 *         test     |    1     |   0  |    237
 *         test     |    0     |   1  |    237
 *         more elegant would be
 *         test     |    1     |   1  |    237
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function store_addcategory()
{
    $course_id = api_get_course_int_id();
    $_user = api_get_user_info();

    // check if the target is valid
    if ($_POST['target'] == 'sent') {
        $sent = 1;
        $received = 0;
    } elseif ($_POST['target'] == 'received') {
        $sent = 0;
        $received = 1;
    } else {
        return get_lang('Error');
    }

    // check if the category name is valid
    if ($_POST['category_name'] == '') {
        return ['type' => 'error', 'message' => get_lang('ErrorPleaseGiveCategoryName')];
    }

    if (!isset($_POST['edit_id'])) {
        $session_id = api_get_session_id();
        // step 3a, we check if the category doesn't already exist
        $sql = "SELECT * FROM ".Database::get_course_table(TABLE_DROPBOX_CATEGORY)."
                WHERE
                    c_id = $course_id AND
                    user_id='".$_user['user_id']."' AND
                    cat_name='".Database::escape_string($_POST['category_name'])."' AND
                    received='".$received."' AND
                    sent='$sent' AND
                    session_id='$session_id'";
        $result = Database::query($sql);

        // step 3b, we add the category if it does not exist yet.
        if (Database::num_rows($result) == 0) {
            $params = [
                'cat_id' => 0,
                'c_id' => $course_id,
                'cat_name' => $_POST['category_name'],
                'received' => $received,
                'sent' => $sent,
                'user_id' => $_user['user_id'],
                'session_id' => $session_id,
            ];
            $id = Database::insert(Database::get_course_table(TABLE_DROPBOX_CATEGORY), $params);
            if ($id) {
                $sql = "UPDATE ".Database::get_course_table(TABLE_DROPBOX_CATEGORY)." SET cat_id = iid
                        WHERE iid = $id";
                Database::query($sql);
            }

            return ['type' => 'confirmation', 'message' => get_lang('CategoryStored')];
        } else {
            return ['type' => 'error', 'message' => get_lang('CategoryAlreadyExistsEditIt')];
        }
    } else {
        $params = [
            'cat_name' => $_POST['category_name'],
            'received' => $received,
            'sent' => $sent,
        ];

        Database::update(
            Database::get_course_table(TABLE_DROPBOX_CATEGORY),
            $params,
            [
                'c_id = ? AND user_id = ? AND cat_id = ?' => [
                    $course_id,
                    $_user['user_id'],
                    $_POST['edit_id'],
                ],
            ]
        );

        return ['type' => 'confirmation', 'message' => get_lang('CategoryModified')];
    }
}

/**
 * This function displays the form to add a new category.
 *
 * @param string $category_name this parameter is the name of the category (used when no section is selected)
 * @param int    $id            this is the id of the category we are editing
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function display_addcategory_form($category_name = '', $id = 0, $action = '')
{
    $course_id = api_get_course_int_id();
    $title = get_lang('AddNewCategory');

    $id = (int) $id;

    if (!empty($id)) {
        // retrieve the category we are editing
        $sql = "SELECT * FROM ".Database::get_course_table(TABLE_DROPBOX_CATEGORY)."
                WHERE c_id = $course_id AND cat_id = ".$id;
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        if (empty($category_name)) {
            // after an edit with an error we do not want to return to the
            // original name but the name we already modified.
            // (happens when createinrecievedfiles AND createinsentfiles are not checked)
            $category_name = $row['cat_name'];
        }
        if ($row['received'] == '1') {
            $target = 'received';
        }
        if ($row['sent'] == '1') {
            $target = 'sent';
        }
        $title = get_lang('EditCategory');
    }

    if ($action == 'addreceivedcategory') {
        $target = 'received';
    }
    if ($action == 'addsentcategory') {
        $target = 'sent';
    }

    if ($action == 'editcategory') {
        $text = get_lang('ModifyCategory');
    } elseif ($action == 'addreceivedcategory' || $action == 'addsentcategory') {
        $text = get_lang('CreateCategory');
    }

    $form = new FormValidator(
        'add_new_category',
        'post',
        api_get_self().'?'.api_get_cidreq().'&view='.Security::remove_XSS($_GET['view'])
    );
    $form->addElement('header', $title);

    if (!empty($id)) {
        $form->addElement('hidden', 'edit_id', $id);
    }
    $form->addElement('hidden', 'action', Security::remove_XSS($action));
    $form->addElement('hidden', 'target', Security::remove_XSS($target));

    $form->addElement('text', 'category_name', get_lang('CategoryName'));
    $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');
    $form->addButtonSave($text, 'StoreCategory');

    $defaults = [];
    $defaults['category_name'] = Security::remove_XSS($category_name);
    $form->setDefaults($defaults);
    $form->display();
}

/**
 * this function displays the form to upload a new item to the dropbox.
 *
 * @param $viewReceivedCategory
 * @param $viewSentCategory
 * @param $view
 * @param int $id
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Julio Montoya
 *
 * @version march 2006
 */
function display_add_form($viewReceivedCategory, $viewSentCategory, $view, $id = 0, $action = 'add')
{
    $course_info = api_get_course_info();
    $_user = api_get_user_info();
    $is_courseAdmin = api_is_course_admin();
    $is_courseTutor = api_is_course_tutor();
    $origin = api_get_origin();

    $token = Security::get_token();
    $dropbox_person = new Dropbox_Person(
        api_get_user_id(),
        $is_courseAdmin,
        $is_courseTutor
    );

    $idCondition = !empty($id) ? '&id='.(int) $id : '';

    $url = api_get_self().'?view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&'.api_get_cidreq().$idCondition;
    $form = new FormValidator(
        'sent_form',
        'post',
        $url,
        null,
        [
            'enctype' => 'multipart/form-data',
            'onsubmit' => 'javascript: return checkForm(this);',
        ]
    );

    $langFormHeader = ('send_other_users' == $action ? get_lang('SendFileToOtherUsers') : get_lang('UploadNewFile'));
    $form->addElement('header', $langFormHeader);
    $form->addElement('hidden', 'sec_token', $token);
    $form->addElement('hidden', 'origin', $origin);
    if ('add' == $action) {
        $maxFileSize = getIniMaxFileSizeInBytes();
        $form->addElement('hidden', 'MAX_FILE_SIZE', $maxFileSize);
        $form->addElement(
            'file',
            'file',
            get_lang('UploadFile'),
            ['onChange' => 'javascript: checkfile(this.value);']
        );

        $allowOverwrite = api_get_setting('dropbox_allow_overwrite');
        if ($allowOverwrite == 'true' && empty($idCondition)) {
            $form->addElement(
                'checkbox',
                'cb_overwrite',
                null,
                get_lang('OverwriteFile'),
                ['id' => 'cb_overwrite']
            );
        }
    }

    // List of all users in this course and all virtual courses combined with it
    if (api_get_session_id()) {
        $complete_user_list_for_dropbox = [];
        if (api_get_setting('dropbox_allow_student_to_student') == 'true' || $_user['status'] != STUDENT) {
            $complete_user_list_for_dropbox = CourseManager::get_user_list_from_course_code(
                $course_info['code'],
                api_get_session_id(),
                null,
                null,
                0,
                false,
                false,
                false,
                [],
                [],
                [],
                true
            );
        }

        $complete_user_list2 = CourseManager::get_coach_list_from_course_code(
            $course_info['code'],
            api_get_session_id()
        );

        $generalCoachList = [];
        $courseCoachList = [];
        foreach ($complete_user_list2 as $coach) {
            if ($coach['type'] == 'general_coach') {
                $generalCoachList[] = $coach;
            } else {
                $courseCoachList[] = $coach;
            }
        }

        $hideCourseCoach = api_get_setting('dropbox_hide_course_coach');
        if ($hideCourseCoach == 'false') {
            $complete_user_list_for_dropbox = array_merge(
                $complete_user_list_for_dropbox,
                $courseCoachList
            );
        }
        $hideGeneralCoach = api_get_setting('dropbox_hide_general_coach');

        if ($hideGeneralCoach == 'false') {
            $complete_user_list_for_dropbox = array_merge(
                $complete_user_list_for_dropbox,
                $generalCoachList
            );
        }
    } else {
        if (api_get_setting('dropbox_allow_student_to_student') == 'true' || $_user['status'] != STUDENT) {
            $complete_user_list_for_dropbox = CourseManager::get_user_list_from_course_code(
                $course_info['code'],
                api_get_session_id(),
                null,
                null,
                null,
                false,
                false,
                false,
                [],
                [],
                [],
                true
            );
        } else {
            $complete_user_list_for_dropbox = CourseManager::get_teacher_list_from_course_code(
                $course_info['code'],
                false
            );
        }
    }

    if (!empty($complete_user_list_for_dropbox)) {
        foreach ($complete_user_list_for_dropbox as $k => $e) {
            $complete_user_list_for_dropbox[$k] = $e + [
                'lastcommafirst' => api_get_person_name(
                    $e['firstname'],
                    $e['lastname']
                ),
            ];
        }
        $complete_user_list_for_dropbox = TableSort::sort_table($complete_user_list_for_dropbox, 'lastcommafirst');
    }

    /*
        Create the options inside the select box:
        List all selected users their user id as value and a name string as display
    */
    $current_user_id = '';
    $allowStudentToStudent = api_get_setting('dropbox_allow_student_to_student');
    $options = [];
    $userGroup = new UserGroup();
    foreach ($complete_user_list_for_dropbox as $current_user) {
        if ((
            $dropbox_person->isCourseTutor
                || $dropbox_person->isCourseAdmin
                || $allowStudentToStudent == 'true'
                || $current_user['status'] != 5                         // Always allow teachers.
                || $current_user['is_tutor'] == 1                       // Always allow tutors.
                ) && $current_user['user_id'] != $_user['user_id']) {   // Don't include yourself.
            if ($current_user['user_id'] == $current_user_id) {
                continue;
            }
            $userId = $current_user['user_id'];
            $userInfo = api_get_user_info($userId);
            if ($userInfo['status'] != INVITEE) {
                $groupNameListToString = '';
                if (!empty($groups)) {
                    $groupNameList = array_column($groups, 'name');
                    $groupNameListToString = ' - ['.implode(', ', $groupNameList).']';
                }
                $groups = $userGroup->getUserGroupListByUser($userId);

                $full_name = $userInfo['complete_name'].$groupNameListToString;
                $current_user_id = $current_user['user_id'];
                $options['user_'.$current_user_id] = $full_name;
            }
        }
    }

    /*
    * Show groups
    */
    $allowGroups = api_get_setting('dropbox_allow_group');
    if (($dropbox_person->isCourseTutor || $dropbox_person->isCourseAdmin)
        && $allowGroups == 'true' || $allowStudentToStudent == 'true'
    ) {
        $complete_group_list_for_dropbox = GroupManager::get_group_list(null, $course_info);

        if (count($complete_group_list_for_dropbox) > 0) {
            foreach ($complete_group_list_for_dropbox as $current_group) {
                if ($current_group['number_of_members'] > 0) {
                    $options['group_'.$current_group['id']] = 'G: '.$current_group['name'].' - '.$current_group['number_of_members'].' '.get_lang('Users');
                }
            }
        }
    }

    if ('add' == $action) {
        $allowUpload = api_get_setting('dropbox_allow_just_upload');
        if ($allowUpload == 'true') {
            $options['user_'.$_user['user_id']] = get_lang('JustUploadInSelect');
        }

        if (empty($idCondition)) {
            $form->addSelect(
                'recipients',
                get_lang('SendTo'),
                $options,
                [
                    'multiple' => 'multiple',
                    'size' => '10',
                ]
            );
        }
        $form->addButtonUpload(get_lang('Upload'), 'submitWork');
        $headers = [
            get_lang('Upload'),
            get_lang('Upload').' ('.get_lang('Simple').')',
        ];
        $multipleForm = new FormValidator(
            'sent_multiple',
            'post',
            '#',
            null,
            ['enctype' => 'multipart/form-data', 'id' => 'fileupload']
        );

        if (empty($idCondition)) {
            $multipleForm->addSelect(
                'recipients',
                get_lang('SendTo'),
                $options,
                [
                    'multiple' => 'multiple',
                    'size' => '10',
                    'id' => 'recipient_form',
                ]
            );
        }

        $url = api_get_path(WEB_AJAX_PATH).'dropbox.ajax.php?'.api_get_cidreq().'&a=upload_file&'.$idCondition;
        if (empty($idCondition)) {
            $multipleForm->addHtml('<div id="multiple_form" style="display:none">');
        }
        $multipleForm->addMultipleUpload($url);
        if (empty($idCondition)) {
            $multipleForm->addHtml('</div>');
        }

        echo Display::tabs(
            $headers,
            [$multipleForm->returnForm(), $form->returnForm()],
            'tabs'
        );
    } else {
        $tblDropboxPerson = Database::get_course_table(TABLE_DROPBOX_PERSON);
        $tblDropboxFile = Database::get_course_table(TABLE_DROPBOX_FILE);
        $courseId = api_get_course_int_id();
        $optionsDpUsers = [];
        $current_user_id = api_get_user_id();

        $sql = "SELECT user_id
                FROM $tblDropboxPerson
                WHERE
                    c_id = $courseId AND
                    file_id = $id AND user_id != $current_user_id";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                $dpUserId = $row['user_id'];
                $dpUser = api_get_user_info($dpUserId);
                $optionsDpUsers['user_'.$dpUserId] = $dpUser['complete_name'];
                if (isset($options['user_'.$dpUserId])) {
                    unset($options['user_'.$dpUserId]);
                }
            }
        }

        $form->addSelect(
            'recipients',
            get_lang('SendTo'),
            $options,
            [
                'multiple' => 'multiple',
                'size' => '10',
            ]
        );

        $formRemove = new FormValidator('sent_remove', 'post');
        $formRemove->addElement('header', get_lang('RemoveFileFromSelectedUsers'));
        $formRemove->addSelect(
            'recipients',
            get_lang('Remove'),
            $optionsDpUsers,
            [
                'multiple' => 'multiple',
                'size' => '10',
            ]
        );

        // Current file information.
        $sql = "SELECT title, filesize
                FROM $tblDropboxFile
                WHERE
                    c_id = $courseId AND
                    iid = $id";
        $rs = Database::query($sql);
        $dropboxFile = Database::fetch_array($rs);
        if (!empty($dropboxFile)) {
            $icon = DocumentManager::build_document_icon_tag('file', $dropboxFile['title']);
            $filesize = format_file_size($dropboxFile['filesize']);
            $labelFile = "$icon {$dropboxFile['title']} ($filesize)";
            $form->addLabel(get_lang('File'), $labelFile);
            $formRemove->addLabel(get_lang('File'), $labelFile);
        }
        $form->addElement('hidden', 'file_id', $id);
        $form->addElement('hidden', 'action', $action);
        $form->addElement('hidden', 'option', 'add_users');
        $form->addButtonUpload(get_lang('SendToUsers'), 'submitWork');
        $formRemove->addElement('hidden', 'sec_token', $token);
        $formRemove->addElement('hidden', 'origin', $origin);
        $formRemove->addElement('hidden', 'file_id', $id);
        $formRemove->addElement('hidden', 'action', $action);
        $formRemove->addElement('hidden', 'option', 'remove_users');
        $formRemove->addButtonUpload(get_lang('RemoveUsers'), 'submitWork');

        echo Display::tabs(
            [get_lang('AddUsers'), get_lang('RemoveUsers')],
            [$form->returnForm(), $formRemove->returnForm()],
            'tabs'
        );
    }
}

/**
 * Checks if there are files in the dropbox_file table that aren't used anymore in dropbox_person table.
 * If there are, all entries concerning the file are deleted from the db + the file is deleted from the server.
 */
function removeUnusedFiles(int $courseId = 0, int $sessionId = 0)
{
    if (empty($courseId)) {
        $courseId = api_get_course_int_id();
    }
    $_course = api_get_course_info_by_id($courseId);
    if (empty($sessionId)) {
        $sessionId = api_get_session_id();
    }
    $condition_session = api_get_session_condition($sessionId, true, false, 'f.session_id');

    // select all files that aren't referenced anymore
    $sql = "SELECT DISTINCT f.iid, f.filename
            FROM ".Database::get_course_table(TABLE_DROPBOX_FILE)." f
            LEFT JOIN ".Database::get_course_table(TABLE_DROPBOX_PERSON)." p
            ON (f.iid = p.file_id)
            WHERE p.user_id IS NULL AND
                  f.c_id = $courseId
                  $condition_session
            ";
    $result = Database::query($sql);
    $condition_session = api_get_session_condition($sessionId);
    while ($res = Database::fetch_array($result)) {
        //delete the selected files from the post and file tables
        $sql = "DELETE FROM ".Database::get_course_table(TABLE_DROPBOX_POST)."
                WHERE c_id = $courseId $condition_session AND file_id = ".$res['iid'];
        Database::query($sql);
        $sql = "DELETE FROM ".Database::get_course_table(TABLE_DROPBOX_FILE)."
                WHERE iid = ".$res['iid'];
        Database::query($sql);
        //delete file from server
        @unlink(api_get_path(SYS_COURSE_PATH).$_course['path'].'/dropbox/'.$res['filename']);
    }
}

/**
 * Mailing zip-file is posted to (dest_user_id = ) mailing pseudo_id
 * and is only visible to its uploader (user_id).
 *
 * Mailing content files have uploader_id == mailing pseudo_id, a normal recipient,
 * and are visible initially to recipient and pseudo_id.
 *
 * @author René Haentjens, Ghent University
 *
 * @todo check if this function is still necessary.
 */
function getUserOwningThisMailing($mailingPseudoId, $owner = 0, $or_die = '')
{
    $course_id = api_get_course_int_id();

    $mailingPseudoId = (int) $mailingPseudoId;
    $sql = "SELECT f.uploader_id
            FROM ".Database::get_course_table(TABLE_DROPBOX_FILE)." f
            LEFT JOIN ".Database::get_course_table(TABLE_DROPBOX_POST)." p
            ON (f.id = p.file_id AND f.c_id = $course_id AND p.c_id = $course_id)
            WHERE
                p.dest_user_id = '".$mailingPseudoId."' AND
                p.c_id = $course_id
            ";
    $result = Database::query($sql);

    if (!($res = Database::fetch_array($result))) {
        exit(get_lang('GeneralError').' (code 901)');
    }
    if ($owner == 0) {
        return $res['uploader_id'];
    }
    if ($res['uploader_id'] == $owner) {
        return true;
    }
    exit(get_lang('GeneralError').' (code '.$or_die.')');
}

/**
 * @author René Haentjens, Ghent University
 *
 * @todo check if this function is still necessary.
 */
function removeMoreIfMailing(int $file_id, int $courseId = 0, int $sessionId = 0, int $uploaderId = 0)
{
    if (empty($courseId)) {
        $courseId = api_get_course_int_id();
    }
    if (empty($sessionId)) {
        $sessionId = api_get_session_id();
    }
    $condition_session = api_get_session_condition($sessionId);
    if (empty($uploaderId)) {
        $uploaderId = api_get_user_id();
    }
    // when deleting a mailing zip-file (posted to mailingPseudoId):
    // 1. the detail window is no longer reachable, so
    //    for all content files, delete mailingPseudoId from person-table
    // 2. finding the owner (getUserOwningThisMailing) is no longer possible, so
    //    for all content files, replace mailingPseudoId by owner as uploader
    $sql = "SELECT p.dest_user_id
            FROM ".Database::get_course_table(TABLE_DROPBOX_POST)." p
            WHERE c_id = $courseId $condition_session AND p.file_id = $file_id";
    $result = Database::query($sql);

    if ($res = Database::fetch_array($result)) {
        $mailingPseudoId = $res['dest_user_id'];
        $mailId = get_mail_id_base();
        if ($mailingPseudoId > $mailId) {
            $sql = "DELETE FROM ".Database::get_course_table(TABLE_DROPBOX_PERSON)."
                    WHERE c_id = $courseId AND user_id = $mailingPseudoId";
            Database::query($sql);

            $sql = "UPDATE ".Database::get_course_table(TABLE_DROPBOX_FILE)."
                    SET uploader_id = $uploaderId
                    WHERE c_id = $courseId $condition_session AND uploader_id = $mailingPseudoId";
            Database::query($sql);
        }
    }
}

/**
 * @param array            $file
 * @param Dropbox_SentWork $work
 *
 * @return array|string|null
 */
function store_add_dropbox($file = [], $work = null)
{
    $_course = api_get_course_info();
    $_user = api_get_user_info();

    if (empty($file)) {
        $file = isset($_FILES['file']) ? $_FILES['file'] : null;
    }

    if (empty($work)) {
        // Validating the form data
        // there are no recipients selected
        if (!isset($_POST['recipients']) || count($_POST['recipients']) <= 0) {
            return get_lang('YouMustSelectAtLeastOneDestinee');
        } else {
            // Check if all the recipients are valid
            $thisIsAMailing = false;
            $thisIsJustUpload = false;

            foreach ($_POST['recipients'] as $rec) {
                if ($rec == 'mailing') {
                    $thisIsAMailing = true;
                } elseif ($rec == 'upload') {
                    $thisIsJustUpload = true;
                } elseif (strpos($rec, 'user_') === 0 &&
                    !CourseManager::is_user_subscribed_in_course(
                        substr($rec, strlen('user_')),
                        $_course['code'],
                        true
                    )
                ) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('InvalideUserDetected'),
                            'warning'
                        )
                    );

                    return false;
                } elseif (strpos($rec, 'group_') !== 0 && strpos($rec, 'user_') !== 0) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('InvalideGroupDetected'),
                            'warning'
                        )
                    );

                    return false;
                }
            }
        }

        // we are doing a mailing but an additional recipient is selected
        if ($thisIsAMailing && (count($_POST['recipients']) != 1)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('MailingSelectNoOther'),
                    'warning'
                )
            );

            return false;
        }

        // we are doing a just upload but an additional recipient is selected.
        // note: why can't this be valid? It is like sending a document to
        // yourself AND to a different person (I do this quite often with my e-mails)
        if ($thisIsJustUpload && (count($_POST['recipients']) != 1)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('MailingJustUploadSelectNoOther'),
                    'warning'
                )
            );

            return false;
        }
    }

    $fileId = 0;
    $sendToOtherUsers = false;
    if ((isset($_POST['action']) && 'send_other_users' == $_POST['action']) && isset($_POST['file_id'])) {
        $fileId = (int) $_POST['file_id'];
        $sendToOtherUsers = true;
    }

    if (!$sendToOtherUsers) {
        if (empty($file['name'])) {
            Display::addFlash(Display::return_message(get_lang('NoFileSpecified'), 'warning'));

            return false;
        }

        // are we overwriting a previous file or sending a new one
        $dropbox_overwrite = false;
        if (isset($_POST['cb_overwrite']) && $_POST['cb_overwrite']) {
            $dropbox_overwrite = true;
        }

        // doing the upload
        $dropbox_filename = $file['name'];
        $dropbox_filesize = $file['size'];
        $dropbox_filetype = $file['type'];
        $dropbox_filetmpname = $file['tmp_name'];

        // check if the filesize does not exceed the allowed size.
        $maxFileSize = getIniMaxFileSizeInBytes();
        if ($dropbox_filesize <= 0 || $dropbox_filesize > $maxFileSize) {
            Display::addFlash(Display::return_message(get_lang('DropboxFileTooBig'), 'warning'));

            return false;
        }

        // check if the file is actually uploaded
        if (!isset($file['copy_file']) && !is_uploaded_file($dropbox_filetmpname)) { // check user fraud : no clean error msg.
            Display::addFlash(Display::return_message(get_lang('TheFileIsNotUploaded'), 'warning'));

            return false;
        }

        $upload_ok = process_uploaded_file($file, true);

        if (!$upload_ok) {
            return null;
        }

        // Try to add an extension to the file if it hasn't got one
        $dropbox_filename = add_ext_on_mime($dropbox_filename, $dropbox_filetype);
        // Replace dangerous characters
        $dropbox_filename = api_replace_dangerous_char($dropbox_filename);
        // Transform any .php file in .phps fo security
        $dropbox_filename = php2phps($dropbox_filename);

        //filter extension
        if (!filter_extension($dropbox_filename)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('UplUnableToSaveFileFilteredExtension'),
                    'warning'
                )
            );

            return false;
        }

        // set title
        $dropbox_title = $dropbox_filename;
        // note: I think we could better migrate everything from here on to
        // separate functions: store_new_dropbox, store_new_mailing, store_just_upload
        if ($dropbox_overwrite && empty($work)) {
            $dropbox_person = new Dropbox_Person(
                $_user['user_id'],
                api_is_course_admin(),
                api_is_course_tutor()
            );
            $mailId = get_mail_id_base();
            foreach ($dropbox_person->sentWork as $w) {
                if ($w->title == $dropbox_filename) {
                    if (($w->recipients[0]['id'] > $mailId) xor $thisIsAMailing) {
                        Display::addFlash(Display::return_message(get_lang('MailingNonMailingError'), 'warning'));

                        return false;
                    }
                    if (($w->recipients[0]['id'] == $_user['user_id']) xor $thisIsJustUpload) {
                        Display::addFlash(Display::return_message(get_lang('MailingJustUploadSelectNoOther'), 'warning'));

                        return false;
                    }
                    $dropbox_filename = $w->filename;
                    $found = true; // note: do we still need this?
                    break;
                }
            }
        } else {  // rename file to login_filename_uniqueId format
            $dropbox_filename = $_user['username']."_".$dropbox_filename."_".uniqid('');
        }

        if (isset($file['copy_file']) && $file['copy_file']) {
            @copy($dropbox_filetmpname, api_get_path(SYS_COURSE_PATH).$_course['path'].'/dropbox/'.$dropbox_filename);
            @unlink($dropbox_filetmpname);
        } else {
            @move_uploaded_file(
                $dropbox_filetmpname,
                api_get_path(SYS_COURSE_PATH).$_course['path'].'/dropbox/'.$dropbox_filename
            );
        }
    }

    if (empty($work)) {
        // creating the array that contains all the users who will receive the file
        $new_work_recipients = [];
        foreach ($_POST['recipients'] as $rec) {
            if (strpos($rec, 'user_') === 0) {
                $new_work_recipients[] = substr($rec, strlen('user_'));
            } elseif (strpos($rec, 'group_') === 0) {
                $groupInfo = GroupManager::get_group_properties(substr($rec, strlen('group_')));
                $userList = GroupManager::get_subscribed_users($groupInfo);
                foreach ($userList as $usr) {
                    if (!in_array($usr['user_id'], $new_work_recipients) && $usr['user_id'] != $_user['user_id']) {
                        $new_work_recipients[] = $usr['user_id'];
                    }
                }
            }
        }

        $b_send_mail = api_get_course_setting('email_alert_on_new_doc_dropbox');
        if ($b_send_mail) {
            foreach ($new_work_recipients as $recipient_id) {
                $recipent_temp = api_get_user_info($recipient_id);
                $additionalParameters = [
                    'smsType' => SmsPlugin::NEW_FILE_SHARED_COURSE_BY,
                    'userId' => $recipient_id,
                    'courseTitle' => $_course['title'],
                    'userUsername' => $recipent_temp['username'],
                ];

                $message = get_lang('NewDropboxFileUploadedContent').
                    ' <a href="'.api_get_path(WEB_CODE_PATH).'dropbox/index.php?'.api_get_cidreq().'">'.get_lang('SeeFile').'</a>'.
                    "\n\n".
                    api_get_person_name(
                        $_user['firstName'],
                        $_user['lastName'],
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    )."\n".get_lang('Email')." : ".$_user['mail'];

                MessageManager::send_message_simple(
                    $recipient_id,
                    get_lang('NewDropboxFileUploaded'),
                    $message,
                    $_user['user_id'],
                    false,
                    false,
                    $additionalParameters
                );
            }
        }
    }

    $successMessage = get_lang('FileUploadSucces');
    if ($sendToOtherUsers) {
        $result = true;
        if ('remove_users' == $_POST['option']) {
            foreach ($new_work_recipients as $userId) {
                removeUserDropboxFile($fileId, $userId);
            }
            $successMessage = get_lang('FileRemovedFromSelectedUsers');
        } else {
            foreach ($new_work_recipients as $userId) {
                addDropBoxFileToUser($fileId, $userId);
            }
        }
    } else {
        if (empty($work)) {
            // Create new
            $result = new Dropbox_SentWork(
                $_user['user_id'],
                $dropbox_title,
                isset($_POST['description']) ? $_POST['description'] : '',
                api_get_user_id(),
                $dropbox_filename,
                $dropbox_filesize,
                $new_work_recipients
            );
        } else {
            // Update
            $work->title = $dropbox_title;
            $work->filename = $dropbox_filename;
            $work->filesize = $dropbox_filesize;
            $work->upload_date = api_get_utc_datetime();
            $work->last_upload_date = api_get_utc_datetime();
            $work->description = isset($_POST['description']) ? $_POST['description'] : '';
            $work->uploader_id = api_get_user_id();
            $work->updateFile();
            $result = $work;
        }
    }

    Security::clear_token();
    Display::addFlash(Display::return_message($successMessage));

    return $result;
}

/**
 * It removes a dropbox file of a selected user.
 *
 * @param $fileId
 * @param $userId
 */
function removeUserDropboxFile($fileId, $userId)
{
    $tblDropboxPerson = Database::get_course_table(TABLE_DROPBOX_PERSON);
    $tblDropboxPost = Database::get_course_table(TABLE_DROPBOX_POST);
    $courseId = api_get_course_int_id();
    $sessionId = api_get_session_id();

    $params = [$courseId, $fileId, $userId];
    $result = Database::delete(
        $tblDropboxPerson,
        ['c_id = ? AND file_id = ? AND user_id = ?' => $params]
    );

    $params = [$courseId, $fileId, $userId, $sessionId];
    $result = Database::delete(
        $tblDropboxPost,
        ['c_id = ? AND file_id = ? AND dest_user_id = ? AND session_id = ?' => $params]
    );
}

/**
 * It sends a file to a selected user.
 *
 * @param $fileId
 * @param $userId
 */
function addDropBoxFileToUser($fileId, $userId)
{
    $tblDropboxPerson = Database::get_course_table(TABLE_DROPBOX_PERSON);
    $tblDropboxPost = Database::get_course_table(TABLE_DROPBOX_POST);
    $courseId = api_get_course_int_id();
    $sessionId = api_get_session_id();

    $sql = "SELECT count(file_id) as count
                        FROM $tblDropboxPerson
                        WHERE c_id = $courseId AND file_id = $fileId AND user_id = $userId";
    $rs = Database::query($sql);
    $row = Database::fetch_array($rs);
    if (0 == $row['count']) {
        $params = [
            'c_id' => $courseId,
            'file_id' => $fileId,
            'user_id' => $userId,
        ];
        Database::insert($tblDropboxPerson, $params);
    }

    $sql = "SELECT count(file_id) as count
                        FROM $tblDropboxPost
                        WHERE c_id = $courseId AND file_id = $fileId AND dest_user_id = $userId AND session_id = $sessionId";
    $rs = Database::query($sql);
    $row = Database::fetch_array($rs);
    if (0 == $row['count']) {
        $params = [
            'c_id' => $courseId,
            'file_id' => $fileId,
            'dest_user_id' => $userId,
            'session_id' => $sessionId,
            'feedback_date' => api_get_utc_datetime(),
            'cat_id' => 0,
        ];
        Database::insert($tblDropboxPost, $params);
    }

    // Update item_property table for each recipient
    api_item_property_update(
        api_get_course_info(),
        TOOL_DROPBOX,
        $fileId,
        'DropboxFileAdded',
        api_get_user_id(),
        null,
        $userId
    );
}

/**
 * Transforms the array containing all the feedback into something visually attractive.
 *
 * @param an array containing all the feedback about the given message
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function feedback($array, $url)
{
    $output = null;
    foreach ($array as $value) {
        $output .= format_feedback($value);
    }
    $output .= feedback_form($url);

    return $output;
}

/**
 * This function returns the html code to display the feedback messages on a given dropbox file.
 *
 * @param array $feedback an array that contains all the feedback messages about the given document
 *
 * @return string code
 *
 * @todo add the form for adding new comment (if the other party has not deleted it yet).
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function format_feedback($feedback)
{
    $userInfo = api_get_user_info($feedback['author_user_id']);
    $output = UserManager::getUserProfileLink($userInfo);
    $output .= '&nbsp;&nbsp;'.Display::dateToStringAgoAndLongDate($feedback['feedback_date']).'<br />';
    $output .= '<div style="padding-top:6px">'.nl2br($feedback['feedback']).'</div><hr size="1" noshade/><br />';

    return $output;
}

/**
 * this function returns the code for the form for adding a new feedback message to a dropbox file.
 *
 * @param $url  url string
 *
 * @return string code
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function feedback_form($url)
{
    $return = '<div class="feeback-form">';
    $number_users_who_see_file = check_if_file_exist($_GET['id']);
    if ($number_users_who_see_file) {
        $token = Security::get_token();
        $return .= '<div class="form-group">';
        $return .= '<input type="hidden" name="sec_token" value="'.$token.'"/>';
        $return .= '<label class="col-sm-3 control-label">'.get_lang('AddNewFeedback');
        $return .= '</label>';
        $return .= '<div class="col-sm-6">';
        $return .= '<textarea name="feedback" class="form-control" rows="4"></textarea>';
        $return .= '</div>';
        $return .= '<div class="col-sm-3">';
        $return .= '<div class="pull-right"><a class="btn btn-default btn-sm" href="'.$url.'"><i class="fa fa-times" aria-hidden="true"></i></a></div>';
        $return .= '<button type="submit" class="btn btn-primary btn-sm" name="store_feedback" value="'.get_lang('Ok').'"
                    onclick="javascript: document.form_dropbox.attributes.action.value = document.location;">'.get_lang('AddComment').'</button>';
        $return .= '</div>';
        $return .= '</div>';
        $return .= '</div>';
    } else {
        $return .= get_lang('AllUsersHaveDeletedTheFileAndWillNotSeeFeedback');
    }

    return $return;
}

function user_can_download_file($id, $user_id)
{
    $course_id = api_get_course_int_id();
    $id = (int) $id;
    $user_id = (int) $user_id;

    $sql = "SELECT file_id
            FROM ".Database::get_course_table(TABLE_DROPBOX_PERSON)."
            WHERE c_id = $course_id AND user_id = $user_id AND file_id = ".$id;
    $result = Database::query($sql);
    $number_users_who_see_file = Database::num_rows($result);

    $sql = "SELECT file_id
            FROM ".Database::get_course_table(TABLE_DROPBOX_POST)."
            WHERE c_id = $course_id AND dest_user_id = $user_id AND file_id = ".$id;
    $result = Database::query($sql);
    $count = Database::num_rows($result);

    return $number_users_who_see_file > 0 || $count > 0;
}

// we now check if the other users have not delete this document yet.
// If this is the case then it is useless to see the
// add feedback since the other users will never get to see the feedback.
function check_if_file_exist($id)
{
    $id = (int) $id;
    $course_id = api_get_course_int_id();
    $sql = "SELECT file_id
            FROM ".Database::get_course_table(TABLE_DROPBOX_PERSON)."
            WHERE c_id = $course_id AND file_id = ".$id;
    $result = Database::query($sql);
    $number_users_who_see_file = Database::num_rows($result);

    $sql = "SELECT file_id
            FROM ".Database::get_course_table(TABLE_DROPBOX_POST)."
            WHERE c_id = $course_id AND file_id = ".$id;
    $result = Database::query($sql);
    $count = Database::num_rows($result);

    return $number_users_who_see_file > 0 || $count > 0;
}

/**
 * @return string language string (depending on the success or failure
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function store_feedback()
{
    if (!is_numeric($_GET['id'])) {
        return get_lang('FeedbackError');
    }
    $course_id = api_get_course_int_id();
    if (empty($_POST['feedback'])) {
        return get_lang('PleaseTypeText');
    } else {
        $table = Database::get_course_table(TABLE_DROPBOX_FEEDBACK);
        $params = [
            'c_id' => $course_id,
            'file_id' => $_GET['id'],
            'author_user_id' => api_get_user_id(),
            'feedback' => $_POST['feedback'],
            'feedback_date' => api_get_utc_datetime(),
            'feedback_id' => 0,
        ];

        $id = Database::insert($table, $params);
        if ($id) {
            $sql = "UPDATE $table SET feedback_id = iid WHERE iid = $id";
            Database::query($sql);
        }

        return get_lang('DropboxFeedbackStored');
    }
}

/**
 * This function downloads all the files of the input array into one zip.
 *
 * @param array $fileList containing all the ids of the files that have to be downloaded
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @todo consider removing the check if the user has received or sent this file (zip download of a folder already sufficiently checks for this).
 * @todo integrate some cleanup function that removes zip files that are older than 2 days
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Julio Montoya  Addin c_id support
 *
 * @version march 2006
 */
function zip_download($fileList)
{
    $_course = api_get_course_info();
    $course_id = api_get_course_int_id();
    $fileList = array_map('intval', $fileList);

    // note: we also have to add the check if the user has received or sent this file.
    $sql = "SELECT DISTINCT file.filename, file.title, file.author, file.description
            FROM ".Database::get_course_table(TABLE_DROPBOX_FILE)." file
            INNER JOIN ".Database::get_course_table(TABLE_DROPBOX_PERSON)." person
            ON (person.file_id=file.id AND file.c_id = $course_id AND person.c_id = $course_id)
            INNER JOIN ".Database::get_course_table(TABLE_DROPBOX_POST)." post
            ON (post.file_id = file.id AND post.c_id = $course_id AND file.c_id = $course_id)
            WHERE
                file.id IN (".implode(', ', $fileList).") AND
                file.id = person.file_id AND
                (
                    person.user_id = '".api_get_user_id()."' OR
                    post.dest_user_id = '".api_get_user_id()."'
                ) ";
    $result = Database::query($sql);

    $files = [];
    while ($row = Database::fetch_array($result)) {
        $files[$row['filename']] = [
            'filename' => $row['filename'],
            'title' => $row['title'],
            'author' => $row['author'],
            'description' => $row['description'],
        ];
    }

    // Step 3: create the zip file and add all the files to it
    $temp_zip_file = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().".zip";
    Session::write('dropbox_files_to_download', $files);
    $zip = new PclZip($temp_zip_file);
    foreach ($files as $value) {
        $zip->add(
            api_get_path(SYS_COURSE_PATH).$_course['path'].'/dropbox/'.$value['filename'],
            PCLZIP_OPT_REMOVE_ALL_PATH,
            PCLZIP_CB_PRE_ADD,
            'my_pre_add_callback'
        );
    }
    Session::erase('dropbox_files_to_download');
    $name = 'dropbox-'.api_get_utc_datetime().'.zip';
    $result = DocumentManager::file_send_for_download($temp_zip_file, true, $name);
    if ($result === false) {
        api_not_allowed(true);
    }
    @unlink($temp_zip_file);
    exit;
}

/**
 * This is a callback function to decrypt the files in the zip file
 * to their normal filename (as stored in the database).
 *
 * @param array $p_event  a variable of PCLZip
 * @param array $p_header a variable of PCLZip
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function my_pre_add_callback($p_event, &$p_header)
{
    $files = Session::read('dropbox_files_to_download');
    $p_header['stored_filename'] = $files[$p_header['stored_filename']]['title'];

    return 1;
}

/**
 * @desc Generates the contents of a html file that gives an overview of all the files in the zip file.
 *       This is to know the information of the files that are inside the zip file (who send it, the comment, ...)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, March 2006
 * @author Ivan Tcholakov, 2010, code for html metadata has been added.
 */
function generate_html_overview($files, $dont_show_columns = [], $make_link = [])
{
    $return = '<!DOCTYPE html'."\n";
    $return .= "\t".'PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'."\n";
    $return .= "\t".'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
    $return .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.api_get_language_isocode().'" lang="'.api_get_language_isocode().'">'."\n";

    $return .= "<head>\n\t<title>".get_lang('OverviewOfFilesInThisZip')."</title>\n";
    $return .= "\t".'<meta http-equiv="Content-Type" content="text/html; charset='.api_get_system_encoding().'" />'."\n";
    $return .= "</head>\n\n";
    $return .= '<body dir="'.api_get_text_direction().'">'."\n\n";
    $return .= "<table border=\"1px\">\n";

    $counter = 0;
    foreach ($files as $value) {
        // Adding the header.
        if ($counter == 0) {
            $columns_array = array_keys($value);
            $return .= "\n<tr>";
            foreach ($columns_array as $columns_array_key => $columns_array_value) {
                if (!in_array($columns_array_value, $dont_show_columns)) {
                    $return .= "\n\t<th>".$columns_array_value."</th>";
                }
                $column[] = $columns_array_value;
            }
            $return .= "\n</tr>\n";
        }
        $counter++;

        // Adding the content.
        $return .= "\n<tr>";
        foreach ($column as $column_key => $column_value) {
            if (!in_array($column_value, $dont_show_columns)) {
                $return .= "\n\t<td>";
                if (in_array($column_value, $make_link)) {
                    $return .= '<a href="'.$value[$column_value].'">'.$value[$column_value].'</a>';
                } else {
                    $return .= $value[$column_value];
                }
                $return .= "</td>";
            }
        }
        $return .= "\n</tr>\n";
    }
    $return .= "\n</table>\n\n</body>";
    $return .= "\n</html>";

    return $return;
}

/**
 * @desc This function retrieves the number of feedback messages on every
 * document. This function might become obsolete when
 *       the feedback becomes user individual.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function get_total_number_feedback()
{
    $course_id = api_get_course_int_id();
    $sql = "SELECT COUNT(feedback_id) AS total, file_id
            FROM ".Database::get_course_table(TABLE_DROPBOX_FEEDBACK)."
            WHERE c_id = $course_id
            GROUP BY file_id";
    $result = Database::query($sql);
    $return = [];
    while ($row = Database::fetch_array($result)) {
        $return[$row['file_id']] = $row['total'];
    }

    return $return;
}

/**
 * @desc this function checks if the key exists. If this is the case
 * it returns the value, if not it returns 0
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 */
function check_number_feedback($key, $array)
{
    if (is_array($array)) {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        } else {
            return 0;
        }
    } else {
        return 0;
    }
}

/**
 * Get the last access to a given tool of a given user.
 *
 * @param $tool string the tool constant
 * @param $courseId the course_id
 * @param $user_id the id of the user
 *
 * @return string last tool access date
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version march 2006
 *
 * @todo consider moving this function to a more appropriate place.
 */
function get_last_tool_access($tool, $courseId = null, $user_id = null)
{
    // The default values of the parameters
    if (empty($courseId)) {
        $courseId = api_get_course_int_id();
    }
    if (empty($user_id)) {
        $user_id = api_get_user_id();
    }

    // the table where the last tool access is stored (=track_e_lastaccess)
    $table_last_access = Database::get_main_table('track_e_lastaccess');

    $sql = "SELECT access_date FROM $table_last_access
            WHERE
                access_user_id = ".intval($user_id)." AND
                c_id='".intval($courseId)."' AND
                access_tool='".Database::escape_string($tool)."'
                ORDER BY access_date DESC
                LIMIT 1";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);

    return $row['access_date'];
}
/**
 * Previously $dropbox_cnf['mailingIdBase'], returns a mailing ID to generate a mail ID.
 *
 * @return int
 */
function get_mail_id_base()
{
    // false = no mailing functionality
    //$dropbox_cnf['mailingIdBase'] = 10000000;  // bigger than any user_id,
    // allowing enough space for pseudo_ids as uploader_id, dest_user_id, user_id:
    // mailing pseudo_id = dropbox_cnf('mailingIdBase') + mailing id
    return 10000000;
}
