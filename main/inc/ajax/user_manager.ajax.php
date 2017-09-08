<?php
/* For licensing terms, see /license.txt */
use Doctrine\Common\Collections\Criteria,
    Chamilo\UserBundle\Entity\User,
    Doctrine\ORM\Query\Expr\Join;

/**
 * Responses to AJAX calls
 */
require_once __DIR__.'/../global.inc.php';

$action = $_GET['a'];

switch ($action) {
    case 'get_user_like':
        $query = $_REQUEST['q'];
        $conditions = [
            'username' => $query,
            'firstname' => $query,
            'lastname' => $query,
        ];
        $users = UserManager::get_user_list_like($conditions, [], false, 'OR');
        $result = [];
        if (!empty($users)) {
            foreach ($users as $user) {
                $result[] = ['id' => $user['id'], 'text' => $user['complete_name'].' ('.$user['username'].')'];
            }
            $result['items'] = $result;
        }
        echo json_encode($result);
        break;
    case 'get_user_popup':
        $user_info = api_get_user_info($_REQUEST['user_id']);
        $ajax_url = api_get_path(WEB_AJAX_PATH).'message.ajax.php';

        echo '<div class="row">';
        echo '<div class="col-sm-5">';
        echo '<div class="thumbnail">';
        echo '<img src="'.$user_info['avatar'].'" /> ';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-sm-7">';
        if (api_get_setting('show_email_addresses') == 'false') {
            $user_info['mail'] = ' ';
        } else {
            $user_info['mail'] = ' '.$user_info['mail'].' ';
        }
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user_info['user_id'].'">';
        echo '<h3>'.$user_info['complete_name'].'</h3>'.$user_info['mail'].$user_info['official_code'];
        echo '</a>';
        echo '</div>';
        echo '</div>';

        if (api_get_setting('allow_message_tool') == 'true') {
            echo '<script>';
            echo '
                $("#send_message_link").on("click", function() {
                    var url = "'.$ajax_url.'?a=send_message&user_id='.$user_info['user_id'].'";
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
                            <em class="fa fa-envelope"></em> ' . get_lang('Send').'
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
            $array_list_key = array();
            $user_id = api_get_user_id();
            $api_service = 'dokeos';
            $num = UserManager::update_api_key($user_id, $api_service);
            $array_list_key = UserManager::get_api_keys($user_id, $api_service);
            ?>
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
        if (api_is_platform_admin() && api_global_admin_can_edit_admin($_GET['user_id'])) {
            $user_id = intval($_GET['user_id']);
            $status  = intval($_GET['status']);

            if (!empty($user_id)) {
                $user_table = Database::get_main_table(TABLE_MAIN_USER);
                $sql = "UPDATE $user_table 
                        SET active='".$status."' 
                        WHERE user_id='".$user_id."'";
                $result = Database::query($sql);

                //Send and email if account is active
                if ($status == 1) {
                    $user_info = api_get_user_info($user_id);
                    $recipient_name = api_get_person_name($user_info['firstname'], $user_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
                    $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
                    $email_admin = api_get_setting('emailAdministrator');
                    $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
                    $emailbody = get_lang('Dear')." ".stripslashes($recipient_name).",\n\n";

                    $emailbody .= sprintf(get_lang('YourAccountOnXHasJustBeenApprovedByOneOfOurAdministrators'), api_get_setting('siteName'))."\n";
                    $emailbody .= sprintf(get_lang('YouCanNowLoginAtXUsingTheLoginAndThePasswordYouHaveProvided'), api_get_path(WEB_PATH)).",\n\n";
                    $emailbody .= get_lang('HaveFun')."\n\n";
                    //$emailbody.=get_lang('Problem'). "\n\n". get_lang('SignatureFormula');
                    $emailbody .= api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".get_lang('Manager')." ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n".get_lang('Email')." : ".api_get_setting('emailAdministrator');

                    $additionalParameters = array(
                        'smsType' => SmsPlugin::ACCOUNT_APPROVED_CONNECT,
                        'userId' => $user_id
                    );

                    $result = api_mail_html(
                        $recipient_name,
                        $user_info['mail'],
                        $emailsubject,
                        $emailbody,
                        $sender_name,
                        $email_admin,
                        null,
                        null,
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
                Criteria::expr()->eq('status', DRH)
            );

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
                'text' => $user->getCompleteNameWithUsername()
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
                'text' => $user->getCompleteNameWithUsername()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(['items' => $items]);
        break;
    default:
        echo '';
}
exit;
