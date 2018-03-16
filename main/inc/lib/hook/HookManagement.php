<?php
/* For licensing terms, see /license.txt */

/**
 * @TODO: Improve description
 *
 * @package chamilo.hookmanagement
 */
class HookManagement implements HookManagementInterface
{
    /**
     * Constructor.
     */
    protected function __construct()
    {
        $this->tables[TABLE_HOOK_OBSERVER] = Database::get_main_table(TABLE_HOOK_OBSERVER);
        $this->tables[TABLE_HOOK_EVENT] = Database::get_main_table(TABLE_HOOK_EVENT);
        $this->tables[TABLE_HOOK_CALL] = Database::get_main_table(TABLE_HOOK_CALL);

        $this->hookCalls = $this->listAllHookCalls();
        $this->hookEvents = $this->listAllHookEvents();
        $this->hookObservers = $this->listAllHookObservers();
    }

    /**
     * Instance the hook manager.
     *
     * @staticvar null $result
     *
     * @return HookManagement
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Insert hook into Database. Return insert id.
     *
     * @param string $eventName
     * @param string $observerClassName
     * @param int    $type
     *
     * @return int
     */
    public function insertHook($eventName, $observerClassName, $type)
    {
        if ($type === HOOK_EVENT_TYPE_ALL) {
            $this->insertHook($eventName, $observerClassName, HOOK_EVENT_TYPE_PRE);
            $this->insertHook($eventName, $observerClassName, HOOK_EVENT_TYPE_POST);
        } else {
            $this->insertHookIfNotExist($eventName, $observerClassName);
            // Check if exists hook call
            $row = Database::select(
                'id, enabled',
                $this->tables[TABLE_HOOK_CALL],
                [
                    'where' => [
                        'hook_event_id = ? ' => $this->hookEvents[$eventName],
                        'AND hook_observer_id = ? ' => $this->hookObservers[$observerClassName],
                        'AND type = ? ' => $type,
                    ],
                ],
                'ASSOC'
            );

            if (!empty($row) && is_array($row)) {
                // Check if is hook call is active
                if ((int) $row['enabled'] === 0) {
                    Database::update(
                        $this->tables[TABLE_HOOK_CALL],
                        [
                            'enabled' => 1,
                        ],
                        [
                            'id = ?' => $row['id'],
                        ]
                    );
                }
            }
        }
    }

    /**
     * Delete hook from Database. Return deleted rows number.
     *
     * @param string $eventName
     * @param string $observerClassName
     * @param int    $type
     *
     * @return int
     */
    public function deleteHook($eventName, $observerClassName, $type)
    {
        if ($type === HOOK_EVENT_TYPE_ALL) {
            $this->deleteHook($eventName, $observerClassName, HOOK_EVENT_TYPE_PRE);
            $this->deleteHook($eventName, $observerClassName, HOOK_EVENT_TYPE_POST);
        } else {
            $this->insertHookIfNotExist($eventName, $observerClassName);

            Database::update(
                $this->tables[TABLE_HOOK_CALL],
                [
                    'enabled' => 0,
                ],
                [
                    'id = ? ' => $this->hookCalls[$eventName][$observerClassName][$type],
                ]
            );
        }
    }

    /**
     * Update hook observer order by hook event.
     *
     * @param $eventName
     * @param $type
     * @param $hookOrders
     *
     * @return int
     */
    public function orderHook($eventName, $type, $hookOrders)
    {
        foreach ($this->hookCalls[$eventName] as $observerClassName => $types) {
            foreach ($hookOrders as $oldOrder => $newOrder) {
                $res = Database::update(
                    $this->tables[TABLE_HOOK_CALL],
                    [
                        'hook_order ' => $newOrder,
                    ],
                    [
                        'id = ? ' => $types[$type],
                        'AND hook_order = ? ' => $oldOrder,
                    ]
                );

                if ($res) {
                    break;
                }
            }
        }
    }

    /**
     * Return a list an associative array where keys are the active hook observer class name.
     *
     * @param string $eventName
     *
     * @return array
     */
    public function listHookObservers($eventName)
    {
        $array = [];
        $joinTable = $this->tables[TABLE_HOOK_CALL].' hc'.
            ' INNER JOIN '.$this->tables[TABLE_HOOK_EVENT].' he'.
            ' ON hc.hook_event_id = he.id '.
            ' INNER JOIN '.$this->tables[TABLE_HOOK_OBSERVER].' ho '.
            ' ON hc.hook_observer_id = ho.id ';
        $columns = 'ho.class_name, ho.path, ho.plugin_name, hc.enabled';
        $where = ['where' => ['he.class_name = ? ' => $eventName, 'AND hc.enabled = ? ' => 1]];
        $rows = Database::select($columns, $joinTable, $where);

        foreach ($rows as $row) {
            $array[$row['class_name']] = $row;
        }

        return $array;
    }

    /**
     * Return a list an associative array where keys are the active hook observer class name.
     *
     * @return array
     */
    public function listAllHookObservers()
    {
        $array = [];
        $columns = 'id, class_name';
        $rows = Database::select($columns, $this->tables[TABLE_HOOK_OBSERVER]);

        foreach ($rows as $row) {
            $array[$row['class_name']] = $row['id'];
        }

        return $array;
    }

