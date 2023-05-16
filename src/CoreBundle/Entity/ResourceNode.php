<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use Chamilo\CoreBundle\Entity\Listener\ResourceNodeListener;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Traits\TimestampableAgoTrait;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CToolIntro;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use InvalidArgumentException;
use Stringable;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Validator\Constraints as Assert;

//*     attributes={"security"="is_granted('ROLE_ADMIN')"},
/**
 * Base entity for all resources.
 */
#[ORM\Table(name: 'resource_node')]
#[ORM\Entity(repositoryClass: ResourceNodeRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\EntityListeners([ResourceNodeListener::class])]
#[Gedmo\Tree(type: 'materializedPath')]
#[ApiResource(
    operations: [
        new Get(),
        new Put(),
        new Patch(),
        new Delete(),
        new GetCollection()
    ],
    normalizationContext: [
        'groups' => [
            'resource_node:read',
            'document:read'
        ]
    ],
    denormalizationContext: [
        'groups' => [
            'resource_node:write',
            'document:write'
        ]
    ]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['id', 'title', 'resourceFile', 'createdAt', 'updatedAt'])]
#[ApiFilter(filterClass: PropertyFilter::class)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['title' => 'partial'])]
class ResourceNode implements Stringable
{
    use TimestampableTypedEntity;
    use TimestampableAgoTrait;
    public const PATH_SEPARATOR = '/';
    #[Groups(['resource_node:read', 'document:read', 'ctool:read', 'user_json:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;
    #[Groups(['resource_node:read', 'resource_node:write', 'document:read', 'document:write'])]
    #[Assert\NotBlank]
    #[Gedmo\TreePathSource]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;
    #[Assert\NotBlank]
    #[Gedmo\Slug(fields: ['title'])]
    #[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: false)]
    protected string $slug;
    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: ResourceType::class, inversedBy: 'resourceNodes')]
    #[ORM\JoinColumn(name: 'resource_type_id', referencedColumnName: 'id', nullable: false)]
    protected ResourceType $resourceType;
    /**
     * @var Collection<int, ResourceLink>
     */
    #[Groups(['ctool:read', 'c_tool_intro:read'])]
    #[ORM\OneToMany(mappedBy: 'resourceNode', targetEntity: ResourceLink::class, cascade: ['persist', 'remove'])]
    protected Collection $resourceLinks;
    /**
     * ResourceFile available file for this node.
     */
    #[Groups(['resource_node:read', 'resource_node:write', 'document:read', 'document:write', 'message:read'])]
    #[ORM\OneToOne(inversedBy: 'resourceNode', targetEntity: ResourceFile::class, orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'resource_file_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?ResourceFile $resourceFile = null;
    #[Assert\NotNull]
    #[Groups(['resource_node:read', 'resource_node:write', 'document:write'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class, inversedBy: 'resourceNodes')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected User $creator;
    #[ORM\JoinColumn(name: 'parent_id', onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    protected ?ResourceNode $parent = null;
    /**
     * @var Collection|ResourceNode[]
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    protected Collection $children;
    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'level', type: 'integer', nullable: true)]
    protected ?int $level = null;
    #[Groups(['resource_node:read', 'document:read'])]
    #[Gedmo\TreePath(appendId: true, separator: '/')]
    #[ORM\Column(name: 'path', type: 'text', nullable: true)]
    protected ?string $path = null;
    /**
     * Shortcut to access Course resource from ResourceNode.
     * Groups({"resource_node:read", "course:read"}).
     *
     * ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\Illustration", mappedBy="resourceNode")
     */
    //protected $illustration;
    /**
     * @var Collection|ResourceComment[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\ResourceComment::class, mappedBy: 'resourceNode', cascade: ['persist', 'remove'])]
    protected Collection $comments;
    #[Groups(['resource_node:read', 'document:read'])]
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime')]
    protected DateTime $createdAt;
    #[Groups(['resource_node:read', 'document:read'])]
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime')]
    protected DateTime $updatedAt;
    #[Groups(['resource_node:read', 'document:read'])]
    protected bool $fileEditableText;
    #[Groups(['resource_node:read', 'document:read'])]
    #[ORM\Column(type: 'boolean')]
    protected bool $public;
    protected ?string $content = null;
    #[ORM\OneToOne(targetEntity: \Chamilo\CourseBundle\Entity\CShortcut::class, mappedBy: 'shortCutNode', cascade: ['persist', 'remove'])]
    protected ?CShortcut $shortCut = null;
    #[Groups(['resource_node:read', 'document:read'])]
    #[ORM\Column(type: 'uuid', unique: true)]
    protected ?UuidV4 $uuid = null;
    public function __construct()
    {
        $this->public = false;
        $this->uuid = Uuid::v4();
        $this->children = new ArrayCollection();
        $this->resourceLinks = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->fileEditableText = false;
    }
    public function __toString(): string
    {
        return $this->getPathForDisplay();
    }
    public function getUuid(): ?UuidV4
    {
        return $this->uuid;
    }
    /**
     * Returns the resource id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    public function hasCreator(): bool
    {
        return null !== $this->creator;
    }
    public function getCreator(): ?User
    {
        return $this->creator;
    }
    public function setCreator(User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }
    /**
     * Returns the children resource instances.
     *
     * @return Collection|ResourceNode[]
     */
    public function getChildren(): Collection|array
    {
        return $this->children;
    }
    /**
     * Sets the parent resource.
     */
    public function setParent(self $parent = null): self
    {
        $this->parent = $parent;

        return $this;
    }
    /**
     * Returns the parent resource.
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }
    /**
     * Return the lvl value of the resource in the tree.
     */
    public function getLevel(): int
    {
        return $this->level;
    }
    /**
     * Returns the "raw" path of the resource
     * (the path merge names and ids of all items).
     * Eg.: "Root-1/subdir-2/file.txt-3/".
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    /**
     * @return Collection|ResourceComment[]
     */
    public function getComments(): Collection|array
    {
        return $this->comments;
    }
    public function addComment(ResourceComment $comment): self
    {
        $comment->setResourceNode($this);
        $this->comments->add($comment);

        return $this;
    }
    /**
     * Returns the path cleaned from its ids.
     * Eg.: "Root/subdir/file.txt".
     */
    public function getPathForDisplay(): string
    {
        return $this->path;
        //return $this->convertPathForDisplay($this->path);
    }
    public function getPathForDisplayToArray(?int $baseRoot = null): array
    {
        $parts = explode(self::PATH_SEPARATOR, $this->path);
        $list = [];
        foreach ($parts as $part) {
            $parts = explode('-', $part);
            if (empty($parts[1])) {
                continue;
            }
            $value = $parts[0];
            $id = $parts[1];
            if (!empty($baseRoot) && $id < $baseRoot) {
                continue;
            }
            $list[$id] = $value;
        }

        return $list;
    }
    public function getPathForDisplayRemoveBase(string $base): string
    {
        $path = str_replace($base, '', $this->path);

        return $this->convertPathForDisplay($path);
    }
    public function getSlug(): string
    {
        return $this->slug;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $title = str_replace('/', '-', $title);
        $this->title = $title;

        return $this;
    }
    public function setSlug(string $slug): self
    {
        if (str_contains(self::PATH_SEPARATOR, $slug)) {
            $message = 'Invalid character "'.self::PATH_SEPARATOR.'" in resource name';

            throw new InvalidArgumentException($message);
        }
        $this->slug = $slug;

        return $this;
    }
    /**
     * Convert a path for display: remove ids.
     */
    public function convertPathForDisplay(string $path): string
    {
        /*$pathForDisplay = preg_replace(
              '/-\d+'.self::PATH_SEPARATOR.'/',
              ' / ',
              $path
          );
          if ($pathForDisplay !== null && strlen($pathForDisplay) > 0) {
              $pathForDisplay = substr_replace($pathForDisplay, '', -3);
          }
          */
        $pathForDisplay = preg_replace('/-\\d+\\'.self::PATH_SEPARATOR.'/', '/', $path);
        if (null !== $pathForDisplay && '' !== $pathForDisplay) {
            $pathForDisplay = substr_replace($pathForDisplay, '', -1);
        }

        return $pathForDisplay;
    }
    public function getResourceType(): ResourceType
    {
        return $this->resourceType;
    }
    public function setResourceType(ResourceType $resourceType): self
    {
        $this->resourceType = $resourceType;

        return $this;
    }
    public function getResourceLinks(): Collection
    {
        return $this->resourceLinks;
    }
    public function addResourceLink(ResourceLink $link): self
    {
        $link->setResourceNode($this);
        $this->resourceLinks->add($link);

        return $this;
    }
    public function setResourceLinks(Collection $resourceLinks): self
    {
        $this->resourceLinks = $resourceLinks;

        return $this;
    }
    public function hasResourceFile(): bool
    {
        return null !== $this->resourceFile;
    }
    public function getResourceFile(): ?ResourceFile
    {
        return $this->resourceFile;
    }
    public function hasEditableTextContent(): bool
    {
        if ($this->hasResourceFile()) {
            $mimeType = $this->getResourceFile()->getMimeType();
            if (str_contains($mimeType, 'text')) {
                return true;
            }
        }

        return false;
    }
    public function isResourceFileAnImage(): bool
    {
        if ($this->hasResourceFile()) {
            $mimeType = $this->getResourceFile()->getMimeType();
            if (str_contains($mimeType, 'image')) {
                return true;
            }
        }

        return false;
    }
    public function isResourceFileAVideo(): bool
    {
        if ($this->hasResourceFile()) {
            $mimeType = $this->getResourceFile()->getMimeType();
            if (str_contains($mimeType, 'video')) {
                return true;
            }
        }

        return false;
    }
    public function setResourceFile(?ResourceFile $resourceFile = null): self
    {
        $this->resourceFile = $resourceFile;
        if (null !== $resourceFile) {
            $resourceFile->setResourceNode($this);
        }

        return $this;
    }
    public function getIcon(): string
    {
        $class = 'fa fa-folder';
        if ($this->hasResourceFile()) {
            $class = 'far fa-file';
            if ($this->isResourceFileAnImage()) {
                $class = 'far fa-file-image';
            }
            if ($this->isResourceFileAVideo()) {
                $class = 'far fa-file-video';
            }
        }

        return '<i class="'.$class.'"></i>';
    }
    public function getThumbnail(RouterInterface $router): string
    {
        $size = 'fa-3x';
        $class = sprintf('fa fa-folder %s', $size);
        if ($this->hasResourceFile()) {
            $class = sprintf('far fa-file %s', $size);
            if ($this->isResourceFileAnImage()) {
                $class = sprintf('far fa-file-image %s', $size);
                $params = [
                    'id' => $this->getId(),
                    'tool' => $this->getResourceType()->getTool(),
                    'type' => $this->getResourceType()->getName(),
                    'filter' => 'editor_thumbnail',
                ];
                $url = $router->generate('chamilo_core_resource_view', $params);

                return sprintf("<img src='%s'/>", $url);
            }
            if ($this->isResourceFileAVideo()) {
                $class = sprintf('far fa-file-video %s', $size);
            }
        }

        return '<i class="'.$class.'"></i>';
    }
    public function getContent(): ?string
    {
        return $this->content;
    }
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }
    public function getShortCut(): ?CShortcut
    {
        return $this->shortCut;
    }
    public function setShortCut(?CShortcut $shortCut): self
    {
        $this->shortCut = $shortCut;

        return $this;
    }
    public function isPublic(): bool
    {
        return $this->public;
    }
    public function setPublic(bool $public): self
    {
        $this->public = $public;

        return $this;
    }
}
