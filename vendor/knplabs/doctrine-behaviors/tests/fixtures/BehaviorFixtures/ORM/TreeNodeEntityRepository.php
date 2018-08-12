<?php

namespace BehaviorFixtures\ORM;

use Knp\DoctrineBehaviors\ORM\Tree;
use Doctrine\ORM\EntityRepository;

/**
 * @author     Florian Klein <florian.klein@free.fr>
 */
class TreeNodeEntityRepository extends EntityRepository
{
    use Tree\Tree;
}

