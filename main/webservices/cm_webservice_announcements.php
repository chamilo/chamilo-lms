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
class WSCMAnnouncements extends WSCM
{
    public function get_announcements_id($username, $password, $course_code)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $result = self::get_announcements($username, $course_code);

            $announcements = "#";
            while ($announcement = Database::fetch_array($result)) {
                $announcements .= $announcement['id']."#";
            }

            return $announcements;
        } else {
            return get_lang('InvalidId');
        }
    }

    public function get_announcement_data(
        $username,
        $password,
        $course_code,
        $announcement_id,
        $field
    ) {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $htmlcode = false;
            $user_id = UserManager::get_user_id_from_username($username);

            $result = self::get_announcements(
                $username,
                $course_code,
                $announcement_id
            );
            while ($announcement = Database::fetch_array($result)) {
                $announcements[] = $announcement;
            }

            switch ($field) {
                case 'sender':
                    $field_table = "insert_user_id";
                    $sender = api_get_user_info(
                        $announcements[0][$field_table]
                    );
                    $announcements[0][$field_table] = $sender['firstname']." ".$sender['lastname'];
                    break;
                case 'title':
                    $htmlcode = true;
                    $field_table = "title";
                    break;
                case 'date':
                    $field_table = "end_date";
                    break;
                case 'content':
                    $htmlcode = true;
                    $field_table = "content";
                    $announcements[0][$field_table] = nl2br_revert(
                        $announcements[0][$field_table]
                    );
                    break;
                default:
                    $field_table = "title";
            }

            return (htmlcode) ? html_entity_decode(
                $announcements[0][$field_table]
            ) : $announcements[0][$field_table];
        } else {
            return get_lang('InvalidId');
        }
    }

    private function get_announcements(
        $username,
        $course_code,
        $announcement_id = 0
    ) {
        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition($session_id);

        $announcement_id = ($announcement_id == 0) ? "" : "AND announcement.id=".$announcement_id;
        $user_id = UserManager::get_user_id_from_username($username);
        $course_info = CourseManager::get_course_information($course_code);
        $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
        $maximum = '12';

        $group_memberships = GroupManager::get_group_ids(
            $course_info['real_id'],
            $user_id
        );

        if (api_get_group_id() == 0) {
            $cond_user_id = " AND (
                ip.to_user_id='".$user_id."' OR
                ip.to_group_id IN (0, ".implode(", ", $group_memberships).") OR
                ip.to_group_id IS NULL
            ) ";
        } else {
            $cond_user_id = " AND (
                ip.to_user_id='".$user_id."' OR
                ip.to_group_id IN (0, ".api_get_group_id().") OR
                ip.to_group_id IS NULL
            ) ";
        }

        // the user is member of several groups => display personal
        // announcements AND his group announcements AND the general announcements
        if (is_array($group_memberships) && count($group_memberships) > 0) {
            $sql = "SELECT
                    announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
                FROM $tbl_announcement announcement, $tbl_item_property ip
                WHERE
                    announcement.id = ip.ref AND
                    ip.tool='announcement' AND
                    ip.visibility='1'
                    $announcement_id
                    $cond_user_id
                    $condition_session
                GROUP BY ip.ref
                ORDER BY display_order DESC
                LIMIT 0,$maximum";
        } else {
            // the user is not member of any group
            // this is an identified user => show the general announcements AND his personal announcements
            if ($user_id) {
                if ((api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
                    $cond_user_id = " AND (
                        ip.lastedit_user_id = '".api_get_user_id()."' OR
                        ( ip.to_user_id='".$user_id."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL)
                    ) ";
                } else {
                    $cond_user_id = " AND ( ip.to_user_id='".$user_id."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL) ";
                }

                $sql = "SELECT
                        announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
                        FROM $tbl_announcement announcement, $tbl_item_property ip
                        WHERE
                            announcement.id = ip.ref AND
                            ip.tool='announcement' AND
                            ip.visibility='1'
                            $announcement_id
                            $cond_user_id
                            $condition_session
                        GROUP BY ip.ref
                        ORDER BY display_order DESC
                        LIMIT 0,$maximum";
            } else {
                if (api_get_course_setting('allow_user_edit_announcement')) {
                    $cond_user_id = " AND (
                        ip.lastedit_user_id = '".api_get_user_id()."' OR ip.to_group_id='0' OR ip.to_group_id IS NULL
                    ) ";
                } else {
                    $cond_user_id = " AND ip.to_group_id='0' ";
                }

                // the user is not identiefied => show only the general announcements
                $sql = "SELECT
                        announcement.*, ip.visibility, ip.to_group_id, ip.insert_user_id
                        FROM $tbl_announcement announcement, $tbl_item_property ip
                        WHERE announcement.id = ip.ref
                        AND ip.tool='announcement'
                        AND ip.visibility='1'
                        AND ip.to_group_id='0'
                        $announcement_id
                        $condition_session
                        GROUP BY ip.ref
                        ORDER BY display_order DESC
                        LIMIT 0,$maximum";
            }
        }

        $result = Database::query($sql);

        return $result;
    }
}

/*
echo "aqui: ";
$aqui = new WSCMAnnouncements();
echo "<pre>";
//print_r($aqui->unreadMessage("aluno", "e695f51fe3dd6b7cf2be3188a614f10f"));
print_r($aqui->get_announcement_data("aluno", "c4ca4238a0b923820dcc509a6f75849b", "P0204", "17", "title"));
echo "</pre>";
*/
