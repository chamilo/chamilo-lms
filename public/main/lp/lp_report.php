<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpRelUser;
use Chamilo\CourseBundle\Repository\CLpRelUserRepository;
use Chamilo\CourseBundle\Entity\CLpRelGroup;
use Chamilo\CourseBundle\Repository\CLpRelGroupRepository;

/**
 * Report from students for learning path.
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);
$isAllowedToEdit = api_is_allowed_to_edit(null, true);
if (!$isAllowedToEdit) {
    api_not_allowed(true);
}

$lpTable = Database::get_course_table(TABLE_LP_MAIN);
$course = api_get_course_entity();
$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();

$lpId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
$studentId = isset($_REQUEST['student_id']) ? (int) $_REQUEST['student_id'] : 0;
$groupFilter = isset($_REQUEST['group_filter']) ? Security::remove_XSS($_REQUEST['group_filter']) : '';
$deleteExercisesAttempts = isset($_REQUEST['delete_exercise_attempts']) && 1 === (int) $_REQUEST['delete_exercise_attempts'] ? true : false;

$groupFilterType = '';
$groupFilterId = 0;
$groupFilterParts = explode(':', $groupFilter);
if (!empty($groupFilterParts) && isset($groupFilterParts[1])) {
    $groupFilterType = $groupFilterParts[0];
    $groupFilterId = (int) $groupFilterParts[1];
}
$export = isset($_REQUEST['export']);
$reset = isset($_REQUEST['reset']) ? $_REQUEST['reset'] : '';

$repo = Container::getLpRepository();
/** @var CLp $entity */
$entity = $repo->find($lpId);

$lp = new learnpath($entity, $courseInfo, api_get_user_id());
if (empty($lp)) {
    api_not_allowed(true);
}

$urlBase = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=report&lp_id='.$lpId;
$url = $urlBase.'&group_filter='.$groupFilter;
$allowUserGroups = api_get_configuration_value('allow_lp_subscription_to_usergroups');

$course = api_get_course_entity($courseId);
$session = api_get_session_entity($sessionId);

$em = Database::getManager();
// Check LP subscribers
if ('1' === $lp->getSubscribeUsers()) {

    /** @var CLpRelUserRepository $cLpRelUserRepo */
    $cLpRelUserRepo = $em->getRepository('ChamiloCourseBundle:CLpRelUser');
    $subscribedUsersInLp = $cLpRelUserRepo->getUsersSubscribedToItem(
        $entity,
        $course,
        $session
    );

    /** @var CLpRelGroupRepository $cLpRelGroupRepo */
    $cLpRelGroupRepo = $em->getRepository('ChamiloCourseBundle:CLpRelGroup');

    // Subscribed groups to a LP
    $subscribedGroupsInLp = $cLpRelGroupRepo->getGroupsSubscribedToItem(
        $entity,
        $course,
        $session
    );

    $selectedGroupChoices = [];
    /** @var CLpRelGroup $cLpRelGroup */
    foreach ($subscribedGroupsInLp as $cLpRelGroup) {
        $selectedGroupChoices[] = $cLpRelGroup->getGroup()->getIid();
    }

    $groups = [];
    if (!empty($subscribedGroupsInLp)) {
        /** @var CLpRelGroup $cLpRelGroup */
        foreach ($subscribedGroupsInLp as $cLpRelGroup) {
            if (!empty($cLpRelGroup)) {
                $getGroup = $cLpRelGroup->getGroup();
                if (!empty($getGroup)) {
                    $groups[] = $getGroup->getIid();
                }
            }
        }
    }

    $users = [];
    if (!empty($groups)) {
        foreach ($groups as $groupId) {
            $students = GroupManager::getStudents($groupId);
            if (!empty($students)) {
                foreach ($students as $studentInfo) {
                    $users[]['user_id'] = $studentInfo['user_id'];
                }
            }
        }
    }

    if (!empty($subscribedUsersInLp)) {
        foreach ($subscribedUsersInLp as $users) {
            /** @var CLpRelUser $users */
            $user = $users->getUser();
            if ($user) {
                $users[]['user_id'] = $user->getId();
            }
        }
    }
} else {
    $categoryId = $lp->getCategoryId();
    $users = [];
    if (!empty($categoryId)) {
        /** @var CLpCategory $category */
        $category = $em->getRepository(CLpCategory::class)->find($categoryId);
        $subscribedUsersInCategory = $category->getUsers();
        if (!empty($subscribedUsersInCategory)) {
            foreach ($subscribedUsersInCategory as $item) {
                $user = $item->getUser();
                if ($user) {
                    $users[]['user_id'] = $item->getUser()->getId();
                }
            }
        }
    }

    if (empty($categoryId) || empty($users)) {
        if (empty($sessionId)) {
            $users = CourseManager::get_user_list_from_course_code(
                $courseCode,
                0,
                null,
                null,
                STUDENT
            );
        } else {
            $users = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId,
                null,
                null,
                0
            );
        }
    }
}

