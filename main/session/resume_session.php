<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Entity\Repository\SessionRepository;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use ChamiloSession as PHPSession;

/**
 * @author  Bart Mollet, Julio Montoya lot of fixes
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : null;

if (empty($sessionId)) {
    api_not_allowed(true);
}
PHPSession::write('id_session', $sessionId);
SessionManager::protectSession($sessionId);
$codePath = api_get_path(WEB_CODE_PATH);

$tool_name = get_lang('SessionOverview');
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('SessionList'),
];

$orig_param = '&origin=resume_session';

$allowSkills = api_get_configuration_value('allow_skill_rel_items');
if ($allowSkills) {
    $htmlContentExtraClass[] = 'feature-item-user-skill-on';
}

// Database Table Definitions
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_class = Database::get_main_table(TABLE_MAIN_SESSION_CLASS);

$em = Database::getManager();
$sessionInfo = api_get_session_info($sessionId);
/** @var SessionRepository $sessionRepository */
$sessionRepository = $em->getRepository('ChamiloCoreBundle:Session');
/** @var Session $session */
$session = $sessionRepository->find($sessionId);
$sessionCategory = $session->getCategory();

$action = isset($_GET['action']) ? $_GET['action'] : null;
$url_id = api_get_current_access_url_id();

switch ($action) {
    case 'move_up':
        SessionManager::moveUp($sessionId, $_GET['course_id']);
        header('Location: resume_session.php?id_session='.$sessionId);
        exit;
        break;
    case 'move_down':
        SessionManager::moveDown($sessionId, $_GET['course_id']);
        header('Location: resume_session.php?id_session='.$sessionId);
        exit;
        break;
    case 'add_user_to_url':
        $user_id = $_REQUEST['user_id'];
        $result = UrlManager::add_user_to_url($user_id, $url_id);
        $user_info = api_get_user_info($user_id);
        if ($result) {
            Display::addFlash(
                Display::return_message(
                    get_lang('UserAdded').' '.api_get_person_name($user_info['firstname'], $user_info['lastname']),
                    'confirm'
                )
            );
        }
        break;
    case 'delete':
        // Delete course from session.
        $idChecked = isset($_GET['idChecked']) ? $_GET['idChecked'] : null;
        $message = get_lang('TokenExpiredActionAlreadyRealized');
        if (is_array($idChecked)) {
            $usersToDelete = [];
            $check = Security::check_token('get');
            if ($check) {
                foreach ($idChecked as $courseCode) {
                    // forcing the escape_string
                    $courseInfo = api_get_course_info($courseCode);
                    SessionManager::unsubscribe_course_from_session(
                        $sessionId,
                        $courseInfo['real_id']
                    );
                }
                $message = get_lang('Updated');
            }
        }

        if (!empty($_GET['class'])) {
            $class = (int) $_GET['class'];
            $result = Database::query(
                "DELETE FROM $tbl_session_rel_class
                 WHERE session_id = $sessionId
                  AND class_id = $class"
            );
            $nbr_affected_rows = Database::affected_rows($result);
            Database::query(
                "UPDATE $tbl_session
                SET nbr_classes = nbr_classes - $nbr_affected_rows
                WHERE id = $sessionId");
            $message = get_lang('Updated');
        }

        if (!empty($_GET['user'])) {
            $check = Security::check_token('get');
            if ($check) {
                SessionManager::unsubscribe_user_from_session(
                    $sessionId,
                    $_GET['user']
                );
                $message = get_lang('Updated');
            }
            Security::clear_token();
        }

        Display::addFlash(Display::return_message($message));
        break;
}

$sessionHeader = Display::page_header(
    Display::return_icon('session.png', get_lang('Session')).' '.$session->getName(),
    null,
    'h3'
);

$url = Display::url(
    Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL),
    "session_edit.php?page=resume_session.php&id=$sessionId"
);

$sessionTitle = Display::page_subheader(get_lang('GeneralProperties').$url);
$generalCoach = api_get_user_info($sessionInfo['id_coach']);

$sessionField = new ExtraField('session');
$extraFieldData = $sessionField->getDataAndFormattedValues($sessionId);

$multiple_url_is_on = api_get_multiple_access_url();
$urlList = [];
if ($multiple_url_is_on) {
    $urlList = UrlManager::get_access_url_from_session($sessionId);
}

$url = Display::url(
    Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL),
    "add_courses_to_session.php?page=resume_session.php&id_session=$sessionId"
);
$courseListToShow = Display::page_subheader(get_lang('CourseList').$url);

