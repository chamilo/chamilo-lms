<?php
/* For licensing terms, see /license.txt */

/**
 * @author Bart Mollet, Julio Montoya lot of fixes
 * @package chamilo.admin
 */

use Chamilo\CoreBundle\Entity\Repository\SequenceRepository;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Promotion;
use Chamilo\CoreBundle\Entity\Session,
    Doctrine\Common\Collections\Criteria,
    Chamilo\CoreBundle\Entity\SessionRelUser,
    Chamilo\CoreBundle\Entity\Repository\SessionRepository,
    Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$sessionId = isset($_GET['id_session']) ? intval($_GET['id_session']) : null;

if (empty($sessionId)) {
    api_not_allowed(true);
}

SessionManager::protectSession($sessionId);

$tool_name = get_lang('SessionOverview');
$interbreadcrumb[] = array(
    'url' => 'session_list.php',
    'name' => get_lang('SessionList')
);

$orig_param = '&origin=resume_session';

// Database Table Definitions
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_class = Database::get_main_table(TABLE_MAIN_SESSION_CLASS);
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);
$table_access_url_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

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
        if (is_array($idChecked)) {
            $usersToDelete = array();
            foreach ($idChecked as $courseCode) {
                // forcing the escape_string
                $courseInfo = api_get_course_info($courseCode);
                SessionManager::unsubscribe_course_from_session(
                    $sessionId,
                    $courseInfo['real_id']
                );
            }
        }

        if (!empty($_GET['class'])) {
            $result = Database::query("DELETE FROM $tbl_session_rel_class
                             WHERE session_id='$sessionId' AND class_id=".intval($_GET['class']));
            $nbr_affected_rows = Database::affected_rows($result);
            Database::query("UPDATE $tbl_session SET nbr_classes=nbr_classes-$nbr_affected_rows WHERE id='$sessionId'");
        }

        if (!empty($_GET['user'])) {
            SessionManager::unsubscribe_user_from_session(
                $sessionId,
                $_GET['user']
            );
        }
        break;
}

$sessionHeader = Display::page_header(
    Display::return_icon('session.png', get_lang('Session')).' '.$sessionInfo['name'],
    null,
    'h3'
);

$url = Display::url(
    Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL),
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
    Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL),
    "add_courses_to_session.php?page=resume_session.php&id_session=$sessionId"
);
$courseListToShow = Display::page_subheader(get_lang('CourseList').$url);

$courseListToShow .= '<table id="session-list-course" class="data_table">
<tr>
  <th width="35%">'.get_lang('CourseTitle').'</th>
  <th width="30%">'.get_lang('CourseCoach').'</th>
  <th width="10%">'.get_lang('UsersNumber').'</th>
  <th width="25%">'.get_lang('Actions').'</th>
</tr>';

if ($sessionInfo['nbr_courses'] == 0) {
    $courseListToShow .= '<tr>
			<td colspan="4">'.get_lang('NoCoursesForThisSession').'</td>
		</tr>';
} else {
    $count = 0;
    $courseItem = '';
    $courses = $sessionRepository->getCoursesOrderedByPosition($session);

    foreach ($courses as $course) {
        // Select the number of users
        $numberOfUsers = SessionManager::getCountUsersInCourseSession($course, $session);
        // Get coachs of the courses in session
        $namesOfCoaches = [];
        $coachSubscriptions = $session
            ->getUserCourseSubscriptionsByStatus($course, Session::COACH)
            ->forAll(function ($index, SessionRelCourseRelUser $subscription) use (&$namesOfCoaches) {
                $namesOfCoaches[] = $subscription->getUser()->getCompleteNameWithUserName();

                return true;
            });

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
                    $count + 1 == count($courses) ? 'down_na.png'  : 'down.png',
                    get_lang('MoveDown')
                ),
                $count + 1 == count($courses)
                    ? '#'
                    : api_get_self().'?id_session='.$sessionId.'&course_id='.$course->getId().'&action=move_down'
            );
        }

        $courseUrl = api_get_course_url($course->getCode(), $sessionId);

        // hide_course_breadcrumb the parameter has been added to hide the name
        // of the course, that appeared in the default $interbreadcrumb
        $courseItem .= '
		<tr>
			<td class="title">'.Display::url(
                $course->getTitle().' ('.$course->getVisualCode().')',
                $courseUrl
            ).'</td>
			<td>'.($namesOfCoaches ? implode('<br>', $namesOfCoaches) : get_lang('None')).'</td>
			<td>'.$numberOfUsers.'</td>
			<td>
                <a href="'. $courseUrl.'">'.
                Display::return_icon('course_home.gif', get_lang('Course')).'</a>
                '.$orderButtons.'
                <a href="session_course_user_list.php?id_session='.$sessionId.'&course_code='.$course->getCode().'">'.
                Display::return_icon('user.png', get_lang('Users'), '', ICON_SIZE_SMALL).'</a>
                <a href="'.api_get_path(WEB_CODE_PATH).'user/user_import.php?action=import&cidReq='.$course->getCode().'&id_session='.$sessionId.'">'.
                Display::return_icon('import_csv.png', get_lang('ImportUsersToACourse'), null, ICON_SIZE_SMALL).'</a>
                <a href="'.api_get_path(WEB_CODE_PATH).'user/user_export.php?file_type=csv&course_session='.$course->getCode().':'.$sessionId.'&addcsvheader=1">'.
                Display::return_icon('export_csv.png', get_lang('ExportUsersOfACourse'), null, ICON_SIZE_SMALL).'</a>
				<a href="../tracking/courseLog.php?id_session='.$sessionId.'&cidReq='.$course->getCode().$orig_param.'&hide_course_breadcrumb=1">'.
                Display::return_icon('statistics.gif', get_lang('Tracking')).'</a>&nbsp;
				<a href="session_course_edit.php?id_session='.$sessionId.'&page=resume_session.php&course_code='.$course->getCode().''.$orig_param.'">'.
                Display::return_icon('teacher.png', get_lang('ModifyCoach'), '', ICON_SIZE_SMALL).'</a>
				<a href="'.api_get_self().'?id_session='.$sessionId.'&action=delete&idChecked[]='.$course->getCode().'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">'.
            Display::return_icon('delete.png', get_lang('Delete')).'</a>
			</td>
		</tr>';
        $count++;
    }
    $courseListToShow .= $courseItem;
}
$courseListToShow .= '</table><br />';

