<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Repository\ItemPropertyRepository;
use Chamilo\CourseBundle\Entity\CLpCategory;

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

$lpId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
$export = isset($_REQUEST['export']);

$lp = new learnpath(api_get_course_id(), $lpId, api_get_user_id());
if (empty($lp)) {
    api_not_allowed(true);
}

$url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=report&lp_id='.$lpId;

$em = Database::getManager();
$sessionId = api_get_session_id();
$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();

// Check LP subscribers
if ('1' === $lp->getSubscribeUsers()) {
    /** @var ItemPropertyRepository $itemRepo */
    $itemRepo = $em->getRepository('ChamiloCourseBundle:CItemProperty');
    $subscribedUsersInLp = $itemRepo->getUsersSubscribedToItem(
        'learnpath',
        $lpId,
        api_get_course_entity($courseId),
        api_get_session_entity($sessionId)
    );
    $users = [];
    if (!empty($subscribedUsersInLp)) {
        foreach ($subscribedUsersInLp as $itemProperty) {
            $users[]['user_id'] = $itemProperty->getToUser()->getId();
        }
    }
} else {
    $categoryId = $lp->getCategoryId();
    if (!empty($categoryId)) {
        /** @var CLpCategory $category */
        $category = $em->getRepository('ChamiloCourseBundle:CLpCategory')->find($categoryId);
        $subscribedUsersInCategory = $category->getUsers();
        $users = [];
        if (!empty($subscribedUsersInCategory)) {
            foreach ($subscribedUsersInCategory as $item) {
                $users[]['user_id'] = $item->getUser()->getId();
            }
        }
    } else {
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

$lpInfo = Database::select(
    '*',
    $lpTable,
    [
        'where' => [
            'c_id = ? AND ' => $courseId,
            'id = ?' => $lpId,
        ],
    ],
    'first'
);

$groups = GroupManager::get_group_list(null, api_get_course_info(), null, api_get_session_id());
$groupFilter = '';
if (!empty($groups)) {
    $form = new FormValidator('group', 'post', $url);
    $form->addSelect(
        'group_id',
        get_lang('Groups'),
        array_column($groups, 'name', 'iid'),
        ['placeholder' => get_lang('SelectAnOption')]
    );
    $form->addButtonSearch(get_lang('Search'));

    if ($form->validate()) {
        $groupId = $form->getSubmitValue('group_id');
        if (!empty($groupId)) {
            $users = GroupManager::getStudents($groupId, true);
        }
    }
    $groupFilter = $form->returnForm();
}

$userList = [];
$showEmail = api_get_setting('show_email_addresses');

if (!empty($users)) {
    foreach ($users as $user) {
        $userId = $user['user_id'];
        $userInfo = api_get_user_info($userId);
        $lpTime = Tracking::get_time_spent_in_lp(
            $userId,
            $courseCode,
            [$lpId],
            $sessionId
        );

        $lpScore = Tracking::get_avg_student_score(
            $userId,
            $courseCode,
            [$lpId],
            $sessionId
        );

        $lpProgress = Tracking::get_avg_student_progress(
            $userId,
            $courseCode,
            [$lpId],
            $sessionId
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
            if (!empty($groupsByUser)) {
                $userGroupList = implode(', ', array_column($groupsByUser, 'name'));
            }
        }

        $userList[] = [
            'id' => $userId,
            'first_name' => $userInfo['firstname'],
            'last_name' => $userInfo['lastname'],
            'email' => 'true' === $showEmail ? $userInfo['email'] : '',
            'groups' => $userGroupList,
            'lp_time' => api_time_to_hms($lpTime),
            'lp_score' => is_numeric($lpScore) ? "$lpScore%" : $lpScore,
            'lp_progress' => "$lpProgress%",
            'lp_last_connection' => $lpLastConnection,
        ];
    }
} else {
    Display::addFlash(Display::return_message(get_lang('NoUserAdded'), 'warning'));
}

// View
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
}

$template = new Template(get_lang('StudentScore'));
$template->assign('user_list', $userList);
$template->assign('session_id', api_get_session_id());
$template->assign('course_code', api_get_course_id());
$template->assign('lp_id', $lpId);
$template->assign('show_email', 'true' === $showEmail);
$template->assign('export', (int) $export);
$template->assign('groups', $groupFilter);

$layout = $template->get_template('learnpath/report.tpl');

$template->assign('header', $lpInfo['name']);
$template->assign('actions', Display::toolbarAction('lp_actions', [$actions]));

$result = $template->fetch($layout);
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
