<?php

namespace Tests\Knp\DoctrineBehaviors\ORM;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;
use Doctrine\Common\EventManager;

require_once 'EntityManagerProvider.php';

class LoggableTest extends \PHPUnit_Framework_TestCase
{
    private $subscriber;
    private $logs = [];

    use EntityManagerProvider;

    protected function getUsedEntityFixtures()
    {
        return [
            'BehaviorFixtures\\ORM\\LoggableEntity',
        ];
    }

    protected function getEventManager()
    {
        $em = new EventManager;
        $loggerCallback = function($message) {
            $this->logs[] = $message;
        };
        $this->subscriber = new \Knp\DoctrineBehaviors\ORM\Loggable\LoggableSubscriber(
            new ClassAnalyzer(),
            false,
            $loggerCallback
        );

        $em->addEventSubscriber($this->subscriber);

        return $em;
    }

    /**
     * @test
     *
     * @dataProvider dataProviderValues
     */
    public function should_log_changeset_message_when_created($field, $value, $expected)
    {
        $em = $this->getEntityManager($this->getEventManager());

        $entity = new \BehaviorFixtures\ORM\LoggableEntity();

        $set = "set" . ucfirst($field);

        $entity->$set($value);

        $em->persist($entity);
        $em->flush();

        $this->assertCount(2, $this->logs);
        $this->assertEquals(
            $this->logs[0],
            'BehaviorFixtures\ORM\LoggableEntity #1 created'
        );

        $this->assertEquals(
            $this->logs[1],
            'BehaviorFixtures\ORM\LoggableEntity #1 : property "' . $field . '" changed from "" to "' . $expected . '"'
        );
    }

    /**
     * @test
     *
     * @dataProvider dataProviderValues
     */
    public function should_log_changeset_message_when_updated($field, $value, $expected)
    {
        $em = $this->getEntityManager($this->getEventManager());

        $entity = new \BehaviorFixtures\ORM\LoggableEntity();

        $em->persist($entity);
        $em->flush();

        $set = "set" . ucfirst($field);

        $entity->$set($value);
        $em->flush();

        $this->assertCount(3, $this->logs);
        $this->assertEquals(
            $this->logs[2],
            'BehaviorFixtures\ORM\LoggableEntity #1 : property "' . $field . '" changed from "" to "' . $expected . '"'
        );
    }

    /**
     * @test
     */
    public function should_not_log_changeset_message_when_no_change()
    {
        $em = $this->getEntityManager($this->getEventManager());

        $entity = new \BehaviorFixtures\ORM\LoggableEntity();

        $em->persist($entity);
        $em->flush();

        $entity->setTitle('test2');
        $entity->setTitle(null);
        $em->flush();

        $this->assertCount(2, $this->logs);
    }

    /**
     * @test
     */
    public function should_log_removal_message_when_deleted()
    {
        $em = $this->getEntityManager($this->getEventManager());

        $entity = new \BehaviorFixtures\ORM\LoggableEntity();

        $em->persist($entity);
        $em->flush();

        $em->remove($entity);
        $em->flush();

        $this->assertCount(3, $this->logs);
        $this->assertEquals(
            $this->logs[2],
            'BehaviorFixtures\ORM\LoggableEntity #1 removed'
        );
    }

    public function dataProviderValues() {
        return array(
            array(
                "title", "test", "test"
            ),
            array(
                "roles", array("x" => "y"), "an array"
            ),
            array(
                "date", new \DateTime("2014-02-02 12:20:30"), "2014-02-02 12:20:30"
            )
        );
    }
}
