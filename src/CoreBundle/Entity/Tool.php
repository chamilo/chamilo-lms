<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Tool.
 *
 * @ORM\Table(name="tool")
 * @ORM\Entity
 */
class Tool
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @Groups({"tool:read"})
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", nullable=false, unique=true)
     */
    protected string $name;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\ResourceType", mappedBy="tool", cascade={"persist", "remove"})
     *
     * @var ResourceType[]|Collection
     */
    protected Collection $resourceTypes;

    public function __construct()
    {
        $this->resourceTypes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    /*public function getToolResourceRight()
    {
        return $this->toolResourceRight;
    }*/

    /*public function setToolResourceRight($toolResourceRight)
    {
        $this->toolResourceRight = new ArrayCollection();

        foreach ($toolResourceRight as $item) {
            $this->addToolResourceRight($item);
        }
    }*/

    /*public function addToolResourceRight(ToolResourceRight $toolResourceRight)
    {
        $toolResourceRight->setTool($this);
        $this->toolResourceRight[] = $toolResourceRight;

        return $this;
    }*/

    /*public function getResourceNodes()
    {
        return $this->resourceNodes;
    }*/

    /*public function setResourceNodes($resourceNodes)
    {
        $this->resourceNodes = $resourceNodes;

        return $this;
    }*/

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection
     */
    public function getResourceTypes()
    {
        return $this->resourceTypes;
    }

    public function hasResourceType(ResourceType $resourceType): bool
    {
        if (0 !== $this->resourceTypes->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('name', $resourceType->getName())
            );
            $relation = $this->resourceTypes->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    public function setResourceTypes(Collection $resourceTypes): self
    {
        $this->resourceTypes = $resourceTypes;

        return $this;
    }

    /**
     * @return ResourceType
     */
    public function getResourceTypeByName(string $name)
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('name', $name));

        return $this->getResourceTypes()->matching($criteria)->first();
    }
}
