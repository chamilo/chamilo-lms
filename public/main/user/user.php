<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Framework\Container;

/**
 * This script displays a list of the users of the current course.
 * Course admins can change user permissions, subscribe and unsubscribe users...
 *
 * show users registered in courses
 *
 * @author Roan Embrechts
 * @author Julio Montoya, Several fixes
 */
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_USER;
$this_section = SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true, false, 'user');

if (!api_is_platform_admin(true)) {
    if (!api_is_course_admin() && !api_is_coach()) {
        if (0 == api_get_course_setting('allow_user_view_user_list')) {
            api_not_allowed(true);
        }
    }
}

$sessionId = api_get_session_id();
$is_western_name_order = api_is_western_name_order();
$sort_by_first_name = api_sort_by_first_name();
$course_info = api_get_course_info();
$user_id = api_get_user_id();
$_user = api_get_user_info();
$courseCode = $course_info['code'];
$courseId = $course_info['real_id'];
$type = isset($_REQUEST['type']) ? (int) $_REQUEST['type'] : STUDENT;
$canEditUsers = 'true' === api_get_setting('allow_user_course_subscription_by_course_admin') || api_is_platform_admin();
$canEdit = api_is_allowed_to_edit(null, true);
$canRead = api_is_allowed_to_edit(null, true) || api_is_coach();

// Can't auto unregister from a session
if (!empty($sessionId)) {
    $course_info['unsubscribe'] = 0;
}

$disableUsers = 3 === (int) $course_info['visibility'] &&
    api_get_configuration_value('disable_change_user_visibility_for_public_courses');

if (false === $canEdit && $disableUsers) {
    api_not_allowed(true);
}

/* Un registering a user section	*/
if (api_is_allowed_to_edit(null, true)) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'unsubscribe':
                // Make sure we don't unsubscribe current user from the course
                if (is_array($_POST['user'])) {
                    $user_ids = array_diff($_POST['user'], [$user_id]);
                    if (count($user_ids) > 0) {
                        CourseManager::unsubscribe_user($user_ids, $courseCode);
                        Display::addFlash(
                            Display::return_message(
                                get_lang('The selected users have been unsubscribed from the course')
                            )
                        );
                    }
                }
        }
    }
}

