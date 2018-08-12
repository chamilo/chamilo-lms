<?php

namespace BehaviorFixtures\ORM;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="BehaviorFixtures\ORM\FilterableRepository")
 */
class FilterableEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $code;

    /**
     * Returns object id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name.
     *
     * @return name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param name the value to set.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get code.
     *
     * @return integer code.
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set code.
     *
     * @param integer code the value to set.
     */
    public function setCode($code)
    {
        $this->code = $code;
    }
}
