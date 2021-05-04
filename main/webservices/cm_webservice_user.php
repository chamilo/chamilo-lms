<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/cm_webservice.php';

/**
 * Description of cm_soap_user.
 *
 * @author marcosousa
 */
class WSCMUser extends WSCM
{
    public function find_id_user($username, $password, $name)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $listResult = "#";

            $listArrayResult = [];
            $listArray = [];

            $list = $this->get_user_list_like_start(
                ['firstname' => $name],
                ['firstname']
            );
            foreach ($list as $userData) {
                $listArray[] = $userData['user_id'];
            }

            $list = $this->get_user_list_like_start(
                ['lastname' => $name],
                ['firstname']
            );
            foreach ($list as $userData) {
                $listArray[] = $userData['user_id'];
            }

            $list = $this->get_user_list_like_start(
                ['email' => $name],
                ['firstname']
            );
            foreach ($list as $userData) {
                $listArray[] = $userData['user_id'];
            }

            $listArrayResult = array_unique($listArray);
            foreach ($listArrayResult as $result) {
                $listResult .= $result."#";
            }

            return $listResult;
        }

        return "0";
    }

    public function get_link_user_picture($username, $password, $id)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $userPic = UserManager::getUserPicture($id);
            if (empty($userPic)) {
                return "0";
            }

            return $userPic;
        }

        return "0";
    }

    public function get_user_name($username, $password, $id, $field)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $userInfo = api_get_user_info($id);
            switch ($field) {
                case 'firstname':
                    return $userInfo['firstname'];
                    break;
                case 'lastname':
                    return $userInfo['lastname'];
                    break;
                case 'bothfl':
                    return $userInfo['firstname']." ".$userInfo['lastname'];
                    break;
                case 'bothlf':
                    return $userInfo['lastname']." ".$userInfo['firstname'];
                    break;
                default:
                    return $userInfo['firstname'];
            }

            return "0";
        }

        return "0";
    }

    public function send_invitation(
        $username,
        $password,
        $userfriend_id,
        $content_message = ''
    ) {
        global $charset;
        if ($this->verifyUserPass($username, $password) == "valid") {
            $user_id = UserManager::get_user_id_from_username($username);
            $message_title = get_lang('Invitation');
            $count_is_true = SocialManager::send_invitation_friend(
                $user_id,
                $userfriend_id,
                $message_title,
                $content_message
            );

            if ($count_is_true) {
                return Display::return_message(
                    api_htmlentities(
                        get_lang('InvitationHasBeenSent'),
                        ENT_QUOTES,
                        $charset
                    ),
                    'normal',
                    false
                );
            } else {
                return Display::return_message(
                    api_htmlentities(
                        get_lang('YouAlreadySentAnInvitation'),
                        ENT_QUOTES,
                        $charset
                    ),
                    'error',
                    false
                );
            }
        }

        return get_lang('InvalidId');
    }

    public function accept_friend($username, $password, $userfriend_id)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $user_id = UserManager::get_user_id_from_username($username);
            UserManager::relate_users(
                $userfriend_id,
                $user_id,
                USER_RELATION_TYPE_FRIEND
            );
            SocialManager::invitation_accepted($userfriend_id, $user_id);

            return get_lang('AddedContactToList');
        }

        return get_lang('InvalidId');
    }

    public function denied_invitation($username, $password, $userfriend_id)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $user_id = UserManager::get_user_id_from_username($username);
            SocialManager::invitation_denied($userfriend_id, $user_id);

            return get_lang('InvitationDenied');
        }

        return get_lang('InvalidId');
    }

    /**
     * Get a list of users of which the given conditions match with a LIKE '%cond%'.
     *
     * @param array $conditions a list of condition (exemple : status=>STUDENT)
     * @param array $order_by   a list of fields on which sort
     *
     * @return array an array with all users of the platform
     *
     * @todo optional course code parameter, optional sorting parameters...
     *@todo Use the UserManager class
     * @todo security filter order by
     */
    private static function get_user_list_like_start($conditions = [], $order_by = [])
    {
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $return_array = [];
        $sql_query = "SELECT * FROM $user_table";
        if (count($conditions) > 0) {
            $sql_query .= ' WHERE ';
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $sql_query .= $field.' LIKE \''.$value.'%\'';
            }
        }
        $order = '';
        foreach ($order_by as $orderByItem) {
            $order .= Database::escape_string($orderByItem).', ';
        }
        $order = substr($order, 0, -2);
        if (count($order_by) > 0) {
            $sql_query .= ' ORDER BY '.$order;
        }

        $sql_result = Database::query($sql_query);
        while ($result = Database::fetch_array($sql_result)) {
            $return_array[] = $result;
        }

        return $return_array;
    }
}