// Getting extra fields that have the filter option "on"
$extraField = new ExtraField('user');
$extraFields = $extraField->get_all(['filter = ?' => 1]);
$user_image_pdf_size = 80;

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'set_tutor':
            if (!$canEdit) {
                api_not_allowed();
            }
            $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
            $isTutor = isset($_GET['is_tutor']) ? (int) $_GET['is_tutor'] : 0;
            $userInfo = api_get_user_info($userId);

            if (!empty($userId)) {
                if (!$sessionId) {
                    if (INVITEE != $userInfo['status']) {
                        CourseManager::updateUserCourseTutor(
                            $userId,
                            $courseId,
                            $isTutor
                        );
                        Display::addFlash(
                            Display::return_message(get_lang('Update successful'))
                        );
                    } else {
                        Display::addFlash(
                            Display::return_message(
                                get_lang('Invitees cannot be tutors'),
                                'error'
                            )
                        );
                    }
                }
            }
            break;
        case 'export':
            if (!$canRead) {
                api_not_allowed();
            }
            $table_users = Database::get_main_table(TABLE_MAIN_USER);
            $is_western_name_order = api_is_western_name_order();

            $data = [];
            $a_users = [];
            $current_access_url_id = api_get_current_access_url_id();
            $extra_fields = UserManager::get_extra_user_data(
                api_get_user_id(),
                false,
                false,
                false,
                true
            );

            $extra_fields = array_keys($extra_fields);
            $select_email_condition = '';

            if ('true' === api_get_setting('show_email_addresses')) {
                $select_email_condition = ' user.email, ';
                if ($sort_by_first_name) {
                    $a_users[0] = [
                        'id',
                        get_lang('First name'),
                        get_lang('Last name'),
                        get_lang('Username'),
                        get_lang('e-mail'),
                        get_lang('Phone'),
                        get_lang('Code'),
                        get_lang('active'),
                    ];
                } else {
                    $a_users[0] = [
                        'id',
                        get_lang('Last name'),
                        get_lang('First name'),
                        get_lang('Username'),
                        get_lang('e-mail'),
                        get_lang('Phone'),
                        get_lang('Code'),
                        get_lang('active'),
                    ];
                }
            } else {
                if ($sort_by_first_name) {
                    $a_users[0] = [
                        'id',
                        get_lang('First name'),
                        get_lang('Last name'),
                        get_lang('Username'),
                        get_lang('Phone'),
                        get_lang('Code'),
                        get_lang('active'),
                    ];
                } else {
                    $a_users[0] = [
                        'id',
                        get_lang('Last name'),
                        get_lang('First name'),
                        get_lang('Username'),
                        get_lang('Phone'),
                        get_lang('Code'),
                        get_lang('active'),
                    ];
                }
            }

            $legal = '';

            if (isset($course_info['activate_legal']) && 1 == $course_info['activate_legal']) {
                $legal = ', legal_agreement';
                $a_users[0][] = get_lang('Legal agreement accepted');
            }

            if ('pdf' === $_GET['format']) {
                $select_email_condition = ' user.email, ';
                if ($is_western_name_order) {
                    $a_users[0] = [
                        '#',
                        get_lang('Picture'),
                        get_lang('Code'),
                        get_lang('First name').', '.get_lang('Last name'),
                        get_lang('e-mail'),
                        get_lang('Phone'),
                    ];
                } else {
                    $a_users[0] = [
                        '#',
                        get_lang('Picture'),
                        get_lang('Code'),
                        get_lang('Last name').', '.get_lang('First name'),
                        get_lang('e-mail'),
                        get_lang('Phone'),
                    ];
                }
            }

            $a_users[0] = array_merge($a_users[0], $extra_fields);

            // users subscribed to the course through a session.
            if (api_get_session_id()) {
                $table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
                $sql = "SELECT DISTINCT
                            user.id as user_id, ".($is_western_name_order ? "user.firstname, user.lastname" : "user.lastname, user.firstname").",
                            user.username,
                            $select_email_condition
                            phone,
                            user.official_code,
                            active
                            $legal
                        FROM $table_session_course_user as session_course_user,
                        $table_users as user ";
                if (api_is_multiple_url_enabled()) {
                    $sql .= ' , '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).' au ';
                }
                $sql .= "
                    WHERE c_id = $courseId
                        AND session_course_user.user_id = user.id
                        AND session_id = $sessionId
                ";

                if (api_is_multiple_url_enabled()) {
                    $sql .= " AND user.id = au.user_id AND access_url_id =  $current_access_url_id  ";
                }

                // only users no coaches/teachers
                if (COURSEMANAGER == $type) {
                    $sql .= " AND session_course_user.status = 2 ";
                } else {
                    $sql .= " AND session_course_user.status = 0 ";
                }
                $sql .= $sort_by_first_name ? ' ORDER BY user.firstname, user.lastname' : ' ORDER BY user.lastname, user.firstname';

                $rs = Database::query($sql);
                $counter = 1;

                while ($user = Database:: fetch_array($rs, 'ASSOC')) {
                    if (isset($user['legal_agreement'])) {
                        if (1 == $user['legal_agreement']) {
                            $user['legal_agreement'] = get_lang('Yes');
                        } else {
                            $user['legal_agreement'] = get_lang('No');
                        }
                    }
                    $extra_fields = UserManager::get_extra_user_data(
                        $user['user_id'],
                        false,
                        false,
                        false,
                        true
                    );
                    if (!empty($extra_fields)) {
                        foreach ($extra_fields as $key => $extra_value) {
                            $user[$key] = $extra_value;
                        }
                    }
                    $data[] = $user;
                    if ('pdf' === $_GET['format']) {
                        $user_info = api_get_user_info($user['user_id']);
                        $user_image = '<img src="'.$user_info['avatar'].'" width ="'.$user_image_pdf_size.'px" />';

                        if ($is_western_name_order) {
                            $user_pdf = [
                                $counter,
                                $user_image,
                                $user['official_code'],
                                $user['firstname'].', '.$user['lastname'],
                                $user['email'],
                                $user['phone'],
                            ];
                        } else {
                            $user_pdf = [
                                $counter,
                                $user_image,
                                $user['official_code'],
                                $user['lastname'].', '.$user['firstname'],
                                $user['email'],
                                $user['phone'],
                            ];
                        }

                        $a_users[] = $user_pdf;
                    } else {
                        $a_users[] = $user;
                    }
                    $counter++;
                }
            }

            if (0 == $sessionId) {
                // users directly subscribed to the course
                $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
                $sql = "SELECT DISTINCT
                            user.id as user_id, ".($is_western_name_order ? "user.firstname, user.lastname" : "user.lastname, user.firstname").",
                            user.username,
                            $select_email_condition
                            phone,
                            user.official_code,
                            active $legal
                        FROM $table_course_user as course_user, $table_users as user ";
                if (api_is_multiple_url_enabled()) {
                    $sql .= ' , '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).' au ';
                }
                $sql .= " WHERE
                        c_id = '$courseId' AND
                        course_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND
                        course_user.user_id = user.id ";

                if (api_is_multiple_url_enabled()) {
                    $sql .= " AND user.id = au.user_id  AND access_url_id =  $current_access_url_id  ";
                }

                // only users no teachers/coaches
                if (COURSEMANAGER == $type) {
                    $sql .= " AND course_user.status = 1 ";
                } else {
                    $sql .= " AND course_user.status = 5 ";
                }

                $sql .= ($sort_by_first_name ? " ORDER BY user.firstname, user.lastname" : " ORDER BY user.lastname, user.firstname");

                $rs = Database::query($sql);
                $counter = 1;
                while ($user = Database::fetch_array($rs, 'ASSOC')) {
                    if (isset($user['legal_agreement'])) {
                        if (1 == $user['legal_agreement']) {
                            $user['legal_agreement'] = get_lang('Yes');
                        } else {
                            $user['legal_agreement'] = get_lang('No');
                        }
                    }

                    $extra_fields = UserManager::get_extra_user_data(
                        $user['user_id'],
                        false,
                        false,
                        false,
                        true
                    );
                    if (!empty($extra_fields)) {
                        foreach ($extra_fields as $key => $extra_value) {
                            $user[$key] = $extra_value;
                        }
                    }
                    if ('pdf' === $_GET['format']) {
                        $user_info = api_get_user_info($user['user_id']);
                        $user_image = '<img src="'.$user_info['avatar'].'" width ="'.$user_image_pdf_size.'px" />';

                        if ($is_western_name_order) {
                            $user_pdf = [
                                $counter,
                                $user_image,
                                $user['official_code'],
                                $user['firstname'].', '.$user['lastname'],
                                $user['email'],
                                $user['phone'],
                            ];
                        } else {
                            $user_pdf = [
                                $counter,
                                $user_image,
                                $user['official_code'],
                                $user['lastname'].', '.$user['firstname'],
                                $user['email'],
                                $user['phone'],
                            ];
                        }

                        $a_users[] = $user_pdf;
                    } else {
                        $a_users[] = $user;
                    }
                    $data[] = $user;
                    $counter++;
                }
            }

            $fileName = get_lang('Learners list');
            $pdfTitle = get_lang('Learners list');

            if (COURSEMANAGER == $type) {
                $fileName = get_lang('Trainers');
                $pdfTitle = get_lang('Trainers');
            }

            switch ($_GET['format']) {
                case 'csv':
                    Export::arrayToCsv($a_users, $fileName);
                    exit;
                case 'xls':
                    Export::arrayToXls($a_users, $fileName);
                    exit;
                case 'pdf':
                    $header_attributes = [
                        ['style' => 'width:10px'],
                        ['style' => 'width:30px'],
                        ['style' => 'width:50px'],
                        ['style' => 'width:500px'],
                    ];
                    $params = [
                        'filename' => $fileName,
                        'pdf_title' => $pdfTitle,
                        'header_attributes' => $header_attributes,
                    ];

                    Export::export_table_pdf($a_users, $params);
                    exit;
            }
    }
}

