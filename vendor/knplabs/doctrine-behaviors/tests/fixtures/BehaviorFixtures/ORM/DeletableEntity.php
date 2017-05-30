<?php

namespace BehaviorFixtures\ORM;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({
 *     "mainclass" = "BehaviorFixtures\ORM\DeletableEntity",
 *     "subclass" = "BehaviorFixtures\ORM\DeletableEntityInherit"
 * })
 */
class DeletableEntity
{
    use Model\SoftDeletable\SoftDeletable;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Returns object id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
