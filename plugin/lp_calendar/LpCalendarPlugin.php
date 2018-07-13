<?php
/* For license terms, see /license.txt */

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class LpCalendarPlugin
 */
class LpCalendarPlugin extends Plugin
{
    const EVENT_TYPE_TAKEN = 1;
    const EVENT_TYPE_EXAM = 2;

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $version = '0.1';
        $author = 'Julio Montoya';
        parent::__construct($version, $author, ['enabled' => 'boolean']);
    }

    public static function getEventTypeList()
    {
        return [
            //self::EVENT_TYPE_FREE => 'green',
            self::EVENT_TYPE_TAKEN => 'red',
            self::EVENT_TYPE_EXAM => 'yellow'
        ];
    }

    /**
     * Get the class instance
     *
     * @return $this
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Get the plugin directory name
     */
    public function get_name()
    {
        return 'lp_calendar';
    }

    /**
     * Install the plugin. Setup the database
     */
    public function install()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS learning_calendar(
              id int not null AUTO_INCREMENT primary key,
              title varchar(255) not null default '',
              description longtext default null,
              total_hours int not null default 0,
              minutes_per_day int not null default 0,
              disabled int default 0
            )
        ";
        Database::query($sql);

        $sql = "
            CREATE TABLE IF NOT EXISTS learning_calendar_events(
              id int not null AUTO_INCREMENT primary key,
              name varchar(255) default '',
              calendar_id int not null,
              start_date date not null,
              end_date date not null,
              type int not null
            )
        ";
        Database::query($sql);

        $sql = "
            CREATE TABLE IF NOT EXISTS learning_calendar_user(
              id int not null AUTO_INCREMENT primary key,
              user_id int(11) not null,
              calendar_id int not null
            )
        ";
        Database::query($sql);

        $extraField = new ExtraField('lp_item');
        $params = [
            'variable' => 'calendar',
            'visible_to_self' => 1,
            'changeable' => 1,
            'visible_to_others' => 1,
            'field_type' => ExtraField::FIELD_TYPE_CHECKBOX
        ];

        $extraField->save($params);

        $extraField = new ExtraField('course');
        $params = [
            'variable' => 'course_hours_duration',
            'visible_to_self' => 1,
            'changeable' => 1,
            'visible_to_others' => 1,
            'field_type' => ExtraField::FIELD_TYPE_TEXT
        ];

        $extraField->save($params);

        return true;
    }

    /**
     * uninstall plugin. Clear the database
     */
    public function uninstall()
    {
        $tables = [
            'learning_calendar',
            'learning_calendar_events',
            'learning_calendar_user',
        ];

        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }

        $extraField = new ExtraField('lp_item');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable('calendar');

        if ($fieldInfo) {
            $extraField->delete($fieldInfo['id']);
        }

        $extraField = new ExtraField('course');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable('course_hours_duration');
        if ($fieldInfo) {
            $extraField->delete($fieldInfo['id']);
        }

        return true;
    }

    /**
     * @param int    $from
     * @param int    $numberOfItems
     * @param int    $column
     * @param string $direction
     *
     * @return array|\Doctrine\DBAL\Driver\Statement
     */
    public static function getCalendars(
        $from,
        $numberOfItems,
        $column,
        $direction = 'DESC'
    ) {
        $column = (int) $column;
        $from = (int) $from;
        $numberOfItems = (int) $numberOfItems;
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'DESC';
        }

        $sql = 'select * FROM learning_calendar';

        $sql .= " LIMIT $from, $numberOfItems ";

        $result = Database::query($sql);
        $list = [];
        $link = api_get_path(WEB_PLUGIN_PATH).'lp_calendar/start.php';
        while ($row = Database::fetch_array($result)) {
            $id = $row['id'];
            $row['title'] = Display::url(
                $row['title'],
                api_get_path(WEB_PLUGIN_PATH).'lp_calendar/calendar.php?id='.$id
            );
            $actions = Display::url(
                Display::return_icon('edit.png', get_lang('Edit')),
                $link.'?action=edit&id='.$id
            );

            $actions .= Display::url(
                Display::return_icon('copy.png', get_lang('Copy')),
                $link.'?action=copy&id='.$id
            );

            $actions .= Display::url(
                Display::return_icon('delete.png', get_lang('Delete')),
                $link.'?action=delete&id='.$id
            );
            $row['actions'] = $actions;
            $list[] = $row;
        }

        return $list;

    }

    /**
     * @return int
     */
    public static function getCalendarCount()
    {
        $sql = 'select count(*) as count FROM learning_calendar';
        $result = Database::query($sql);
        $result = Database::fetch_array($result);

        return (int) $result['count'];
    }

    /**
     * @param int $id
     */
    public function toggleVisibility($id)
    {
        $extraField = new ExtraField('lp_item');
        $fieldInfo = $extraField->get_handler_field_info_by_field_variable('calendar');
        if ($fieldInfo) {
            $itemInfo = $this->getItemVisibility($id);
            if (empty($itemInfo)) {
                $extraField = new ExtraFieldValue('lp_item');
                $value = 1;
                $params = [
                    'field_id' => $fieldInfo['id'],
                    'value' => $value,
                    'item_id' => $id,
                ];
                $extraField->save($params);
            } else {
                $newValue = (int) $itemInfo['value'] === 1 ? 0 : 1;
                $extraField = new ExtraFieldValue('lp_item');
                $params = [
                    'id' => $itemInfo['id'],
                    'value' => $newValue
                ];
                $extraField->update($params);
            }
        }
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getItemVisibility($id)
    {
        $extraField = new ExtraFieldValue('lp_item');
        $values = $extraField->get_values_by_handler_and_field_variable($id, 'calendar');

        return $values;
    }

    /**
     * @param int $calendarId
     *
     * @return array|mixed
     */
    public static function getCalendar($calendarId)
    {
        $calendarId = (int) $calendarId;
        $sql = "SELECT * FROM learning_calendar WHERE id = $calendarId";
        $result = Database::query($sql);
        $item = Database::fetch_array($result, 'ASSOC');

        return $item;
    }

    /**
     * @param int $userId
     *
     * @return array|mixed
     */
    public static function getUserCalendar($userId)
    {
        $userId = (int) $userId;
        $sql = "SELECT * FROM learning_calendar_user WHERE user_id = $userId";
        $result = Database::query($sql);
        $item = Database::fetch_array($result, 'ASSOC');

        return $item;
    }

    /**
     * @param int $userId
     *
     * @return mixed|string
     */
    public static function getUserCalendarToString($userId)
    {
        $calendar = self::getUserCalendar($userId);
        if ($calendar) {
            $calendarInfo = self::getCalendar($calendar['calendar_id']);

            return $calendarInfo['title'];
        }

        return '';
    }

    /**
     * @param int $calendarId
     * @param int $userId
     *
     * @return bool
     */
    public static function addUserToCalendar($calendarId, $userId)
    {
        $calendar = self::getUserCalendar($userId);
        if (empty($calendar)) {
            $params = [
                'calendar_id' => $calendarId,
                'user_id' => $userId,
            ];

            Database::insert('learning_calendar_user', $params);
        }

        return true;
    }

    /**
     * @param int $calendarId
     * @param int $userId
     *
     * @return bool
     */
    public static function updateUserToCalendar($calendarId, $userId)
    {
        $calendar = self::getUserCalendar($userId);
        if (!empty($calendar)) {
            $params = [
                'calendar_id' => $calendarId,
                'user_id' => $userId,
            ];

            Database::update('learning_calendar_user', $params, ['id = ?' => $calendar['id']]);
        }

        return true;
    }

    /**
     * @param int $calendarId
     * @param int $userId
     *
     * @return bool
     */
    public static function deleteAllCalendarFromUser($calendarId, $userId)
    {
        $calendarId = (int) $calendarId;
        $userId = (int) $userId;
        $sql = "DELETE FROM learning_calendar_user 
                WHERE user_id = $userId AND calendar_id = $calendarId";
        Database::query($sql);

        return true;
    }


    /*public static function getUserCalendar($calendarId, $userId)
    {
        $params = [
            'calendar_id' => $calendarId,
            'user_id' => $calendarId,
        ];

        Database::insert('learning_calendar_user', $params);

        return true;
    }*/

    /**
     * @param FormValidator $form
     */
    public function getForm(FormValidator &$form)
    {
        $form->addText('title', get_lang('Title'));
        $form->addText('total_hours', get_lang('TotalHours'));
        $form->addText('minutes_per_day', get_lang('MinutesPerDay'));
        $form->addHtmlEditor('description', get_lang('Description'), false);
    }
}