if (api_is_allowed_to_edit(null, true)) {
    // Unregister user from course
    if (isset($_REQUEST['unregister']) && $_REQUEST['unregister']) {
        if (isset($_GET['user_id']) && is_numeric($_GET['user_id']) &&
            ($_GET['user_id'] != $_user['user_id'] || api_is_platform_admin())
        ) {
            $user_id = (int) $_GET['user_id'];
            $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
            $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
            $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

            $sql = "SELECT user.id as user_id
					FROM $tbl_user user
					INNER JOIN $tbl_session_rel_user reluser
					ON user.id = reluser.user_id AND reluser.relation_type <> ".Session::DRH."
					INNER JOIN $tbl_session_rel_course rel_course
					ON rel_course.session_id = reluser.session_id
					WHERE
					    user.id = $user_id AND
					    rel_course.c_id = $courseId ";

            $result = Database::query($sql);
            $row = Database::fetch_array($result, 'ASSOC');
            if (($row && $row['user_id'] == $user_id) || empty($row)) {
                CourseManager::unsubscribe_user($user_id, $courseCode);
                Display::addFlash(
                    Display::return_message(get_lang('User is now unsubscribed'))
                );
            } else {
                Display::addFlash(
                    Display::return_message(
                        get_lang(
                            'This learner is subscribed in this training through a training session. You cannot edit his information'
                        )
                    )
                );
            }
        }
    }
} else {
    // If student can unsubscribe
    if (isset($_REQUEST['unregister']) && 'yes' === $_REQUEST['unregister']) {
        if (1 == $course_info['unsubscribe']) {
            $user_id = api_get_user_id();
            CourseManager::unsubscribe_user($user_id, $course_info['code']);
            header('Location: '.api_get_path(WEB_PATH).'user_portal.php');
            exit;
        }
    }
}

