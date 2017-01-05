<?php
/* For licensing terms, see /license.txt */

use Chamilo\TicketBundle\Entity\Project;
use Chamilo\TicketBundle\Entity\Status;
use Chamilo\TicketBundle\Entity\Priority;
use Chamilo\TicketBundle\Entity\Ticket;

/**
 * Class TicketManager
 * @package chamilo.plugin.ticket
 */
class TicketManager
{
    const PRIORITY_NORMAL = 'NRM';
    const PRIORITY_HIGH = 'HGH';
    const PRIORITY_LOW = 'LOW';

    const SOURCE_EMAIL = 'MAI';
    const SOURCE_PHONE = 'TEL';
    const SOURCE_PLATFORM = 'PLA';
    const SOURCE_PRESENTIAL = 'PRE';

    const STATUS_NEW = 'NAT';
    const STATUS_PENDING = 'PND';
    const STATUS_UNCONFIRMED = 'XCF';
    const STATUS_CLOSE = 'CLS';
    const STATUS_FORWARDED = 'REE';

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Get categories of tickets
     * @return array
     */
    public static function get_all_tickets_categories($projectId, $order = '')
    {
        $table_support_category = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $table_support_project = Database::get_main_table(TABLE_TICKET_PROJECT);

        $order = empty($order) ? 'category.total_tickets DESC' : $order;
        $projectId = (int) $projectId;

        $sql = "SELECT 
                    category.*, 
                    category.id category_id,
                    project.other_area, 
                    project.email
                FROM 
                $table_support_category category INNER JOIN
                $table_support_project project
                ON project.id = category.project_id
                WHERE project.id  = $projectId
                ORDER BY $order";
        $result = Database::query($sql);
        $types = array();
        while ($row = Database::fetch_assoc($result)) {
            $types[] = $row;
        }

        return $types;
    }

    /**
     * @param $from
     * @param $numberItems
     * @param $column
     * @param $direction
     * @return array
     */
    public static function getCategories($from, $numberItems, $column, $direction)
    {
        $table = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $sql = "SELECT id, name, description, total_tickets
                FROM $table";

        if (!in_array($direction, array('ASC','DESC'))) {
            $direction = 'ASC';
        }
        $column = intval($column);
        $from = intval($from);
        $numberItems = intval($numberItems);

        //$sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$numberItems";

        $result = Database::query($sql);
        $types = array();
        while ($row = Database::fetch_array($result)) {
            $types[] = $row;
        }

        return $types;
    }

    /**
     * @param int $id
     * @return array|mixed
     */
    public static function getCategory($id)
    {
        $table = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $id = intval($id);
        $sql = "SELECT id, name, description, total_tickets
                FROM $table WHERE id = $id";

        $result = Database::query($sql);
        $category = Database::fetch_array($result);

        return $category;
    }

    /**
     * @return int
     */
    public static function getCategoriesCount()
    {
        $table = Database::get_main_table(TABLE_TICKET_CATEGORY);

        $sql = "SELECT count(id) count
                FROM $table ";

        $result = Database::query($sql);
        $category = Database::fetch_array($result);

        return $category['count'];
    }

    /**
     * @param int $id
     * @param array $params
     */
    public static function updateCategory($id, $params)
    {
        $table = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $id = intval($id);
        Database::update($table, $params, ['id = ?' => $id]);
    }

    /**
     * @param array $params
     */
    public static function addCategory($params)
    {
        $table = Database::get_main_table(TABLE_TICKET_CATEGORY);
        Database::insert($table, $params);
    }

    /**
     * @param int $id
     */
    public static function deleteCategory($id)
    {
        $id = intval($id);

        $table = Database::get_main_table(TABLE_TICKET_TICKET);
        $sql = "UPDATE $table SET category_id = NULL WHERE category_id = $id";
        Database::query($sql);

        $table = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $sql = "DELETE FROM $table WHERE id = $id";
        Database::query($sql);
    }

    /**
     * @param int $categoryId
     * @param array $users
     */
    public static function addUsersToCategory($categoryId, $users)
    {
        $table = Database::get_main_table(TABLE_TICKET_CATEGORY_REL_USER);
        if (empty($users) || empty($categoryId)) {
            return false;
        }

        foreach ($users as $userId) {
            if (self::userIsAssignedToCategory($userId, $categoryId) == false) {
                $params = [
                    'category_id' => $categoryId,
                    'user_id' => $userId
                ];
                Database::insert($table, $params);
            }
        }
    }

    /**
     * @param int $userId
     * @param int $categoryId
     *
     * @return bool
     */
    public static function userIsAssignedToCategory($userId, $categoryId)
    {
        $table = Database::get_main_table(TABLE_TICKET_CATEGORY_REL_USER);
        $userId = intval($userId);
        $categoryId = intval($categoryId);
        $sql = "SELECT * FROM $table 
                WHERE category_id = $categoryId AND user_id = $userId";
        $result = Database::query($sql);

        return Database::num_rows($result) > 0;
    }

    /**
     * @param int $categoryId
     *
     * @return array
     */
    public static function getUsersInCategory($categoryId)
    {
        $table = Database::get_main_table(TABLE_TICKET_CATEGORY_REL_USER);
        $categoryId = intval($categoryId);
        $sql = "SELECT * FROM $table WHERE category_id = $categoryId";
        $result = Database::query($sql);

        return Database::store_result($result);
    }

    /**
     * @param int $categoryId
     */
    public static function deleteAllUserInCategory($categoryId)
    {
        $table = Database::get_main_table(TABLE_TICKET_CATEGORY_REL_USER);
        $categoryId = intval($categoryId);
        $sql = "DELETE FROM $table WHERE category_id = $categoryId";
        Database::query($sql);
    }

    /**
     * Get all possible tickets statuses
     * @return array
     */
    public static function get_all_tickets_status()
    {
        $table_support_status = Database::get_main_table(TABLE_TICKET_STATUS);
        $sql = "SELECT * FROM " . $table_support_status;
        $result = Database::query($sql);
        $types = array();
        while ($row = Database::fetch_assoc($result)) {
            $types[] = $row;
        }

        return $types;
    }