$groups = GroupManager::get_group_list(null, $course, null, $sessionId);
$label = get_lang('Groups');
$classes = [];
if ($allowUserGroups) {
    $label = get_lang('Groups').' / '.get_lang('Classes');
    $userGroup = new UserGroupModel();
    $conditions = [];
    $conditions['where'] = [' usergroup.course_id = ? ' => $courseId];
    $classes = $userGroup->getUserGroupInCourse($conditions);
}

$groupFilterForm = '';
if (!empty($groups)) {
    $form = new FormValidator('group', 'GET', $url);
    $form->addHidden('action', 'report');
    $form->addHidden('lp_id', $lpId);
    $form->addCourseHiddenParams();

    $courseGroups = [];
    foreach ($groups as $group) {
        $option = [
            'text' => $group['name'],
            'value' => "group:".$group['iid'],
        ];
        $courseGroups[] = $option;
    }

    $select = $form->addSelect(
        'group_filter',
        $label,
        [],
        [
            'id' => 'group_filter',
            'placeholder' => get_lang('All'),
        ]
    );
    $select->addOptGroup($courseGroups, get_lang('Groups'));

    if ($allowUserGroups) {
        $options = [];
        foreach ($classes as $group) {
            $option = [
                'text' => $group['name'],
                'value' => "class:".$group['id'],
            ];
            $options[] = $option;
        }
        $select->addOptGroup($options, get_lang('Classes'));
    }

    if (!empty($groupFilter)) {
        switch ($groupFilterType) {
            case 'group':
                $users = GroupManager::getStudents($groupFilterId, true);
                break;
            case 'class':
                if ($allowUserGroups) {
                    $users = $userGroup->getUserListByUserGroup($groupFilterId);
                }
                break;
        }

        $form->setDefaults(['group_filter' => $groupFilter]);
    }
    $groupFilterForm = $form->returnForm();
}

if ($reset) {
    switch ($reset) {
        case 'student':
            if ($studentId) {
                $studentInfo = api_get_user_info($studentId);
                if ($studentInfo) {
                    Event::delete_student_lp_events(
                        $studentId,
                        $lpId,
                        $courseInfo,
                        $sessionId,
                        false === $deleteExercisesAttempts
                    );
                    Display::addFlash(
                        Display::return_message(
                            get_lang('LPWasReset').': '.$studentInfo['complete_name_with_username'],
                            'success'
                        )
                    );
                }
            }
            break;
        case 'all':
            $result = [];
            foreach ($users as $user) {
                $userId = $user['user_id'];
                $studentInfo = api_get_user_info($userId);
                if ($studentInfo) {
                    Event::delete_student_lp_events(
                        $userId,
                        $lpId,
                        $courseInfo,
                        $sessionId,
                        false === $deleteExercisesAttempts
                    );
                    $result[] = $studentInfo['complete_name_with_username'];
                }
            }

            if (!empty($result)) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('LPWasReset').': '.implode(', ', $result),
                        'success'
                    )
                );
            }

            break;
    }
    api_location($url);
}

$userList = [];
$showEmail = api_get_setting('show_email_addresses');

