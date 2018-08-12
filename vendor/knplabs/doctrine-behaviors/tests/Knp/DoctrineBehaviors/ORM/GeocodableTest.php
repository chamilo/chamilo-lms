<?php

namespace Tests\Knp\DoctrineBehaviors\ORM;

use BehaviorFixtures\ORM\GeocodableEntity;
use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;
use Knp\DoctrineBehaviors\ORM\Geocodable\Type\Point;
use Doctrine\Common\EventManager;

require_once 'EntityManagerProvider.php';

class GeocodableTest extends \PHPUnit_Framework_TestCase
{
    use EntityManagerProvider;

    /**
     * @var callable $callable
     */
    private $callable;

    protected function getUsedEntityFixtures()
    {
        return array(
            'BehaviorFixtures\\ORM\\GeocodableEntity'
        );
    }

    /**
     * @return \Doctrine\Common\EventManager
     */
    protected function getEventManager()
    {
        $em = new EventManager;

        if ($this->callable === false) {
            $callable = function ($entity) {
                if ($location = $entity->getLocation()) {
                    return $location;
                }

                return Point::fromArray(
                    [
                        'longitude' => 47.7,
                        'latitude' => 7.9
                    ]
                );
            };
        } else {
            $callable = $this->callable;
        }

        $em->addEventSubscriber(
            new \Knp\DoctrineBehaviors\ORM\Geocodable\GeocodableSubscriber(
                new ClassAnalyzer(),
                false,
                'Knp\DoctrineBehaviors\Model\Geocodable\Geocodable',
                $callable
            )
        );

        return $em;
    }

    public function setUp()
    {
        $em = $this->getDBEngineEntityManager();

        $cities = $this->dataSetCities();

        foreach ($cities as $city) {
            $entity = new GeocodableEntity($city[1][0], $city[1][1]);
            $entity->setTitle($city[0]);
            $em->persist($entity);
        };

        $em->flush();
    }

    /**
     * @dataProvider dataSetCities
     */
    public function testInsertLocation($city, array $location)
    {
        $em = $this->getDBEngineEntityManager();

        $repository = $em->getRepository('BehaviorFixtures\ORM\GeocodableEntity');

        $entity = $repository->findOneByTitle($city);

        $this->assertLocation($location, $entity->getLocation());
    }

    /**
     * @dataProvider dataSetCities
     */
    public function testUpdateWithEditLocation($city, array $location, array $newLocation)
    {
        $em = $this->getDBEngineEntityManager();

        $repository = $em->getRepository('BehaviorFixtures\ORM\GeocodableEntity');

        /** @var GeocodableEntity $entity */
        $entity = $repository->findOneByTitle($city);

        $entity->setLocation(new Point($newLocation[0], $newLocation[1]));

        $newTitle = $city . " - edited";

        $entity->setTitle($newTitle);

        $em->flush();

        /** @var GeocodableEntity $entity */
        $entity = $repository->findOneByTitle($newTitle);

        $this->assertEquals($newTitle, $entity->getTitle());

        $this->assertLocation($newLocation, $entity->getLocation());
    }

    /**
     * @dataProvider dataSetCities
     */
    public function testUpdateWithoutEditLocation($city, array $location)
    {
        $em = $this->getDBEngineEntityManager();

        $repository = $em->getRepository('BehaviorFixtures\ORM\GeocodableEntity');

        /** @var GeocodableEntity $entity */
        $entity = $repository->findOneByTitle($city);

        $em->flush();

        $this->assertLocation($location, $entity->getLocation());
    }

    /**
     * @dataProvider dataSetCities
     */
    public function testUpdateWithoutEditWithGeocodableWatcher($city, array $location, array $newLocation)
    {
        $this->callable = null;

        $this->testUpdateWithEditLocation($city, $location, $newLocation);
    }

    public function testGetLocation()
    {
        $em = $this->getEntityManager();

        $entity = new \BehaviorFixtures\ORM\GeocodableEntity();

        $em->persist($entity);
        $em->flush();

        $this->assertInstanceOf('Knp\DoctrineBehaviors\ORM\Geocodable\Type\Point', $entity->getLocation());
    }

    /**
     * Geographical info
     * Taken from http://andrew.hedges.name/experiments/haversine, I don't know if it's the same
     * formula Postgresql uses, but it should do the trick.
     *
     * Requisheim <-> Paris         ~384 km,  ~238 miles
     * Requisheim <-> Nantes        ~671 km,  ~417 miles
     * Requisheim <-> New-York     ~6217 km, ~3864 miles
     *
     * @dataProvider dataSetCitiesDistances
     */
    public function testFindByDistance(array $location, $distance, $result, $text = null)
    {
        if (getenv("DB") == "mysql") {
            $this->markTestSkipped("findByDistance does not work with MYSQL");

            return null;
        }

        $em = $this->getDBEngineEntityManager();

        $repo = $em->getRepository('BehaviorFixtures\ORM\GeocodableEntity');

        $cities = $repo->findByDistance(new Point($location[0], $location[1]), $distance);

        $this->assertCount($result, $cities, $text);
    }

    /**
     * @return array
     */
    public function dataSetCities()
    {
        return array(
            array(
                'New-York',
                array(40.742786, -73.989272),
                array(40.742787, -73.989273)
            ),
            array(
                'Paris',
                array(48.858842, 2.355194),
                array(48.858843, 2.355195)
            ),
            array(
                'Nantes',
                array(47.218635, -1.544266),
                array(47.218636, -1.544267)
            )
        );
    }

    /**
     * From 'Reguisheim' (47.896319, 7.352943)
     *
     * @return array
     */
    public function dataSetCitiesDistances()
    {
        return array(
            array(
                array(47.896319, 7.352943),
                384000,
                0,
                'Paris is more than 384 km far from Reguisheim'
            ),
            array(
                array(47.896319, 7.352943),
                385000,
                1,
                'Paris is less than 385 km far from Reguisheim'
            ),
            array(
                array(47.896319, 7.352943),
                672000,
                1,
                'Nantes is more than 672 km far from Reguisheim'
            ),
            array(
                array(47.896319, 7.352943),
                673000,
                2,
                'Paris and Nantes are less than 673 km far from Reguisheim'
            ),
            array(
                array(47.896319, 7.352943),
                6222000,
                2,
                'New-York is more than 6222 km far from Reguisheim'
            ),
            array(
                array(47.896319, 7.352943),
                6223000,
                3,
                'Paris, Nantes and New-York are less than 6223 km far from Reguisheim'
            )
        );
    }

    private function assertLocation(array $expected, Point $given = null, $message = null)
    {
        $this->assertInstanceOf('Knp\DoctrineBehaviors\ORM\Geocodable\Type\Point', $given, $message);

        $this->assertEquals($expected[0], $given->getLatitude(), $message);
        $this->assertEquals($expected[1], $given->getLongitude(), $message);
    }
}
