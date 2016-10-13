<?php

namespace Tests\Knp\DoctrineBehaviors\ORM;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;
use Doctrine\Common\EventManager;

require_once 'EntityManagerProvider.php';

class TimestampableTest extends \PHPUnit_Framework_TestCase
{
    use EntityManagerProvider;

    protected function getUsedEntityFixtures()
    {
        return array(
            'BehaviorFixtures\\ORM\\TimestampableEntity'
        );
    }

    protected function getEventManager()
    {
        $em = new EventManager;

        $em->addEventSubscriber(
            new \Knp\DoctrineBehaviors\ORM\Timestampable\TimestampableSubscriber(
                new ClassAnalyzer(),
                false,
                'Knp\DoctrineBehaviors\Model\Timestampable\Timestampable',
                'datetime'
        ));

        return $em;
    }

    /**
     * @test
     */
    public function it_should_initialize_create_and_update_datetime_when_created()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TimestampableEntity();

        $em->persist($entity);
        $em->flush();

        $this->assertInstanceOf('Datetime', $entity->getCreatedAt());
        $this->assertInstanceOf('Datetime', $entity->getUpdatedAt());

        $this->assertEquals(
            $entity->getCreatedAt(),
            $entity->getUpdatedAt(),
            'On creation, createdAt and updatedAt are the same'
        );
    }

    /**
     * @test
     */
    public function it_should_modify_update_datetime_when_updated_but_not_the_creation_datetime()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TimestampableEntity();

        $em->persist($entity);
        $em->flush();
        $em->refresh($entity);
        $id = $entity->getId();
        $createdAt = $entity->getCreatedAt();
        $em->clear();

        // wait for a second:
        sleep(1);

        $entity = $em->getRepository('BehaviorFixtures\ORM\TimestampableEntity')->find($id);
        $entity->setTitle('test');
        $em->flush();
        $em->clear();

        $entity = $em->getRepository('BehaviorFixtures\ORM\TimestampableEntity')->find($id);
        $this->assertEquals($createdAt, $entity->getCreatedAt(), 'createdAt is constant');

        $this->assertNotEquals(
            $entity->getCreatedAt(),
            $entity->getUpdatedAt(),
            'createat and updatedAt have diverged since new update'
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_same_datetime_when_not_updated()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TimestampableEntity();

        $em->persist($entity);
        $em->flush();
        $em->refresh($entity);
        $id = $entity->getId();
        $createdAt = $entity->getCreatedAt();
        $updatedAt = $entity->getUpdatedAt();
        $em->clear();

        sleep(1);

        $entity = $em->getRepository('BehaviorFixtures\ORM\TimestampableEntity')->find($id);
        $em->persist($entity);
        $em->flush();
        $em->clear();

        $this->assertEquals(
            $entity->getCreatedAt(),
            $createdAt,
            'Creation timestamp has changed'
        );

        $this->assertEquals(
            $entity->getUpdatedAt(),
            $updatedAt,
            'Update timestamp has changed'
        );
    }

    /**
     * @test
     */
    public function it_should_modify_update_datetime_only_once()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\TimestampableEntity();

        $em->persist($entity);
        $em->flush();
        $em->refresh($entity);
        $id = $entity->getId();
        $createdAt = $entity->getCreatedAt();
        $em->clear();

        sleep(1);

        $entity = $em->getRepository('BehaviorFixtures\ORM\TimestampableEntity')->find($id);
        $entity->setTitle('test');
        $em->flush();
        $updatedAt = $entity->getUpdatedAt();

        sleep(1);

        $em->flush();

        $this->assertEquals(
            $entity->getCreatedAt(),
            $createdAt,
            'Creation timestamp has changed'
        );

        $this->assertEquals(
            $entity->getUpdatedAt(),
            $updatedAt,
            'Update timestamp has changed'
        );
    }
}
