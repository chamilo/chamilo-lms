<?php

namespace Tests\Knp\DoctrineBehaviors\ORM;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;
use Doctrine\Common\EventManager;

require_once 'EntityManagerProvider.php';

class SoftDeletableTest extends \PHPUnit_Framework_TestCase
{
    use EntityManagerProvider;

    protected function getUsedEntityFixtures()
    {
        return array(
            'BehaviorFixtures\\ORM\\DeletableEntity'
        );
    }

    protected function getEventManager()
    {
        $em = new EventManager;

        $em->addEventSubscriber(
            new \Knp\DoctrineBehaviors\ORM\SoftDeletable\SoftDeletableSubscriber(
                new ClassAnalyzer(),
                true,
                'Knp\DoctrineBehaviors\Model\SoftDeletable\SoftDeletable'
        ));

        return $em;
    }

    public function testDelete()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\DeletableEntity();

        $em->persist($entity);
        $em->flush();

        $this->assertNotNull($id = $entity->getId());
        $this->assertFalse($entity->isDeleted());

        $em->remove($entity);
        $em->flush();
        $em->clear();

        $entity = $em->getRepository('BehaviorFixtures\ORM\DeletableEntity')->find($id);

        $this->assertNotNull($entity);
        $this->assertTrue($entity->isDeleted());
    }

    public function testPostDelete()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\DeletableEntity();

        $em->persist($entity);
        $em->flush();

        $this->assertNotNull($id = $entity->getId());

        $entity->setDeletedAt((new \DateTime())->modify('+1 day'));

        $em->flush();
        $em->clear();

        $entity = $em->getRepository('BehaviorFixtures\ORM\DeletableEntity')->find($id);

        $this->assertNotNull($entity);
        $this->assertFalse($entity->isDeleted());
        $this->assertTrue($entity->willBeDeleted());
        $this->assertTrue($entity->willBeDeleted((new \DateTime())->modify('+2 day')));
        $this->assertFalse($entity->willBeDeleted((new \DateTime())->modify('+12 hour')));

        $entity->setDeletedAt((new \DateTime())->modify('-1 day'));

        $em->flush();
        $em->clear();

        $entity = $em->getRepository('BehaviorFixtures\ORM\DeletableEntity')->find($id);

        $this->assertNotNull($entity);
        $this->assertTrue($entity->isDeleted());
    }

    public function testDeleteInheritance()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\DeletableEntityInherit();

        $em->persist($entity);
        $em->flush();

        $em->remove($entity);
        $em->flush();

        $this->assertTrue($entity->isDeleted());
    }

    public function testRestore()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\DeletableEntityInherit();

        $em->persist($entity);
        $em->flush();

        $em->remove($entity);
        $em->flush();

        $this->assertTrue($entity->isDeleted());

        $entity->restore();

        $this->assertFalse($entity->isDeleted());
    }
}