$courseListToShow .= '<table id="session-list-course" class="table table-hover table-striped data_table">
<tr>
  <th width="35%">'.get_lang('CourseTitle').'</th>
  <th width="30%">'.get_lang('CourseCoach').'</th>
  <th width="10%">'.get_lang('UsersNumber').'</th>
  <th width="25%">'.get_lang('Actions').'</th>
</tr>';

if ($session->getNbrCourses() === 0) {
    $courseListToShow .= '<tr>
			<td colspan="4">'.get_lang('NoCoursesForThisSession').'</td>
		</tr>';
} else {
    $secToken = Security::get_token();
    $count = 0;
    $courseItem = '';
    //$courses = $sessionRepository->getCoursesOrderedByPosition($session);

    $courses = $session->getCourses();
    $iterator = $courses->getIterator();
    // define ordering closure, using preferred comparison method/field
    $iterator->uasort(function ($first, $second) {
        return (int) $first->getPosition() > (int) $second->getPosition() ? 1 : -1;
    });
    $courseList = [];
    $positionList = [];
    $courseListByCode = [];
    /** @var \Chamilo\CoreBundle\Entity\SessionRelCourse $sessionRelCourse */
    foreach ($iterator as $sessionRelCourse) {
        $courseList[] = $sessionRelCourse->getCourse();
        $courseListByCode[$sessionRelCourse->getCourse()->getCode()] = $sessionRelCourse->getCourse();
        $positionList[] = $sessionRelCourse->getPosition();
    }

    $checkPosition = array_filter($positionList);
    if (empty($checkPosition)) {
        // The session course list doesn't have any position,
        // then order the course list by course code.
        $orderByCode = array_keys($courseListByCode);
        sort($orderByCode, SORT_NATURAL);
        $newCourseList = [];
        foreach ($orderByCode as $code) {
            $newCourseList[] = $courseListByCode[$code];
        }
        $courseList = $newCourseList;
    }

    /** @var Course $course */
    foreach ($courseList as $course) {
        // Select the number of users
        $numberOfUsers = SessionManager::getCountUsersInCourseSession($course, $session);

        // Get coachs of the courses in session
        $namesOfCoaches = [];
        $coachSubscriptions = $session->getUserCourseSubscriptionsByStatus($course, Session::COACH);

        if ($coachSubscriptions) {
            /** @var SessionRelCourseRelUser $subscription */
            foreach ($coachSubscriptions as $subscription) {
                $namesOfCoaches[] = $subscription->getUser()->getCompleteNameWithUserName();
            }
        }

        $orderButtons = '';
        if (SessionManager::orderCourseIsEnabled()) {
            $orderButtons = Display::url(
                Display::return_icon(
                    !$count ? 'up_na.png' : 'up.png',
                    get_lang('MoveUp')
                ),
                !$count
                    ? '#'
                    : api_get_self().'?id_session='.$sessionId.'&course_id='.$course->getId().'&action=move_up'
            );

            $orderButtons .= Display::url(
                Display::return_icon(
                    $count + 1 == count($courses) ? 'down_na.png' : 'down.png',
                    get_lang('MoveDown')
                ),
                $count + 1 == count($courses)
                    ? '#'
                    : api_get_self().'?id_session='.$sessionId.'&course_id='.$course->getId().'&action=move_down'
            );
        }

        $courseUrl = api_get_course_url($course->getCode(), $sessionId);
        $courseBaseUrl = api_get_course_url($course->getCode());

        // hide_course_breadcrumb the parameter has been added to hide the name
        // of the course, that appeared in the default $interbreadcrumb
        $courseItem .= '<tr>
			<td class="title">'
            .Display::url(
                $course->getTitle().' ('.$course->getVisualCode().')',
                $courseUrl
            )
            .'</td>';
        $courseItem .= '<td>'.($namesOfCoaches ? implode('<br>', $namesOfCoaches) : get_lang('None')).'</td>';
        $courseItem .= '<td>'.$numberOfUsers.'</td>';
        $courseItem .= '<td>';
        $courseItem .= Display::url(Display::return_icon('course_home.gif', get_lang('CourseInSession')), $courseUrl);

        $courseItem .= Display::url(
            Display::return_icon('settings.png', get_lang('Course')),
            $courseBaseUrl,
            ['target' => '_blank']
        );

        if ($allowSkills) {
            $courseItem .= Display::url(
                Display::return_icon('skill-badges.png', get_lang('Skills')),
                $codePath.'admin/skill_rel_course.php?session_id='.$sessionId.'&course_id='.$course->getId()
            );
        }
        $courseItem .= $orderButtons;

        $courseItem .= Display::url(
            Display::return_icon('new_user.png', get_lang('AddUsers')),
            $codePath."session/add_users_to_session_course.php?id_session=$sessionId&course_id=".$course->getId()
        );
        $courseItem .= Display::url(
            Display::return_icon('user.png', get_lang('Users')),
            $codePath."session/session_course_user_list.php?id_session=$sessionId&course_code=".$course->getCode()
        );
        $courseItem .= Display::url(
            Display::return_icon('import_csv.png', get_lang('ImportUsersToACourse')),
            $codePath."user/user_import.php?action=import&cidReq={$course->getCode()}&id_session=$sessionId"
        );
        $courseItem .= Display::url(
            Display::return_icon('export_csv.png', get_lang('ExportUsersOfACourse')),
            $codePath."user/user_export.php?file_type=csv&course_session={$course->getCode()}:$sessionId&addcsvheader=1"
        );
        $courseItem .= Display::url(
            Display::return_icon('statistics.gif', get_lang('Tracking')),
            $codePath."tracking/courseLog.php?id_session=$sessionId&cidReq={$course->getCode()}$orig_param&hide_course_breadcrumb=1"
        );
        $courseItem .= Display::url(
            Display::return_icon('teacher.png', get_lang('ModifyCoach')),
            $codePath."session/session_course_edit.php?id_session=$sessionId&page=resume_session.php&course_code={$course->getCode()}$orig_param"
        );
        $courseItem .= Display::url(
            Display::return_icon('folder_document.png', get_lang('UploadFile')),
            '#',
            [
                'class' => 'session-upload-file-btn',
                'data-session' => $sessionId,
                'data-course' => $course->getId(),
            ]
        );
        $courseItem .= Display::url(
            Display::return_icon('delete.png', get_lang('Delete')),
            api_get_self()."?id_session=$sessionId&action=delete&idChecked[]={$course->getCode()}&sec_token=".Security::getTokenFromSession(),
            [
                'onclick' => "javascript:if(!confirm('".get_lang('ConfirmYourChoice')."')) return false;",
            ]
        );

        $courseItem .= '</td></tr>';
        $count++;
    }
    $courseListToShow .= $courseItem;
}
$courseListToShow .= '</table><br />';

