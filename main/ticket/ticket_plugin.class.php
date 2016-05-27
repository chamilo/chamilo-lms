<?php
/* For licensing terms, see /license.txt */

/**
 * Class TicketPlugin
 *
 * @package chamilo.plugin.ticket
 *
 */
class TicketPlugin extends Plugin
{
    /**
     * Set the result
     * @staticvar null $result
     * @return TicketPlugin
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    /**
     * TicketPlugin constructor.
     */
    protected function __construct()
    {
        $settings = array(
            'tool_enable' => 'boolean',
            'allow_student_add' => 'boolean',
            'allow_category_edition' => 'boolean',
            'warn_admin_no_user_in_category' => 'boolean',
            'send_warning_to_all_admins' => 'boolean'
        );

        parent::__construct(
            '3.0',
            'Kenny Rodas Chavez, Genesis Lopez, Francis Gonzales, Yannick Warnier, Julio Montoya',
            $settings
        );
    }

    /**
     * Install the ticket plugin
     */
    public function install()
    {
        // Create database tables and insert a Tab
        require_once api_get_path(SYS_PLUGIN_PATH) . PLUGIN_NAME . '/database.php';
    }

    /**
     * Uninstall the ticket plugin
     */
    public function uninstall()
    {
        $tblSettings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $tblTicketTicket = Database::get_main_table(TABLE_TICKET_TICKET);
        $tblTicketStatus = Database::get_main_table(TABLE_TICKET_STATUS);
        $tblTicketProject = Database::get_main_table(TABLE_TICKET_PROJECT);
        $tblTicketPriority = Database::get_main_table(TABLE_TICKET_PRIORITY);
        $tblTicketMesAttch = Database::get_main_table(TABLE_TICKET_MESSAGE_ATTACHMENTS);
        $tblTicketMessage = Database::get_main_table(TABLE_TICKET_MESSAGE);
        $tblTicketCategory = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $tblTicketAssgLog = Database::get_main_table(TABLE_TICKET_ASSIGNED_LOG);
        $settings = $this->get_settings();
        $plugSetting = current($settings);

        // Delete settings
        $sql = "DELETE FROM $tblSettings WHERE variable = 'ticket_tool_enable'";
        Database::query($sql);

        $sql = "DROP TABLE IF EXISTS $tblTicketTicket";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketStatus";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketProject";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketPriority";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketMesAttch";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketMessage";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketCategory";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketAssgLog";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketTicket";
        Database::query($sql);

        $rsTab = $this->deleteTab($plugSetting['comment']);

        if ($rsTab) {
            echo "<script>location.href = '" . $_SERVER['REQUEST_URI'] . "';</script>";
        }
    }
}