if (!empty($users)) {
    $added = [];
    foreach ($users as $user) {
        $userId = $user['user_id'];
        if (in_array($userId, $added)) {
            continue;
        }

        $userInfo = api_get_user_info($userId);
        $lpTime = Tracking::get_time_spent_in_lp(
            $userId,
            $course,
            [$lpId],
            $sessionId
        );

        $lpScore = Tracking::get_avg_student_score(
            $userId,
            $course,
            [$lpId],
            $session
        );

        $lpProgress = Tracking::get_avg_student_progress(
            $userId,
            $course,
            [$lpId],
            $session
        );

        $lpLastConnection = Tracking::get_last_connection_time_in_lp(
            $userId,
            $courseCode,
            $lpId,
            $sessionId
        );

        $lpLastConnection = empty($lpLastConnection) ? '-' : api_convert_and_format_date(
            $lpLastConnection,
            DATE_TIME_FORMAT_LONG
        );

        $userGroupList = '';
        if (!empty($groups)) {
            $groupsByUser = GroupManager::getAllGroupPerUserSubscription($userId, $courseId, $sessionId);
            $icon = Display::return_icon('group.png', get_lang('Group'));
            if (!empty($groupsByUser)) {
                $groupUrl = api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(true, false);
                foreach ($groupsByUser as $group) {
                    $userGroupList .= Display::url($icon.$group['name'], $groupUrl.'&gid='.$group['iid']).'&nbsp;';
                }
            }
        }

        $classesToString = '';
        if ($allowUserGroups) {
            $classes = $userGroup->getUserGroupListByUser($userId, Usergroup::NORMAL_CLASS);
            $icon = Display::return_icon('class.png', get_lang('Class'));
            if (!empty($classes)) {
                $classUrl = api_get_path(WEB_CODE_PATH).'user/class.php?'.api_get_cidreq(true, false);
                foreach ($classes as $class) {
                    $classesToString .= Display::url(
                            $icon.$class['name'],
                            $classUrl.'&class_id='.$class['id']
                        ).'&nbsp;';
                }
            }
        }
        $trackingUrl = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?details=true&'.
        api_get_cidreq().'&course='.$courseCode.'&origin=tracking_course&student='.$userId;
        $row = [];
        $row[] = Display::url($userInfo['firstname'], $trackingUrl);
        $row[] = Display::url($userInfo['lastname'], $trackingUrl);
        if ('true' === $showEmail) {
            $row[] = $userInfo['email'];
        }
        $row[] = $userGroupList.$classesToString;
        $row[] = api_time_to_hms($lpTime);
        $row[] = "$lpProgress %";
        $row[] = is_numeric($lpScore) ? "$lpScore%" : $lpScore;
        $row[] = $lpLastConnection;
        $actions = Display::url(Display::return_icon('statistics.png', get_lang('Reporting')), $trackingUrl).'&nbsp;';

        $actions .= Display::url(
            Display::return_icon('2rightarrow.png', get_lang('Details')),
            'javascript:void(0);',
            ['data-id' => $userId, 'class' => 'details']
        ).'&nbsp;';

        $actions .= Display::url(
            Display::return_icon('clean.png', get_lang('Reset')),
            'javascript:void(0);',
            ['data-id' => $userId, 'data-username' => $userInfo['username'], 'class' => 'delete_attempt']
        );

        $row[] = $actions;
        $row['username'] = $userInfo['username'];
        $userList[] = $row;
        $added[] = $userId;
    }
} else {
    Display::addFlash(Display::return_message(get_lang('NoUserAdded'), 'warning'));
}

$parameters = [
    'action' => 'report',
    'group_filter' => $groupFilter,
    'cidReq' => $courseCode,
    'id_session' => $sessionId,
    'lp_id' => $lpId,
];

$table = new SortableTableFromArrayConfig($userList, 1, 20, 'lp');
$table->set_additional_parameters($parameters);
$column = 0;
$table->set_header($column++, get_lang('FirstName'));
$table->set_header($column++, get_lang('LastName'));
if ('true' === $showEmail) {
    $table->set_header($column++, get_lang('Email'));
}
$table->set_header($column++, $label, false);
$table->set_header($column++, get_lang('ScormTime'));
$table->set_header($column++, get_lang('Progress'));
$table->set_header($column++, get_lang('ScormScore'));
$table->set_header($column++, get_lang('LastConnection'), false);
if (false === $export) {
    $table->set_header($column++, get_lang('Actions'), false);
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];

$actions = Display::url(
    Display::return_icon(
        'back.png',
        get_lang('Back'),
        [],
        ICON_SIZE_MEDIUM
    ),
    api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq()
);

if (!empty($users)) {
    $actions .= Display::url(
        Display::return_icon(
            'pdf.png',
            get_lang('ExportToPdf'),
            [],
            ICON_SIZE_MEDIUM
        ),
        $url.'&export=pdf'
    );
    $userListToString = array_column($userList, 'username');
    $userListToString = implode(', ', $userListToString);
    $actions .= Display::url(
        Display::return_icon(
            'clean.png',
            get_lang('Clean'),
            [],
            ICON_SIZE_MEDIUM
        ),
        'javascript:void(0);',
        ['data-users' => $userListToString, 'class' => 'delete_all']
    );
}

$template = new Template(get_lang('StudentScore'));
$template->assign('group_class_label', $label);
$template->assign('user_list', $userList);
$template->assign('session_id', api_get_session_id());
$template->assign('course_code', api_get_course_id());
$template->assign('lp_id', $lpId);
$template->assign('show_email', 'true' === $showEmail);
$template->assign('table', $table->return_table());
$template->assign('export', (int) $export);
$template->assign('group_form', $groupFilterForm);
$template->assign('url', $url);
$template->assign('url_base', $urlBase);
$template->assign('header', $entity->getName());
$template->assign('actions', Display::toolbarAction('lp_actions', [$actions]));
$result = $template->fetch('@ChamiloCore/LearnPath/report.html.twig');
$template->assign('content', $result);

if ($export) {
    $pdfParams = [
        'filename' => get_lang('StudentScore').'_'.api_get_local_time(),
    ];
    $pdf = new PDF('A4', 'P', $pdfParams);
    $pdf->html_to_pdf_with_template(
        $result,
        false,
        false,
        true
    );
    exit;
}

$template->display_one_col_template();
