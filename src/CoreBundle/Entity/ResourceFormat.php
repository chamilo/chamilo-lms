<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'resource_format')]
class ResourceFormat
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    protected string $name;

    /**
     * @var Collection<int, ResourceNode>
     */
    #[ORM\OneToMany(targetEntity: ResourceNode::class, mappedBy: 'resourceFormat', cascade: ['persist', 'remove'])]
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

    /**
     * @return Collection<int, ResourceNode>
     */
    public function getResourceNodes(): Collection
    {
        return $this->resourceNodes;
    }

    public function setResourceNodes(Collection $resourceNodes): self
    {
        $this->resourceNodes = $resourceNodes;

        return $this;
    }
}
