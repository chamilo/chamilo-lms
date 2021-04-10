<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="c_shortcut")
 * @ORM\Entity
 */
class CShortcut extends AbstractResource implements ResourceInterface
{
    /**
     * @Groups({"cshortcut:read"})
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @Groups({"cshortcut:read"})
     *
     * @Assert\NotBlank
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected string $name;

    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\ResourceNode")
     * @ORM\JoinColumn(name="shortcut_node_id", referencedColumnName="id" )
     */
    protected ResourceNode $shortCutNode;

    public function __toString(): string
    {
        return $this->getName();
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

    public function getResourceIdentifier(): int
    {
        return $this->id;
    }

    public function getShortCutNode(): ResourceNode
    {
        return $this->shortCutNode;
    }

    public function setShortCutNode(ResourceNode $shortCutNode): self
    {
        $this->shortCutNode = $shortCutNode;

        return $this;
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }

    public function setResourceName(string $name): self
    {
        return $this->setName($name);
    }
}
