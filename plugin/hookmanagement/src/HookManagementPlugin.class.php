<?php
/* For licensing terms, see /license.txt */
/**
 * @TODO: Improve description
 * @package chamilo.plugin.hookmanagement
 */

class HookManagementPlugin extends Plugin implements HookManagementInterface
{
    /**
     * Constructor
     */
    function __construct()
    {
        $parameters = array(
            'tool_enable' => 'boolean',
        );

        $this->tables[TABLE_PLUGIN_HOOK_OBSERVER] = Database::get_main_table(TABLE_PLUGIN_HOOK_OBSERVER);
        $this->tables[TABLE_PLUGIN_HOOK_EVENT] = Database::get_main_table(TABLE_PLUGIN_HOOK_EVENT);
        $this->tables[TABLE_PLUGIN_HOOK_CALL] = Database::get_main_table(TABLE_PLUGIN_HOOK_CALL);

        $this->hookCalls = array();
        $this->hookEvents = array();
        $this->hookObservers = array();

        parent::__construct('1.0', 'Daniel Barreto', $parameters);
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return HookManagementPlugin
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin
     * @return void
     */
    public function install()
    {
        $this->installDatabase();
        $this->initDatabase();
    }

    /**
     * Uninstall the plugin
     * @return void
     */
    public function uninstall()
    {
        $this->uninstallDatabase();
    }

    /**
     * Create the database tables for the plugin
     * @return void
     */
    private function installDatabase()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->tables[TABLE_PLUGIN_HOOK_OBSERVER] . '( ' .
            'id int UNSIGNED NOT NULL AUTO_INCREMENT, ' .
            'class_name varchar(255) UNIQUE, ' .
            'plugin_name varchar(255) NULL, ' .
            'PRIMARY KEY PK_hook_management_hook_observer (id) ' .
            '); ';
        Database::query($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->tables[TABLE_PLUGIN_HOOK_EVENT] . '( ' .
            'id int UNSIGNED NOT NULL AUTO_INCREMENT, ' .
            'class_name varchar(255) UNIQUE, ' .
            'description varchar(255), ' .
            'PRIMARY KEY PK_hook_management_hook_event (id) ' .
            '); ';
        Database::query($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->tables[TABLE_PLUGIN_HOOK_CALL] . '( ' .
            'id int UNSIGNED NOT NULL AUTO_INCREMENT, ' .
            'hook_event_id int UNSIGNED NOT NULL, ' .
            'hook_observer_id int UNSIGNED NOT NULL, ' .
            'type tinyint NOT NULL, ' .
            'hook_order int UNSIGNED NOT NULL, ' .
            'enabled tinyint NOT NULL, ' .
            'PRIMARY KEY PK_hook_management_hook_call (id) ' .
            '); ';
        Database::query($sql);
    }

    /**
     * Initialize Database storing hooks (events, observers, calls)
     * This should be called right after installDatabase method
     * @return int
     */
    public function initDatabase()
    {
        // Search hook events
        $hookEvents = array();
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, '\HookEvent')) {
                $interfaces = class_implements($class);
                $hookInterfaces = array();
                foreach ($interfaces as $interface) {
                    $hookInterface = (preg_filter('/Hook(.+)EventInterface/', '$1', $interface));
                    if (!empty($hookInterface)) {
                        $hookInterfaces[] = $hookInterface;
                    }
                }
                $hookEvents[$class] = $hookInterfaces;
            }
        }
        // Search hook observers
        $hookObservers = array();
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, '\HookObserver')) {
                $interfaces = class_implements($class);
                $hookInterfaces = array();
                foreach ($interfaces as $interface) {
                    $hookInterface = (preg_filter('/Hook(.+)ObserverInterface/', '$1', $interface));
                    if (!empty($hookInterface)) {
                        $hookInterfaces[] =$hookInterface;
                    }
                }
                $hookObservers[$class] = $hookInterfaces;
            }
        }
        // Search hook calls
        $hookCalls = array();
        foreach ($hookEvents as $hookEvent => $eventInterfaces) {
            if (!empty($eventInterfaces)) {
                $order = 0;
                foreach ($hookObservers as $hookObserver => $observerInterfaces) {
                    if ($observerInterfaces === $eventInterfaces) {
                        $order += 1;
                        $hookCalls[] = array($hookEvent, $hookObserver, HOOK_TYPE_PRE, $order);
                        $hookCalls[] = array($hookEvent, $hookObserver, HOOK_TYPE_POST, $order);
                    }
                }
            }
        }

        // Insert hook events
        foreach ($hookEvents as $hookEvent => $v) {
            $attributes = array(
                'class_name' => $hookEvent,
                'description' => get_plugin_lang('HookDescription' . $hookEvent, 'HookManagementPlugin'),
            );
            $id = Database::insert($this->tables[TABLE_PLUGIN_HOOK_EVENT], $attributes);
            // store hook event into property
            $this->hookEvents[$hookEvent] = $id;
        }

        // Insert hook observer
        foreach ($hookObservers as $hookObserver => $v) {
            $attributes = array(
                'class_name' => $hookObserver,
            );
            $id = Database::insert($this->tables[TABLE_PLUGIN_HOOK_OBSERVER], $attributes);
            // store hook observer into property
            $this->hookObservers[$hookObserver] = $id;
        }

        // Insert hook call
        foreach ($hookCalls as $hookCall) {
            $attributes = array(
                'hook_event_id' => $this->hookEvents[$hookCall[0]],
                'hook_observer_id' => $this->hookObservers[$hookCall[1]],
                'type' => $hookCall[2],
                'hook_order' => $hookCall[3],
            );
            // store hook call into property
            $id = Database::insert($this->tables[TABLE_PLUGIN_HOOK_CALL], $attributes);
            $this->hookCalls[$hookCall[0]][$hookCall[1]][$hookCall[2]] = $id;
        }

        return 1;
    }

    /**
     * Drop the database tables for the plugin
     * @return void
     */
    private function uninstallDatabase()
    {
        $sql = 'DROP TABLE IF EXISTS ' . $this->tables[TABLE_PLUGIN_HOOK_CALL] . '; ';
        Database::query($sql);
        $sql = 'DROP TABLE IF EXISTS ' . $this->tables[TABLE_PLUGIN_HOOK_EVENT] . '; ';
        Database::query($sql);
        $sql = 'DROP TABLE IF EXISTS ' . $this->tables[TABLE_PLUGIN_HOOK_OBSERVER] . '; ';
        Database::query($sql);
    }

    /**
     * Insert hook into Database. Return insert id
     * @param string $eventName
     * @param string $observerClassName
     * @param int $type
     * @return int
     */
    public function insertHook($eventName, $observerClassName, $type)
    {
        if ($type === HOOK_TYPE_ALL) {
            $this->insertHook($eventName, $observerClassName, HOOK_TYPE_PRE);
            $this->insertHook($eventName, $observerClassName, HOOK_TYPE_POST);
        } else {
            $this->insertHookIfNotExist($eventName, $observerClassName);
            // Check if exists hook call
            $row = Database::select('id, enabled',
                $this->tables[TABLE_PLUGIN_HOOK_CALL],
                array(
                    'where' => array(
                        'hook_event_id = ? ' => $this->hookEvents[$eventName],
                        'AND hook_observer_id = ? ' => $this->hookObservers[$observerClassName],
                        'AND type = ? ' => $type,
                    ),
                ),
                'ASSOC');

            if (!empty($row) && is_array($row)) {
                // Check if is hook call is active
                if ((int) $row['enabled'] === 0) {
                    Database::update(
                        $this->tables[TABLE_PLUGIN_HOOK_CALL],
                        array(
                            'enabled' => 1,
                        ),
                        array(
                            'id = ?' => $row['id'],
                        )
                    );
                }
            }
        }

    }

    /**
     * Delete hook from Database. Return deleted rows number
     * @param string $eventName
     * @param string $observerClassName
     * @param int $type
     * @return int
     */
    public function deleteHook($eventName, $observerClassName, $type)
    {
        if ($type === HOOK_TYPE_ALL) {
            $this->insertHook($eventName, $observerClassName, HOOK_TYPE_PRE);
            $this->insertHook($eventName, $observerClassName, HOOK_TYPE_POST);
        } else {
            $this->insertHookIfNotExist($eventName, $observerClassName);

            Database::update(
                $this->tables[TABLE_PLUGIN_HOOK_CALL],
                array(
                    'enabled' => 0,
                ),
                array(
                    'id = ? ' => $this->hookCalls[$eventName][$observerClassName][$type],
                )
            );
        }
    }

    /**
     * Update hook observer order by hook event
     * @param $eventName
     * @param $type
     * @param $hookOrders
     * @return int
     */
    public function orderHook($eventName, $type, $hookOrders)
    {
        foreach ($this->hookCalls[$eventName] as $observerClassName => $types) {
            foreach ($hookOrders as $oldOrder => $newOrder)
            {
                $res = Database::update(
                    $this->tables[TABLE_PLUGIN_HOOK_CALL],
                    array(
                        'hook_order ' => $newOrder,
                    ),
                    array(
                        'id = ? ' => $types[$type],
                        'AND hook_order = ? ' => $oldOrder,
                    )
                );

                if ($res) {
                    break;
                }
            }

        }
    }

    /**
     * Return a list an associative array where keys are the active hook observer class name
     * @param $eventName
     * @return array
     */
    public function listHookObservers($eventName)
    {
        $array = array();
        $joinTable = $this->tables[TABLE_PLUGIN_HOOK_CALL] . 'hc ' .
            ' INNER JOIN ' . $this->tables[TABLE_PLUGIN_HOOK_EVENT] . 'he ' .
            ' ON hc.hook_event_id = he.id ' .
            ' INNER JOIN ' . $this->tables[TABLE_PLUGIN_HOOK_OBSERVER] . ' ho ' .
            ' ON hc.hook_observer_id = ho.id ';
        $columns = 'ho.class_name, hc.enabled';
        $where = array('where' => array('he.class_name = ? ' => $eventName, 'AND hc.enabled = ? ' => 1));
        $rows = Database::select($columns, $joinTable, $where);

        foreach ($rows as $row) {
            $array[$row['class_name']] = $row['enabled'];
        }

        return $array;
    }

    /**
     * Check if hooks (event, observer, call) exist in Database, if not,
     * Will insert them into their respective table
     * @param string $eventName
     * @param string $observerClassName
     * @return int
     */
    public function insertHookIfNotExist($eventName = null, $observerClassName = null)
    {
        // Check if exists hook event
        if (isset($eventName) && !isset($this->hookEvents[$eventName])) {
            $attributes = array(
                'class_name' => $eventName,
                'description' => get_plugin_lang('HookDescription' . $eventName, 'HookManagementPlugin'),
            );
            $id = Database::insert($this->tables[TABLE_PLUGIN_HOOK_EVENT], $attributes);
            $this->hookEvents[$eventName] = $id;
        }

        // Check if exists hook observer
        if (isset($observerClassName) && !isset($this->hookObservers[$observerClassName])){
            $attributes = array(
                'class_name' => $observerClassName,
            );
            $id = Database::insert($this->tables[TABLE_PLUGIN_HOOK_OBSERVER], $attributes);
            $this->hookObservers[$observerClassName] = $id;
        }

        if (
            isset($eventName) &&
            isset($observerClassName) &&
            !isset($this->hookCalls[$eventName][$observerClassName])
        ) {
            // HOOK TYPE PRE

            $row = Database::select(
                'MAX(hook_order) as hook_order',
                $this->tables[TABLE_PLUGIN_HOOK_CALL],
                array(
                    'where' => array(
                        'hook_event_id = ? ' =>$this->hookEvents[$eventName],
                        'AND type = ? ' => HOOK_TYPE_PRE,
                    ),
                ),
                'ASSOC'
            );

            // Check if exists hook call
            $id = Database::insert(
                $this->tables[TABLE_PLUGIN_HOOK_CALL],
                array(
                    'hook_event_id' => $this->hookEvents[$eventName],
                    'hook_observer_id' => $this->hookObservers[$observerClassName],
                    'type' => HOOK_TYPE_PRE,
                    'enabled' => 0,
                    'hook_order' => $row['hook_order'] + 1,
                )
            );

            $this->hookCalls[$eventName][$observerClassName][HOOK_TYPE_PRE] = $id;

            // HOOK TYPE POST

            $row = Database::select(
                'MAX(hook_order) as hook_order',
                $this->tables[TABLE_PLUGIN_HOOK_CALL],
                array(
                    'where' => array(
                        'hook_event_id = ? ' =>$this->hookEvents[$eventName],
                        'AND type = ? ' => HOOK_TYPE_POST,
                    ),
                ),
                'ASSOC'
            );

            // Check if exists hook call
            $id = Database::insert(
                $this->tables[TABLE_PLUGIN_HOOK_CALL],
                array(
                    'hook_event_id' => $this->hookEvents[$eventName],
                    'hook_observer_id' => $this->hookObservers[$observerClassName],
                    'type' => HOOK_TYPE_POST,
                    'enabled' => 0,
                    'hook_order' => $row['hook_order'] + 1,
                )
            );

            $this->hookCalls[$eventName][$observerClassName][HOOK_TYPE_POST] = $id;

        } elseif (isset($eventName) && !isset($observerClassName)) {
            foreach ($this->hookObservers as $observer => $id) {
                $this->insertHookIfNotExist($eventName, $observer);
            }
        } elseif (!isset($eventName) && isset($observerClassName)) {
            foreach ($this->hookEvents as $event => $id) {
                $this->insertHookIfNotExist($event, $observerClassName);
            }
        }

        return 1;
    }

    /**
     * Return the hook call id identified by hook event, hook observer and type
     * @param $eventName
     * @param $observerClassName
     * @param $type
     * @return mixed
     */
    public function getHookCallId($eventName, $observerClassName, $type)
    {
        $eventName = Database::escape_string($eventName);
        $observerClassName($observerClassName);
        $type = Database::escape_string($type);
        $joinTable = $this->tables[TABLE_PLUGIN_HOOK_CALL] . 'hc ' .
            ' INNER JOIN ' . $this->tables[TABLE_PLUGIN_HOOK_EVENT] . 'he ' .
            ' ON hc.hook_event_id = he.id ' .
            ' INNER JOIN ' . $this->tables[TABLE_PLUGIN_HOOK_OBSERVER] . ' ho ' .
            ' ON hc.hook_observer_id = ho.id ';
        $row = Database::select(
            'id',
            $joinTable,
            array(
                'where' => array(
                    'he.class_name = ? ' => $eventName,
                    'AND ho.class_name = ? ' => $observerClassName,
                    'AND hc.type = ? ' => $type,
                ),
            ),
            'ASSOC'
        );

        return $row['id'];
    }
}