    /**
     * Return a list an associative array where keys are the active hook observer class name.
     *
     * @return array
     */
    public function listAllHookEvents()
    {
        $array = [];
        $columns = 'id, class_name';
        $rows = Database::select($columns, $this->tables[TABLE_HOOK_EVENT]);

        foreach ($rows as $row) {
            $array[$row['class_name']] = $row['id'];
        }

        return $array;
    }

    /**
     * Return a list an associative array where keys are the active hook observer class name.
     *
     * @return array
     */
    public function listAllHookCalls()
    {
        $array = [];
        $joinTable = $this->tables[TABLE_HOOK_CALL].' hc'.
            ' INNER JOIN '.$this->tables[TABLE_HOOK_EVENT].' he'.
            ' ON hc.hook_event_id = he.id '.
            ' INNER JOIN '.$this->tables[TABLE_HOOK_OBSERVER].' ho '.
            ' ON hc.hook_observer_id = ho.id ';
        $columns = 'he.class_name AS event_class_name, ho.class_name AS observer_class_name, hc.id AS id, hc.type AS type';
        $rows = Database::select($columns, $joinTable);

        foreach ($rows as $row) {
            $array[$row['event_class_name']][$row['observer_class_name']][$row['type']] = $row['id'];
        }

        return $array;
    }

    /**
     * Check if hooks (event, observer, call) exist in Database, if not,
     * Will insert them into their respective table.
     *
     * @param string $eventName
     * @param string $observerClassName
     *
     * @return int
     */
    public function insertHookIfNotExist($eventName = null, $observerClassName = null)
    {
        // Check if exists hook event
        if (isset($eventName) && !isset($this->hookEvents[$eventName])) {
            $attributes = [
                'class_name' => $eventName,
                'description' => get_lang('HookDescription'.$eventName),
            ];
            $id = Database::insert($this->tables[TABLE_HOOK_EVENT], $attributes);
            $this->hookEvents[$eventName] = $id;
        }

        // Check if exists hook observer
        if (isset($observerClassName) &&
            !isset($this->hookObservers[$observerClassName])
        ) {
            $object = $observerClassName::create();
            $attributes = [
                'class_name' => $observerClassName,
                'path' => $object->getPath(),
                'plugin_name' => $object->getPluginName(),
            ];
            $id = Database::insert($this->tables[TABLE_HOOK_OBSERVER], $attributes);
            $this->hookObservers[$observerClassName] = $id;
        }

        if (isset($eventName) &&
            isset($observerClassName) &&
            !isset($this->hookCalls[$eventName][$observerClassName])
        ) {
            // HOOK TYPE PRE

            $row = Database::select(
                'MAX(hook_order) as hook_order',
                $this->tables[TABLE_HOOK_CALL],
                [
                    'where' => [
                        'hook_event_id = ? ' => $this->hookEvents[$eventName],
                        'AND type = ? ' => HOOK_EVENT_TYPE_PRE,
                    ],
                ],
                'ASSOC'
            );

            // Check if exists hook call
            $id = Database::insert(
                $this->tables[TABLE_HOOK_CALL],
                [
                    'hook_event_id' => $this->hookEvents[$eventName],
                    'hook_observer_id' => $this->hookObservers[$observerClassName],
                    'type' => HOOK_EVENT_TYPE_PRE,
                    'enabled' => 0,
                    'hook_order' => $row['hook_order'] + 1,
                ]
            );

            $this->hookCalls[$eventName][$observerClassName][HOOK_EVENT_TYPE_PRE] = $id;

            // HOOK TYPE POST
            $row = Database::select(
                'MAX(hook_order) as hook_order',
                $this->tables[TABLE_HOOK_CALL],
                [
                    'where' => [
                        'hook_event_id = ? ' => $this->hookEvents[$eventName],
                        'AND type = ? ' => HOOK_EVENT_TYPE_POST,
                    ],
                ],
                'ASSOC'
            );

            // Check if exists hook call
            $id = Database::insert(
                $this->tables[TABLE_HOOK_CALL],
                [
                    'hook_event_id' => $this->hookEvents[$eventName],
                    'hook_observer_id' => $this->hookObservers[$observerClassName],
                    'type' => HOOK_EVENT_TYPE_POST,
                    'enabled' => 0,
                    'hook_order' => $row['hook_order'] + 1,
                ]
            );

            $this->hookCalls[$eventName][$observerClassName][HOOK_EVENT_TYPE_POST] = $id;
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
     * Return the hook call id identified by hook event, hook observer and type.
     *
     * @param string $eventName
     * @param string $observerClassName
     * @param int    $type
     *
     * @return mixed
     */
    public function getHookCallId($eventName, $observerClassName, $type)
    {
        $eventName = Database::escape_string($eventName);
        $observerClassName($observerClassName);
        $type = Database::escape_string($type);
        $joinTable = $this->tables[TABLE_HOOK_CALL].' hc'.
            ' INNER JOIN '.$this->tables[TABLE_HOOK_EVENT].' he'.
            ' ON hc.hook_event_id = he.id '.
            ' INNER JOIN '.$this->tables[TABLE_HOOK_OBSERVER].' ho '.
            ' ON hc.hook_observer_id = ho.id ';
        $row = Database::select(
            'id',
            $joinTable,
            [
                'where' => [
                    'he.class_name = ? ' => $eventName,
                    'AND ho.class_name = ? ' => $observerClassName,
                    'AND hc.type = ? ' => $type,
                ],
            ],
            'ASSOC'
        );

        return $row['id'];
    }
}