// $is_allowed_in_course is first defined in local.inc.php
if (!api_is_allowed_in_course()) {
    api_not_allowed(true);
}

Event::event_access_tool(TOOL_USER);

$default_column = 3;
$tableLabel = STUDENT === $type ? 'student' : 'teacher';
$table = new SortableTable(
    $tableLabel.'_list',
    'get_number_of_users',
    'get_user_data',
    $default_column
);
$parameters['keyword'] = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
$parameters['sec_token'] = Security::get_token();
$parameters['id_session'] = api_get_session_id();
$parameters['type'] = $type;

$table->set_additional_parameters($parameters);
$header_nr = 0;
$indexList = [];
$table->set_header($header_nr++, '', false);

$indexList['photo'] = $header_nr;
$table->set_header($header_nr++, get_lang('Photo'), false);
$indexList['official_code'] = $header_nr;
$table->set_header($header_nr++, get_lang('Code'));

if ($is_western_name_order) {
    $indexList['firstname'] = $header_nr;
    $table->set_header($header_nr++, get_lang('First name'));
    $indexList['lastname'] = $header_nr;
    $table->set_header($header_nr++, get_lang('Last name'));
} else {
    $indexList['lastname'] = $header_nr;
    $table->set_header($header_nr++, get_lang('Last name'));
    $indexList['firstname'] = $header_nr;
    $table->set_header($header_nr++, get_lang('First name'));
}
$indexList['username'] = $header_nr;
$table->set_header($header_nr++, get_lang('Login'));
$indexList['groups'] = $header_nr;
$table->set_header($header_nr++, get_lang('Group'), false);

$hideFields = api_get_configuration_value('hide_user_field_from_list');

if (!empty($hideFields)) {
    $hideFields = $hideFields['fields'];
    foreach ($hideFields as $fieldToHide) {
        if (isset($indexList[$fieldToHide])) {
            $table->setHideColumn($indexList[$fieldToHide]);
        }
    }
}

$table->setHideColumn('is_tutor');
$table->setHideColumn('user_status_in_course');

if (api_is_allowed_to_edit(null, true)) {
    $table->set_header($header_nr++, get_lang('Status'), false);
    $table->set_header($header_nr++, get_lang('active'), false);
    if ($canEditUsers) {
        $table->set_column_filter(8, 'active_filter');
    } else {
        $table->set_column_filter(8, 'active_filter');
    }

    foreach ($extraFields as $extraField) {
        $table->set_header($header_nr++, $extraField['display_text'], false);
    }

    // Actions column
    $table->set_header($header_nr++, get_lang('Action'), false);
    $table->set_column_filter($header_nr - 1, 'modify_filter');

    if ($canEditUsers) {
        $table->set_form_actions(['unsubscribe' => get_lang('Unsubscribe')], 'user');
    }
} else {
    if (1 == $course_info['unsubscribe']) {
        $table->set_header($header_nr++, get_lang('Action'), false);
        $table->set_column_filter($header_nr - 1, 'modify_filter');
    }
}

