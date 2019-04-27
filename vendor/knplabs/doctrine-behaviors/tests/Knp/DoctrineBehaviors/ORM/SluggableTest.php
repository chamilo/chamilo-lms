<?php

namespace Tests\Knp\DoctrineBehaviors\ORM;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;
use Doctrine\Common\EventManager;

require_once 'EntityManagerProvider.php';

class SluggableTest extends \PHPUnit_Framework_TestCase
{
    use EntityManagerProvider;

    protected function getUsedEntityFixtures()
    {
        return [
            'BehaviorFixtures\\ORM\\SluggableEntity'
        ];
    }

    protected function getEventManager()
    {
        $em = new EventManager;

        $em->addEventSubscriber(
            new \Knp\DoctrineBehaviors\ORM\Sluggable\SluggableSubscriber(
                new ClassAnalyzer(),
                false,
                'Knp\DoctrineBehaviors\Model\Sluggable\Sluggable'
        ));

        return $em;
    }

    public function testSlugLoading()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\SluggableEntity();

        $expected = 'the-name';

        $entity->setName('The name');

        $em->persist($entity);
        $em->flush();

        $this->assertNotNull($id = $entity->getId());

        $em->clear();

        $entity = $em->getRepository('BehaviorFixtures\ORM\SluggableEntity')->find($id);

        $this->assertNotNull($entity);
        $this->assertEquals($expected, $entity->getSlug());
    }

    public function testNotUpdatedSlug()
    {
        $em = $this->getEntityManager();

        $data = [
            [
                'slug' => 'the-name',
                'name' => 'The name',
            ],
            [
                'slug' => 'loic-rene',
                'name' => 'Löic & René',
            ],
            [
                'slug' => 'ivan-ivanovich',
                'name' => 'Иван Иванович',
            ],
            [
                'slug' => 'chateauneuf-du-pape',
                'name' => 'Châteauneuf du Pape'
            ],
            [
                'slug' => 'zlutoucky-kun',
                'name' => 'Žluťoučký kůň'
            ]
        ];

        foreach ($data as $row) {
            $entity = new \BehaviorFixtures\ORM\SluggableEntity();

            $entity->setName($row['name']);

            $em->persist($entity);
            $em->flush();

            $entity->setDate(new \DateTime);

            $em->persist($entity);
            $em->flush();

            $this->assertEquals($row['slug'], $entity->getSlug());
        }
    }

    public function testUpdatedSlug()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\SluggableEntity();

        $expected = 'the-name';

        $entity->setName('The name');

        $em->persist($entity);
        $em->flush();

        $this->assertEquals($entity->getSlug(), $expected);

        $expected = 'the-name-2';

        $entity->setName('The name 2');

        $em->persist($entity);
        $em->flush();

        $this->assertEquals($expected, $entity->getSlug());
    }
}
