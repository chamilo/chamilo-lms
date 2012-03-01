<?php

require_once(dirname(__FILE__).'/../inc/global.inc.php');
require_once(dirname(__FILE__).'/cm_webservice.php');

/**
 * Description of cm_soap_user
 *
 * @author marcosousa
 */

class WSCMUser extends WSCM {

    public function find_id_user($username, $password, $name)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {

            $listResult = "#";
            
            $listArrayResult = Array();
            $listArray = Array();

            $list = $this->get_user_list_like_start(array('firstname'=>$name), array('firstname'));
            foreach ($list as $userData)
            {
                $listArray[] = $userData['user_id'];
            }

            $list = $this->get_user_list_like_start(array('lastname'=>$name), array('firstname'));
            foreach ($list as $userData)
            {
                $listArray[] = $userData['user_id'];
            }

            $list = $this->get_user_list_like_start(array('email'=>$name), array('firstname'));
            foreach ($list as $userData)
            {
                $listArray[] = $userData['user_id'];
            }

            $listArrayResult = array_unique($listArray);
            foreach($listArrayResult as $result)
            {
                $listResult .= $result . "#";
            }

            return $listResult;
        }
        return "0";
    }

    public function get_link_user_picture($username, $password, $id)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $userPic = UserManager::get_user_picture_path_by_id($id, "web");
            if(empty ($userPic['file']))
                return "0";
            return $userPic['dir'].$userPic['file'];
        }
        return "0";
    }

    public function get_user_name($username, $password, $id, $field)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $userInfo = UserManager::get_user_info_by_id($id);
            switch ($field)
            {

                case 'firstname':
                    return $userInfo['firstname'];
                    break;
                case 'lastname' :
                    return $userInfo['lastname'];
                    break;
                case 'bothfl' :
                    return $userInfo['firstname']." ".$userInfo['lastname'];
                    break;
                case 'bothlf' :
                    return $userInfo['lastname']." ".$userInfo['firstname'];
                    break;
                default :
                    return $userInfo['firstname'];
            }
            return "0";
        }
        return "0";
    }

    public function send_invitation($username, $password, $userfriend_id, $content_message = '')
    {
        global $charset;
        if($this->verifyUserPass($username, $password) == "valid")
        { 
		$user_id = UserManager::get_user_id_from_username($username); 
                $message_title = get_lang('Invitation'); 
                $count_is_true = SocialManager::send_invitation_friend($user_id,$userfriend_id, $message_title, $content_message);

                if ($count_is_true) {
                        return Display::display_normal_message(api_htmlentities(get_lang('InvitationHasBeenSent'), ENT_QUOTES,$charset),false);
                }else {
                        return Display::display_error_message(api_htmlentities(get_lang('YouAlreadySentAnInvitation'), ENT_QUOTES,$charset),false);
                }
        }
        return get_lang('InvalidId');
    }

    public function accept_friend($username, $password, $userfriend_id)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $user_id = UserManager::get_user_id_from_username($username);
            UserManager::relate_users($userfriend_id, $user_id, USER_RELATION_TYPE_FRIEND);
            SocialManager::invitation_accepted($userfriend_id, $user_id);
            return get_lang('AddedContactToList');
        }
        return get_lang('InvalidId');
    }

    public function denied_invitation($username, $password, $userfriend_id)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $user_id = UserManager::get_user_id_from_username($username);
            SocialManager::invitation_denied($userfriend_id, $user_id);
            return get_lang('InvitationDenied');
        }
        return get_lang('InvalidId');
    }


    /**
    * Get a list of users of which the given conditions match with a LIKE '%cond%'
    * @param array $conditions a list of condition (exemple : status=>STUDENT)
    * @param array $order_by a list of fields on which sort
    * @return array An array with all users of the platform.
    * @todo optional course code parameter, optional sorting parameters...
     *@todo Use the UserManager class
    */
    private static function get_user_list_like_start($conditions = array(), $order_by = array()) {
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $return_array = array();
        $sql_query = "SELECT * FROM $user_table";
        if (count($conditions) > 0) {
            $sql_query .= ' WHERE ';
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $sql_query .= $field.' LIKE \''.$value.'%\'';
            }
        }
        if (count($order_by) > 0) {
            $sql_query .= ' ORDER BY '.Database::escape_string(implode(',', $order_by));
        }
        
        $sql_result = Database::query($sql_query);
        while ($result = Database::fetch_array($sql_result)) {
            $return_array[] = $result;
        }
        return $return_array;
    }

 
}

/*
echo "aqui: ";
$aqui = new WSCMUser();

//print_r($aqui->unreadMessage("aluno", "e695f51fe3dd6b7cf2be3188a614f10f"));
//print_r($aqui->send_invitation("marco", "c4ca4238a0b923820dcc509a6f75849b", "1", "oia ai"));
print_r($aqui->denied_invitation("admin", "c4ca4238a0b923820dcc509a6f75849b", "3"));
*/

?>
