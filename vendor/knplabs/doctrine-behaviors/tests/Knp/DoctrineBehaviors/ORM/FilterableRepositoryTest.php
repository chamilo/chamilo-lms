<?php

namespace tests\Knp\DoctrineBehaviors\ORM;

use BehaviorFixtures\ORM\FilterableEntity;

require_once 'EntityManagerProvider.php';

class FilterableRepositoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityManagerProvider;

    protected function getUsedEntityFixtures()
    {
        return [
            'BehaviorFixtures\\ORM\\FilterableEntity'
        ];
    }

    /**
     * @test
     */
    public function shouldFilterByNameUsingLike()
    {
        $this->createEntities();
        /**@var \BehaviorFixtures\ORM\FilterableRepository $repository*/
        $repository = $this->getEntityManager()->getRepository('BehaviorFixtures\ORM\FilterableEntity');

        $collection = $repository->filterBy(['e:name' => 'name'])->getQuery()->execute();

        $this->assertCount(2, $collection);
        $this->assertEquals('name1', $collection[0]->getName());
        $this->assertEquals('name2', $collection[1]->getName());
    }

    /**
     * @test
     */
    public function shouldFilterByCodeUsingEqual()
    {
        $this->createEntities();

        $repository = $this->getEntityManager()->getRepository('BehaviorFixtures\ORM\FilterableEntity');

        $collection = $repository->filterBy(['e:code' => '2'])->getQuery()->execute();

        $this->assertCount(1, $collection);
        $this->assertEquals('name1', $collection[0]->getName());
    }

    private function createEntities()
    {
        $em = $this->getEntityManager();

        foreach ([2 => 'name1', 20 => 'name2', 40 => 'otherValue'] as $code => $name) {
            $entity = new FilterableEntity();
            $entity->setCode($code);
            $entity->setName($name);

            $em->persist($entity);
        }
        $em->flush();
    }
}
