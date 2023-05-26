<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'resource_type')]
#[ORM\Entity]
class ResourceType implements Stringable
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    #[Groups(['resource_node:read'])]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column]
    #[Groups(['resource_node:read'])]
    protected string $name;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Tool::class, inversedBy: 'resourceTypes')]
    #[ORM\JoinColumn(name: 'tool_id', referencedColumnName: 'id')]
    protected Tool $tool;

    /**
     * @var ResourceNode[]|Collection
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\ResourceNode::class, mappedBy: 'resourceType', cascade: ['persist', 'remove'])]
    protected Collection $resourceNodes;

    public function __construct()
    {
        $this->resourceNodes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getTool(): Tool
    {
        return $this->tool;
    }

    public function setTool(Tool $tool): self
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * @return ResourceNode[]|Collection
     */
    public function getResourceNodes(): array|Collection
    {
        return $this->resourceNodes;
    }

    /**
     * @param ResourceNode[]|Collection $resourceNodes
     */
    public function setResourceNodes(array|Collection $resourceNodes): self
    {
        $this->resourceNodes = $resourceNodes;

        return $this;
    }
}
