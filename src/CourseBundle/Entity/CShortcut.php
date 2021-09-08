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
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Repository\CShortcutRepository")
 */
class CShortcut extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @Groups({"cshortcut:read"})
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    #[Assert\NotBlank]
    protected string $name;

    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\ResourceNode", inversedBy="shortCut")
     * @ORM\JoinColumn(name="shortcut_node_id", referencedColumnName="id" )
     */
    protected ResourceNode $shortCutNode;

    /**
     * @Groups({"cshortcut:read"})
     */
    protected string $url;

    /**
     * @Groups({"cshortcut:read"})
     */
    protected string $tool;

    /**
     * @Groups({"cshortcut:read"})
     */
    protected string $type;

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return '/r/'.$this->getShortCutNode()->getResourceType()->getTool()->getName().
            '/'.$this->getShortCutNode()->getResourceType()->getName().
            '/'.$this->getShortCutNode()->getId().
            '/link';
    }

    public function getTool(): string
    {
        return $this->getShortCutNode()->getResourceType()->getTool()->getName();
    }

    public function getType(): string
    {
        return $this->getShortCutNode()->getResourceType()->getName();
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

    public function getId(): int
    {
        return $this->id;
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
