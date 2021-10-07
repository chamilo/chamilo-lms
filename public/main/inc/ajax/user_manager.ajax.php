<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = $_GET['a'];

switch ($action) {
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
        $courseId = isset($_REQUEST['course_id']) ? (int) $_REQUEST['course_id'] : 0;
        $sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;

        $user_info = api_get_user_info($_REQUEST['user_id']);
        $isAnonymous = api_is_anonymous();

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

        if ($isAnonymous || 'false' == api_get_setting('show_email_addresses')) {
            $user_info['mail'] = ' ';
        }

        $userData = '<h3>'.$user_info['complete_name'].'</h3>'
            .PHP_EOL
            .$user_info['mail']
            .PHP_EOL
            .$user_info['official_code'];

        if ($isAnonymous) {
            // Only allow anonymous users to see user popup if the popup user
            // is a teacher (which might be necessary to illustrate a course)
            if (COURSEMANAGER === (int) $user_info['status']) {
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

        if (false === $isAnonymous &&
            'true' == api_get_setting('allow_message_tool')
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
                            <em class="fa fa-envelope"></em> '.get_lang('Send message').'
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
                <label class="col-sm-2 control-label"><?php echo get_lang('My API key'); ?></label>
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
                        WHERE id = '".$user_id."'";
                $result = Database::query($sql);

                // Send and email if account is active
                if (1 == $status) {
                    $user_info = api_get_user_info($user_id);
                    $recipientName = api_get_person_name(
                        $user_info['firstname'],
                        $user_info['lastname'],
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    );

                    $subject = '['.api_get_setting('siteName').'] '.get_lang('Your registration on').' '.api_get_setting('siteName');
                    $emailAdmin = api_get_setting('emailAdministrator');
                    $sender_name = api_get_person_name(
                        api_get_setting('administratorName'),
                        api_get_setting('administratorSurname'),
                        null,
                        PERSON_NAME_EMAIL_ADDRESS
                    );
                    $body = get_lang('Dear')." ".stripslashes($recipientName).",\n\n";
                    $body .= sprintf(
                        get_lang('Your account on %s has just been approved by one of our administrators.'),
                        api_get_setting('siteName')
                    )."\n";
                    $body .= sprintf(
                        get_lang('You can now login at %s using the login and the password you have provided.'),
                        api_get_path(WEB_PATH)
                    ).",\n\n";
                    $body .= get_lang('Have fun,')."\n\n";
                    //$body.=get_lang('In case of trouble, contact us.'). "\n\n". get_lang('Sincerely');
                    $body .= api_get_person_name(
                        api_get_setting('administratorName'),
                        api_get_setting('administratorSurname')
                    )."\n".
                    get_lang('Administrator')." ".
                    api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n".
                    get_lang('e-mail')." : ".api_get_setting('emailAdministrator');

                    MessageManager::send_message_simple(
                        $user_id,
                        $subject,
                        $body
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

        $role = User::getRoleFromStatus($status);

        $users = Container::getUserRepository()->findByRole($role, $_REQUEST['q'], api_get_current_access_url_id());

        if (empty($users)) {
            echo json_encode([]);
            break;
        }

        $items = [];

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
                ->innerJoin('ChamiloCoreBundle:AccessUrlRelUser', 'uru', Join::WITH, 'u.id = uru.user')
                ->andWhere('uru.url = '.$urlId);
        }

        $qb
            ->andWhere('u.status != '.DRH.' AND u.status != '.ANONYMOUS)
            ->orderBy(
                $sortByFirstName
                    ? 'u.firstname, u.firstname'
                    : 'u.firstname, u.lastname'
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
