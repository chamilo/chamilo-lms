<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/cm_webservice.php';

/**
 * Description of cm_soap_inbox.
 *
 * @author marcosousa
 */
class WSCMInbox extends WSCM
{
    public function unreadMessage($username, $password)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $table_message = Database::get_main_table(TABLE_MESSAGE);
            $user_id = UserManager::get_user_id_from_username($username);
            $condition_msg_status = ' msg_status = 1 '; // define('MESSAGE_STATUS_UNREAD', '1');

            $sql_query = "SELECT COUNT(*) as number_messages
                          FROM $table_message
                          WHERE $condition_msg_status AND user_receiver_id=".$user_id;

            $sql_result = Database::query($sql_query);
            $result = Database::fetch_array($sql_result);

            return $result['number_messages'];
        }

        return "0";
    }

    public function get_message_id(
        $username,
        $password,
        $from,
        $number_of_items
    ) {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $user_id = UserManager::get_user_id_from_username($username);
            $table_message = Database::get_main_table(TABLE_MESSAGE);

            $sql_query = "SELECT id FROM $table_message ".
                                     " WHERE user_receiver_id=".$user_id." AND msg_status IN (0,1)".
                                     " ORDER BY send_date LIMIT $from,$number_of_items";

            $sql_result = Database::query($sql_query);
            $message = "#";
            while ($result = Database::fetch_row($sql_result)) {
                $message .= $result[0]."#";
            }

            return $message;
        } else {
            return get_lang('InvalidId');
        }
    }

    public function get_message_data($username, $password, $message_id, $field)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $htmlcode = false;
            $user_id = UserManager::get_user_id_from_username($username);
            switch ($field) {
                case 'sender':
                    $field_table = "user_sender_id";
                    break;
                case 'title':
                    $htmlcode = true;
                    $field_table = "title";
                    break;
                case 'date':
                    $field_table = "send_date";
                    break;
                case 'status':
                    $field_table = "msg_status";
                    break;
                case 'content':
                    $this->set_message_as_read($user_id, $message_id);
                    $htmlcode = true;
                    $field_table = "content";
                    break;
                default:
                    $field_table = "title";
            }

            $table_message = Database::get_main_table(TABLE_MESSAGE);

            $sql_query = "SELECT ".$field_table." FROM $table_message ".
                                     " WHERE user_receiver_id=".$user_id." AND id=".$message_id;

            $sql_result = Database::query($sql_query);
            $result = Database::fetch_row($sql_result);

            return $htmlcode ? html_entity_decode($result[0]) : $result[0];
        } else {
            return get_lang('InvalidId');
        }
    }

    public function get_message_id_sent(
        $username,
        $password,
        $from,
        $number_of_items
    ) {
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;

        if ($this->verifyUserPass($username, $password) == "valid") {
            $user_id = UserManager::get_user_id_from_username($username);

            $table_message = Database::get_main_table(TABLE_MESSAGE);
            $sql_query = "SELECT id FROM $table_message
                          WHERE user_sender_id=".$user_id." AND msg_status=".MESSAGE_STATUS_OUTBOX."
                          ORDER BY send_date
                          LIMIT $from,$number_of_items";

            $sql_result = Database::query($sql_query);
            $message = "#";
            while ($result = Database::fetch_row($sql_result)) {
                $message .= $result[0]."#";
            }

            return $message;
        }

        return get_lang('InvalidId');
    }

    public function get_message_data_sent($username, $password, $id, $field)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $htmlcode = false;
            switch ($field) {
                case 'sender':
                    $field_table = "user_sender_id";
                    break;
                case 'title':
                    $htmlcode = true;
                    $field_table = "title";
                    break;
                case 'date':
                    $field_table = "send_date";
                    break;
                case 'status':
                    $field_table = "msg_status";
                    break;
                case 'content':
                    $htmlcode = true;
                    $field_table = "content";
                    break;
                default:
                    $field_table = "title";
            }
            $user_id = UserManager::get_user_id_from_username($username);
            $table_message = Database::get_main_table(TABLE_MESSAGE);
            $sql_query = "SELECT ".$field_table." FROM $table_message ".
                         " WHERE user_sender_id=".$user_id." AND id=".$id;
            $sql_result = Database::query($sql_query);
            $result = Database::fetch_row($sql_result);

            return $htmlcode ? html_entity_decode($result[0]) : $result[0];
        } else {
            return get_lang('InvalidId');
        }
    }

    public function message_send(
        $username,
        $password,
        $receiver_user_id,
        $subject,
        $content
    ) {
        //TODO: verificar data de envio. Esta divergindo de data!
        if ($this->verifyUserPass($username, $password) == "valid") {
            $group_id = intval(0);
            $parent_id = intval(0);
            $edit_message_id = intval(0);
            $sent_email = false;
            $user_sender_id = UserManager::get_user_id_from_username($username);

            $subject = htmlentities($subject);
            $content = htmlentities($content);

            $table_message = Database::get_main_table(TABLE_MESSAGE);

            $query = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content, group_id, parent_id, update_date ) ".
                     " VALUES ('$user_sender_id', '$receiver_user_id', '1', '".api_get_utc_datetime()."','$subject','$content','$group_id','$parent_id', '".api_get_utc_datetime()."')";
            Database::query($query);

            $query = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content, group_id, parent_id, update_date ) ".
                           " VALUES ('$user_sender_id', '$receiver_user_id', '4', '".api_get_utc_datetime()."','$subject','$content','$group_id','$parent_id', '".api_get_utc_datetime()."')";
            Database::query($query);

            $inbox_last_id = Database::insert_id();

            return $inbox_last_id;
        } else {
            return get_lang('InvalidId');
        }
    }

    protected function set_message_as_read($user_id, $message_id)
    {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $query = "UPDATE $table_message SET msg_status = '".MESSAGE_STATUS_NEW."'
                  WHERE user_receiver_id=".$user_id." AND id='".$message_id."';";
        Database::query($query);
    }
}

/*
echo "aqui: ";
$aqui = new WSCMInbox();

//print_r($aqui->unreadMessage("aluno", "e695f51fe3dd6b7cf2be3188a614f10f"));
print_r($aqui->message_send("aluno", "356a192b7913b04c54574d18c28d46e6395428ab", "1", "Título da mensagem", "Conteúdo da mensagem com ç ã"));


*/
