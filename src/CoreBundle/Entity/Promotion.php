<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Promotion.
 *
 * @ORM\Table(name="promotion")
 * @ORM\Entity
 */
class Promotion
{
    use TimestampableEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected $description;

    /**
     * @var Career
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Career")
     * @ORM\JoinColumn(name="career_id", referencedColumnName="id")
     */
    protected $career;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected $status;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Promotion
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set career.
     */
    public function setCareer(Career $career): self
    {
        $this->career = $career;

        return $this;
    }

    /**
     * Get career.
     *
     * @return Career
     */
    public function getCareer()
    {
        return $this->career;
    }

    /**
     * Set status.
     *
     * @param int $status
     */
    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
