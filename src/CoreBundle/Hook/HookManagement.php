<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Entity\HookCall;
use Database;
use Doctrine\ORM\EntityManager;

/**
 * @TODO: Improve description
 */
class HookManagement
{
    private $entityManager;

    /**
     * Constructor.
     */
    protected function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        //$this->tables[TABLE_HOOK_OBSERVER] = Database::get_main_table(TABLE_HOOK_OBSERVER);
        //$this->tables[TABLE_HOOK_EVENT] = Database::get_main_table(TABLE_HOOK_EVENT);
        //$this->tables[TABLE_HOOK_CALL] = Database::get_main_table(TABLE_HOOK_CALL);

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
    public static function create(EntityManager $entityManager)
    {
        static $result = null;

        return $result ?: $result = new self($entityManager);
    }

    /**
     * Insert hook into Database. Return insert id.
     *
     * @param string $eventName
     * @param string $observerClassName
     * @param int    $type
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return int
     */
    public function insertHook($eventName, $observerClassName, $type)
    {
        if (HOOK_EVENT_TYPE_ALL === $type) {
            $this->insertHook($eventName, $observerClassName, HOOK_EVENT_TYPE_PRE);
            $this->insertHook($eventName, $observerClassName, HOOK_EVENT_TYPE_POST);

            return 1;
        }

        $em = $this->entityManager;

        $this->insertHookIfNotExist($eventName, $observerClassName);
        // Check if exists hook call
        $call = $em
            ->getRepository('ChamiloCoreBundle:HookCall')
            ->findOneBy(
                [
                    'hookEventId' => $this->hookEvents[$eventName],
                    'hookObserverId' => $this->hookObservers[$observerClassName],
                    'type' => $type,
                ]
            );

        if (!$call) {
            return 0;
        }

        $call->setEnabled(true);

        $em->persist($call);
        $em->flush();

        return 1;
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
        if (HOOK_EVENT_TYPE_ALL === $type) {
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
     *
     * @return int
     */
    //public function orderHook($eventName, $type, $hookOrders)
    //{
    /*foreach ($this->hookCalls[$eventName] as $observerClassName => $types) {
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
    }*/
    //}

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
        $rows = $this
            ->entityManager
            ->createQuery(
                'SELECT ho.className AS class_name, ho.path, ho.pluginName AS plugin_name, hc.enabled
                FROM ChamiloCoreBundle:HookCall hc
                INNER JOIN ChamiloCoreBundle:HookEvent he WITH hc.hookEventId = he.id
                INNER JOIN ChamiloCoreBundle:HookObserver ho WITH hc.hookObserverId = ho.id
                WHERE he.className = :class_name AND hc.enabled = TRUE'
            )
            ->setParameter('class_name', $eventName)
            ->getResult();

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
        $rows = $this
            ->entityManager
            ->getRepository('ChamiloCoreBundle:HookObserver')
            ->findAll();

        /** @var \Chamilo\CoreBundle\Entity\HookObserver $hookObserver */
        foreach ($rows as $hookObserver) {
            $array[$hookObserver->getClassName()] = $hookObserver->getId();
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
        $rows = $this
            ->entityManager
            ->getRepository('ChamiloCoreBundle:HookEvent')
            ->findAll();

        /** @var \Chamilo\CoreBundle\Entity\HookEvent $hookEvent */
        foreach ($rows as $hookEvent) {
            $array[$hookEvent->getClassName()] = $hookEvent->getId();
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
        $rows = $this
            ->entityManager
            ->createQuery(
                'SELECT he.className AS event_class_name, ho.className observer_class_name, hc.id, hc.type
                FROM ChamiloCoreBundle:HookCall hc
                INNER JOIN ChamiloCoreBundle:HookEvent he WITH hc.hookEventId = he.id
                INNER JOIN ChamiloCoreBundle:HookObserver ho WITH hc.hookObserverId = ho.id'
            )
            ->getResult();

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
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return int
     */
    public function insertHookIfNotExist($eventName = null, $observerClassName = null)
    {
        // Check if exists hook event
        if (isset($eventName) && !isset($this->hookEvents[$eventName])) {
            $hookEvent = new \Chamilo\CoreBundle\Entity\HookEvent();
            $hookEvent
                ->setClassName($eventName)
                ->setDescription(get_lang("HookDescription$eventName"));

            $this->entityManager->persist($hookEvent);
            $this->entityManager->flush();

            $this->hookEvents[$eventName] = $hookEvent->getId();
        }

        // Check if exists hook observer
        if (isset($observerClassName) &&
            !isset($this->hookObservers[$observerClassName])
        ) {
            $object = $observerClassName::create();

            $hookObserver = new \Chamilo\CoreBundle\Entity\HookObserver();
            $hookObserver
                ->setClassName($observerClassName)
                ->setPath($object->getPath())
                ->setPluginName($object->getPluginName());

            $this->entityManager->persist($hookObserver);
            $this->entityManager->flush();

            $this->hookObservers[$observerClassName] = $hookObserver->getId();
        }

        if (isset($eventName) &&
            isset($observerClassName) &&
            !isset($this->hookCalls[$eventName][$observerClassName])
        ) {
            // HOOK TYPE PRE
            $maxHookOrder = (int) $this
                ->entityManager
                ->createQuery(
                    'SELECT MAX(hc.hookOrder) AS hook_order FROM ChamiloCoreBundle:HookCall hc
                    WHERE hc.hookEventId = :id AND hc.type = :type'
                )
                ->setParameters(['id' => $this->hookEvents[$eventName], 'type' => HOOK_EVENT_TYPE_PRE])
                ->getSingleScalarResult();

            // Check if exists hook call
            $hookCall = new HookCall();
            $hookCall
                ->setHookEventId($this->hookEvents[$eventName])
                ->setHookObserverId($this->hookObservers[$observerClassName])
                ->setType(HOOK_EVENT_TYPE_PRE)
                ->setEnabled(0)
                ->setHookOrder($maxHookOrder + 1);

            $this->entityManager->persist($hookCall);
            $this->entityManager->flush();

            $this->hookCalls[$eventName][$observerClassName][HOOK_EVENT_TYPE_PRE] = $hookCall->getId();

            // HOOK TYPE POST
            $maxHookOrder = (int) $this
                ->entityManager
                ->createQuery(
                    'SELECT MAX(hc.hookOrder) AS hook_order FROM ChamiloCoreBundle:HookCall hc
                    WHERE hc.hookEventId = :id AND hc.type = :type'
                )
                ->setParameters(['id' => $this->hookEvents[$eventName], 'type' => HOOK_EVENT_TYPE_POST])
                ->getSingleScalarResult();

            // Check if exists hook call
            $hookCall = new HookCall();
            $hookCall
                ->setHookEventId($this->hookEvents[$eventName])
                ->setHookObserverId($this->hookObservers[$observerClassName])
                ->setType(HOOK_EVENT_TYPE_POST)
                ->setEnabled(0)
                ->setHookOrder($maxHookOrder + 1);

            $this->entityManager->persist($hookCall);
            $this->entityManager->flush();

            $this->hookCalls[$eventName][$observerClassName][HOOK_EVENT_TYPE_POST] = $hookCall->getId();
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
