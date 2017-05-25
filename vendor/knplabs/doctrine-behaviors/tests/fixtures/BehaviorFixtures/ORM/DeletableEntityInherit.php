<?php

namespace BehaviorFixtures\ORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class DeletableEntityInherit extends DeletableEntity
{

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * Returns object name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
