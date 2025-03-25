<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_shortcut')]
#[ORM\Entity(repositoryClass: CShortcutRepository::class)]
class CShortcut extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['cshortcut:read'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\OneToOne(targetEntity: ResourceNode::class, inversedBy: 'shortCut')]
    #[ORM\JoinColumn(name: 'shortcut_node_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ResourceNode $shortCutNode;

    #[Groups(['cshortcut:read'])]
    protected string $url;

    #[Groups(['cshortcut:read'])]
    protected string $tool;

    #[Groups(['cshortcut:read'])]
    protected string $type;

    #[Groups(['cshortcut:read'])]
    private ?string $customImageUrl = null;


    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return '/r/'.$this->getShortCutNode()->getResourceType()->getTool()->getTitle().
            '/'.$this->getShortCutNode()->getResourceType()->getTitle().
            '/'.$this->getShortCutNode()->getId().
            '/link';
    }

    public function getTool(): string
    {
        return $this->getShortCutNode()->getResourceType()->getTool()->getTitle();
    }

    public function getType(): string
    {
        return $this->getShortCutNode()->getResourceType()->getTitle();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getCustomImageUrl(): ?string
    {
        return $this->customImageUrl;
    }

    public function setCustomImageUrl(?string $customImageUrl): self
    {
        $this->customImageUrl = $customImageUrl;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