$url = '&nbsp;'.Display::url(
    Display::return_icon('user_subscribe_session.png', get_lang('Add')),
    $codePath."session/add_users_to_session.php?page=resume_session.php&id_session=$sessionId"
);
$url .= Display::url(
    Display::return_icon('import_csv.png', get_lang('ImportUsers')),
    $codePath."session/session_user_import.php?id_session=$sessionId"
);
$url .= Display::url(
    Display::return_icon('export_csv.png', get_lang('ExportUsers')),
    $codePath."user/user_export.php?file_type=csv&session=$sessionId&addcsvheader=1"
);
$url .= Display::url(
    Display::return_icon('pdf.png', get_lang('CertificateOfAchievement'), [], ICON_SIZE_SMALL),
    $codePath.'mySpace/session.php?'.http_build_query(
        [
            'action' => 'export_to_pdf',
            'type' => 'achievement',
            'session_to_export' => $sessionId,
            'all_students' => 1,
        ]
    )
);

$userListToShow = Display::page_subheader(get_lang('UserList').$url);
$userList = SessionManager::get_users_by_session($sessionId);

if (!empty($userList)) {
    $table = new HTML_Table(
        ['class' => 'table table-hover table-striped data_table', 'id' => 'session-user-list']
    );
    $table->setHeaderContents(0, 0, get_lang('User'));
    $table->setHeaderContents(0, 1, get_lang('Status'));
    $table->setHeaderContents(0, 2, get_lang('RegistrationDate'));
    $table->setHeaderContents(0, 3, get_lang('Actions'));

    $row = 1;
    foreach ($userList as $user) {
        $userId = $user['user_id'];
        $userInfo = api_get_user_info($userId);

        $userLink = '<a href="'.$codePath.'admin/user_information.php?user_id='.$userId.'">'.
            api_htmlentities($userInfo['complete_name_with_username']).'</a>';

        $reportingLink = Display::url(
            Display::return_icon('statistics.gif', get_lang('Reporting')),
            $codePath.'mySpace/myStudents.php?student='.$user['user_id'].''.$orig_param.'&id_session='
            .$sessionId
        );

        $courseUserLink = Display::url(
            Display::return_icon('course.png', get_lang('BlockCoursesForThisUser')),
            $codePath.'session/session_course_user.php?id_user='.$user['user_id'].'&id_session='
            .$sessionId
        );

        $removeLink = Display::url(
            Display::return_icon('delete.png', get_lang('Delete')),
            api_get_self().'?id_session='.$sessionId.'&action=delete&user='.$user['user_id'].'&sec_token='.Security::getTokenFromSession(),
            ['onclick' => "javascript:if(!confirm('".get_lang('ConfirmYourChoice')."')) return false;"]
        );

        $addUserToUrlLink = '';
        if ($multiple_url_is_on) {
            if ($user['access_url_id'] != $url_id) {
                $userLink .= ' '.Display::return_icon('warning.png', get_lang('UserNotAddedInURL'));
                $add = Display::return_icon('add.png', get_lang('AddUsersToURL'));
                $addUserToUrlLink = '<a href="resume_session.php?action=add_user_to_url&id_session='.$sessionId
                    .'&user_id='.$user['user_id'].'">'.$add.'</a>';
            }
        }

        $editUrl = null;
        /*
        if (isset($sessionInfo['duration']) && !empty($sessionInfo['duration'])) {
            $editUrl = $codePath . 'session/session_user_edit.php?session_id=' . $sessionId . '&user_id=' . $userId;
            $editUrl = Display::url(
                Display::return_icon('agenda.png', get_lang('SessionDurationEdit')),
                $editUrl
            );
        }*/

        $table->setCellContents($row, 0, $userLink);
        $link = $reportingLink.$courseUserLink.$removeLink.$addUserToUrlLink.$editUrl;
        switch ($user['relation_type']) {
            case 1:
                $status = get_lang('Drh');
                $link = Display::url(
                    Display::return_icon('edit.png', get_lang('Edit')),
                    $codePath.'admin/dashboard_add_sessions_to_user.php?user='.$userId
                );
                break;
            default:
                $status = get_lang('Student');
        }

        $registered = !empty($user['registered_at']) ? Display::dateToStringAgoAndLongDate($user['registered_at']) : '';

        $table->setCellContents($row, 1, $status);
        $table->setCellContents($row, 2, $registered);
        $table->setCellContents($row, 3, $link);
        $row++;
    }
    $userListToShow .= $table->toHtml();
}

