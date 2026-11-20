<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackELastaccess;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDropboxCategory;
use Chamilo\CourseBundle\Entity\CDropboxFeedback;
use Chamilo\CourseBundle\Entity\CDropboxFile;
use Chamilo\CourseBundle\Entity\CDropboxPerson;
use Chamilo\CourseBundle\Entity\CDropboxPost;
use ChamiloSession as Session;
use Doctrine\ORM\QueryBuilder;

/**
 * This file contains additional dropbox functions. Initially there were some
 * functions in the init files also but I have moved them over
 * to one file -- Patrick Cool <patrick.cool@UGent.be>, Ghent University.
 *
 * The dropbox tool is served by the Vue views (assets/vue/views/dropbox) and the API; this legacy code still
 * uses removed columns and the undefined SYS_COURSE_PATH, but it is still included by dropbox_init.inc.php.
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
    if ('delete_received' == $_POST['action'] || 'download_received' == $_POST['action']) {
        $part = 'received';
    } elseif ('delete_sent' == $_POST['action'] || 'download_sent' == $_POST['action']) {
        $part = 'sent';
    }

    // STEP 2: at least one file has to be selected. If not we return an error message
    $ids = isset($_GET['id']) ? $_GET['id'] : [];
    if (count($ids) > 0) {
        $checked_file_ids = $_POST['id'];
    } else {
        foreach ($_POST as $key => $value) {
            if (strstr($value, $part.'_') && 'view_received_category' != $key && 'view_sent_category' != $key) {
                $checked_files = true;
                $checked_file_ids[] = intval(substr($value, strrpos($value, '_')));
            }
        }
    }
    $checked_file_ids = $_POST['id'];

    if (!is_array($checked_file_ids) || 0 == count($checked_file_ids)) {
        return get_lang('Check at least one file.');
    }

    // Deleting
    if ('delete_received' == $_POST['action'] || 'delete_sent' == $_POST['action']) {
        $dropboxfile = new Dropbox_Person($_user['user_id'], $is_courseAdmin, $is_courseTutor);
        foreach ($checked_file_ids as $key => $value) {
            if ('received' == $_GET['view']) {
                $dropboxfile->deleteReceivedWork($value);
                $message = get_lang('The received file has been deleted.');
            }
            if ('sent' == $_GET['view'] || empty($_GET['view'])) {
                $dropboxfile->deleteSentWork($value);
                $message = get_lang('The sent file has been deleted.');
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

        return get_lang('The selected files have been moved.');
    }

    // STEP 3D: downloading
    if ('download_sent' == $_POST['action'] || 'download_received' == $_POST['action']) {
        return zip_download($checked_file_ids);
    }
}

/**
 * Every column of the dropbox category table, aliased to the snake_case keys the callers read.
 */
function dropboxCategoryRowsQueryBuilder(): QueryBuilder
{
    return Container::getEntityManager()->createQueryBuilder()
        ->select(
            'c.iid AS iid',
            'c.cId AS c_id',
            'c.catId AS cat_id',
            'c.title AS title',
            'c.received AS received',
            'c.sent AS sent',
            'c.userId AS user_id',
            'c.sessionId AS session_id'
        )
        ->from(CDropboxCategory::class, 'c')
    ;
}

/**
 * The raw table row used to come back with integer flags.
 */
function dropboxToLegacyCategoryRow(array $row): array
{
    foreach (['iid', 'c_id', 'cat_id', 'received', 'sent', 'user_id', 'session_id'] as $key) {
        $row[$key] = (int) $row[$key];
    }

    return $row;
}

/**
 * Database::fetch_array() style row: the named keys followed by the same values under numeric keys.
 */