$url = Display::url(
    Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL),
    "add_users_to_session.php?page=resume_session.php&id_session=$sessionId"
);
$url .= Display::url(
    Display::return_icon('import_csv.png', get_lang('ImportUsers'), array(), ICON_SIZE_SMALL),
    "session_user_import.php?id_session=$sessionId"
);
$url .= Display::url(
    Display::return_icon('export_csv.png', get_lang('ExportUsers'), array(), ICON_SIZE_SMALL),
    api_get_path(WEB_CODE_PATH)."user/user_export.php?file_type=csv&session=$sessionId&addcsvheader=1"
);

$userListToShow = Display::page_subheader(get_lang('UserList').$url);
$userList = SessionManager::get_users_by_session($sessionId);

if (!empty($userList)) {
    $table = new HTML_Table(
        array('class' => 'data_table', 'id' => 'session-user-list')
    );
    $table->setHeaderContents(0, 0, get_lang('User'));
    $table->setHeaderContents(0, 1, get_lang('Status'));
    $table->setHeaderContents(0, 2, get_lang('Actions'));

    $row = 1;
    foreach ($userList as $user) {
        $userId = $user['user_id'];
        $userInfo = api_get_user_info($userId);

        $userLink = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$userId.'">'.
            api_htmlentities($userInfo['complete_name_with_username']).'</a>';

        $reportingLink = Display::url(
            Display::return_icon('statistics.gif', get_lang('Reporting')),
            api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$user['user_id'].''.$orig_param.'&id_session='.$sessionId
        );

        $courseUserLink = Display::url(
            Display::return_icon('course.png', get_lang('BlockCoursesForThisUser')),
            api_get_path(WEB_CODE_PATH).'session/session_course_user.php?id_user='.$user['user_id'].'&id_session='.$sessionId
        );

        $removeLink = Display::url(
            Display::return_icon('delete.png', get_lang('Delete')),
            api_get_self().'?id_session='.$sessionId.'&action=delete&user='.$user['user_id'],
            array('onclick' => "javascript:if(!confirm('".get_lang('ConfirmYourChoice')."')) return false;")
        );

        $addUserToUrlLink = '';
        if ($multiple_url_is_on) {
            if ($user['access_url_id'] != $url_id) {
                $userLink .= ' '.Display::return_icon(
                    'warning.png',
                    get_lang('UserNotAddedInURL'),
                    array(),
                    ICON_SIZE_SMALL
                );
                $add = Display::return_icon(
                    'add.png',
                    get_lang('AddUsersToURL'),
                    array(),
                    ICON_SIZE_SMALL
                );
                $addUserToUrlLink = '<a href="resume_session.php?action=add_user_to_url&id_session='.$sessionId.'&user_id='.$user['user_id'].'">'.$add.'</a>';
            }
        }

        $editUrl = null;
        /*
        if (isset($sessionInfo['duration']) && !empty($sessionInfo['duration'])) {
            $editUrl = api_get_path(WEB_CODE_PATH) . 'session/session_user_edit.php?session_id=' . $sessionId . '&user_id=' . $userId;
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
                    api_get_path(WEB_CODE_PATH).'admin/dashboard_add_sessions_to_user.php?user='.$userId
                );
                break;
            default:
                $status = get_lang('Student');
        }

        $table->setCellContents($row, 1, $status);
        $table->setCellContents($row, 2, $link);
        $row++;
    }
    $userListToShow .= $table->toHtml();
}

/** @var SequenceRepository $repo */
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