if (isset($origin) && 'learnpath' === $origin) {
    Display::display_reduced_header();
} else {
    if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
        $interbreadcrumb[] = [
            'url' => 'user.php?'.api_get_cidreq(),
            'name' => get_lang('Users'),
        ];
        $tool_name = get_lang('Search results');
    } else {
        $tool_name = get_lang('Users');
        $origin = 'users';
    }
    Display::display_header($tool_name, 'User');
}

Display::display_introduction_section(TOOL_USER, 'left');
$actions = '';
$selectedTab = 1;

if ($canRead) {
    switch ($type) {
        case STUDENT:
            $selectedTab = 1;
            $url = api_get_path(WEB_CODE_PATH).'user/subscribe_user.php?'.api_get_cidreq().'&type='.STUDENT;
            $icon = Display::url(
                Display::return_icon('add-user.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
                $url
            );
            break;
        case COURSEMANAGER:
            $selectedTab = 2;
            $url = api_get_path(WEB_CODE_PATH).'user/subscribe_user.php?'.api_get_cidreq().'&type='.COURSEMANAGER;
            $icon = Display::url(
                Display::return_icon('add-teacher.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
                $url
            );
            break;
    }

    $actionsLeft = '';
    if ($canEdit) {
        $actionsLeft .= $icon;
    }

    if ($canRead) {
        $actionsLeft .= '<a href="user.php?'.api_get_cidreq().'&action=export&format=csv&type='.$type.'">'.
            Display::return_icon('export_csv.png', get_lang('CSV export'), [], ICON_SIZE_MEDIUM).'</a> ';
        $actionsLeft .= '<a href="user.php?'.api_get_cidreq().'&action=export&format=xls&type='.$type.'">'.
            Display::return_icon('export_excel.png', get_lang('Excel export'), [], ICON_SIZE_MEDIUM).'</a> ';
    }

    if ($canEditUsers && $canEdit) {
        $actionsLeft .= '<a href="user_import.php?'.api_get_cidreq().'&action=import&type='.$type.'">'.
            Display::return_icon('import_csv.png', get_lang('Import users list'), [], ICON_SIZE_MEDIUM).'</a> ';
    }

    if ($canRead) {
        $actionsLeft .= '<a href="user.php?'.api_get_cidreq().'&action=export&format=pdf&type='.$type.'">'.
            Display::return_icon('pdf.png', get_lang('Export to PDF'), [], ICON_SIZE_MEDIUM).'</a> ';
    }

    // Build search-form
    $form = new FormValidator(
        'search_user',
        'get',
        api_get_self().'?type='.$type,
        '',
        null,
        FormValidator::LAYOUT_INLINE
    );
    $form->addHidden('type', $type);
    $form->addText('keyword', '', false);
    $form->addElement('hidden', 'cidReq', api_get_course_id());
    $form->addButtonSearch(get_lang('Search'));
    $actionsRight = $form->returnForm();

    $allowTutors = api_get_setting('allow_tutors_to_assign_students_to_session');
    if (api_is_allowed_to_edit() && 'true' === $allowTutors) {
        $actionsRight .= ' <a class="btn btn-default" href="session_list.php?'.api_get_cidreq().'">'.
            get_lang('Course sessions').'</a>';
    }

    echo Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight]);
}

echo UserManager::getUserSubscriptionTab($selectedTab);
$table->display();