function dropboxWithNumericKeys(array $row): array
{
    foreach ($row as $value) {
        $row[] = $value;
    }

    return $row;
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
    if (empty($cat)) {
        return false;
    }

    if ($cat['user_id'] != $user_id && !api_is_platform_admin($user_id)) {
        return false;
    }

    // an additional check that might not be necessary
    if ('deletereceivedcategory' == $action) {
        $sentreceived = 'received';
        $id_field = 'file_id';
        $return_message = get_lang('The folder has been deleted');
    } elseif ('deletesentcategory' == $action) {
        $sentreceived = 'sent';
        $id_field = 'iid';
        $return_message = get_lang('The folder has been deleted');
    } else {
        return get_lang('Error');
    }

    // step 1: delete the category
    // $sentreceived is one of the two literals set above: only those two columns can be filtered on.
    $kindCondition = 'received' === $sentreceived ? 'c.received = true' : 'c.sent = true';
    $em = Container::getEntityManager();
    $em->createQuery(
        'DELETE FROM '.CDropboxCategory::class.' c WHERE c.cId = :courseId AND c.catId = :catId AND '.$kindCondition
    )
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('catId', (int) $id)
        ->execute()
    ;

    // step 2: delete all the documents in this category
    if ('deletereceivedcategory' == $action) {
        $entries = $em->createQueryBuilder()
            ->select('e.fileId AS file_id')
            ->from(CDropboxPost::class, 'e')
        ;
    } else {
        $entries = $em->createQueryBuilder()
            ->select('e.iid AS iid')
            ->from(CDropboxFile::class, 'e')
        ;
    }
    $entries = $entries
        ->where('e.cId = :courseId')
        ->andWhere('e.catId = :catId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('catId', (int) $id)
        ->getQuery()
        ->getArrayResult()
    ;

    foreach ($entries as $row) {
        $dropboxfile = new Dropbox_Person($user_id, $is_courseAdmin, $is_courseTutor);
        if ('deletereceivedcategory' == $action) {
            $dropboxfile->deleteReceivedWork($row[$id_field]);
        }
        if ('deletesentcategory' == $action) {
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
    $target = [],
    $extra_params = [],
    $viewReceivedCategory,
    $viewSentCategory,
    $view
) {
    $form = new FormValidator(
        'form1',
        'post',
        api_get_self().'?view_received_category='.$viewReceivedCategory.'&view_sent_category='.$viewSentCategory.'&view='.$view.'&'.$extra_params
    );
    $form->addElement('header', get_lang('Move file to'));
    $form->addElement('hidden', 'id', intval($id));
    $form->addElement('hidden', 'part', Security::remove_XSS($part));

    $options = ['0' => get_lang('root')];
    foreach ($target as $category) {
        $options[$category['cat_id']] = $category['title'];
    }
    $form->addSelect('move_target', get_lang('Move file to'), $options);
    $form->addButtonMove(get_lang('Move the file'), 'do_move');
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

    if ((isset($id) && '' != $id) &&
        (isset($target) && '' != $target) &&
        (isset($part) && '' != $part)
    ) {
        if ('received' == $part) {
            Container::getEntityManager()
                ->createQuery(
                    'UPDATE '.CDropboxPost::class.' p SET p.catId = :target
                     WHERE p.cId = :courseId AND p.destUserId = :userId AND p.fileId = :fileId'
                )
                ->setParameter('target', (int) $target)
                ->setParameter('courseId', (int) $course_id)
                ->setParameter('userId', (int) $_user['user_id'])
                ->setParameter('fileId', (int) $id)
                ->execute()
            ;
            $return_message = get_lang('The received file has been moved.');
        }
        if ('sent' == $part) {
            Container::getEntityManager()
                ->createQuery(
                    'UPDATE '.CDropboxFile::class.' f SET f.catId = :target
                     WHERE f.cId = :courseId AND f.uploaderId = :userId AND f.iid = :fileId'
                )
                ->setParameter('target', (int) $target)
                ->setParameter('courseId', (int) $course_id)
                ->setParameter('userId', (int) $_user['user_id'])
                ->setParameter('fileId', (int) $id)
                ->execute()
            ;
            $return_message = get_lang('The sent file has been moved');
        }
    } else {
        $return_message = get_lang('The file(s) can not be moved.');
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

    $session_id = (int) api_get_session_id();

    $qb = dropboxCategoryRowsQueryBuilder()
        ->where('c.cId = :courseId')
        ->andWhere('c.userId = :userId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('userId', (int) $_user['user_id'])
    ;
    // Same condition as api_get_session_condition(): the base course also matches the rows without a session.
    if (empty($session_id)) {
        $qb->andWhere('(c.sessionId = 0 OR c.sessionId IS NULL)');
    } else {
        $qb->andWhere('c.sessionId = :sessionId')->setParameter('sessionId', $session_id);
    }

    foreach ($qb->getQuery()->getArrayResult() as $row) {
        $row = dropboxWithNumericKeys(dropboxToLegacyCategoryRow($row));
        if (('sent' == $filter && 1 == $row['sent']) ||
            ('received' == $filter && 1 == $row['received']) || '' == $filter
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

    $row = dropboxCategoryRowsQueryBuilder()
        ->where('c.cId = :courseId')
        ->andWhere('c.catId = :catId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('catId', $id)
        ->setMaxResults(1)
        ->getQuery()
        ->getArrayResult()[0] ?? null;

    // Database::fetch_assoc() semantics: false when there is no such category.
    return null === $row ? false : dropboxToLegacyCategoryRow($row);
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
    if ('sent' == $_POST['target']) {
        $sent = 1;
        $received = 0;
    } elseif ('received' == $_POST['target']) {
        $sent = 0;
        $received = 1;
    } else {
        return ['type' => 'error', 'message' => get_lang('Error')];
    }

    // check if the category name is valid
    if ('' == $_POST['category_name']) {
        return ['type' => 'error', 'message' => get_lang('Please give a category name')];
    }

    if (!isset($_POST['edit_id'])) {
        $session_id = api_get_session_id();
        // step 3a, we check if the category doesn't already exist
        $existing = (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(c.iid)')
            ->from(CDropboxCategory::class, 'c')
            ->where('c.cId = :courseId')
            ->andWhere('c.userId = :userId')
            ->andWhere('c.title = :title')
            ->andWhere('c.received = :received')
            ->andWhere('c.sent = :sent')
            ->andWhere('c.sessionId = :sessionId')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('userId', (int) $_user['user_id'])
            ->setParameter('title', (string) $_POST['category_name'])
            ->setParameter('received', (bool) $received)
            ->setParameter('sent', (bool) $sent)
            ->setParameter('sessionId', (int) $session_id)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        // step 3b, we add the category if it does not exist yet.
        if (0 === $existing) {
            $params = [
                'cat_id' => 0,
                'c_id' => $course_id,
                'title' => $_POST['category_name'],
                'received' => $received,
                'sent' => $sent,
                'user_id' => $_user['user_id'],
                'session_id' => $session_id,
            ];
            $id = Database::insert(Database::get_course_table(TABLE_DROPBOX_CATEGORY), $params);
            if ($id) {
                Container::getEntityManager()
                    ->createQuery('UPDATE '.CDropboxCategory::class.' c SET c.catId = c.iid WHERE c.iid = :iid')
                    ->setParameter('iid', (int) $id)
                    ->execute()
                ;
            }

            return ['type' => 'confirmation', 'message' => get_lang('The folder has been created')];
        } else {
            return ['type' => 'error', 'message' => get_lang('This category already exists, please use a different name')];
        }
    } else {
        $params = [
            'title' => $_POST['category_name'],
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

        return ['type' => 'confirmation', 'message' => get_lang('The category has been modified.')];
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
    $title = get_lang('Add a new folder');

    $id = (int) $id;

    if (!empty($id)) {
        // retrieve the category we are editing
        $row = dropboxCategoryRowsQueryBuilder()
            ->where('c.cId = :courseId')
            ->andWhere('c.catId = :catId')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('catId', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()[0] ?? null;
        // Database::fetch_array() semantics: an empty array when there is no such category.
        $row = null === $row ? [] : dropboxWithNumericKeys(dropboxToLegacyCategoryRow($row));

        if (empty($category_name)) {
            // after an edit with an error we do not want to return to the
            // original name but the name we already modified.
            // (happens when createinrecievedfiles AND createinsentfiles are not checked)
            $category_name = $row['title'];
        }
        if ('1' == $row['received']) {
            $target = 'received';
        }
        if ('1' == $row['sent']) {
            $target = 'sent';
        }
        $title = get_lang('Edit this category');
    }

    if ('addreceivedcategory' == $action) {
        $target = 'received';
    }
    if ('addsentcategory' == $action) {
        $target = 'sent';
    }

    if ('editcategory' == $action) {
        $text = get_lang('Edit category');
    } elseif ('addreceivedcategory' == $action || 'addsentcategory' == $action) {
        $text = get_lang('Create category');
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

    $form->addElement('text', 'category_name', get_lang('Category name'));
    $form->addRule('category_name', get_lang('Required field'), 'required');
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
function display_add_form($viewReceivedCategory, $viewSentCategory, $view, $id = 0)
{
    $course_info = api_get_course_info();
    $course = api_get_course_entity();
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

    $form->addElement('header', get_lang('Share a new file'));
    $maxFileSize = api_get_setting('dropbox_max_filesize');
    $form->addElement('hidden', 'MAX_FILE_SIZE', $maxFileSize);
    $form->addElement('hidden', 'sec_token', $token);
    $form->addElement('hidden', 'origin', $origin);
    $form->addElement(
        'file',
        'file',
        get_lang('File upload'),
        ['onChange' => 'javascript: checkfile(this.value);']
    );

    $allowOverwrite = api_get_setting('dropbox_allow_overwrite');
    if ('true' == $allowOverwrite && empty($idCondition)) {
        $form->addElement(
            'checkbox',
            'cb_overwrite',
            null,
            get_lang('Overwrite previous versions of same document?'),
            ['id' => 'cb_overwrite']
        );
    }

    // List of all users in this course and all virtual courses combined with it
    if (api_get_session_id()) {
        $complete_user_list_for_dropbox = [];
        if ('true' == api_get_setting('dropbox_allow_student_to_student') || STUDENT != $_user['status']) {
            $complete_user_list_for_dropbox = CourseManager:: get_user_list_from_course_code(
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
            if ('general_coach' == $coach['type']) {
                $generalCoachList[] = $coach;
            } else {
                $courseCoachList[] = $coach;
            }
        }

        $hideCourseCoach = api_get_setting('dropbox_hide_course_coach');
        if ('false' == $hideCourseCoach) {
            $complete_user_list_for_dropbox = array_merge(
                $complete_user_list_for_dropbox,
                $courseCoachList
            );
        }
        $hideGeneralCoach = api_get_setting('dropbox_hide_general_coach');

        if ('false' == $hideGeneralCoach) {
            $complete_user_list_for_dropbox = array_merge(
                $complete_user_list_for_dropbox,
                $generalCoachList
            );
        }
    } else {
        if ('true' == api_get_setting('dropbox_allow_student_to_student') || STUDENT != $_user['status']) {
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
    $userGroup = new UserGroupModel();
    foreach ($complete_user_list_for_dropbox as $current_user) {
        if ((
            $dropbox_person->isCourseTutor
                || $dropbox_person->isCourseAdmin
                || 'true' == $allowStudentToStudent
                || 5 != $current_user['status']                         // Always allow teachers.
                || 1 == $current_user['is_tutor']                       // Always allow tutors.
                ) && $current_user['user_id'] != $_user['user_id']) {   // Don't include yourself.
            if ($current_user['user_id'] == $current_user_id) {
                continue;
            }
            $userId = $current_user['user_id'];
            $userInfo = api_get_user_info($userId);
            if (INVITEE != $userInfo['status']) {
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
        && 'true' == $allowGroups || 'true' === $allowStudentToStudent
    ) {
        $complete_group_list_for_dropbox = GroupManager::get_group_list(null, $course);

        if (count($complete_group_list_for_dropbox) > 0) {
            foreach ($complete_group_list_for_dropbox as $current_group) {
                if ($current_group['number_of_members'] > 0) {
                    $options['group_'.$current_group['iid']] = 'G: '.$current_group['name'].' - '.$current_group['number_of_members'].' '.get_lang('Users');
                }
            }
        }
    }

    $allowUpload = api_get_setting('dropbox_allow_just_upload');
    if ('true' == $allowUpload) {
        $options['user_'.$_user['user_id']] = get_lang('---Just upload---');
    }

    if (empty($idCondition)) {
        $form->addSelect(
            'recipients',
            get_lang('Send to'),
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
            get_lang('Send to'),
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
}

/**
 * Checks if there are files in the dropbox_file table that aren't used anymore in dropbox_person table.
 * If there are, all entries concerning the file are deleted from the db + the file is deleted from the server.
 */
function removeUnusedFiles()
{
    $_course = api_get_course_info();
    $course_id = $_course['real_id'];
    $em = Container::getEntityManager();

    // select all files that aren't referenced anymore
    $unused = $em->createQueryBuilder()
        ->select('f.iid AS iid', 'f.filename AS filename')
        ->distinct()
        ->from(CDropboxFile::class, 'f')
        ->leftJoin(CDropboxPerson::class, 'p', 'WITH', 'f.iid = p.fileId')
        ->where('p.userId IS NULL')
        ->andWhere('f.cId = :courseId')
        ->setParameter('courseId', (int) $course_id)
        ->getQuery()
        ->getArrayResult()
    ;
    foreach ($unused as $res) {
        //delete the selected files from the post and file tables
        $em->createQuery('DELETE FROM '.CDropboxPost::class.' p WHERE p.cId = :courseId AND p.fileId = :fileId')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('fileId', (int) $res['iid'])
            ->execute()
        ;
        $em->createQuery('DELETE FROM '.CDropboxFile::class.' f WHERE f.cId = :courseId AND f.iid = :fileId')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('fileId', (int) $res['iid'])
            ->execute()
        ;
        //delete file from server
        // SYS_COURSE_PATH no longer exists (course files moved to resource nodes): nothing to remove from disk.
        if (defined('SYS_COURSE_PATH')) {
            @unlink(api_get_path(SYS_COURSE_PATH).$_course['path'].'/dropbox/'.$res['filename']);
        }
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
    $res = Container::getEntityManager()->createQueryBuilder()
        ->select('f.uploaderId AS uploader_id')
        ->from(CDropboxFile::class, 'f')
        ->leftJoin(CDropboxPost::class, 'p', 'WITH', 'f.iid = p.fileId AND f.cId = :courseId AND p.cId = :courseId')
        ->where('p.destUserId = :mailingPseudoId')
        ->andWhere('p.cId = :courseId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('mailingPseudoId', $mailingPseudoId)
        ->setMaxResults(1)
        ->getQuery()
        ->getArrayResult()[0] ?? null;

    if (null === $res) {
        exit(get_lang('An error has occurred. Please contact your system administrator.').' (code 901)');
    }
    if (0 == $owner) {
        return $res['uploader_id'];
    }
    if ($res['uploader_id'] == $owner) {
        return true;
    }
    exit(get_lang('An error has occurred. Please contact your system administrator.').' (code '.$or_die.')');
}

/**
 * @author René Haentjens, Ghent University
 *
 * @todo check if this function is still necessary.
 */
function removeMoreIfMailing($file_id)
{
    $course_id = api_get_course_int_id();
    // when deleting a mailing zip-file (posted to mailingPseudoId):
    // 1. the detail window is no longer reachable, so
    //    for all content files, delete mailingPseudoId from person-table
    // 2. finding the owner (getUserOwningThisMailing) is no longer possible, so
    //    for all content files, replace mailingPseudoId by owner as uploader
    $file_id = (int) $file_id;
    $em = Container::getEntityManager();
    $res = $em->createQueryBuilder()
        ->select('p.destUserId AS dest_user_id')
        ->from(CDropboxPost::class, 'p')
        ->where('p.cId = :courseId')
        ->andWhere('p.fileId = :fileId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('fileId', $file_id)
        ->setMaxResults(1)
        ->getQuery()
        ->getArrayResult()[0] ?? null;

    if (null !== $res) {
        $mailingPseudoId = $res['dest_user_id'];
        $mailId = get_mail_id_base();
        if ($mailingPseudoId > $mailId) {
            $em->createQuery('DELETE FROM '.CDropboxPerson::class.' p WHERE p.cId = :courseId AND p.userId = :userId')
                ->setParameter('courseId', (int) $course_id)
                ->setParameter('userId', (int) $mailingPseudoId)
                ->execute()
            ;

            $em->createQuery(
                'UPDATE '.CDropboxFile::class.' f SET f.uploaderId = :newUploader
                 WHERE f.cId = :courseId AND f.uploaderId = :oldUploader'
            )
                ->setParameter('newUploader', (int) api_get_user_id())
                ->setParameter('courseId', (int) $course_id)
                ->setParameter('oldUploader', (int) $mailingPseudoId)
                ->execute()
            ;
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
            return get_lang('You must select at least one destinee');
        } else {
            // Check if all the recipients are valid
            $thisIsAMailing = false;
            $thisIsJustUpload = false;

            foreach ($_POST['recipients'] as $rec) {
                if ('mailing' == $rec) {
                    $thisIsAMailing = true;
                } elseif ('upload' == $rec) {
                    $thisIsJustUpload = true;
                } elseif (0 === strpos($rec, 'user_') &&
                    !CourseManager::is_user_subscribed_in_course(
                        substr($rec, strlen('user_')),
                        $_course['code'],
                        true
                    )
                ) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('Invalid user detected.'),
                            'warning'
                        )
                    );

                    return false;
                } elseif (0 !== strpos($rec, 'group_') && 0 !== strpos($rec, 'user_')) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('Invalid group detected.'),
                            'warning'
                        )
                    );

                    return false;
                }
            }
        }

        // we are doing a mailing but an additional recipient is selected
        if ($thisIsAMailing && (1 != count($_POST['recipients']))) {
            Display::addFlash(
                Display::return_message(
                    get_lang('Mailing cannot be combined with other recipients'),
                    'warning'
                )
            );

            return false;
        }

        // we are doing a just upload but an additional recipient is selected.
        // note: why can't this be valid? It is like sending a document to
        // yourself AND to a different person (I do this quite often with my e-mails)
        if ($thisIsJustUpload && (1 != count($_POST['recipients']))) {
            Display::addFlash(
                Display::return_message(
                    get_lang('Just Upload cannot be combined with other recipients'),
                    'warning'
                )
            );

            return false;
        }
    }

    if (empty($file['name'])) {
        Display::addFlash(Display::return_message(get_lang('You didn\'t specify a file to upload.'), 'warning'));

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
    $maxFileSize = api_get_setting('dropbox_max_filesize');
    if ($dropbox_filesize <= 0 || $dropbox_filesize > $maxFileSize) {
        Display::addFlash(Display::return_message(get_lang('This file\'s volume is too big.'), 'warning'));

        return false;
    }

    // The legacy upload path cannot safely serialize My Files/Dropbox quota writes.
    // When aggregate quotas are enabled, use the transactional Symfony uploader.
    // With both settings at 0, preserve the original legacy behavior.
    if ((int) api_get_setting('dropbox.dropbox_user_quota_mb') > 0
        || (int) api_get_setting('dropbox.dropbox_course_quota_mb') > 0) {
        Display::addFlash(Display::return_message(
            get_lang('Dropbox storage quotas are enabled. Please upload files from the new Dropbox interface.'),
            'warning'
        ));

        return false;
    }

    // check if the file is actually uploaded
    if (!is_uploaded_file($dropbox_filetmpname)) { // check user fraud : no clean error msg.
        Display::addFlash(Display::return_message(get_lang('The file is not uploaded.'), 'warning'));

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
                get_lang('File upload failed: this file extension or file type is prohibited'),
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
                    Display::addFlash(Display::return_message(get_lang('Mailing cannot be overwritten by non-mailing and vice-versa'), 'warning'));

                    return false;
                }
                if (($w->recipients[0]['id'] == $_user['user_id']) xor $thisIsJustUpload) {
                    Display::addFlash(Display::return_message(get_lang('Just Upload cannot be combined with other recipients'), 'warning'));

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

    if (empty($work)) {
        // creating the array that contains all the users who will receive the file
        $new_work_recipients = [];
        foreach ($_POST['recipients'] as $rec) {
            if (0 === strpos($rec, 'user_')) {
                $new_work_recipients[] = substr($rec, strlen('user_'));
            } elseif (0 === strpos($rec, 'group_')) {
                $group = api_get_group_entity(substr($rec, strlen('group_')));
                $userList = GroupManager::get_subscribed_users($group);
                foreach ($userList as $usr) {
                    if (!in_array($usr['user_id'], $new_work_recipients) && $usr['user_id'] != $_user['user_id']) {
                        $new_work_recipients[] = $usr['user_id'];
                    }
                }
            }
        }
    }

    @move_uploaded_file(
        $dropbox_filetmpname,
        api_get_path(SYS_COURSE_PATH).$_course['path'].'/dropbox/'.$dropbox_filename
    );

    $b_send_mail = api_get_course_setting('email_alert_on_new_doc_dropbox');

    if ($b_send_mail && empty($work)) {
        foreach ($new_work_recipients as $recipient_id) {
            $recipent_temp = api_get_user_info($recipient_id);
            api_mail_html(
                api_get_person_name(
                    $recipent_temp['firstname'].' '.$recipent_temp['lastname'],
                    null,
                    PERSON_NAME_EMAIL_ADDRESS
                ),
                $recipent_temp['email'],
                get_lang('A new file has been sent in the dropbox'),
                get_lang('A new file has been sent to the Dropbox').' <a href="'.api_get_path(WEB_CODE_PATH).'dropbox/index.php?'.api_get_cidreq().'">'.get_lang('See file').'</a>'.
                "\n\n".
                api_get_person_name(
                    $_user['firstName'],
                    $_user['lastName'],
                    null,
                    PERSON_NAME_EMAIL_ADDRESS
                )."\n".get_lang('E-mail')." : ".$_user['mail']
            );
        }
    }

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

    Security::clear_token();
    Display::addFlash(Display::return_message(get_lang('The file has successfully been uploaded.')));

    return $result;
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
        $return .= '<label class="col-sm-3 control-label">'.get_lang('Add feedback');
        $return .= '</label>';
        $return .= '<div class="col-sm-6">';
        $return .= '<textarea name="feedback" class="form-control" rows="4"></textarea>';
        $return .= '</div>';
        $return .= '<div class="col-sm-3">';
        $return .= '<div class="pull-right"><a class="btn btn--plain btn-sm" href="'.$url.'"><i class="fa fa-times" aria-hidden="true"></i></a></div>';
        $return .= '<button type="submit" class="btn btn--primary btn-sm" name="store_feedback" value="'.get_lang('Validate').'"
                    onclick="javascript: document.form_dropbox.attributes.action.value = document.location;">'.get_lang('Add/Edit a comment to').'</button>';
        $return .= '</div>';
        $return .= '</div>';
        $return .= '</div>';
    } else {
        $return .= get_lang('All users have deleted the file so nobody will see the feedback you are adding.');
    }

    return $return;
}

function user_can_download_file($id, $user_id)
{
    $course_id = api_get_course_int_id();
    $id = (int) $id;
    $user_id = (int) $user_id;
    $em = Container::getEntityManager();

    $number_users_who_see_file = (int) $em->createQueryBuilder()
        ->select('COUNT(p.iid)')
        ->from(CDropboxPerson::class, 'p')
        ->where('p.cId = :courseId')
        ->andWhere('p.userId = :userId')
        ->andWhere('p.fileId = :fileId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('userId', $user_id)
        ->setParameter('fileId', $id)
        ->getQuery()
        ->getSingleScalarResult()
    ;

    $count = (int) $em->createQueryBuilder()
        ->select('COUNT(p.iid)')
        ->from(CDropboxPost::class, 'p')
        ->where('p.cId = :courseId')
        ->andWhere('p.destUserId = :userId')
        ->andWhere('p.fileId = :fileId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('userId', $user_id)
        ->setParameter('fileId', $id)
        ->getQuery()
        ->getSingleScalarResult()
    ;

    return $number_users_who_see_file > 0 || $count > 0;
}

// we now check if the other users have not delete this document yet.
// If this is the case then it is useless to see the
// add feedback since the other users will never get to see the feedback.
function check_if_file_exist($id)
{
    $id = (int) $id;
    $course_id = api_get_course_int_id();
    $em = Container::getEntityManager();

    $number_users_who_see_file = (int) $em->createQueryBuilder()
        ->select('COUNT(p.iid)')
        ->from(CDropboxPerson::class, 'p')
        ->where('p.cId = :courseId')
        ->andWhere('p.fileId = :fileId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('fileId', $id)
        ->getQuery()
        ->getSingleScalarResult()
    ;

    $count = (int) $em->createQueryBuilder()
        ->select('COUNT(p.iid)')
        ->from(CDropboxPost::class, 'p')
        ->where('p.cId = :courseId')
        ->andWhere('p.fileId = :fileId')
        ->setParameter('courseId', (int) $course_id)
        ->setParameter('fileId', $id)
        ->getQuery()
        ->getSingleScalarResult()
    ;

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
        return get_lang('Feedback error');
    }
    $course_id = api_get_course_int_id();
    if (empty($_POST['feedback'])) {
        return get_lang('Please type some text.');
    } elseif (!user_can_download_file((int) $_GET['id'], api_get_user_id())) {
        // The id comes from the request: only a file sent or received by the user, in this course, takes feedback.
        return get_lang('Feedback error');
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
            Container::getEntityManager()
                ->createQuery('UPDATE '.CDropboxFeedback::class.' f SET f.feedbackId = f.iid WHERE f.iid = :iid')
                ->setParameter('iid', (int) $id)
                ->execute()
            ;
        }

        return get_lang('The feedback message has been stored');
    }
}

/**
 * The files of the current course, among the given ids, that the current user sent or received, keyed by their
 * stored filename. This is what zip_download() puts into the archive.
 *
 * @param array $fileList ids of the c_dropbox_file rows
 */
function dropbox_files_to_zip(array $fileList): array
{
    $course_id = api_get_course_int_id();
    $fileList = array_map('intval', $fileList);

    // note: we also have to add the check if the user has received or sent this file.
    $userId = (int) api_get_user_id();
    $rows = [];
    if (!empty($fileList)) {
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('DISTINCT f.filename AS filename', 'f.title AS title', 'f.author AS author', 'f.description AS description')
            ->from(CDropboxFile::class, 'f')
            ->innerJoin(CDropboxPerson::class, 'person', 'WITH', 'person.fileId = f.iid AND person.cId = :courseId')
            ->innerJoin(CDropboxPost::class, 'post', 'WITH', 'post.fileId = f.iid AND post.cId = :courseId')
            ->where('f.cId = :courseId')
            ->andWhere('f.iid IN (:fileIds)')
            ->andWhere('person.userId = :userId OR post.destUserId = :userId')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('fileIds', $fileList)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult()
        ;
    }

    $files = [];
    foreach ($rows as $row) {
        $files[$row['filename']] = [
            'filename' => $row['filename'],
            'title' => $row['title'],
            'author' => $row['author'],
            'description' => $row['description'],
        ];
    }

    return $files;
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
function dropbox_build_zip(array $fileList): ?string
{
    $files = dropbox_files_to_zip($fileList);
    if (empty($files)) {
        return null;
    }

    $em = Container::getEntityManager();
    $resourceNodeRepository = Container::getResourceNodeRepository();
    $courseId = (int) api_get_course_int_id();

    $temp_zip_file = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().'.zip';
    $zip = new ZipArchive();
    if (true !== $zip->open($temp_zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        return null;
    }

    $usedNames = [];
    foreach ($files as $value) {
        // The stored filename is unique, and the selection above already limited it to this course.
        $file = $em->getRepository(CDropboxFile::class)->findOneBy([
            'filename' => $value['filename'],
            'cId' => $courseId,
        ]);
        $resourceNode = $file?->getResourceNode();
        if (null === $resourceNode) {
            continue;
        }

        try {
            $content = $resourceNodeRepository->getResourceNodeFileContent($resourceNode);
        } catch (Throwable $e) {
            continue;
        }

        // The title is user input: keep only its last path segment, without control characters.
        $entryName = trim(basename(str_replace('\\', '/', (string) preg_replace('/[\x00-\x1F\x7F]/', '', (string) $value['title']))));
        if ('' === $entryName || '.' === $entryName || '..' === $entryName) {
            $entryName = basename((string) $value['filename']);
        }
        $info = pathinfo($entryName);
        $base = $info['filename'];
        $extension = isset($info['extension']) ? '.'.$info['extension'] : '';
        $candidate = $entryName;
        for ($i = 2; isset($usedNames[mb_strtolower($candidate)]); $i++) {
            $candidate = $base.' ('.$i.')'.$extension;
        }
        $usedNames[mb_strtolower($candidate)] = true;

        $zip->addFromString($candidate, $content);
    }

    $added = $zip->numFiles;
    $zip->close();
    if (0 === $added) {
        @unlink($temp_zip_file);

        return null;
    }

    return $temp_zip_file;
}

function zip_download($fileList)
{
    $temp_zip_file = dropbox_build_zip($fileList);
    if (null === $temp_zip_file) {
        return get_lang('Check at least one file.');
    }
    $name = 'dropbox-'.api_get_utc_datetime().'.zip';
    $result = DocumentManager::file_send_for_download($temp_zip_file, true, $name);
    if (false === $result) {
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
 *
 * Passed by name as the PclZip pre-add callback of zip_download().
 */
function my_pre_add_callback($p_event, &$p_header)
{
    $files = Session::read('dropbox_files_to_download');
    $p_header['stored_filename'] = $files[$p_header['stored_filename']]['title'];

    return 1;
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
    $rows = Container::getEntityManager()->createQueryBuilder()
        ->select('COUNT(f.feedbackId) AS total', 'f.fileId AS file_id')
        ->from(CDropboxFeedback::class, 'f')
        ->where('f.cId = :courseId')
        ->groupBy('f.fileId')
        ->setParameter('courseId', (int) $course_id)
        ->getQuery()
        ->getArrayResult()
    ;
    $return = [];
    foreach ($rows as $row) {
        $return[$row['file_id']] = (int) $row['total'];
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

    $row = Container::getEntityManager()->createQueryBuilder()
        ->select('a.accessDate AS access_date')
        ->from(TrackELastaccess::class, 'a')
        ->where('a.accessUserId = :userId')
        ->andWhere('a.cId = :courseId')
        ->andWhere('a.accessTool = :tool')
        ->setParameter('userId', (int) $user_id)
        ->setParameter('courseId', (int) $courseId)
        ->setParameter('tool', (string) $tool)
        ->orderBy('a.accessDate', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getArrayResult()[0] ?? null;

    return null === $row ? null : $row['access_date']->format('Y-m-d H:i:s');
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
