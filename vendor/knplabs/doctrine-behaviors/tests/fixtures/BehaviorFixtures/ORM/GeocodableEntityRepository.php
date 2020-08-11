<?php

namespace BehaviorFixtures\ORM;

use Knp\DoctrineBehaviors\ORM\Geocodable;
use Doctrine\ORM\EntityRepository;

/**
 * @author     Florian Klein <florian.klein@free.fr>
 */
class GeocodableEntityRepository extends EntityRepository
{
    use Geocodable\GeocodableRepository;
}

