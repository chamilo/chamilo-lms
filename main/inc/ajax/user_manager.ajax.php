<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$request = HttpRequest::createFromGlobals();
$isRequestByAjax = $request->isXmlHttpRequest();

$action = $_REQUEST['a'];

switch ($action) {
    case 'comment_attendance':
        $selected = $_REQUEST['selected'];
        $comment = $_REQUEST['comment'];
        $attendanceId = (int) $_REQUEST['attendance_id'];
        if (!empty($selected)) {
            list($prefix, $userId, $attendanceCalendarId) = explode('-', $selected);
            $attendance = new Attendance();
            $attendance->saveComment(
                (int) $userId,
                (int) $attendanceCalendarId,
                $comment,
                $attendanceId
            );
            echo 1;
            exit;
        }
        echo 0;
        break;
    case 'get_attendance_comment':
        $selected = $_REQUEST['selected'];
        if (!empty($selected)) {
            list($prefix, $userId, $attendanceCalendarId) = explode('-', $selected);
            $attendance = new Attendance();
            $commentInfo = $attendance->getComment(
                (int) $userId,
                (int) $attendanceCalendarId
            );
            echo json_encode(
              [
                  'comment' => $commentInfo['comment'],
                  'author' => !empty($commentInfo['author']) ? get_lang('Author').': '.$commentInfo['author'] : '',
              ]
            );
        }
        break;
    case 'block_attendance_calendar':
        $calendarId = (int) $_REQUEST['calendar_id'];
        $attendance = new Attendance();
        $attendance->updateCalendarBlocked($calendarId);
        echo (int) $attendance->isCalendarBlocked($calendarId);
        break;
    case 'get_attendance_sign':
        $selected = $_REQUEST['selected'];
        if (!empty($selected)) {
            list($prefix, $userId, $attendanceCalendarId) = explode('-', $selected);
            $attendance = new Attendance();
            $signature = $attendance->getSignature($userId, $attendanceCalendarId);
            echo $signature;
        }
        break;
    case 'remove_attendance_sign':
        $selected = $_REQUEST['selected'];
        $attendanceId = (int) $_REQUEST['attendance_id'];
        if (!empty($selected)) {
            list($prefix, $userId, $attendanceCalendarId) = explode('-', $selected);
            $attendance = new Attendance();
            $attendance->deleteSignature($userId, $attendanceCalendarId, $attendanceId);
        }
        break;
    case 'sign_attendance':
        $selected = $_REQUEST['selected'];
        $file = isset($_REQUEST['file']) ? $_REQUEST['file'] : '';
        $file = str_replace(' ', '+', $file);
        $attendanceId = $_REQUEST['attendance_id'];
        if (!empty($selected)) {
            list($prefix, $userId, $attendanceCalendarId) = explode('-', $selected);
            $attendance = new Attendance();
            $attendance->saveSignature($userId, $attendanceCalendarId, $file, $attendanceId);
            echo 1;
            exit;
        }
        echo 0;
        break;
    case 'set_expiration_date':
        $status = (int) $_REQUEST['status'];
        $dates = UserManager::getExpirationDateByRole($status);
        echo json_encode($dates);
        break;
    case 'get_user_like':
        if (api_is_platform_admin() || api_is_drh()) {
            $query = $_REQUEST['q'];
            $conditions = [
                'username' => $query,
                'firstname' => $query,
                'lastname' => $query,
            ];
            $users = UserManager::getUserListLike($conditions, [], false, 'OR');
            $result = [];
            if (!empty($users)) {
                foreach ($users as $user) {
                    $result[] = ['id' => $user['id'], 'text' => $user['complete_name'].' ('.$user['username'].')'];
                }
                $result['items'] = $result;
            }
            echo json_encode($result);
        }
        break;
    case 'get_user_popup':
        if (!$isRequestByAjax) {
            break;
        }

        $courseId = (int) $request->get('course_id');
        $sessionId = (int) $request->get('session_id');
        $userId = (int) $request->get('user_id');

        $user_info = api_get_user_info($userId);

        if (empty($user_info)) {
            break;
        }

        if ($courseId) {
            $courseInfo = api_get_course_info_by_id($courseId);

            if (empty($courseInfo)) {
                break;
            }
        }

        if ($sessionId) {
            $sessionInfo = api_get_session_info($sessionId);

            if (empty($sessionInfo)) {
                break;
            }
        }

        $isAnonymous = api_is_anonymous();

        if ($isAnonymous && empty($courseId)) {
            break;
        }

        if ($isAnonymous && $courseId) {
            if ('false' === api_get_setting('course_catalog_published')) {
                break;
            }

            $coursesNotInCatalog = CoursesAndSessionsCatalog::getCoursesToAvoid();

            if (in_array($courseId, $coursesNotInCatalog)) {
                break;
            }
        }

        echo '<div class="row">';
        echo '<div class="col-sm-5">';
        echo '<div class="thumbnail">';
        echo Display::img($user_info['avatar'], $user_info['complete_name']);
        echo '</div>';
        echo '</div>';

        echo '<div class="col-sm-7">';

        if ($isAnonymous || api_get_setting('show_email_addresses') == 'false') {
            $user_info['mail'] = '';
        }

        $userData = '<h3>'.$user_info['complete_name'].'</h3>'
            .PHP_EOL
            .$user_info['mail']
            .PHP_EOL
            .$user_info['official_code'];

        if ($isAnonymous) {
            // Only allow anonymous users to see user popup if the popup user
            // is a teacher (which might be necessary to illustrate a course)
            if ((int) $user_info['status'] === COURSEMANAGER) {
                echo $userData;
            }
        } else {
            echo Display::url(
                $userData,
                api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user_info['user_id']
            );
        }
        echo '</div>';
        echo '</div>';

        $url = api_get_path(WEB_AJAX_PATH).'message.ajax.php?'
            .http_build_query(
                [
                    'a' => 'send_message',
                    'user_id' => $user_info['user_id'],
                    'course_id' => $courseId,
                    'session_id' => $sessionId,
                ]
            );

        if ($isAnonymous === false &&
            api_get_setting('allow_message_tool') == 'true'
        ) {
            echo '<script>';
            echo '
                $("#send_message_link").on("click", function() {
                    var url = "'.$url.'";
                    var params = $("#send_message").serialize();
                    $.ajax({
                        url: url+"&"+params,
                        success:function(data) {
                            $("#subject_id").val("");
                            $("#content_id").val("");
                            $("#send_message").html(data);
                            $("#send_message_link").hide();
                        }
                    });
                });';

            echo '</script>';
            echo MessageManager::generate_message_form();
            echo '
                <div class="row">
                    <div class="col-sm-10 col-sm-offset-2">
                        <a class="btn btn-primary" id="send_message_link">
                            <em class="fa fa-envelope"></em> '.get_lang('Send').'
                        </a>
                    </div>
                </div>
            ';
        }
        break;
    case 'user_id_exists':
        if (api_is_anonymous()) {
            echo '';
        } else {
            if (UserManager::is_user_id_valid($_GET['user_id'])) {
                echo 1;
            } else {
                echo 0;
            }
        }
        break;
    case 'search_tags':
        header('Content-Type: application/json');

        $result = ['items' => []];

        if (api_is_anonymous()) {
            echo json_encode($result);
            break;
        }

        if (!isset($_GET['q'], $_GET['field_id'])) {
            echo json_encode($result);
            break;
        }

        $result['items'] = UserManager::get_tags($_GET['q'], $_GET['field_id'], null, '10');
        echo json_encode($result);
        break;
    case 'generate_api_key':
        if (api_is_anonymous()) {
            echo '';
        } else {
            $array_list_key = [];
            $user_id = api_get_user_id();
            $api_service = 'dokeos';
            $num = UserManager::update_api_key($user_id, $api_service);
            $array_list_key = UserManager::get_api_keys($user_id, $api_service); ?>
            <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo get_lang('MyApiKey'); ?></label>
                <div class="col-sm-8">
                    <input type="text" name="api_key_generate" id="id_api_key_generate" class="form-control" value="<?php echo $array_list_key[$num]; ?>"/>
                </div>
            </div>
            <?php
        }
        break;
    case 'active_user':
        $allow = api_get_configuration_value('allow_disable_user_for_session_admin');
        if ((api_is_platform_admin() && api_global_admin_can_edit_admin($_GET['user_id'])) ||
            (
                $allow &&
                api_is_session_admin() &&
                api_global_admin_can_edit_admin($_GET['user_id'], null, true)
            )
        ) {
            $user_id = intval($_GET['user_id']);
            $status = intval($_GET['status']);

            if (!empty($user_id)) {
                $user_table = Database::get_main_table(TABLE_MAIN_USER);
                $sql = "UPDATE $user_table
                        SET active = '".$status."'
                        WHERE user_id = '".$user_id."'";
                $result = Database::query($sql);

                // Send and email if account is active
                if ($status == 1) {
                    $user_info = api_get_user_info($user_id);
                    $recipientName = api_get_person_name(
                        $user_info['firstname'],
                        $user_info['lastname'],
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    );

                    $subject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
                    $emailAdmin = api_get_setting('emailAdministrator');
                    $sender_name = api_get_person_name(
                        api_get_setting('administratorName'),
                        api_get_setting('administratorSurname'),
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    );
                    $body = get_lang('Dear')." ".stripslashes($recipientName).",\n\n";
                    $body .= sprintf(
                        get_lang('YourAccountOnXHasJustBeenApprovedByOneOfOurAdministrators'),
                        api_get_setting('siteName')
                    )."\n";
                    $body .= sprintf(
                        get_lang('YouCanNowLoginAtXUsingTheLoginAndThePasswordYouHaveProvided'),
                        api_get_path(WEB_PATH)
                    ).",\n\n";
                    $body .= get_lang('HaveFun')."\n\n";
                    //$body.=get_lang('Problem'). "\n\n". get_lang('SignatureFormula');
                    $body .= api_get_person_name(
                        api_get_setting('administratorName'),
                        api_get_setting('administratorSurname')
                    )."\n".
                    get_lang('Manager')." ".
                    api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n".
                    get_lang('Email')." : ".api_get_setting('emailAdministrator');

                    $additionalParameters = [
                        'smsType' => SmsPlugin::ACCOUNT_APPROVED_CONNECT,
                        'userId' => $user_id,
                    ];

                    MessageManager::send_message_simple(
                        $user_id,
                        $subject,
                        $body,
                        null,
                        false,
                        false,
                        $additionalParameters
                    );
                    Event::addEvent(LOG_USER_ENABLE, LOG_USER_ID, $user_id);
                } else {
                    Event::addEvent(LOG_USER_DISABLE, LOG_USER_ID, $user_id);
                }
                echo $status;
            }
        } else {
            echo '-1';
        }
        break;
    case 'user_by_role':
        api_block_anonymous_users(false);

        $status = isset($_REQUEST['status']) ? (int) $_REQUEST['status'] : DRH;
        $active = isset($_REQUEST['active']) ? (int) $_REQUEST['active'] : null;

        $criteria = new Criteria();
        $criteria
            ->where(
                Criteria::expr()->orX(
                    Criteria::expr()->contains('username', $_REQUEST['q']),
                    Criteria::expr()->contains('firstname', $_REQUEST['q']),
                    Criteria::expr()->contains('lastname', $_REQUEST['q'])
                )
            )
            ->andWhere(
                Criteria::expr()->eq('status', $status)
            );

        if (null !== $active) {
            $criteria->andWhere(Criteria::expr()->eq('active', $active));
        }
        $users = UserManager::getRepository()->matching($criteria);

        if (!$users->count()) {
            echo json_encode([]);
            break;
        }

        $items = [];

        /** @var User $user */
        foreach ($users as $user) {
            $items[] = [
                'id' => $user->getId(),
                'text' => UserManager::formatUserFullName($user, true),
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(['items' => $items]);
        break;
    case 'teacher_to_basis_course':
        api_block_anonymous_users(false);

        $sortByFirstName = api_sort_by_first_name();
        $urlId = api_get_current_access_url_id();

        $qb = UserManager::getRepository()->createQueryBuilder('u');
        $qb->where(
            $qb->expr()->orX(
                $qb->expr()->like('u.username', ':q'),
                $qb->expr()->like('u.firstname', ':q'),
                $qb->expr()->like('u.lastname', ':q')
            )
        );

        if (api_is_multiple_url_enabled()) {
            $qb
                ->innerJoin('ChamiloCoreBundle:AccessUrlRelUser', 'uru', Join::WITH, 'u.userId = uru.userId')
                ->andWhere('uru.accessUrlId = '.$urlId);
        }

        $qb
            ->andWhere(
                $qb->expr()->in('u.status', UserManager::getAllowedRolesAsTeacher())
            )
            ->orderBy(
                $sortByFirstName
                    ? 'u.firstname, u.lastname'
                    : 'u.lastname, u.firstname'
            )
            ->setParameter('q', '%'.$_REQUEST['q'].'%');

        $users = $qb->getQuery()->getResult();

        if (!$users) {
            echo json_encode([]);
            break;
        }

        $items = [];

        /** @var User $user */
        foreach ($users as $user) {
            $items[] = [
                'id' => $user->getId(),
                'text' => UserManager::formatUserFullName($user, true),
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(['items' => $items]);
        break;
    default:
        echo '';
}
exit;
