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

    #[Groups(['cshortcut:read'])]
    public ?string $target = null;

    /**
     * Optional custom URL to override the default /r/.../link format.
     * Example (for blogs): /resources/blog/{courseNodeId}/{blogId}/posts?cid=...&sid=...&gid=0
     */
    #[Groups(['cshortcut:read'])]
    private ?string $urlOverride = null;

    /**
     * Optional icon name (e.g., 'mdi-notebook-outline' for CBlog).
     */
    #[Groups(['cshortcut:read'])]
    private ?string $icon = null;

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getResourceIdentifier(): int
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
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

    /**
     * Main URL for the shortcut:
     * - If a custom URL was set (e.g., for CBlog), return that one.
     * - Otherwise, fallback to the legacy /r/{tool}/{type}/{nodeId}/link pattern.
     */
    public function getUrl(): string
    {
        if (!empty($this->urlOverride)) {
            return $this->urlOverride;
        }

        return '/r/'.
            $this->getShortCutNode()->getResourceType()->getTool()->getTitle().'/'.
            $this->getShortCutNode()->getResourceType()->getTitle().'/'.
            $this->getShortCutNode()->getId().
            '/link';
    }

    /**
     * Tool name (derived from the shortcut node).
     */
    public function getTool(): string
    {
        return $this->getShortCutNode()
            ->getResourceType()
            ->getTool()
            ->getTitle();
    }

    /**
     * Resource type name (derived from the shortcut node).
     */
    public function getType(): string
    {
        return $this->getShortCutNode()
            ->getResourceType()
            ->getTitle();
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

    /**
     * Set a custom URL that will be returned by getUrl().
     * Use it, for example, to point a CBlog shortcut to:
     *   /resources/blog/{courseNodeId}/{blogId}/posts?cid=...&sid=...&gid=0
     */
    public function setUrlOverride(?string $url): self
    {
        $this->urlOverride = $url;

        return $this;
    }

    public function getUrlOverride(): ?string
    {
        return $this->urlOverride;
    }

    /**
     * Set an icon name (e.g., 'mdi-notebook-outline').
     */
    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }
}