/** @var SequenceResourceRepository $repo */
$repo = $em->getRepository('ChamiloCoreBundle:SequenceResource');
$requirementAndDependencies = $repo->getRequirementAndDependencies(
    $sessionId,
    SequenceResource::SESSION_TYPE
);

$requirements = '';
if (!empty($requirementAndDependencies['requirements'])) {
    $requirements = Display::page_subheader(get_lang('Requirements'));
    $requirements .= implode(' + ', array_column($requirementAndDependencies['requirements'], 'admin_link'));
}
$dependencies = '';
if (!empty($requirementAndDependencies['dependencies'])) {
    $dependencies = Display::page_subheader(get_lang('Dependencies'));
    $dependencies .= implode(', ', array_column($requirementAndDependencies['dependencies'], 'admin_link'));
}

$promotion = null;
if (!empty($sessionInfo['promotion_id'])) {
    $promotion = $em->getRepository('ChamiloCoreBundle:Promotion');
    $promotion = $promotion->find($sessionInfo['promotion_id']);
}

$programmedAnnouncement = new ScheduledAnnouncement();
$programmedAnnouncement = $programmedAnnouncement->allowed();

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);

$tpl = new Template($tool_name);
$tpl->assign('session_header', $sessionHeader);
$tpl->assign('title', $sessionTitle);
$tpl->assign('general_coach', $generalCoach);
$tpl->assign('session_admin', api_get_user_info($session->getSessionAdminId()));
$tpl->assign('session', $sessionInfo);
$tpl->assign('programmed_announcement', $programmedAnnouncement);
$tpl->assign('session_category', is_null($sessionCategory) ? null : $sessionCategory->getName());
$tpl->assign('session_dates', SessionManager::parseSessionDates($sessionInfo, true));
$tpl->assign('session_visibility', SessionManager::getSessionVisibility($sessionInfo));
$tpl->assign('promotion', $promotion);
$tpl->assign('url_list', $urlList);
$tpl->assign('extra_fields', $extraFieldData);
$tpl->assign('course_list', $courseListToShow);
$tpl->assign('user_list', $userListToShow);
$tpl->assign('dependencies', $dependencies);
$tpl->assign('requirements', $requirements);

$layout = $tpl->get_template('session/resume_session.tpl');
$tpl->display($layout);