if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
    $keyword_name = Security::remove_XSS($_GET['keyword']);
    echo '<br/>'.get_lang('Search resultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

if (!isset($origin) || 'learnpath' !== $origin) {
    Display::display_footer();
}

/**
 * Get the users to display on the current page.
 */
function get_number_of_users()
{
    $counter = 0;
    $sessionId = api_get_session_id();
    $courseCode = api_get_course_id();
    $active = isset($_GET['active']) ? $_GET['active'] : null;
    $type = isset($_REQUEST['type']) ? (int) $_REQUEST['type'] : STUDENT;

    if (empty($sessionId)) {
        $status = $type;
    } else {
        if (COURSEMANAGER == $type) {
            $status = 2;
        } else {
            $status = 0;
        }
    }

    if (!empty($sessionId)) {
        $users = CourseManager::get_user_list_from_course_code(
            $courseCode,
            $sessionId,
            null,
            null,
            $status,
            null,
            false,
            false,
            null,
            null,
            null,
            $active
        );
    } else {
        $users = CourseManager::get_user_list_from_course_code(
            $courseCode,
            0,
            null,
            null,
            $status,
            null,
            false,
            false,
            null,
            null,
            null,
            $active
        );
    }

    foreach ($users as $user) {
        if ((
            isset($_GET['keyword']) &&
                searchUserKeyword(
                    $user['firstname'],
                    $user['lastname'],
                    $user['username'],
                    $user['official_code'],
                    $_GET['keyword']
                )
            ) || !isset($_GET['keyword']) || empty($_GET['keyword'])
        ) {
            $counter++;
        }
    }

    return $counter;
}

/**
 * @param string $firstname
 * @param string $lastname
 * @param string $username
 * @param string $official_code
 * @param $keyword
 *
 * @return bool
 */
function searchUserKeyword($firstname, $lastname, $username, $official_code, $keyword)
{
    if (false !== api_strripos($firstname, $keyword) ||
        false !== api_strripos($lastname, $keyword) ||
        false !== api_strripos($username, $keyword) ||
        false !== api_strripos($official_code, $keyword)
    ) {
        return true;
    }

    return false;
}

/**
 * Get the users to display on the current page.
 *
 * @param int    $from            Offset
 * @param int    $number_of_items
 * @param int    $column          The column on which to sort
 * @param string $direction       ASC or DESC, for the sort order of the query results
 *
 * @return array
 */
function get_user_data($from, $number_of_items, $column, $direction)
{
    global $is_western_name_order;
    global $extraFields;
    $type = isset($_REQUEST['type']) ? (int) $_REQUEST['type'] : STUDENT;
    $course_info = api_get_course_info();
    $sessionId = api_get_session_id();
    $course_code = $course_info['code'];
    $a_users = [];
    $limit = null;
    $from = (int) $from;
    $number_of_items = (int) $number_of_items;

    // limit
    if (!isset($_GET['keyword']) || empty($_GET['keyword'])) {
        $limit = 'LIMIT '.$from.','.$number_of_items;
    }

    if (!in_array($direction, ['ASC', 'DESC'])) {
        $direction = 'ASC';
    }

    switch ($column) {
        case 2: //official code
            $order_by = 'ORDER BY user.official_code '.$direction;
            break;
        case 3:
            if ($is_western_name_order) {
                $order_by = 'ORDER BY user.firstname '.$direction.', user.lastname '.$direction;
            } else {
                $order_by = 'ORDER BY user.lastname '.$direction.', user.firstname '.$direction;
            }
            break;
        case 4:
            if ($is_western_name_order) {
                $order_by = 'ORDER BY user.lastname '.$direction.', user.firstname '.$direction;
            } else {
                $order_by = 'ORDER BY user.firstname '.$direction.', user.lastname '.$direction;
            }
            break;
        case 5: //username
            $order_by = 'ORDER BY user.username '.$direction;
            break;
        default:
            if ($is_western_name_order) {
                $order_by = 'ORDER BY user.lastname '.$direction.', user.firstname '.$direction;
            } else {
                $order_by = 'ORDER BY user.firstname '.$direction.', user.lastname '.$direction;
            }
            break;
    }

    $active = $_GET['active'] ?? null;

    if (empty($sessionId)) {
        $status = $type;
    } else {
        $status = 0;
        if (COURSEMANAGER == $type) {
            $status = 2;
        }
    }

    $users = CourseManager::get_user_list_from_course_code(
        $course_code,
        $sessionId,
        $limit,
        $order_by,
        $status,
        null,
        false,
        false,
        null,
        [],
        [],
        $active
    );

    $illustrationRepo = Container::getIllustrationRepository();

    foreach ($users as $user_id => $userData) {
        if ((
            isset($_GET['keyword']) &&
                searchUserKeyword(
                    $userData['firstname'],
                    $userData['lastname'],
                    $userData['username'],
                    $userData['official_code'],
                    $_GET['keyword']
                )
            ) || !isset($_GET['keyword']) || empty($_GET['keyword'])
        ) {
            $groupsNameList = GroupManager::getAllGroupPerUserSubscription($user_id);
            $groupsNameListParsed = [];
            if (!empty($groupsNameList)) {
                $groupsNameListParsed = array_column($groupsNameList, 'name');
            }

            $temp = [];
            $user = api_get_user_entity($user_id);
            $url = $illustrationRepo->getIllustrationUrl($user, 'user_picture_small', ICON_SIZE_BIG);
            $photo = Display::img($url, '', [], false);

            if (api_is_allowed_to_edit(null, true)) {
                $temp[] = $user_id;
                $temp[] = $photo;
                $temp[] = $userData['official_code'];

                if ($is_western_name_order) {
                    $temp[] = $userData['firstname'];
                    $temp[] = $userData['lastname'];
                } else {
                    $temp[] = $userData['lastname'];
                    $temp[] = $userData['firstname'];
                }

                $temp[] = $userData['username'];

                // Groups.
                $temp[] = implode(', ', $groupsNameListParsed);

                // Status
                $default_status = get_lang('Student');
                if ((isset($userData['status_rel']) && 1 == $userData['status_rel']) ||
                    (isset($userData['status_session']) && 2 == $userData['status_session'])
                ) {
                    $default_status = get_lang('CourseManager');
                } elseif (isset($userData['is_tutor']) && 1 == $userData['is_tutor']) {
                    $default_status = get_lang('Tutor');
                }

                $temp[] = $default_status;

                // active
                $temp[] = $userData['active'];
                $extraFieldOption = new ExtraFieldOption('user');
                $extraFieldValue = new ExtraFieldValue('user');

                if (!empty($extraFields)) {
                    foreach ($extraFields as $extraField) {
                        $data = $extraFieldValue->get_values_by_handler_and_field_id(
                            $user_id,
                            $extraField['id']
                        );
                        if (isset($data['value'])) {
                            $optionList = $extraFieldOption->get_field_option_by_field_and_option(
                                $extraField['id'],
                                $data['value']
                            );
                            if (!empty($optionList)) {
                                $options = implode(', ', array_column($optionList, 'display_text'));
                                $temp[] = Security::remove_XSS($options);
                            } else {
                                $temp[] = Security::remove_XSS($data['value']);
                            }
                        } else {
                            $temp[] = '';
                        }
                    }
                }

                // User id for actions
                $temp[] = $user_id;
                $temp['is_tutor'] = $userData['is_tutor'] ?? '';
                $temp['user_status_in_course'] = $userData['status_rel'] ?? '';
            } else {
                $temp[] = '';
                $temp[] = $photo;
                $temp[] = $userData['official_code'];

                if ($is_western_name_order) {
                    $temp[] = $userData['firstname'];
                    $temp[] = $userData['lastname'];
                } else {
                    $temp[] = $userData['lastname'];
                    $temp[] = $userData['firstname'];
                }

                $temp[] = $userData['username'];
                // Group.
                $temp[] = implode(', ', $groupsNameListParsed);

                if (1 == $course_info['unsubscribe']) {
                    //User id for actions
                    $temp[] = $user_id;
                }
            }
            $a_users[$user_id] = $temp;
        }
    }

    return $a_users;
}

/**
 * Build the active-column of the table to lock or unlock a certain user
 * lock = the user can no longer use this account.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @param int    $active    the current state of the account
 * @param string $urlParams
 *
 * @return string Some HTML-code with the lock/unlock button
 */
function active_filter($active, $urlParams, $row)
{
    $userId = api_get_user_id();
    $action = '';
    $image = '';
    if ('1' == $active) {
        $action = 'Accountactive';
        $image = 'accept';
    }
    if ('0' == $active) {
        $action = 'AccountInactive';
        $image = 'error';
    }
    $result = '';

    /* you cannot lock yourself out otherwise you could disable all the accounts including your own => everybody is
        locked out and nobody can change it anymore.*/
    if ($row[0] != $userId) {
        $result = '<center><img src="'.Display::returnIconPath($image.'.png', 16).'" border="0" alt="'.get_lang(ucfirst($action)).'" title="'.get_lang(ucfirst($action)).'"/></center>';
    }

    return $result;
}

/**
 * Build the modify-column of the table.
 *
 * @param int $user_id The user id
 *
 * @return string Some HTML-code
 */
function modify_filter($user_id, $row, $data)
{
    $canEditUsers = 'true' == api_get_setting('allow_user_course_subscription_by_course_admin') || api_is_platform_admin();

    $is_allowed_to_track = api_is_allowed_to_edit(true, true);
    $user_id = $data[0];
    $userInfo = api_get_user_info($user_id);
    $isInvitee = INVITEE == $userInfo['status'] ? true : false;
    $course_info = $_course = api_get_course_info();
    $current_user_id = api_get_user_id();
    $sessionId = api_get_session_id();
    $courseId = $_course['id'];
    $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : STUDENT;

    $result = '';
    if ($is_allowed_to_track) {
        $result .= '<a href="../mySpace/myStudents.php?'.api_get_cidreq().'&student='.$user_id.'&details=true&course='.$courseId.'&origin=user_course&id_session='.api_get_session_id().'"
        title="'.get_lang('Reporting').'">
            '.Display::return_icon('statistics.png', get_lang('Reporting')).'
        </a>';
    }

    // If platform admin, show the login_as icon (this drastically shortens
    // time taken by support to test things out)
    if (api_is_platform_admin()) {
        $result .= ' <a
        href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&user_id='.$user_id.'&sec_token='.Security::getTokenFromSession().'">'.
            Display::return_icon('login_as.png', get_lang('Login as')).'</a>&nbsp;&nbsp;';
    }

    if (api_is_allowed_to_edit(null, true)) {
        if (empty($sessionId)) {
            $isTutor = isset($data['is_tutor']) ? (int) $data['is_tutor'] : 0;
            $isTutor = empty($isTutor) ? 1 : 0;

            $text = get_lang('Remove assistant role');
            if ($isTutor) {
                $text = get_lang('Convert to assistant');
            }

            if ($isInvitee) {
                $disabled = 'disabled';
            } else {
                $disabled = '';
            }

            $allow = api_get_configuration_value('extra');
            if ($allow) {
                $result .= '<a href="'.
                    api_get_path(WEB_CODE_PATH).'extra/userInfo.php?'.api_get_cidreq().'&editMainUserInfo='.$user_id.'"
                    title="'.get_lang('Edit').'" >'.
                    Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).
                    '</a>&nbsp;';
            }

            if (STUDENT == $data['user_status_in_course']) {
                $result .= Display::url(
                    $text,
                    'user.php?'.api_get_cidreq().'&action=set_tutor&is_tutor='.$isTutor.'&user_id='.$user_id.'&type='.$type,
                    ['class' => 'btn btn-default '.$disabled]
                ).'&nbsp;';
            }
        }

        // edit
        if ($canEditUsers) {
            // unregister
            if ($user_id != $current_user_id || api_is_platform_admin()) {
                $result .= '<a
                class="btn btn-sm btn-danger delete-swal"
                href="'.api_get_self().'?'.api_get_cidreq().'&type='.$type.'&unregister=yes&user_id='.$user_id.'"
                title="'.addslashes(api_htmlentities(get_lang('Unsubscribe'))).' " >'.
                    get_lang('Unsubscribe').'</a>&nbsp;';
            }
        }
    } else {
        // Show buttons for unsubscribe
        if (1 == $course_info['unsubscribe']) {
            if ($user_id == $current_user_id) {
                $result .= '<a
                class="btn btn-sm btn-danger delete-swal"
                href="'.api_get_self().'?'.api_get_cidreq().'&type='.$type.'&unregister=yes&user_id='.$user_id.'"
                title="'.addslashes(api_htmlentities(get_lang('Unsubscribe'))).' >'.
                    get_lang('Unsubscribe').'</a>&nbsp;';
            }
        }
    }

    return $result;
}