    /**
     * Inserts a new ticket in the corresponding tables
     * @param int $category_id
     * @param int $course_id
     * @param int $sessionId
     * @param int $project_id
     * @param string $other_area
     * @param string $email
     * @param string $subject
     * @param string $content
     * @param string $personalEmail
     * @param $file_attachments
     * @param string $source
     * @param string $priority
     * @param string $status
     * @param int $assigned_user
     * @return bool
     */
    public static function add(
        $category_id,
        $course_id,
        $sessionId,
        $project_id,
        $other_area,
        $email,
        $subject,
        $content,
        $personalEmail = '',
        $file_attachments,
        $source = '',
        $priority = '',
        $status = '',
        $assigned_user = 0
    ) {
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_category = Database::get_main_table(
            TABLE_TICKET_CATEGORY
        );

        $now = api_get_utc_datetime();
        $course_id = intval($course_id);
        $category_id = intval($category_id);
        $project_id = intval($project_id);
        $subject = Database::escape_string($subject);
        // Remove html tags
        $content = strip_tags($content);
        // Remove &nbsp;
        $content = str_replace("&nbsp;", '', $content);
        // Remove \r\n\t\s... from ticket's beginning and end
        $content = trim($content);
        // Replace server newlines with html
        $content = str_replace("\r\n", '<br>', $content);
        $content = Database::escape_string($content);
        $personalEmail = Database::escape_string($personalEmail);
        $status = Database::escape_string($status);
        $priority = empty($priority) ? self::PRIORITY_NORMAL : $priority;

        if ($status === '') {
            $status = self::STATUS_NEW;
            if ($other_area > 0) {
                $status = self::STATUS_FORWARDED;
            }
        }

        if (!empty($category_id)) {
            if (empty($assigned_user)) {
                $usersInCategory = TicketManager::getUsersInCategory($category_id);
                if (!empty($usersInCategory) && count($usersInCategory) > 0) {
                    $userCategoryInfo = $usersInCategory[0];
                    if (isset($userCategoryInfo['user_id'])) {
                        $assigned_user = $userCategoryInfo['user_id'];
                    }
                }
            }
        }

        if (!empty($assigned_user)) {
            $assignedUserInfo = api_get_user_info($assigned_user);
            if (empty($assignedUserInfo)) {
                return false;
            }
        }

        $currentUserId = api_get_user_id();

        // insert_ticket
        $params = [
            'project_id' => $project_id,
            'category_id' => $category_id,
            'priority_id' => $priority,
            'personal_email' => $personalEmail,
            'status_id' => $status,
            'start_date' => $now,
            'sys_insert_user_id' => $currentUserId,
            'sys_insert_datetime' => $now,
            'sys_lastedit_user_id' => $currentUserId,
            'sys_lastedit_datetime' => $now,
            'source' => $source,
            'assigned_last_user' => $assigned_user,
            'subject' => $subject,
            'message' => $content
        ];

        if (!empty($course_id)) {
            $params['course_id'] = $course_id;
        }

        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        $ticket_id = Database::insert($table_support_tickets, $params);

        if ($ticket_id) {
            $ticket_code = "A" . str_pad($ticket_id, 11, '0', STR_PAD_LEFT);

            Display::addFlash(Display::return_message(
                sprintf(
                    get_lang('TicketXCreated'),
                    $ticket_code
                ),
                'normal',
                false
            ));

            if ($assigned_user != 0) {
                self::assign_ticket_user($ticket_id, $assigned_user);

                Display::addFlash(Display::return_message(
                    sprintf(
                        get_lang('TicketXAssignedToUserX'),
                        $ticket_code,
                        $assignedUserInfo['complete_name']
                    ),
                    'normal',
                    false
                ));
            }

            // Update code
            $sql = "UPDATE $table_support_tickets
                    SET code = '$ticket_code'
                    WHERE id = '$ticket_id'";
            Database::query($sql);

            // Update total
            $sql = "UPDATE $table_support_category
                    SET total_tickets = total_tickets + 1
                    WHERE id = $category_id";
            Database::query($sql);

            $user = api_get_user_info($assigned_user);
            $helpDeskMessage =
                '<table>
                        <tr>
                            <td width="100px"><b>' . get_lang('User') . '</b></td>
                            <td width="400px">' . $user['firstname']. ' ' . $user['lastname'] . '</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Username') . '</b></td>
                            <td width="400px">' . $user['username'] . '</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Email') . '</b></td>
                            <td width="400px">' . $user['email'] . '</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Phone') . '</b></td>
                            <td width="400px">' . $user['phone'] . '</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Date') . '</b></td>
                            <td width="400px">' . api_convert_and_format_date($now, DATE_TIME_FORMAT_LONG) . '</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Title') . '</b></td>
                            <td width="400px">' . $subject . '</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Description') . '</b></td>
                            <td width="400px">' . $content . '</td>
                        </tr>
                    </table>';

            if (empty($category_id)) {
                if (api_get_setting('ticket_send_warning_to_all_admins') === 'true') {
                    $warningSubject = sprintf(
                        get_lang('TicketXCreatedWithNoCategory'),
                        $ticket_code
                    );
                    Display::addFlash(Display::return_message($warningSubject));

                    $admins = UserManager::get_all_administrators();
                    foreach ($admins as $userId => $data) {
                        if ($data['active']) {
                            MessageManager::send_message_simple(
                                $userId,
                                $warningSubject,
                                $helpDeskMessage
                            );
                        }
                    }
                }
            } else {
                $categoryInfo = TicketManager::getCategory($category_id);
                $usersInCategory = TicketManager::getUsersInCategory($category_id);

                $message = '<h2>'.get_lang('TicketInformation').'</h2><br />'.$helpDeskMessage;

                if (api_get_setting('ticket_warn_admin_no_user_in_category') === 'true') {
                    $usersInCategory = TicketManager::getUsersInCategory($category_id);
                    if (empty($usersInCategory)) {
                        $subject = sprintf(
                            get_lang('WarningCategoryXDoesntHaveUsers'),
                            $categoryInfo['name']
                        );

                        if (api_get_setting('ticket_send_warning_to_all_admins') === 'true') {
                            Display::addFlash(Display::return_message(
                                sprintf(
                                    get_lang('CategoryWithNoUserNotificationSentToAdmins'),
                                    $categoryInfo['name']
                                ),
                                null,
                                false
                            ));

                            $admins = UserManager::get_all_administrators();
                            foreach ($admins as $userId => $data) {
                                if ($data['active']) {
                                    MessageManager::send_message_simple(
                                        $userId,
                                        $subject,
                                        $message
                                    );
                                }
                            }
                        } else {
                            Display::addFlash(Display::return_message($subject));
                        }
                    }
                }

                // Send notification to all users
                if (!empty($usersInCategory)) {
                    foreach ($usersInCategory as $data) {
                        if ($data['user_id']) {
                            MessageManager::send_message_simple(
                                $data['user_id'],
                                $subject,
                                $message
                            );
                        }
                    }
                }
            }

            global $data_files;
            if ($other_area) {
                // Send email to "other area" email
                api_mail_html(
                    get_lang('VirtualSupport'),
                    $email,
                    get_lang('IncidentResentToVirtualSupport'),
                    $helpDeskMessage,
                    $user['firstname'].' '.$user['lastname'],
                    $personalEmail,
                    array(),
                    $data_files
                );

                // Send email to user
                api_mail_html(
                    get_lang('VirtualSupport'),
                    $user['email'],
                    get_lang('IncidentResentToVirtualSupport'),
                    $helpDeskMessage,
                    $user['firstname'].' '.$user['lastname'],
                    $personalEmail,
                    array(),
                    $data_files
                );

                $studentMessage = sprintf(get_lang('YourQuestionWasSentToTheResponableAreaX'), $email, $email);
                $studentMessage .= sprintf(get_lang('YourAnswerToTheQuestionWillBeSentToX'), $personalEmail);
                self::insert_message(
                    $ticket_id,
                    get_lang('MessageResent'),
                    $studentMessage,
                    null,
                    1
                );
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Assign ticket to admin
     *
     * @param int $ticket_id
     * @param int $user_id
     */
    public static function assign_ticket_user($ticket_id, $user_id)
    {
        $ticket_id = intval($ticket_id);
        $user_id = intval($user_id);

        if (empty($ticket_id)) {
            return false;
        }

        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_assigned_log = Database::get_main_table(TABLE_TICKET_ASSIGNED_LOG);
        $now = api_get_utc_datetime();
        $ticket = self::get_ticket_detail_by_id($ticket_id);

        if ($ticket) {
            $ticket = $ticket['ticket'];
            $oldUserId = $ticket['assigned_last_user'];
            $oldUserName = '-';
            if (!empty($oldUserId)) {
                $oldUserInfo = api_get_user_info($oldUserId);
                $oldUserName = $oldUserInfo['complete_name'];
            }

            $userCompleteName = '-';
            if (!empty($user_id)) {
                $userInfo = api_get_user_info($user_id);
                $userCompleteName = $userInfo['complete_name'];
            }

            $sql = "UPDATE $table_support_tickets
                    SET assigned_last_user = $user_id
                    WHERE id = $ticket_id";
            $result = Database::query($sql);
            if (Database::affected_rows($result) > 0) {
                $insert_id = api_get_user_id();
                $params = [
                    'ticket_id' => $ticket_id,
                    'user_id' => $user_id,
                    'assigned_date' => $now,
                    'sys_insert_user_id' => $insert_id
                ];
                Database::insert($table_support_assigned_log, $params);

                $subject = '';
                $content = sprintf(get_lang('AssignedChangeFromXToY'), $oldUserName, $userCompleteName);

                self::insert_message(
                    $ticket_id,
                    $subject,
                    $content,
                    [],
                    api_get_user_id(),
                    'NOL'
                );

                if ($insert_id !== $user_id) {
                    $info = api_get_user_info($user_id);
                    $sender = api_get_user_info($insert_id);
                    $href = api_get_path(WEB_CODE_PATH).'/ticket/ticket_details.php?ticket_id='.$ticket_id;
                    $message = sprintf(
                        get_lang('TicketAssignedToXCheckZAtLinkY'),
                        $info['complete_name'],
                        $href,
                        $ticket_id
                    );
                    $mailTitle = sprintf(get_lang('TicketXAssigned'), $ticket_id);
                    api_mail_html(
                        $info['complete_name'],
                        $info['mail'],
                        $mailTitle,
                        $message,
                        null, // sender name
                        null, // sender e-mail
                        array(
                            'cc' => $sender['email']
                        ) // should be support e-mail (platform admin) here
                    );
                }
            }
        }
    }

    /**
     * Insert message between Users and Admins
     * @param $ticket_id
     * @param $subject
     * @param $content
     * @param $file_attachments
     * @param $user_id
     * @param string $status
     * @param bool $sendConfirmation
     * @return bool
     */
    public static function insert_message(
        $ticket_id,
        $subject,
        $content,
        $file_attachments,
        $user_id,
        $status = 'NOL',
        $sendConfirmation = false
    ) {
        global $data_files;
        $ticket_id = intval($ticket_id);
        $user_id = intval($user_id);
        $table_support_messages = Database::get_main_table(TABLE_TICKET_MESSAGE);
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_message_attachments = Database::get_main_table(TABLE_TICKET_MESSAGE_ATTACHMENTS);
        if ($sendConfirmation) {
            $form = '<form action="ticket_details.php?ticket_id=' . $ticket_id . '" id="confirmticket" method="POST" >
                         <p>' . get_lang('TicketWasThisAnswerSatisfying') . '</p>
                         <button class="btn btn-primary responseyes" name="response" id="responseyes" value="1">' . get_lang('Yes') . '</button>
                         <button class="btn btn-danger responseno" name="response" id="responseno" value="0">' . get_lang('No') . '</button>
                     </form>';
            $content .= $form;
        }

        $sql = "SELECT COUNT(*) as total_messages
               FROM $table_support_messages
               WHERE ticket_id = $ticket_id";
        $result = Database::query($sql);
        $obj = Database::fetch_object($result);
        $message_id = $obj->total_messages + 1;
        $now = api_get_utc_datetime();

        $params = [
            'ticket_id' => $ticket_id,
            'subject' => $subject,
            'message' => $content,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'sys_insert_user_id' => $user_id,
            'sys_insert_datetime' => $now,
            'sys_lastedit_user_id' => $user_id,
            'sys_lastedit_datetime' => $now,
            'status' => $status
        ];
        Database::insert($table_support_messages, $params);

        // update_total_message
        $sql = "UPDATE $table_support_tickets
                SET 
                    sys_lastedit_user_id ='$user_id',
                    sys_lastedit_datetime ='$now',
                    total_messages = (
                        SELECT COUNT(*) as total_messages
                        FROM $table_support_messages
                        WHERE ticket_id ='$ticket_id'
                    )
                WHERE id = $ticket_id ";
        Database::query($sql);

        $sql = "SELECT COUNT(*) as total_attach
                FROM $table_support_message_attachments
                WHERE ticket_id = $ticket_id AND message_id = $message_id";
        $result = Database::query($sql);
        $obj = Database::fetch_object($result);

        $message_attch_id = $obj->total_attach + 1;
        if (is_array($file_attachments)) {
            foreach ($file_attachments as $file_attach) {
                if ($file_attach['error'] == 0) {
                    $data_files[] = self::save_message_attachment_file(
                        $file_attach,
                        $ticket_id,
                        $message_id,
                        $message_attch_id
                    );
                    $message_attch_id++;
                } else {
                    if ($file_attach['error'] != UPLOAD_ERR_NO_FILE) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Attachment files when a message is sent
     * @param $file_attach
     * @param $ticket_id
     * @param $message_id
     * @param $message_attch_id
     * @return array
     */
    public static function save_message_attachment_file(
        $file_attach,
        $ticket_id,
        $message_id,
        $message_attch_id
    ) {
        $now = api_get_utc_datetime();
        $user_id = api_get_user_id();
        $ticket_id = intval($ticket_id);
        $new_file_name = add_ext_on_mime(
            stripslashes($file_attach['name']), $file_attach['type']
        );
        $file_name = $file_attach['name'];
        $table_support_message_attachments = Database::get_main_table(TABLE_TICKET_MESSAGE_ATTACHMENTS);
        if (!filter_extension($new_file_name)) {
            Display :: display_error_message(
                get_lang('UplUnableToSaveFileFilteredExtension')
            );
        } else {
            $new_file_name = uniqid('');
            $path_attachment = api_get_path(SYS_ARCHIVE_PATH);
            $path_message_attach = $path_attachment . 'plugin_ticket_messageattch/';
            if (!file_exists($path_message_attach)) {
                @mkdir($path_message_attach, api_get_permissions_for_new_directories(), true);
            }
            $new_path = $path_message_attach . $new_file_name;
            if (is_uploaded_file($file_attach['tmp_name'])) {
                @copy($file_attach['tmp_name'], $new_path);
            }
            $safe_file_name = Database::escape_string($file_name);
            $safe_new_file_name = Database::escape_string($new_file_name);
            $sql = "INSERT INTO $table_support_message_attachments (
                    filename,
                    path,
                    ticket_id,
                    message_id,
                    message_attch_id,
                    size,
                    sys_insert_user_id,
                    sys_insert_datetime,
                    sys_lastedit_user_id,
                    sys_lastedit_datetime
                ) VALUES (
                    '$safe_file_name',
                    '$safe_new_file_name',
                    '$ticket_id',
                    '$message_id',
                    '$message_attch_id',
                    '" . $file_attach['size'] . "',
                    '$user_id',
                    '$now',
                    '$user_id',
                    '$now'
                )";
            Database::query($sql);

            return array(
                'path' => $path_message_attach . $safe_new_file_name,
                'filename' => $safe_file_name,
            );
        }
    }

    /**
     * Get tickets by userId
     * @param $from
     * @param $number_of_items
     * @param $column
     * @param $direction
     * @param int $user_id
     * @return array
     */
    public static function get_tickets_by_user_id(
        $from,
        $number_of_items,
        $column,
        $direction,
        $user_id = 0
    ) {
        $table_support_category = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_priority = Database::get_main_table(TABLE_TICKET_PRIORITY);
        $table_support_status = Database::get_main_table(TABLE_TICKET_STATUS);
        $direction = !empty($direction) ? $direction : 'DESC';
        $user_id = !empty($user_id) ? $user_id : api_get_user_id();
        $isAdmin = UserManager::is_admin($user_id);

        $sql = "SELECT DISTINCT 
                ticket.*,
                ticket.id ticket_id,
                ticket.id AS col0,
                ticket.start_date AS col1,
                ticket.sys_lastedit_datetime AS col2,
                cat.name AS col3,
                priority.name AS col5,
                priority.name AS col6,
                status.name AS col7,
                ticket.total_messages AS col8,
                ticket.message AS col9,
                ticket.subject AS subject,
                ticket.assigned_last_user
            FROM $table_support_tickets ticket,
                $table_support_category cat,
                $table_support_priority priority,
                $table_support_status status
            WHERE 
                cat.id = ticket.category_id AND
                ticket.priority_id = priority.id AND
                ticket.status_id = status.id                
        ";

        if (!$isAdmin) {
            $sql .= " AND (ticket.assigned_last_user = $user_id OR ticket.sys_insert_user_id = $user_id )";
        }

        $keyword_unread = '';
        if (isset($_GET['keyword_unread'])) {
            $keyword_unread = Database::escape_string(
                trim($_GET['keyword_unread'])
            );
        }

        // Search simple
        if (isset($_GET['submit_simple'])) {
            if ($_GET['keyword'] != '') {
                $keyword = Database::escape_string(trim($_GET['keyword']));
                $sql .= " AND (
                            ticket.code = '$keyword' OR 
                            ticket.id = '$keyword'                            
                        )";
            }
        }

        // Search advanced
        if (isset($_GET['submit_advanced'])) {
            $keyword_category = Database::escape_string(
                trim($_GET['keyword_category'])
            );
            $keyword_admin = Database::escape_string(
                trim($_GET['keyword_admin'])
            );
            $keyword_start_date_start = Database::escape_string(
                trim($_GET['keyword_start_date_start'])
            );
            $keyword_start_date_end = Database::escape_string(
                trim($_GET['keyword_start_date_end'])
            );
            $keyword_status = Database::escape_string(
                trim($_GET['keyword_status'])
            );
            $keyword_source = isset($_GET['keyword_source']) ? Database::escape_string(trim($_GET['keyword_source'])) : '';
            $keyword_priority = Database::escape_string(
                trim($_GET['keyword_priority'])
            );

            $keyword_range = isset($_GET['keyword_dates']) ? Database::escape_string(trim($_GET['keyword_dates'])) : '';
            $keyword_course = Database::escape_string(
                trim($_GET['keyword_course'])
            );

            if ($keyword_category != '') {
                $sql .= " AND ticket.category_id = '$keyword_category'  ";
            }
            /*if ($keyword_request_user != '') {
                $sql .= " AND (ticket.request_user = '$keyword_request_user'
                          OR concat(user.firstname,' ',user.lastname) LIKE '%$keyword_request_user%'
                          OR concat(user.lastname,' ',user.firstname) LIKE '%$keyword_request_user%'
                          OR user.username LIKE '%$keyword_request_user%') ";
            }*/
            if ($keyword_admin != '') {
                $sql .= " AND ticket.assigned_last_user = '$keyword_admin'  ";
            }
            if ($keyword_status != '') {
                $sql .= " AND ticket.status_id = '$keyword_status'  ";
            }
            if ($keyword_range == '' && $keyword_start_date_start != '') {
                $sql .= " AND DATE_FORMAT(ticket.start_date,'%d/%m/%Y') = '$keyword_start_date_start' ";
            }
            if ($keyword_range == '1' && $keyword_start_date_start != '' && $keyword_start_date_end != '') {
                $sql .= " AND DATE_FORMAT(ticket.start_date,'%d/%m/%Y') >= '$keyword_start_date_start'
                          AND DATE_FORMAT(ticket.start_date,'%d/%m/%Y') <= '$keyword_start_date_end'";
            }
            if ($keyword_priority != '') {
                $sql .= " AND ticket.priority_id = '$keyword_priority'  ";
            }
            if ($keyword_source != '') {
                $sql .= " AND ticket.source = '$keyword_source' ";
            }
            if ($keyword_course != '') {
                $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
                $sql .= " AND ticket.course_id IN ( 
                         SELECT id FROM $course_table
                         WHERE (
                            title LIKE '%$keyword_course%' OR 
                            code LIKE '%$keyword_course%' OR 
                            visual_code LIKE '%$keyword_course%'
                         )
                )";
            }
        }

        $sql .= " ORDER BY col$column $direction";
        $sql .= " LIMIT $from, $number_of_items";

        $result = Database::query($sql);
        $tickets = array();
        $webPath = api_get_path(WEB_PATH);
        $webCodePath = api_get_path(WEB_CODE_PATH);
        while ($row = Database::fetch_assoc($result)) {
            /*$sql_unread = "SELECT
                              COUNT(DISTINCT message.message_id) AS unread
                           FROM $table_support_tickets  ticket,
                                $table_support_messages message,
                                $table_main_user user
                           WHERE ticket.ticket_id = message.ticket_id
                           AND ticket.ticket_id = '{$row['col0']}'
                           AND message.status = 'NOL'
                           AND message.sys_insert_user_id = user.user_id ";
            if ($isAdmin) {
                $sql_unread .= " AND user.user_id
                                 NOT IN (SELECT user_id FROM $table_main_admin)
                                 AND ticket.status_id != '".self::STATUS_FORWARDED."' ";
            } else {
                $sql_unread .= " AND user.user_id
                                 IN (SELECT user_id FROM $table_main_admin) ";
            }
            $result_unread = Database::query($sql_unread);
            $unread = Database::fetch_object($result_unread)->unread;*/

            $userInfo = api_get_user_info($row['sys_insert_user_id']);
            $hrefUser = $webPath . 'main/admin/user_information.php?user_id=' . $userInfo['user_id'];
            $name = "<a href='$hrefUser'> {$userInfo['username']} </a>";
            $actions = '';

            if ($row['assigned_last_user'] != 0) {
                $assignedUserInfo = api_get_user_info($row['assigned_last_user']);
                if (!empty($assignedUserInfo)) {
                    $hrefResp = $webPath . 'main/admin/user_information.php?user_id=' . $assignedUserInfo['user_id'];
                    $row['assigned_last_user'] = "<a href='$hrefResp'> {$assignedUserInfo['username']} </a>";
                } else {
                    $row['assigned_last_user'] = get_lang('UnknownUser');
                }
            } else {
                if ($row['status_id'] !== self::STATUS_FORWARDED) {
                    $row['assigned_last_user'] = '<span style="color:#ff0000;">' . get_lang('ToBeAssigned') . '</span>';
                } else {
                    $row['assigned_last_user'] = '<span style="color:#00ff00;">' . get_lang('MessageResent') . '</span>';
                }
            }

            switch ($row['source']) {
                case self::SOURCE_PRESENTIAL:
                    $img_source = 'icons/32/user.png';
                    break;
                case self::SOURCE_EMAIL:
                    $img_source = 'icons/32/mail.png';
                    break;
                case self::SOURCE_PHONE:
                    $img_source = 'icons/32/event.png';
                    break;
                default:
                    $img_source = 'icons/32/course_home.png';
                    break;
            }

            $row['col1'] = Display::tip(
                date_to_str_ago($row['col1']),
                api_get_local_time($row['col1'])
            );
            $row['col2'] = Display::tip(
                date_to_str_ago($row['col2']),
                api_get_local_time($row['col2'])
            );
            if ($isAdmin) {
                if ($row['priority_id'] === self::PRIORITY_HIGH && $row['status_id'] != self::STATUS_CLOSE) {
                    $actions .= '<img src="' . $webCodePath . 'img/exclamation.png" border="0" />';
                }
                $row['col0'] = Display::return_icon(
                        $img_source,
                        get_lang('Info')
                        ).
                    '<a href="ticket_details.php?ticket_id=' . $row['col0'] . '">' . $row['code'] . '</a>';
                // @todo fix
                /*if ($row['col7'] == 'PENDIENTE') {
                    $row['col7'] = '<span style="color: #f00; font-weight:bold;">' . $row['col7'] . '</span>';
                }*/

                $ticket = array(
                    $row['col0'].' '.$row['subject'],
                    $row['col7'],
                    $row['col1'],
                    $row['col2'],
                    $row['col3'],
                    $name,
                    $row['assigned_last_user'],
                    $row['col8']
                );
            } else {
                $actions = '';
                $row['col0'] = Display::return_icon(
                    $img_source,
                    get_lang('Info')
                ) . '<a href="ticket_details.php?ticket_id=' . $row['col0'] . '">' . $row['code'] . '</a>';
                $now = api_strtotime(api_get_utc_datetime());
                $last_edit_date = api_strtotime($row['sys_lastedit_datetime']);
                $dif = $now - $last_edit_date;

                if ($dif > 172800 && $row['priority_id'] === self::PRIORITY_NORMAL && $row['status_id'] != self::STATUS_CLOSE) {
                    $actions .= '<a href="'.api_get_path(WEB_CODE_PATH).'ticket/tickets.php?ticket_id=' . $row['ticket_id'] . '&amp;action=alert">
                                 <img src="' . Display::returnIconPath('exclamation.png') . '" border="0" /></a>';
                }
                if ($row['priority_id'] === self::PRIORITY_HIGH) {
                    $actions .= '<img src="' . Display::returnIconPath('admin_star.png') . '" border="0" />';
                }
                $ticket = array(
                    $row['col0'],
                    $row['col7'],
                    $row['col1'],
                    $row['col2'],
                    $row['col3']
                );
            }
            /*if ($unread > 0) {
                $ticket['0'] = $ticket['0'] . '&nbsp;&nbsp;(' . $unread . ')<a href="ticket_details.php?ticket_id=' . $row['ticket_id'] . '">
                                <img src="' . Display::returnIconPath('message_new.png') . '" border="0" title="' . $unread . ' ' . get_lang('Messages') . '"/>
                                </a>';
            }*/
            if ($isAdmin) {
                $ticket['0'] .= '&nbsp;&nbsp;<a  href="javascript:void(0)" onclick="load_history_ticket(\'div_' . $row['ticket_id'] . '\',' . $row['ticket_id'] . ')">
					<img onclick="load_course_list(\'div_' . $row['ticket_id'] . '\',' . $row['ticket_id'] . ')" onmouseover="clear_course_list (\'div_' . $row['ticket_id'] . '\')" src="' . Display::returnIconPath('history.gif') . '" title="' . get_lang('Historial') . '" alt="' . get_lang('Historial') . '"/>
					<div class="blackboard_hide" id="div_' . $row['ticket_id'] . '">&nbsp;&nbsp;</div>
					</a>&nbsp;&nbsp;';
            }
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * @param int $user_id
     * @return mixed
     */
    public static function get_total_tickets_by_user_id($user_id = 0)
    {
        $table_support_category = Database::get_main_table(
            TABLE_TICKET_CATEGORY
        );
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_priority = Database::get_main_table(
            TABLE_TICKET_PRIORITY
        );
        $table_support_status = Database::get_main_table(TABLE_TICKET_STATUS);
        $table_support_messages = Database::get_main_table(
            TABLE_TICKET_MESSAGE
        );
        $table_main_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_main_admin = Database::get_main_table(TABLE_MAIN_ADMIN);

        $sql = "SELECT COUNT(ticket.id) AS total
                FROM $table_support_tickets ticket ,
                $table_support_category cat ,
                $table_support_priority priority,
                $table_support_status status                
	        WHERE 
	            cat.id = ticket.category_id AND 
	            ticket.priority_id = priority.id AND 
	            ticket.status_id = status.id";

        // Search simple
        if (isset($_GET['submit_simple'])) {
            if ($_GET['keyword'] != '') {
                $keyword = Database::escape_string(trim($_GET['keyword']));
                $sql .= " AND (ticket.code = '$keyword'
                          OR user.firstname LIKE '%$keyword%'
                          OR user.lastname LIKE '%$keyword%'
                          OR concat(user.firstname,' ',user.lastname) LIKE '%$keyword%'
                          OR concat(user.lastname,' ',user.firstname) LIKE '%$keyword%'
                          OR user.username LIKE '%$keyword%')  ";
            }
        }
        $keyword_unread = '';
        if (isset($_GET['keyword_unread'])) {
            $keyword_unread = Database::escape_string(
                trim($_GET['keyword_unread'])
            );
        }

        // Search advanced
        if (isset($_GET['submit_advanced'])) {
            $keyword_category = Database::escape_string(
                trim($_GET['keyword_category'])
            );
            $keyword_request_user = '';
            /*$keyword_request_user = Database::escape_string(
                trim($_GET['keyword_request_user'])
            );*/
            $keyword_admin = Database::escape_string(
                trim($_GET['keyword_admin'])
            );
            $keyword_start_date_start = Database::escape_string(
                trim($_GET['keyword_start_date_start'])
            );
            $keyword_start_date_end = Database::escape_string(
                trim($_GET['keyword_start_date_end'])
            );
            $keyword_status = Database::escape_string(
                trim($_GET['keyword_status'])
            );
            $keyword_source = isset($_GET['keyword_source']) ? Database::escape_string(trim($_GET['keyword_source'])) : '';
            $keyword_priority = Database::escape_string(
                trim($_GET['keyword_priority'])
            );

            $keyword_range = isset($_GET['keyword_dates']) ? Database::escape_string(trim($_GET['keyword_dates'])) : '';
            $keyword_course = Database::escape_string(
                trim($_GET['keyword_course'])
            );

            if ($keyword_category != '') {
                $sql .= " AND ticket.category_id = '$keyword_category'  ";
            }
            if ($keyword_request_user != '') {
                $sql .= " AND (ticket.request_user = '$keyword_request_user'
                          OR user.firstname LIKE '%$keyword_request_user%'
                          OR user.official_code LIKE '%$keyword_request_user%'
                          OR user.lastname LIKE '%$keyword_request_user%'
                          OR concat(user.firstname,' ',user.lastname) LIKE '%$keyword_request_user%'
                          OR concat(user.lastname,' ',user.firstname) LIKE '%$keyword_request_user%'
                          OR user.username LIKE '%$keyword_request_user%') ";
            }
            if ($keyword_admin != '') {
                $sql .= " AND ticket.assigned_last_user = '$keyword_admin'  ";
            }
            if ($keyword_status != '') {
                $sql .= " AND ticket.status_id = '$keyword_status'  ";
            }
            if ($keyword_range == '' && $keyword_start_date_start != '') {
                $sql .= " AND DATE_FORMAT( ticket.start_date,'%d/%m/%Y') = '$keyword_start_date_start' ";
            }
            if ($keyword_range == '1' && $keyword_start_date_start != '' && $keyword_start_date_end != '') {
                $sql .= " AND DATE_FORMAT( ticket.start_date,'%d/%m/%Y') >= '$keyword_start_date_start'
                          AND DATE_FORMAT( ticket.start_date,'%d/%m/%Y') <= '$keyword_start_date_end'";
            }
            if ($keyword_priority != '') {
                $sql .= " AND ticket.priority_id = '$keyword_priority'  ";
            }
            if ($keyword_source != '') {
                $sql .= " AND ticket.source = '$keyword_source' ";
            }
            if ($keyword_priority != '') {
                $sql .= " AND ticket.priority_id = '$keyword_priority' ";
            }
            if ($keyword_course != '') {
                $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
                $sql .= " AND ticket.course_id IN ( ";
                $sql .= "SELECT id
                         FROM $course_table
                         WHERE (title LIKE '%$keyword_course%'
                         OR code LIKE '%$keyword_course%'
                         OR visual_code LIKE '%$keyword_course%' )) ";
            }
        }

        if ($keyword_unread == 'yes') {
            $sql .= " AND ticket.id IN ( ";
            $sql .= "SELECT ticket.id
                     FROM  $table_support_tickets ticket,
                     $table_support_messages message,
                     $table_main_user user
                     WHERE ticket.id = message.ticket_id
                     AND message.status = 'NOL'
                     AND message.sys_insert_user_id = user.user_id
                     AND user.user_id NOT IN (
                        SELECT user_id FROM $table_main_admin
                     ) AND ticket.status_id != '".self::STATUS_FORWARDED."'
                     GROUP BY ticket.id)";
        } else {
            if ($keyword_unread == 'no') {
                $sql .= " AND ticket.id NOT IN ( ";
                $sql .= " SELECT ticket.id 
                          FROM  $table_support_tickets ticket,
                          $table_support_messages message,
                          $table_main_user user
                          WHERE ticket.id = message.ticket_id
                          AND message.status = 'NOL'
                          AND message.sys_insert_user_id = user.user_id
                          AND user.user_id NOT IN (SELECT user_id FROM $table_main_admin)
                          AND ticket.status_id != '".self::STATUS_FORWARDED."'
                          GROUP BY ticket.id)";
            }
        }
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total;
    }

    /**
     * @param int $ticket_id
     * @return array
     */
    public static function get_ticket_detail_by_id($ticket_id)
    {
        $ticket_id = intval($ticket_id);
        $table_support_category = Database::get_main_table(
            TABLE_TICKET_CATEGORY
        );
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_priority = Database::get_main_table(
            TABLE_TICKET_PRIORITY
        );
        $table_support_status = Database::get_main_table(TABLE_TICKET_STATUS);
        $table_support_messages = Database::get_main_table(
            TABLE_TICKET_MESSAGE
        );
        $table_support_message_attachments = Database::get_main_table(
            TABLE_TICKET_MESSAGE_ATTACHMENTS
        );
        $table_main_user = Database::get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT
                    ticket.*, 
                    cat.name,
                    status.name as status, 
                    priority.name priority
                FROM $table_support_tickets ticket,
                    $table_support_category cat,
                    $table_support_priority priority,
                    $table_support_status status
		        WHERE
                    ticket.id = $ticket_id
                    AND cat.id = ticket.category_id
                    AND priority.id = ticket.priority_id
                    AND status.id = ticket.status_id ";
        $result = Database::query($sql);
        $ticket = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $row['course'] = null;
                $row['start_date_from_db'] = $row['start_date'];
                $row['start_date'] = api_convert_and_format_date(
                        api_get_local_time($row['start_date']), DATE_TIME_FORMAT_LONG, api_get_timezone()
                );
                $row['end_date_from_db'] = $row['end_date'];
                $row['end_date'] = api_convert_and_format_date(
                        api_get_local_time($row['end_date']), DATE_TIME_FORMAT_LONG, api_get_timezone()
                );
                $row['sys_lastedit_datetime_from_db'] = $row['sys_lastedit_datetime'];
                $row['sys_lastedit_datetime'] = api_convert_and_format_date(
                        api_get_local_time($row['sys_lastedit_datetime']), DATE_TIME_FORMAT_LONG, api_get_timezone()
                );
                $row['course_url'] = null;
                if ($row['course_id'] != 0) {
                    $course = api_get_course_info_by_id($row['course_id']);
                    $sessionId = 0;
                    if ($row['session_id']) {
                        $sessionId = $row['session_id'];
                    }
                    if ($course) {
                        $row['course_url'] = '<a href="'.$course['course_public_url'].'?id_session='.$sessionId.'">'.$course['name'].'</a>';
                    }
                }

                $userInfo = api_get_user_info($row['sys_insert_user_id']);
                $row['user_url'] = '<a href="' . api_get_path(WEB_PATH) . 'main/admin/user_information.php?user_id=' . $userInfo['user_id'] . '">
                ' . $userInfo['complete_name']. '</a>';
                $ticket['usuario'] = $userInfo;
                $ticket['ticket'] = $row;
            }
            $sql = "SELECT * FROM  $table_support_messages message,
                    $table_main_user user
                    WHERE
                        message.ticket_id = '$ticket_id' AND
                        message.sys_insert_user_id = user.user_id ";
            $result = Database::query($sql);
            $ticket['messages'] = array();
            $attach_icon = Display::return_icon('attachment.gif', '');
            $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
            $webPath = api_get_path(WEB_PATH);
            while ($row = Database::fetch_assoc($result)) {
                $message = $row;
                $completeName = api_get_person_name($row['firstname'], $row['lastname']);
                $href = $webPath . 'main/admin/user_information.php?user_id=' . $row['user_id'];
                // Check if user is an admin
                $sql_admin = "SELECT user_id FROM $admin_table
		                      WHERE user_id = '" . intval($message['user_id']) . "'
                              LIMIT 1";
                $result_admin = Database::query($sql_admin);
                $message['admin'] = false;
                if (Database::num_rows($result_admin) > 0) {
                    $message['admin'] = true;
                }

                $message['user_created'] = "<a href='$href'> $completeName </a>";
                $sql = "SELECT *
                        FROM $table_support_message_attachments
                        WHERE
                            message_id = " . $row['id'] . " AND
                            ticket_id = '$ticket_id'  ";
                $result_attach = Database::query($sql);
                while ($row2 = Database::fetch_assoc($result_attach)) {
                    $archiveURL = $archiveURL = $webPath . "plugin/" . PLUGIN_NAME . '/src/download.php?ticket_id=' . $ticket_id . '&file=';
                    $row2['attachment_link'] = $attach_icon . '&nbsp;<a href="' . $archiveURL . $row2['path'] . '&title=' . $row2['filename'] . '">' . $row2['filename'] . '</a>&nbsp;(' . $row2['size'] . ')';
                    $message['attachments'][] = $row2;
                }
                $ticket['messages'][] = $message;
            }
        }

