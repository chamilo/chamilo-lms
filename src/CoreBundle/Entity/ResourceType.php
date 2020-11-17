<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="resource_type")
 */
class ResourceType
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column()
     *
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Tool", inversedBy="resourceTypes")
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="id")
     */
    protected $tool;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\ResourceNode", mappedBy="resourceType", cascade={"persist", "remove"})
     */
    protected $resourceNodes;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Tool
     */
    public function getTool()
    {
        return $this->tool;
    }

    /**
     * @param Tool $tool
     */
    public function setTool($tool): self
    {
        $this->tool = $tool;

        return $this;
    }

    public function getResourceNodes()
    {
        return $this->resourceNodes;
    }

    public function setResourceNodes($resourceNodes): self
    {
        $this->resourceNodes = $resourceNodes;

        return $this;
    }
}
