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
     *
     * @param int $projectId
     * @param string $order
     *
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
                $table_support_category category 
                INNER JOIN $table_support_project project
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

        if (!in_array($direction, array('ASC', 'DESC'))) {
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
     *
     * @return bool
     */
    public static function deleteCategory($id)
    {
        $id = intval($id);
        if (empty($id)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_TICKET_TICKET);
        $sql = "UPDATE $table SET category_id = NULL WHERE category_id = $id";
        Database::query($sql);

        $table = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $sql = "DELETE FROM $table WHERE id = $id";
        Database::query($sql);

        return true;
    }

    /**
     * @param int $categoryId
     * @param array $users
     *
     * @return bool
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

        return true;
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
        $table = Database::get_main_table(TABLE_TICKET_STATUS);
        $sql = "SELECT * FROM ".$table;
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
     * @param string $subject
     * @param string $content
     * @param string $personalEmail
     * @param $file_attachments
     * @param string $source
     * @param string $priority
     * @param string $status
     * @param int $assignedUserId
     *
     * @return bool
     */
    public static function add(
        $category_id,
        $course_id,
        $sessionId,
        $project_id,
        $other_area,
        $subject,
        $content,
        $personalEmail = '',
        $file_attachments = [],
        $source = '',
        $priority = '',
        $status = '',
        $assignedUserId = 0
    ) {
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_category = Database::get_main_table(TABLE_TICKET_CATEGORY);

        if (empty($category_id)) {
            return false;
        }

        $currentUserId = api_get_user_id();
        $currentUserInfo = api_get_user_info();
        $now = api_get_utc_datetime();
        $course_id = intval($course_id);
        $category_id = intval($category_id);
        $project_id = intval($project_id);
        $priority = empty($priority) ? self::PRIORITY_NORMAL : $priority;

        if ($status === '') {
            $status = self::STATUS_NEW;
            if ($other_area > 0) {
                $status = self::STATUS_FORWARDED;
            }
        }

        if (!empty($category_id)) {
            if (empty($assignedUserId)) {
                $usersInCategory = self::getUsersInCategory($category_id);
                if (!empty($usersInCategory) && count($usersInCategory) > 0) {
                    $userCategoryInfo = $usersInCategory[0];
                    if (isset($userCategoryInfo['user_id'])) {
                        $assignedUserId = $userCategoryInfo['user_id'];
                    }
                }
            }
        }

        $assignedUserInfo = [];
        if (!empty($assignedUserId)) {
            $assignedUserInfo = api_get_user_info($assignedUserId);
            if (empty($assignedUserInfo)) {
                return false;
            }
        }


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
            'assigned_last_user' => $assignedUserId,
            'subject' => $subject,
            'message' => $content
        ];

        if (!empty($course_id)) {
            $params['course_id'] = $course_id;
        }

        if (!empty($sessionId)) {
            $params['session_id'] = $sessionId;
        }
        $ticketId = Database::insert($table_support_tickets, $params);

        if ($ticketId) {
            $ticket_code = "A".str_pad($ticketId, 11, '0', STR_PAD_LEFT);
            $titleCreated = sprintf(
                get_lang('TicketXCreated'),
                $ticket_code
            );

            Display::addFlash(Display::return_message(
                $titleCreated,
                'normal',
                false
            ));

            if ($assignedUserId != 0) {
                self::assignTicketToUser(
                    $ticketId,
                    $assignedUserId
                );

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

            if (!empty($file_attachments)) {
                $attachmentCount = 0;
                foreach ($file_attachments as $attach) {
                    if (!empty($attach['tmp_name'])) {
                        $attachmentCount++;
                    }
                }
                if ($attachmentCount > 0) {
                    self::insertMessage(
                        $ticketId,
                        '',
                        '',
                        $file_attachments,
                        $currentUserId
                    );
                }
            }

            // Update code
            $sql = "UPDATE $table_support_tickets
                    SET code = '$ticket_code'
                    WHERE id = '$ticketId'";
            Database::query($sql);

            // Update total
            $sql = "UPDATE $table_support_category
                    SET total_tickets = total_tickets + 1
                    WHERE id = $category_id";
            Database::query($sql);

            $helpDeskMessage =
                '<table>
                        <tr>
                            <td width="100px"><b>' . get_lang('User').'</b></td>
                            <td width="400px">' . $currentUserInfo['complete_name'].'</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Username').'</b></td>
                            <td width="400px">' . $currentUserInfo['username'].'</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Email').'</b></td>
                            <td width="400px">' . $currentUserInfo['email'].'</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Phone').'</b></td>
                            <td width="400px">' . $currentUserInfo['phone'].'</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Date').'</b></td>
                            <td width="400px">' . api_convert_and_format_date($now, DATE_TIME_FORMAT_LONG).'</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Title').'</b></td>
                            <td width="400px">' . $subject.'</td>
                        </tr>
                        <tr>
                            <td width="100px"><b>' . get_lang('Description').'</b></td>
                            <td width="400px">' . $content.'</td>
                        </tr>
                    </table>';

            if ($assignedUserId != 0) {
                $href = api_get_path(WEB_CODE_PATH).'/ticket/ticket_details.php?ticket_id='.$ticketId;
                $helpDeskMessage .= sprintf(
                    get_lang('TicketAssignedToXCheckZAtLinkY'),
                    $assignedUserInfo['complete_name'],
                    $href,
                    $ticketId
                );
            }

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
                            self::send_message_simple(
                                $userId,
                                $warningSubject,
                                $helpDeskMessage
                            );
                        }
                    }
                }
            } else {
                $categoryInfo = self::getCategory($category_id);
                $usersInCategory = self::getUsersInCategory($category_id);

                $message = '<h2>'.get_lang('TicketInformation').'</h2><br />'.$helpDeskMessage;

                if (api_get_setting('ticket_warn_admin_no_user_in_category') === 'true') {
                    $usersInCategory = self::getUsersInCategory($category_id);
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
                                    self::sendNotification(
                                        $ticketId,
                                        $subject,
                                        $message,
                                        $userId
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
                            self::sendNotification(
                                $ticketId,
                                $subject,
                                $message,
                                $data['user_id']
                            );
                        }
                    }
                }
            }

            if (!empty($personalEmail)) {
                api_mail_html(
                    get_lang('VirtualSupport'),
                    $personalEmail,
                    get_lang('IncidentResentToVirtualSupport'),
                    $helpDeskMessage
                );
            }

            self::sendNotification(
                $ticketId,
                $titleCreated,
                $helpDeskMessage
            );

            return true;
        } else {
            return false;
        }
    }

    /**
     * Assign ticket to admin
     *
     * @param int $ticketId
     * @param int $userId
     *
     * @return bool
     */
    public static function assignTicketToUser(
        $ticketId,
        $userId
    ) {
        $ticketId = intval($ticketId);
        $userId = intval($userId);

        if (empty($ticketId)) {
            return false;
        }

        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $ticket = self::get_ticket_detail_by_id($ticketId);

        if ($ticket) {
            $sql = "UPDATE $table_support_tickets
                    SET assigned_last_user = $userId
                    WHERE id = $ticketId";
            Database::query($sql);

            $table = Database::get_main_table(TABLE_TICKET_ASSIGNED_LOG);
            $params = [
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'sys_insert_user_id' => api_get_user_id(),
                'assigned_date' => api_get_utc_datetime()
            ];
            Database::insert($table, $params);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Insert message between Users and Admins
     * @param int $ticketId
     * @param string $subject
     * @param string $content
     * @param array $file_attachments
     * @param int $userId
     * @param string $status
     * @param bool $sendConfirmation
     *
     * @return bool
     */
    public static function insertMessage(
        $ticketId,
        $subject,
        $content,
        $file_attachments,
        $userId,
        $status = 'NOL',
        $sendConfirmation = false
    ) {
        $ticketId = intval($ticketId);
        $userId = intval($userId);
        $table_support_messages = Database::get_main_table(TABLE_TICKET_MESSAGE);
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        if ($sendConfirmation) {
            $form = '<form action="ticket_details.php?ticket_id='.$ticketId.'" id="confirmticket" method="POST" >
                         <p>' . get_lang('TicketWasThisAnswerSatisfying').'</p>
                         <button class="btn btn-primary responseyes" name="response" id="responseyes" value="1">' . get_lang('Yes').'</button>
                         <button class="btn btn-danger responseno" name="response" id="responseno" value="0">' . get_lang('No').'</button>
                     </form>';
            $content .= $form;
        }

        $now = api_get_utc_datetime();

        $params = [
            'ticket_id' => $ticketId,
            'subject' => $subject,
            'message' => $content,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'sys_insert_user_id' => $userId,
            'sys_insert_datetime' => $now,
            'sys_lastedit_user_id' => $userId,
            'sys_lastedit_datetime' => $now,
            'status' => $status
        ];
        $messageId = Database::insert($table_support_messages, $params);
        if ($messageId) {
            // update_total_message
            $sql = "UPDATE $table_support_tickets
                    SET 
                        sys_lastedit_user_id ='$userId',
                        sys_lastedit_datetime ='$now',
                        total_messages = (
                            SELECT COUNT(*) as total_messages
                            FROM $table_support_messages
                            WHERE ticket_id ='$ticketId'
                        )
                    WHERE id = $ticketId ";
            Database::query($sql);

            if (is_array($file_attachments)) {
                foreach ($file_attachments as $file_attach) {
                    if ($file_attach['error'] == 0) {
                        self::save_message_attachment_file(
                            $file_attach,
                            $ticketId,
                            $messageId
                        );
                    } else {
                        if ($file_attach['error'] != UPLOAD_ERR_NO_FILE) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Attachment files when a message is sent
     * @param $file_attach
     * @param $ticketId
     * @param $message_id
     * @return array
     */
    public static function save_message_attachment_file(
        $file_attach,
        $ticketId,
        $message_id
    ) {
        $now = api_get_utc_datetime();
        $userId = api_get_user_id();
        $ticketId = intval($ticketId);
        $new_file_name = add_ext_on_mime(
            stripslashes($file_attach['name']),
            $file_attach['type']
        );
        $file_name = $file_attach['name'];
        $table_support_message_attachments = Database::get_main_table(TABLE_TICKET_MESSAGE_ATTACHMENTS);
        if (!filter_extension($new_file_name)) {
            echo Display::return_message(
                get_lang('UplUnableToSaveFileFilteredExtension'),
                'error'
            );
        } else {
            $new_file_name = uniqid('');
            $path_attachment = api_get_path(SYS_ARCHIVE_PATH);
            $path_message_attach = $path_attachment.'plugin_ticket_messageattch/';
            if (!file_exists($path_message_attach)) {
                @mkdir($path_message_attach, api_get_permissions_for_new_directories(), true);
            }
            $new_path = $path_message_attach.$new_file_name;
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
                    size,
                    sys_insert_user_id,
                    sys_insert_datetime,
                    sys_lastedit_user_id,
                    sys_lastedit_datetime
                ) VALUES (
                    '$safe_file_name',
                    '$safe_new_file_name',
                    '$ticketId',
                    '$message_id',
                    '".$file_attach['size']."',
                    '$userId',
                    '$now',
                    '$userId',
                    '$now'
                )";
            Database::query($sql);

            return array(
                'path' => $path_message_attach.$safe_new_file_name,
                'filename' => $safe_file_name,
            );
        }
    }

    /**
     * Get tickets by userId
     * @param int $from
     * @param int  $number_of_items
     * @param $column
     * @param $direction
     * @param int $userId
     * @return array
     */
    public static function get_tickets_by_user_id(
        $from,
        $number_of_items,
        $column,
        $direction,
        $userId = 0
    ) {
        $table_support_category = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_priority = Database::get_main_table(TABLE_TICKET_PRIORITY);
        $table_support_status = Database::get_main_table(TABLE_TICKET_STATUS);
        $direction = !empty($direction) ? $direction : 'DESC';
        $userId = !empty($userId) ? $userId : api_get_user_id();
        $isAdmin = UserManager::is_admin($userId);

        switch ($column) {
            case 0:
                $column = 'ticket_id';
                break;
            case 1:
                $column = 'status_name';
                break;
            case 2:
                $column = 'start_date';
                break;
            case 3:
                $column = 'sys_lastedit_datetime';
                break;
            case 4:
                $column = 'category_name';
                break;
            case 5:
                $column = 'sys_insert_user_id';
                break;
            case 6:
                $column = 'assigned_last_user';
                break;
            case 7:
                $column = 'total_messages';
                break;
            case 8:
                $column = 'subject';
                break;
            default:
                $column = 'ticket_id';
        }

        $sql = "SELECT DISTINCT 
                ticket.*,
                ticket.id ticket_id,
                status.name AS status_name,
                ticket.start_date,
                ticket.sys_lastedit_datetime,
                cat.name AS category_name,
                priority.name AS priority_name,                           
                ticket.total_messages AS total_messages,
                ticket.message AS message,
                ticket.subject AS subject,
                ticket.assigned_last_user
            FROM $table_support_tickets ticket 
            INNER JOIN $table_support_category cat
            ON (cat.id = ticket.category_id)
            INNER JOIN $table_support_priority priority
            ON (ticket.priority_id = priority.id)
            INNER JOIN $table_support_status status
            ON (ticket.status_id = status.id)
            WHERE 1=1                                
        ";

        if (!$isAdmin) {
            $sql .= " AND (ticket.assigned_last_user = $userId OR ticket.sys_insert_user_id = $userId )";
        }

        // Search simple
        if (isset($_GET['submit_simple']) && $_GET['keyword'] != '') {
            $keyword = Database::escape_string(trim($_GET['keyword']));
            $sql .= " AND (
                      ticket.id LIKE '%$keyword%' OR
                      ticket.code LIKE '%$keyword%' OR
                      ticket.subject LIKE '%$keyword%' OR
                      ticket.message LIKE '%$keyword%' OR
                      ticket.keyword LIKE '%$keyword%' OR
                      ticket.source LIKE '%$keyword%' OR
                      ticket.personal_email LIKE '%$keyword%'                          
            )";
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

            $keyword_range = !empty($keyword_start_date_start) && !empty($keyword_start_date_end);
            $keyword_course = Database::escape_string(trim($_GET['keyword_course']));
            if ($keyword_category != '') {
                $sql .= " AND ticket.category_id = '$keyword_category' ";
            }

            if ($keyword_admin != '') {
                $sql .= " AND ticket.assigned_last_user = '$keyword_admin' ";
            }
            if ($keyword_status != '') {
                $sql .= " AND ticket.status_id = '$keyword_status' ";
            }

            if ($keyword_range == false && $keyword_start_date_start != '') {
                $sql .= " AND DATE_FORMAT(ticket.start_date,'%d/%m/%Y') >= '$keyword_start_date_start' ";
            }
            if ($keyword_range && $keyword_start_date_start != '' && $keyword_start_date_end != '') {
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
                $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
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

        $sql .= " ORDER BY $column $direction";
        $sql .= " LIMIT $from, $number_of_items";

        $result = Database::query($sql);
        $tickets = array();
        $webPath = api_get_path(WEB_PATH);
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
            $hrefUser = $webPath.'main/admin/user_information.php?user_id='.$userInfo['user_id'];
            $name = "<a href='$hrefUser'> {$userInfo['complete_name_with_username']} </a>";
            $actions = '';

            if ($row['assigned_last_user'] != 0) {
                $assignedUserInfo = api_get_user_info($row['assigned_last_user']);
                if (!empty($assignedUserInfo)) {
                    $hrefResp = $webPath.'main/admin/user_information.php?user_id='.$assignedUserInfo['user_id'];
                    $row['assigned_last_user'] = "<a href='$hrefResp'> {$assignedUserInfo['complete_name_with_username']} </a>";
                } else {
                    $row['assigned_last_user'] = get_lang('UnknownUser');
                }
            } else {
                if ($row['status_id'] !== self::STATUS_FORWARDED) {
                    $row['assigned_last_user'] = '<span style="color:#ff0000;">'.get_lang('ToBeAssigned').'</span>';
                } else {
                    $row['assigned_last_user'] = '<span style="color:#00ff00;">'.get_lang('MessageResent').'</span>';
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

            $row['start_date'] = Display::dateToStringAgoAndLongDate($row['start_date']);
            $row['sys_lastedit_datetime'] = Display::dateToStringAgoAndLongDate($row['sys_lastedit_datetime']);

            $icon = Display::return_icon($img_source, get_lang('Info')).'<a href="ticket_details.php?ticket_id='.$row['id'].'">'.$row['code'].'</a>';

            if ($isAdmin) {
                $ticket = array(
                    $icon.' '.$row['subject'],
                    $row['status_name'],
                    $row['start_date'],
                    $row['sys_lastedit_datetime'],
                    $row['category_name'],
                    $name,
                    $row['assigned_last_user'],
                    $row['total_messages']
                );
            } else {
                $actions = '';
                /*
                $now = api_strtotime(api_get_utc_datetime());
                $last_edit_date = api_strtotime($row['sys_lastedit_datetime']);
                $dif = $now - $last_edit_date;

                if ($dif > 172800 && $row['priority_id'] === self::PRIORITY_NORMAL && $row['status_id'] != self::STATUS_CLOSE) {
                    $actions .= '<a href="'.api_get_path(WEB_CODE_PATH).'ticket/tickets.php?ticket_id=' . $row['ticket_id'] . '&amp;action=alert">
                                 <img src="' . Display::returnIconPath('exclamation.png') . '" border="0" /></a>';
                }
                if ($row['priority_id'] === self::PRIORITY_HIGH) {
                    $actions .= '<img src="' . Display::returnIconPath('admin_star.png') . '" border="0" />';
                }*/
                $ticket = array(
                    $icon.' '.$row['subject'],
                    $row['status_name'],
                    $row['start_date'],
                    $row['sys_lastedit_datetime'],
                    $row['category_name']
                );
            }
            /*if ($unread > 0) {
                $ticket['0'] = $ticket['0'] . '&nbsp;&nbsp;(' . $unread . ')<a href="ticket_details.php?ticket_id=' . $row['ticket_id'] . '">
                                <img src="' . Display::returnIconPath('message_new.png') . '" border="0" title="' . $unread . ' ' . get_lang('Messages') . '"/>
                                </a>';
            }*/
            if ($isAdmin) {
                $ticket['0'] .= '&nbsp;&nbsp;<a  href="javascript:void(0)" onclick="load_history_ticket(\'div_'.$row['ticket_id'].'\','.$row['ticket_id'].')">
					<img onclick="load_course_list(\'div_' . $row['ticket_id'].'\','.$row['ticket_id'].')" onmouseover="clear_course_list (\'div_'.$row['ticket_id'].'\')" src="'.Display::returnIconPath('history.gif').'" title="'.get_lang('Historial').'" alt="'.get_lang('Historial').'"/>
					<div class="blackboard_hide" id="div_' . $row['ticket_id'].'">&nbsp;&nbsp;</div>
					</a>&nbsp;&nbsp;';
            }
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * @param int $userId
     * @return mixed
     */
    public static function get_total_tickets_by_user_id($userId = 0)
    {
        $table_support_category = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_priority = Database::get_main_table(TABLE_TICKET_PRIORITY);
        $table_support_status = Database::get_main_table(TABLE_TICKET_STATUS);

        $userId = api_get_user_id();

        $sql = "SELECT COUNT(ticket.id) AS total
                FROM $table_support_tickets ticket
                INNER JOIN $table_support_category cat
                ON (cat.id = ticket.category_id)
                INNER JOIN $table_support_priority priority
                ON (ticket.priority_id = priority.id)
                INNER JOIN $table_support_status status
                ON (ticket.status_id = status.id)
	        WHERE 1 = 1";

        if (!api_is_platform_admin()) {
            $sql .= " AND (ticket.assigned_last_user = $userId OR ticket.sys_insert_user_id = $userId )";
        }

        // Search simple
        if (isset($_GET['submit_simple'])) {
            if ($_GET['keyword'] != '') {
                $keyword = Database::escape_string(trim($_GET['keyword']));
                $sql .= " AND (
                          ticket.code LIKE '%$keyword%' OR
                          ticket.subject LIKE '%$keyword%' OR
                          ticket.message LIKE '%$keyword%' OR
                          ticket.keyword LIKE '%$keyword%' OR
                          ticket.personal_email LIKE '%$keyword%' OR
                          ticket.source LIKE '%$keyword%'
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

            if ($keyword_admin != '') {
                $sql .= " AND ticket.assigned_last_user = '$keyword_admin'  ";
            }
            if ($keyword_status != '') {
                $sql .= " AND ticket.status_id = '$keyword_status'  ";
            }
            if ($keyword_range == false && $keyword_start_date_start != '') {
                $sql .= " AND DATE_FORMAT( ticket.start_date,'%d/%m/%Y') = '$keyword_start_date_start' ";
            }
            if ($keyword_range && $keyword_start_date_start != '' && $keyword_start_date_end != '') {
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
                $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
                $sql .= " AND ticket.course_id IN ( ";
                $sql .= "SELECT id
                         FROM $course_table
                         WHERE (title LIKE '%$keyword_course%'
                         OR code LIKE '%$keyword_course%'
                         OR visual_code LIKE '%$keyword_course%' )) ";
            }
        }
        /*
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
        }*/
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total;
    }

    /**
     * @param int $ticketId
     * @return array
     */
    public static function get_ticket_detail_by_id($ticketId)
    {
        $ticketId = intval($ticketId);
        $table_support_category = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $table_support_priority = Database::get_main_table(TABLE_TICKET_PRIORITY);
        $table_support_status = Database::get_main_table(TABLE_TICKET_STATUS);
        $table_support_messages = Database::get_main_table(TABLE_TICKET_MESSAGE);
        $table_support_message_attachments = Database::get_main_table(TABLE_TICKET_MESSAGE_ATTACHMENTS);
        $table_main_user = Database::get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT
                    ticket.*, 
                    cat.name,
                    status.name as status, 
                    priority.name priority
                FROM $table_support_tickets ticket
                INNER JOIN $table_support_category cat
                ON (cat.id = ticket.category_id)
                INNER JOIN $table_support_priority priority
                ON (priority.id = ticket.priority_id)
                INNER JOIN $table_support_status status
                ON (status.id = ticket.status_id)
		        WHERE
                    ticket.id = $ticketId ";
        $result = Database::query($sql);
        $ticket = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $row['course'] = null;
                $row['start_date_from_db'] = $row['start_date'];
                $row['start_date'] = api_convert_and_format_date(api_get_local_time($row['start_date']), DATE_TIME_FORMAT_LONG, api_get_timezone());
                $row['end_date_from_db'] = $row['end_date'];
                $row['end_date'] = api_convert_and_format_date(api_get_local_time($row['end_date']), DATE_TIME_FORMAT_LONG, api_get_timezone());
                $row['sys_lastedit_datetime_from_db'] = $row['sys_lastedit_datetime'];
                $row['sys_lastedit_datetime'] = api_convert_and_format_date(api_get_local_time($row['sys_lastedit_datetime']), DATE_TIME_FORMAT_LONG, api_get_timezone());
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
                $row['user_url'] = '<a href="'.api_get_path(WEB_PATH).'main/admin/user_information.php?user_id='.$userInfo['user_id'].'">
                ' . $userInfo['complete_name'].'</a>';
                $ticket['usuario'] = $userInfo;
                $ticket['ticket'] = $row;
            }

            $sql = "SELECT *, message.id as message_id 
                    FROM $table_support_messages message 
                    INNER JOIN $table_main_user user
                    ON (message.sys_insert_user_id = user.user_id)
                    WHERE
                        message.ticket_id = '$ticketId' ";
            $result = Database::query($sql);
            $ticket['messages'] = array();
            $attach_icon = Display::return_icon('attachment.gif', '');
            $webPath = api_get_path(WEB_CODE_PATH);
            while ($row = Database::fetch_assoc($result)) {
                $message = $row;
                $completeName = api_get_person_name($row['firstname'], $row['lastname']);
                $href = $webPath.'main/admin/user_information.php?user_id='.$row['user_id'];
                $message['admin'] = UserManager::is_admin($message['user_id']);
                $message['user_created'] = "<a href='$href'> $completeName </a>";
                $sql = "SELECT *
                        FROM $table_support_message_attachments
                        WHERE
                            message_id = ".$row['message_id']." AND
                            ticket_id = $ticketId";

                $result_attach = Database::query($sql);
                while ($row2 = Database::fetch_assoc($result_attach)) {
                    $archiveURL = $archiveURL = $webPath.'ticket/download.php?ticket_id='.$ticketId.'&file=';
                    $row2['attachment_link'] = $attach_icon.'&nbsp;<a href="'.$archiveURL.$row2['path'].'&title='.$row2['filename'].'">'.$row2['filename'].'</a>&nbsp;('.$row2['size'].')';
                    $message['attachments'][] = $row2;
                }
                $ticket['messages'][] = $message;
            }
        }

        return $ticket;
    }

    /**
     * @param int $ticketId
     * @param int $userId
     * @return bool
     */
    public static function update_message_status($ticketId, $userId)
    {
        $ticketId = intval($ticketId);
        $userId = intval($userId);
        $table_support_messages = Database::get_main_table(
            TABLE_TICKET_MESSAGE
        );
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $now = api_get_utc_datetime();
        $sql = "UPDATE $table_support_messages
                SET
                    status = 'LEI',
                    sys_lastedit_user_id ='".api_get_user_id()."',
                    sys_lastedit_datetime ='" . $now."'
                WHERE ticket_id ='$ticketId' ";

        if (api_is_platform_admin()) {
            $sql .= " AND sys_insert_user_id = '$userId'";
        } else {

            $sql .= " AND sys_insert_user_id != '$userId'";
        }
        $result = Database::query($sql);
        if (Database::affected_rows($result) > 0) {
            Database::query(
                "UPDATE $table_support_tickets SET
                    status_id = '".self::STATUS_PENDING."'
                 WHERE id ='$ticketId' AND status_id = '".self::STATUS_NEW."'"
            );
            return true;
        } else {
            return false;
        }
    }

    /**
     * Send notification to a user through the internal messaging system
     * @param int $ticketId
     * @param string $title
     * @param string $message
     * @param int $onlyToUserId
     *
     * @return bool
     */
    public static function sendNotification($ticketId, $title, $message, $onlyToUserId = 0)
    {
        $ticketInfo = self::get_ticket_detail_by_id($ticketId);

        if (empty($ticketInfo)) {
            return false;
        }

        $assignedUserInfo = api_get_user_info($ticketInfo['ticket']['assigned_last_user']);
        $requestUserInfo = $ticketInfo['usuario'];
        $ticketCode = $ticketInfo['ticket']['code'];
        $status = $ticketInfo['ticket']['status'];
        $priority = $ticketInfo['ticket']['priority'];

        // Subject
        $titleEmail = "[$ticketCode] $title";

        // Content
        $href = api_get_path(WEB_CODE_PATH).'/ticket/ticket_details.php?ticket_id='.$ticketId;
        $ticketUrl = Display::url($ticketCode, $href);
        $messageEmail = get_lang('TicketNum').": $ticketUrl <br />";
        $messageEmail .= get_lang('Status').": $status <br />";
        $messageEmail .= get_lang('Priority').": $priority <br />";
        $messageEmail .= '<hr /><br />';
        $messageEmail .= $message;

        $currentUserId = api_get_user_id();

        if (!empty($onlyToUserId)) {
            // Send only to specific user
            if ($currentUserId != $onlyToUserId) {
                MessageManager::send_message_simple(
                    $onlyToUserId,
                    $titleEmail,
                    $messageEmail
                );
            }
        } else {
            // Send to assigned user and to author
            if ($requestUserInfo && $currentUserId != $requestUserInfo['id']) {
                MessageManager::send_message_simple(
                    $requestUserInfo['id'],
                    $titleEmail,
                    $messageEmail
                );
            }

            if ($assignedUserInfo &&
                $requestUserInfo['id'] != $assignedUserInfo['id'] &&
                $currentUserId != $assignedUserInfo['id']
            ) {
                MessageManager::send_message_simple(
                    $assignedUserInfo['id'],
                    $titleEmail,
                    $messageEmail
                );
            }
        }
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

        return true;
    }

    /**
     * @param $status_id
     * @param $ticketId
     * @param $userId
     * @return bool
     */
    public static function update_ticket_status(
        $status_id,
        $ticketId,
        $userId
    ) {
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);

        $ticketId = intval($ticketId);
        $status_id = intval($status_id);
        $userId = intval($userId);

        $now = api_get_utc_datetime();
        $sql = "UPDATE $table_support_tickets
                SET
                    status_id = '$status_id',
                    sys_lastedit_user_id ='$userId',
                    sys_lastedit_datetime ='".$now."'
                WHERE id ='$ticketId'";
        $result = Database::query($sql);

        if (Database::affected_rows($result) > 0) {
            self::sendNotification(
                $ticketId,
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
        $userId = $user_info['user_id'];
        $sql = "SELECT COUNT(DISTINCT ticket.id) AS unread
                FROM $table_support_tickets ticket,
                $table_support_messages message ,
                $table_main_user user
                WHERE
                    ticket.id = message.ticket_id AND
                    message.status = 'NOL' AND
                    user.user_id = message.sys_insert_user_id ";
        if (!api_is_platform_admin()) {
            $sql .= " AND ticket.request_user = '$userId'
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
     * @param int $ticketId
     * @param int $userId
     */
    public static function send_alert($ticketId, $userId)
    {
        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $now = api_get_utc_datetime();

        $ticketId = intval($ticketId);
        $userId = intval($userId);

        $sql = "UPDATE $table_support_tickets SET
                  priority_id = '".self::PRIORITY_HIGH."',
                  sys_lastedit_user_id ='$userId',
                  sys_lastedit_datetime ='$now'
                WHERE id = '$ticketId'";
        Database::query($sql);
    }

    /**
     * @param int $ticketId
     * @param int $userId
     */
    public static function close_ticket($ticketId, $userId)
    {
        $ticketId = intval($ticketId);
        $userId = intval($userId);

        $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
        $now = api_get_utc_datetime();
        $sql = "UPDATE $table_support_tickets SET
                    status_id = '".self::STATUS_CLOSE."',
                    sys_lastedit_user_id ='$userId',
                    sys_lastedit_datetime ='".$now."',
                    end_date ='$now'
                WHERE id ='$ticketId'";
        Database::query($sql);

        self::sendNotification(
            $ticketId,
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
     * @param int $ticketId
     * @return array
     */
    public static function get_assign_log($ticketId)
    {
        $table = Database::get_main_table(TABLE_TICKET_ASSIGNED_LOG);
        $ticketId = intval($ticketId);

        $sql = "SELECT * FROM $table
                WHERE ticket_id = $ticketId
                ORDER BY assigned_date DESC";
        $result = Database::query($sql);
        $history = [];
        $webpath = api_get_path(WEB_PATH);
        while ($row = Database::fetch_assoc($result)) {
            if ($row['user_id'] != 0) {
                $assignuser = api_get_user_info($row['user_id']);
                $row['assignuser'] = '<a href="'.$webpath.'main/admin/user_information.php?user_id='.$row['user_id'].'"  target="_blank">'.
                $assignuser['username'].'</a>';
            } else {
                $row['assignuser'] = get_lang('Unassign');
            }
            $row['assigned_date'] = date_to_str_ago($row['assigned_date']);
            $insertuser = api_get_user_info($row['sys_insert_user_id']);
            $row['insertuser'] = '<a href="'.$webpath.'main/admin/user_information.php?user_id='.$row['sys_insert_user_id'].'"  target="_blank">'.
                $insertuser['username'].'</a>';
            $history[] = $row;
        }
        return $history;
    }

    /**
     * @param $from
     * @param $number_of_items
     * @param $column
     * @param $direction
     * @param null $userId
     * @return array
     */
    public static function export_tickets_by_user_id(
        $from,
        $number_of_items,
        $column,
        $direction,
        $userId = null
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
        if (is_null($userId) || $userId == 0) {
            $userId = api_get_user_id();
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
                $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
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
                $row['responsable'] = $row['responsable']['firstname'].' '.$row['responsable']['lastname'];
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
        if ($project) {
            Database::getManager()->remove($project);
            Database::getManager()->flush();
        }
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
        if ($item) {
            Database::getManager()->remove($item);
            Database::getManager()->flush();
        }
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
        $item = self::getPriority($id);
        if ($item) {
            Database::getManager()->remove($item);
            Database::getManager()->flush();
        }
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
    public static function getDefaultStatusList()
    {
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
    public static function getDefaultPriorityList()
    {
        return [
            self::PRIORITY_NORMAL,
            self::PRIORITY_HIGH,
            self::PRIORITY_LOW,
            self::STATUS_CLOSE,
            self::STATUS_FORWARDED
        ];
    }

    /**
     * Deletes the user from all the ticket system
     * @param int $userId
     */
    public static function deleteUserFromTicketSystem($userId)
    {
        $userId = (int) $userId;
        $schema = Database::getManager()->getConnection()->getSchemaManager();

        if ($schema->tablesExist('ticket_assigned_log')) {
            $sql = "UPDATE ticket_assigned_log SET user_id = NULL WHERE user_id = $userId";
            Database::query($sql);

            $sql = "UPDATE ticket_assigned_log SET sys_insert_user_id = NULL WHERE sys_insert_user_id = $userId";
            Database::query($sql);
        }

        if ($schema->tablesExist('ticket_ticket')) {
            $sql = "UPDATE ticket_ticket SET assigned_last_user = NULL WHERE assigned_last_user = $userId";
            Database::query($sql);

            $sql = "UPDATE ticket_ticket SET sys_insert_user_id = NULL WHERE sys_insert_user_id = $userId";
            Database::query($sql);

            $sql = "UPDATE ticket_ticket SET sys_lastedit_user_id = NULL WHERE sys_lastedit_user_id = $userId";
            Database::query($sql);
        }

        if ($schema->tablesExist('ticket_category')) {
            $sql = "UPDATE ticket_category SET sys_insert_user_id = NULL WHERE sys_insert_user_id = $userId";
            Database::query($sql);

            $sql = "UPDATE ticket_category SET sys_lastedit_user_id = NULL WHERE sys_lastedit_user_id = $userId";
            Database::query($sql);
        }

        if ($schema->tablesExist('ticket_category_rel_user')) {
            $sql = "DELETE FROM ticket_category_rel_user WHERE user_id = $userId";
            Database::query($sql);
        }

        if ($schema->tablesExist('ticket_message')) {
            $sql = "UPDATE ticket_message SET sys_insert_user_id = NULL WHERE sys_insert_user_id = $userId";
            Database::query($sql);

            $sql = "UPDATE ticket_message SET sys_lastedit_user_id = NULL WHERE sys_lastedit_user_id = $userId";
            Database::query($sql);
        }

        if ($schema->tablesExist('ticket_message_attachments')) {
            $sql = "UPDATE ticket_message_attachments SET sys_insert_user_id = NULL WHERE sys_insert_user_id = $userId";
            Database::query($sql);

            $sql = "UPDATE ticket_message_attachments SET sys_lastedit_user_id = NULL WHERE sys_lastedit_user_id = $userId";
            Database::query($sql);
        }

        if ($schema->tablesExist('ticket_priority')) {
            $sql = "UPDATE ticket_priority SET sys_insert_user_id = NULL WHERE sys_insert_user_id = $userId";
            Database::query($sql);

            $sql = "UPDATE ticket_priority SET sys_lastedit_user_id = NULL WHERE sys_lastedit_user_id = $userId";
            Database::query($sql);
        }

        if ($schema->tablesExist('ticket_project')) {
            $sql = "UPDATE ticket_project SET sys_insert_user_id = NULL WHERE sys_insert_user_id = $userId";
            Database::query($sql);

            $sql = "UPDATE ticket_project SET sys_lastedit_user_id = NULL WHERE sys_lastedit_user_id = $userId";
            Database::query($sql);
        }
    }
}