        return $ticket;
    }

    /**
     * @param int $ticket_id
     * @param int $user_id
     * @return bool
     */
    public static function update_message_status($ticket_id, $user_id)
    {
        $ticket_id = intval($ticket_id);
        $user_id = intval($user_id);
        $table_support_messages = Database::get_main_table(
            TABLE_TICKET_MESSAGE
        );
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $now = api_get_utc_datetime();
        $sql = "UPDATE $table_support_messages
                SET 
                    status = 'LEI', 
                    sys_lastedit_user_id ='" . api_get_user_id() . "',
                    sys_lastedit_datetime ='" . $now . "'
                WHERE ticket_id ='$ticket_id' ";

        if (api_is_platform_admin()) {
            $sql .= " AND sys_insert_user_id = '$user_id'";
        } else {

            $sql .= " AND sys_insert_user_id != '$user_id'";
        }
        $result = Database::query($sql);
        if (Database::affected_rows($result) > 0) {
            Database::query(
                "UPDATE $table_support_tickets SET 
                    status_id = '".self::STATUS_PENDING."'
                 WHERE id ='$ticket_id' AND status_id = '".self::STATUS_NEW."'"
            );
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $ticketId
     * @param int $userId
     * @param string $title
     * @param string $message
     */
    public static function sendNotification($ticketId, $userId, $title, $message)
    {
        $userInfo = api_get_user_info($userId);
        $ticketInfo = self::get_ticket_detail_by_id($ticketId);
        $requestUserInfo = $ticketInfo['usuario'];
        $ticketCode = $ticketInfo['ticket']['code'];
        $status = $ticketInfo['ticket']['status'];
        $priority = $ticketInfo['ticket']['priority'];

        $titleEmail = "[$ticketCode] $title";
        $messageEmail = get_lang('TicketNum').": $ticketCode <br />";
        $messageEmail .= get_lang('Status').": $status <br />";
        $messageEmail .= get_lang('Priority').": $priority <br />";
        $messageEmail .= '<hr /><br />';
        $messageEmail .= $message;

        api_mail_html(
            $requestUserInfo['complete_name'],
            $requestUserInfo['email'],
            $titleEmail,
            $messageEmail,
            null,
            null,
            array(),
            null
        );

        // Admin
        api_mail_html(
            $userInfo['complete_name'],
            $userInfo['email'],
            $titleEmail,
            $messageEmail,
            null,
            null,
            array(),
            null
        );
    }

    /**
     * @param array $params
     * @param int $ticketId
     * @param int $userId
     *
     * @return bool
     */
    public static function updateTicket(
        $params,
        $ticketId,
        $userId
    ) {
        $now = api_get_utc_datetime();

        $table = Database::get_main_table(TABLE_TICKET_TICKET);
        $newParams = [
            'priority_id' => isset($params['priority_id']) ? $params['priority_id'] : '',
            'status_id' => isset($params['status_id']) ? $params['status_id'] : '',
            'sys_lastedit_user_id' => $userId,
            'sys_lastedit_datetime' => $now,
        ];
        Database::update($table, $newParams, ['id = ? ' => $ticketId]);

         self::sendNotification(
            $ticketId,
            $userId,
            get_lang('TicketUpdated'),
            get_lang('TicketUpdated')
        );

        return true;
    }

    /**
     * @param $status_id
     * @param $ticket_id
     * @param $user_id
     * @return bool
     */
    public static function update_ticket_status(
        $status_id,
        $ticket_id,
        $user_id
    ) {
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);

        $ticket_id = intval($ticket_id);
        $status_id = intval($status_id);
        $user_id = intval($user_id);

        $now = api_get_utc_datetime();
        $sql = "UPDATE $table_support_tickets
                SET
                    status_id = '$status_id',
                    sys_lastedit_user_id ='$user_id',
                    sys_lastedit_datetime ='" . $now . "'
                WHERE id ='$ticket_id'";
        $result = Database::query($sql);

        if (Database::affected_rows($result) > 0) {
            self::sendNotification(
                $ticket_id,
                $user_id,
                get_lang('TicketUpdated'),
                get_lang('TicketUpdated')
            );
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public static function get_number_of_messages()
    {
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_messages = Database::get_main_table(
            TABLE_TICKET_MESSAGE
        );
        $table_main_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_main_admin = Database::get_main_table(TABLE_MAIN_ADMIN);
        $user_info = api_get_user_info();
        $user_id = $user_info['user_id'];
        $sql = "SELECT COUNT(DISTINCT ticket.id) AS unread
                FROM $table_support_tickets ticket,
                $table_support_messages message ,
                $table_main_user user
                WHERE 
                    ticket.id = message.ticket_id AND 
                    message.status = 'NOL' AND 
                    user.user_id = message.sys_insert_user_id ";
        if (!api_is_platform_admin()) {
            $sql .= " AND ticket.request_user = '$user_id'
                      AND user_id IN (SELECT user_id FROM $table_main_admin)  ";
        } else {
            $sql .= " AND user_id NOT IN (SELECT user_id FROM $table_main_admin)
                      AND ticket.status_id != '".self::STATUS_FORWARDED."'";
        }
        $sql .= "  AND ticket.project_id != '' ";
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->unread;
    }

    /**
     * @param int $ticket_id
     * @param int $user_id
     */
    public static function send_alert($ticket_id, $user_id)
    {
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $now = api_get_utc_datetime();

        $ticket_id = intval($ticket_id);
        $user_id = intval($user_id);

        $sql = "UPDATE $table_support_tickets SET
                  priority_id = '".self::PRIORITY_HIGH."',
                  sys_lastedit_user_id ='$user_id',
                  sys_lastedit_datetime ='$now'
                WHERE id = '$ticket_id'";
        Database::query($sql);
    }

    /**
     * @param int $ticket_id
     * @param int $user_id
     */
    public static function close_ticket($ticket_id, $user_id)
    {
        $ticket_id = intval($ticket_id);
        $user_id = intval($user_id);

        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $now = api_get_utc_datetime();
        $sql = "UPDATE $table_support_tickets SET
                    status_id = '".self::STATUS_CLOSE."',
                    sys_lastedit_user_id ='$user_id',
                    sys_lastedit_datetime ='" . $now . "',
                    end_date ='$now'
                WHERE id ='$ticket_id'";
        Database::query($sql);

        self::sendNotification(
            $ticket_id,
            $user_id,
            get_lang('TicketClosed'),
            get_lang('TicketClosed')
        );
    }

    /**
     *
     */
    public static function close_old_tickets()
    {
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $now = api_get_utc_datetime();
        $userId = api_get_user_id();
        $sql = "UPDATE $table_support_tickets
                SET
                    status_id = '".self::STATUS_CLOSE."',
                    sys_lastedit_user_id ='$userId',
                    sys_lastedit_datetime ='$now',
                    end_date = '$now'
                WHERE 
                DATEDIFF('$now', sys_lastedit_datetime) > 7 AND
                status_id != '".self::STATUS_CLOSE."' AND 
                status_id != '".self::STATUS_NEW."' AND 
                status_id != '".self::STATUS_FORWARDED."'";
        Database::query($sql);
    }

    /**
     * @param $ticket_id
     * @return array
     */
    public static function get_assign_log($ticket_id)
    {
        $table_support_assigned_log = Database::get_main_table(TABLE_TICKET_ASSIGNED_LOG);
        $ticket_id = intval($ticket_id);

        $sql = "SELECT * FROM $table_support_assigned_log 
                WHERE ticket_id = '$ticket_id'
                ORDER BY assigned_date DESC";
        $result = Database::query($sql);
        $history = [];
        $webpath = api_get_path(WEB_PATH);
        while ($row = Database::fetch_assoc($result)) {

            if ($row['user_id'] != 0) {
                $assignuser = api_get_user_info($row['user_id']);
                $row['assignuser'] = '<a href="' . $webpath . 'main/admin/user_information.php?user_id=' . $row['user_id'] . '"  target="_blank">' .
                $assignuser['username'] . '</a>';
            } else {
                $row['assignuser'] = get_lang('Unassign');
            }
            $row['assigned_date'] = date_to_str_ago($row['assigned_date']);
            $insertuser = api_get_user_info($row['sys_insert_user_id']);
            $row['insertuser'] = '<a href="' . $webpath . 'main/admin/user_information.php?user_id=' . $row['sys_insert_user_id'] . '"  target="_blank">' .
                $insertuser['username'] . '</a>';
            $history[] = $row;
        }

        return $history;
    }

    /**
     * @param $from
     * @param $number_of_items
     * @param $column
     * @param $direction
     * @param null $user_id
     * @return array
     */
    public static function export_tickets_by_user_id(
        $from,
        $number_of_items,
        $column,
        $direction,
        $user_id = null
    ) {
        $from = intval($from);
        $number_of_items = intval($number_of_items);
        $table_support_category = Database::get_main_table(
            TABLE_TICKET_CATEGORY
        );
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_priority = Database::get_main_table(
            TABLE_TICKET_PRIORITY
        );
        $table_support_status = Database::get_main_table(TABLE_TICKET_STATUS);
        $table_support_messages = Database::get_main_table(
            TABLE_TICKET_MESSAGE
        );
        $table_main_user = Database::get_main_table(TABLE_MAIN_USER);

        if (is_null($direction)) {
            $direction = "DESC";
        }
        if (is_null($user_id) || $user_id == 0) {
            $user_id = api_get_user_id();
        }

        $sql = "SELECT 
                    ticket.code, 
                    ticket.sys_insert_datetime,
                    ticket.sys_lastedit_datetime, 
                    cat.name as category,
                    CONCAT(user.lastname,' ', user.firstname) AS fullname,
                    status.name as status, 
                    ticket.total_messages as messages,
                    ticket.assigned_last_user as responsable
                FROM $table_support_tickets ticket,
                $table_support_category cat ,
                $table_support_priority priority,
                $table_support_status status ,
                $table_main_user user
                WHERE
                    cat.id = ticket.category_id
                    AND ticket.priority_id = priority.id
                    AND ticket.status_id = status.id
                    AND user.user_id = ticket.request_user ";
        // Search simple
        if (isset($_GET['submit_simple'])) {
            if ($_GET['keyword'] !== '') {
                $keyword = Database::escape_string(trim($_GET['keyword']));
                $sql .= " AND (ticket.code = '$keyword'
                          OR user.firstname LIKE '%$keyword%'
                          OR user.lastname LIKE '%$keyword%'
                          OR concat(user.firstname,' ',user.lastname) LIKE '%$keyword%'
                          OR concat(user.lastname,' ',user.firstname) LIKE '%$keyword%'
                          OR user.username LIKE '%$keyword%')  ";
            }
        }
        //Search advanced
        if (isset($_GET['submit_advanced'])) {
            $keyword_category = Database::escape_string(
                trim($_GET['keyword_category'])
            );
            $keyword_request_user = Database::escape_string(
                trim($_GET['keyword_request_user'])
            );
            $keyword_admin = Database::escape_string(
                trim($_GET['keyword_admin'])
            );
            $keyword_start_date_start = Database::escape_string(
                trim($_GET['keyword_start_date_start'])
            );
            $keyword_start_date_end = Database::escape_string(
                trim($_GET['keyword_start_date_end'])
            );
            $keyword_status = Database::escape_string(
                trim($_GET['keyword_status'])
            );
            $keyword_source = Database::escape_string(
                trim($_GET['keyword_source'])
            );
            $keyword_priority = Database::escape_string(
                trim($_GET['keyword_priority'])
            );
            $keyword_range = Database::escape_string(
                trim($_GET['keyword_dates'])
            );
            $keyword_unread = Database::escape_string(
                trim($_GET['keyword_unread'])
            );
            $keyword_course = Database::escape_string(
                trim($_GET['keyword_course'])
            );

            if ($keyword_category != '') {
                $sql .= " AND ticket.category_id = '$keyword_category'  ";
            }
            if ($keyword_request_user != '') {
                $sql .= " AND (ticket.request_user = '$keyword_request_user'
                          OR user.firstname LIKE '%$keyword_request_user%'
                          OR user.official_code LIKE '%$keyword_request_user%'
                          OR user.lastname LIKE '%$keyword_request_user%'
                          OR concat(user.firstname,' ',user.lastname) LIKE '%$keyword_request_user%'
                          OR concat(user.lastname,' ',user.firstname) LIKE '%$keyword_request_user%'
                          OR user.username LIKE '%$keyword_request_user%') ";
            }
            if ($keyword_admin != '') {
                $sql .= " AND ticket.assigned_last_user = '$keyword_admin'  ";
            }
            if ($keyword_status != '') {
                $sql .= " AND ticket.status_id = '$keyword_status'  ";
            }
            if ($keyword_range == '' && $keyword_start_date_start != '') {
                $sql .= " AND DATE_FORMAT( ticket.start_date,'%d/%m/%Y') = '$keyword_start_date_start' ";
            }
            if ($keyword_range == '1' && $keyword_start_date_start != '' && $keyword_start_date_end != '') {
                $sql .= " AND DATE_FORMAT( ticket.start_date,'%d/%m/%Y') >= '$keyword_start_date_start'
                          AND DATE_FORMAT( ticket.start_date,'%d/%m/%Y') <= '$keyword_start_date_end'";
            }
            if ($keyword_priority != '') {
                $sql .= " AND ticket.priority_id = '$keyword_priority'  ";
            }
            if ($keyword_source != '') {
                $sql .= " AND ticket.source = '$keyword_source' ";
            }
            if ($keyword_priority != '') {
                $sql .= " AND ticket.priority_id = '$keyword_priority' ";
            }
            if ($keyword_course != '') {
                $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
                $sql .= " AND ticket.course_id IN ( ";
                $sql .= "SELECT id
                         FROM $course_table
                         WHERE (title LIKE '%$keyword_course%'
                         OR code LIKE '%$keyword_course%'
                         OR visual_code LIKE '%$keyword_course%' )) ";
            }
            if ($keyword_unread == 'yes') {
                $sql .= " AND ticket.id IN (
                          SELECT ticket.id
                          FROM $table_support_tickets ticket,
                          $table_support_messages message,
                          $table_main_user user
                          WHERE ticket.id = message.ticket_id
                          AND message.status = 'NOL'
                          AND message.sys_insert_user_id = user.user_id
                          AND user.status != 1   AND ticket.status_id != '".self::STATUS_FORWARDED."'
                          GROUP BY ticket.id)";
            } else {
                if ($keyword_unread == 'no') {
                    $sql .= " AND ticket.id NOT IN (
                              SELECT ticket.id
                              FROM  $table_support_tickets ticket,
                              $table_support_messages message,
                              $table_main_user user
                              WHERE ticket.id = message.ticket_id
                              AND message.status = 'NOL'
                              AND message.sys_insert_user_id = user.user_id
                              AND user.status != 1
                              AND ticket.status_id != '".self::STATUS_FORWARDED."'
                             GROUP BY ticket.id)";
                }
            }
        }

        $sql .= " LIMIT $from,$number_of_items";

        $result = Database::query($sql);
        $tickets[0] = array(
            utf8_decode('Ticket#'),
            utf8_decode('Fecha'),
            utf8_decode('Fecha Edicion'),
            utf8_decode('Categoria'),
            utf8_decode('Usuario'),
            utf8_decode('Estado'),
            utf8_decode('Mensajes'),
            utf8_decode('Responsable'),
            utf8_decode('Programa'),
        );

        while ($row = Database::fetch_assoc($result)) {
            if ($row['responsable'] != 0) {
                $row['responsable'] = api_get_user_info($row['responsable']);
                $row['responsable'] = $row['responsable']['firstname'] . ' ' . $row['responsable']['lastname'];
            }
            $row['sys_insert_datetime'] = api_format_date(
                    $row['sys_insert_datetime'], '%d/%m/%y - %I:%M:%S %p'
            );
            $row['sys_lastedit_datetime'] = api_format_date(
                    $row['sys_lastedit_datetime'], '%d/%m/%y - %I:%M:%S %p'
            );
            $row['category'] = utf8_decode($row['category']);
            $row['programa'] = utf8_decode($row['fullname']);
            $row['fullname'] = utf8_decode($row['fullname']);
            $row['responsable'] = utf8_decode($row['responsable']);
            $tickets[] = $row;
        }

        return $tickets;
    }

    /**
     * @param string $url
     * @return FormValidator
     */
    public static function getCategoryForm($url, $projectId)
    {
        $form = new FormValidator('category', 'post', $url);
        $form->addText('name', get_lang('Name'));
        $form->addHtmlEditor('description', get_lang('Description'));
        $form->addHidden('project_id', $projectId);
        $form->addButtonUpdate(get_lang('Save'));

        return $form;
    }

    /**
     * @return array
     */
    public static function getStatusList()
    {
        $items = Database::getManager()->getRepository('ChamiloTicketBundle:Status')->findAll();

        $list = [];
        /** @var \Chamilo\TicketBundle\Entity\Status $row */
        foreach ($items as $row) {
            $list[$row->getId()] = $row->getName();
        }

        return $list;
    }

    /**
     * @return array
     */
    public static function getTicketsFromCriteria($criteria)
    {
        $items = Database::getManager()->getRepository('ChamiloTicketBundle:Ticket')->findBy($criteria);

        $list = [];
        /** @var Ticket $row */
        foreach ($items as $row) {
            $list[$row->getId()] = $row->getCode();
        }

        return $list;
    }

    /**
     * @param string $code
     * @return int
     */
    public static function getStatusIdFromCode($code)
    {
        $item = Database::getManager()
            ->getRepository('ChamiloTicketBundle:Status')
            ->findOneBy(['code' => $code])
        ;
        if ($item) {
            return $item->getId();
        }

        return 0;
    }

     /**
     * @return array
     */
    public static function getPriorityList()
    {
        $projects = Database::getManager()->getRepository('ChamiloTicketBundle:Priority')->findAll();

        $list = [];
        /** @var \Chamilo\TicketBundle\Entity\Priority $row */
        foreach ($projects as $row) {
            $list[$row->getId()] = $row->getName();
        }

        return $list;
    }

    /**
     * @return array
     */
    public static function getProjects()
    {
        $projects = Database::getManager()->getRepository('ChamiloTicketBundle:Project')->findAll();

        $list = [];
        /** @var Project $row */
        foreach ($projects as $row) {
            $list[] = [
                'id' => $row->getId(),
                '0' => $row->getId(),
                '1' => $row->getName(),
                '2' => $row->getDescription(),
                '3' => $row->getId()
            ];
        }

        return $list;
    }

    /**
     * @return array
     */
    public static function getProjectsSimple()
    {
        $projects = Database::getManager()->getRepository('ChamiloTicketBundle:Project')->findAll();

        $list = [];
        /** @var Project $row */
        foreach ($projects as $row) {
            $list[] = [
                'id' => $row->getId(),
                '0' => $row->getId(),
                '1' => Display::url(
                    $row->getName(),
                    api_get_path(WEB_CODE_PATH).'ticket/tickets.php?project_id='.$row->getId()
                ),
                '2' => $row->getDescription()
            ];
        }

        return $list;
    }

    /**
     * @return int
     */
    public static function getProjectsCount()
    {
        $count = Database::getManager()->getRepository('ChamiloTicketBundle:Project')->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $count;
    }

    /**
     * @param array $params
     */
    public static function addProject($params)
    {
        $project = new Project();
        $project->setName($params['name']);
        $project->setDescription($params['description']);
        $project->setInsertUserId(api_get_user_id());
        //$project->setEmail($params['email']);

        Database::getManager()->persist($project);
        Database::getManager()->flush();
    }

    /**
     * @param $id
     * @return Project
     */
    public static function getProject($id)
    {
        return Database::getManager()->getRepository('ChamiloTicketBundle:Project')->find($id);
    }

    /**
     * @param int $id
     * @param array $params
     */
    public static function updateProject($id, $params)
    {
        $project = self::getProject($id);
        $project->setName($params['name']);
        $project->setDescription($params['description']);
        $project->setLastEditDateTime(new DateTime($params['sys_lastedit_datetime']));
        $project->setLastEditUserId($params['sys_lastedit_user_id']);

        Database::getManager()->merge($project);
        Database::getManager()->flush();
    }

    /**
     * @param int $id
     */
    public static function deleteProject($id)
    {
        $project = self::getProject($id);
        Database::getManager()->remove($project);
        Database::getManager()->flush();
    }

    /**
     * @param string $url
     * @return FormValidator
     */
    public static function getProjectForm($url)
    {
        $form = new FormValidator('project', 'post', $url);
        $form->addText('name', get_lang('Name'));
        $form->addHtmlEditor('description', get_lang('Description'));
        $form->addButtonUpdate(get_lang('Save'));

        return $form;
    }

    /**
     * @return array
     */
    public static function getStatusAdminList()
    {
        $items = Database::getManager()->getRepository('ChamiloTicketBundle:Status')->findAll();

        $list = [];
        /** @var Status $row */
        foreach ($items as $row) {
            $list[] = [
                'id' => $row->getId(),
                'code' => $row->getCode(),
                '0' => $row->getId(),
                '1' => $row->getName(),
                '2' => $row->getDescription(),
                '3' => $row->getId()
            ];
        }

        return $list;
    }

    /**
     * @return array
     */
    public static function getStatusSimple()
    {
        $projects = Database::getManager()->getRepository('ChamiloTicketBundle:Status')->findAll();

        $list = [];
        /** @var Project $row */
        foreach ($projects as $row) {
            $list[] = [
                'id' => $row->getId(),
                '0' => $row->getId(),
                '1' => Display::url($row->getName()),
                '2' => $row->getDescription()
            ];
        }

        return $list;
    }

    /**
     * @return int
     */
    public static function getStatusCount()
    {
        $count = Database::getManager()->getRepository('ChamiloTicketBundle:Status')->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $count;
    }

    /**
     * @param array $params
     */
    public static function addStatus($params)
    {
        $item = new Status();
        $item->setCode(URLify::filter($params['name']));
        $item->setName($params['name']);
        $item->setDescription($params['description']);

        Database::getManager()->persist($item);
        Database::getManager()->flush();
    }

    /**
     * @param $id
     * @return Project
     */
    public static function getStatus($id)
    {
        return Database::getManager()->getRepository('ChamiloTicketBundle:Status')->find($id);
    }

    /**
     * @param int $id
     * @param array $params
     */
    public static function updateStatus($id, $params)
    {
        $item = self::getStatus($id);
        $item->setName($params['name']);
        $item->setDescription($params['description']);

        Database::getManager()->merge($item);
        Database::getManager()->flush();
    }

    /**
     * @param int $id
     */
    public static function deleteStatus($id)
    {
        $item = self::getStatus($id);
        Database::getManager()->remove($item);
        Database::getManager()->flush();
    }

    /**
     * @param string $url
     * @return FormValidator
     */
    public static function getStatusForm($url)
    {
        $form = new FormValidator('status', 'post', $url);
        $form->addText('name', get_lang('Name'));
        $form->addHtmlEditor('description', get_lang('Description'));
        $form->addButtonUpdate(get_lang('Save'));

        return $form;
    }

    /**
     * @return array
     */
    public static function getPriorityAdminList()
    {
        $items = Database::getManager()->getRepository('ChamiloTicketBundle:Priority')->findAll();

        $list = [];
        /** @var Status $row */
        foreach ($items as $row) {
            $list[] = [
                'id' => $row->getId(),
                'code' => $row->getCode(),
                '0' => $row->getId(),
                '1' => $row->getName(),
                '2' => $row->getDescription(),
                '3' => $row->getId()
            ];
        }

        return $list;
    }

    /**
     * @return array
     */
    public static function getPrioritySimple()
    {
        $projects = Database::getManager()->getRepository('ChamiloTicketBundle:Priority')->findAll();

        $list = [];
        /** @var Priority $row */
        foreach ($projects as $row) {
            $list[] = [
                'id' => $row->getId(),
                '0' => $row->getId(),
                '1' => Display::url($row->getName()),
                '2' => $row->getDescription()
            ];
        }

        return $list;
    }

    /**
     * @return int
     */
    public static function getPriorityCount()
    {
        $count = Database::getManager()->getRepository('ChamiloTicketBundle:Priority')->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $count;
    }

    /**
     * @param array $params
     */
    public static function addPriority($params)
    {
        $item = new Priority();
        $item
            ->setCode(URLify::filter($params['name']))
            ->setName($params['name'])
            ->setDescription($params['description'])
            ->setColor('')
            ->setInsertUserId(api_get_user_id())
            ->setUrgency('')
        ;

        Database::getManager()->persist($item);
        Database::getManager()->flush();
    }

    /**
     * @param $id
     * @return Priority
     */
    public static function getPriority($id)
    {
        return Database::getManager()->getRepository('ChamiloTicketBundle:Priority')->find($id);
    }

    /**
     * @param int $id
     * @param array $params
     */
    public static function updatePriority($id, $params)
    {
        $item = self::getPriority($id);
        $item->setName($params['name']);
        $item->setDescription($params['description']);

        Database::getManager()->merge($item);
        Database::getManager()->flush();
    }

    /**
     * @param int $id
     */
    public static function deletePriority($id)
    {
        $item = self::getStatus($id);
        Database::getManager()->remove($item);
        Database::getManager()->flush();
    }

    /**
     * @param string $url
     * @return FormValidator
     */
    public static function getPriorityForm($url)
    {
        $form = new FormValidator('priority', 'post', $url);
        $form->addText('name', get_lang('Name'));
        $form->addHtmlEditor('description', get_lang('Description'));
        $form->addButtonUpdate(get_lang('Save'));

        return $form;
    }

    /**
     * @return string
     */
    public static function getSettingsMenu()
    {
        $items = [
            [
                'url' => 'projects.php',
                'content' => get_lang('Projects')
            ],
            [
                'url' => 'status.php',
                'content' => get_lang('Status')
            ],
            [
                'url' => 'priorities.php',
                'content' => get_lang('Priority')
            ]
        ];

        echo Display::actions($items);
    }

    /**
     * @return array
     */
    public static function getDefaultStatusList() {
        return [
            self::STATUS_NEW,
            self::STATUS_PENDING,
            self::STATUS_UNCONFIRMED,
            self::STATUS_CLOSE,
            self::STATUS_FORWARDED
        ];
    }

        /**
     * @return array
     */
    public static function getDefaultPriorityList() {
        return [
            self::PRIORITY_NORMAL,
            self::PRIORITY_HIGH,
            self::PRIORITY_LOW,
            self::STATUS_CLOSE,
            self::STATUS_FORWARDED
        ];
    }
}
