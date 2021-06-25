<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Chamilo\CoreBundle\Traits\TimestampableAgoTrait;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Chamilo\CourseBundle\Entity\CShortcut;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use InvalidArgumentException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Validator\Constraints as Assert;

//*     attributes={"security"="is_granted('ROLE_ADMIN')"},
/**
 * Base entity for all resources.
 *
 * @ApiResource(
 *     collectionOperations={"get"},
 *     normalizationContext={"groups"={"resource_node:read", "document:read"}},
 *     denormalizationContext={"groups"={"resource_node:write", "document:write"}}
 * )
 * @ApiFilter(SearchFilter::class, properties={"title":"partial"})
 * @ApiFilter(PropertyFilter::class)
 * @ApiFilter(OrderFilter::class, properties={"id", "title", "resourceFile", "createdAt", "updatedAt"})
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\ResourceNodeRepository")
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="resource_node")
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\ResourceNodeListener"})
 *
 * @Gedmo\Tree(type="materializedPath")
 */
class ResourceNode
{
    use TimestampableTypedEntity;
    use TimestampableAgoTrait;

    public const PATH_SEPARATOR = '/';

    /**
     * @Groups({"resource_node:read", "document:read", "ctool:read", "user_json:read"})
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @Assert\NotBlank()
     * @Groups({"resource_node:read", "resource_node:write", "document:read", "document:write"})
     * @Gedmo\TreePathSource
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @Assert\NotBlank()
     *
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(name="slug", type="string", length=255, nullable=false)
     */
    protected string $slug;

    /**
     * @ORM\ManyToOne(targetEntity="ResourceType", inversedBy="resourceNodes")
     * @ORM\JoinColumn(name="resource_type_id", referencedColumnName="id", nullable=false)
     */
    protected ResourceType $resourceType;

    /**
     * @ApiSubresource()
     * @Groups({"ctool:read"})
     *
     * @var Collection|ResourceLink[]
     *
     * @ORM\OneToMany(targetEntity="ResourceLink", mappedBy="resourceNode", cascade={"persist", "remove"})
     */
    protected Collection $resourceLinks;

    /**
     * ResourceFile available file for this node.
     *
     * @Groups({"resource_node:read", "resource_node:write", "document:read", "document:write"})
     *
     * @ORM\OneToOne(targetEntity="ResourceFile", inversedBy="resourceNode", orphanRemoval=true)
     * @ORM\JoinColumn(name="resource_file_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?ResourceFile $resourceFile = null;

    /**
     * @Assert\Valid()
     * @Groups({"resource_node:read", "resource_node:write", "document:write"})
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="resourceNodes")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected User $creator;

    /**
     * @ApiSubresource()
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(
     *     targetEntity="ResourceNode",
     *     inversedBy="children"
     * )
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="parent_id", onDelete="CASCADE")
     * })
     */
    protected ?ResourceNode $parent = null;

    /**
     * @var Collection|ResourceNode[]
     *
     * @ORM\OneToMany(targetEntity="ResourceNode", mappedBy="parent")
     * @ORM\OrderBy({"id"="ASC"})
     */
    protected Collection $children;

    /**
     * @Gedmo\TreeLevel
     *
     * @ORM\Column(name="level", type="integer", nullable=true)
     */
    protected ?int $level = null;

    /**
     * @Groups({"resource_node:read", "document:read"})
     * @Gedmo\TreePath(appendId=true, separator="/")
     *
     * @ORM\Column(name="path", type="text", nullable=true)
     */
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
     *
     * @ORM\OneToMany(targetEntity="ResourceComment", mappedBy="resourceNode", cascade={"persist", "remove"})
     */
    protected Collection $comments;

    /**
     * @Groups({"resource_node:read", "document:read"})
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected DateTime $createdAt;

    /**
     * @Groups({"resource_node:read", "document:read"})
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    protected DateTime $updatedAt;

    /**
     * @Groups({"resource_node:read", "document:read"})
     */
    protected bool $fileEditableText;

    protected ?string $content = null;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Chamilo\CourseBundle\Entity\CShortcut",
     *     mappedBy="shortCutNode",
     *     cascade={"persist", "remove"}
     * )
     */
    protected ?CShortcut $shortCut = null;

    /**
     * @ORM\Column(type="uuid", unique=true)
     */
    protected UuidV4 $uuid;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->children = new ArrayCollection();
        $this->resourceLinks = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->fileEditableText = false;
    }

    public function __toString()
    {
        return (string) $this->getPathForDisplay();
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
    public function getChildren()
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
     *
     * @return null|ResourceNode
     */
    public function getParent()
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
    public function getComments()
    {
        return $this->comments;
    }

    public function addComment(ResourceComment $comment)
    {
        $comment->setResourceNode($this);

        return $this->comments->add($comment);
    }

    /**
     * Returns the path cleaned from its ids.
     * Eg.: "Root/subdir/file.txt".
     *
     * @return string
     */
    public function getPathForDisplay()
    {
        return $this->path;
        //return $this->convertPathForDisplay($this->path);
    }

    public function getPathForDisplayToArray($baseRoot = null)
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

    public function getSlug()
    {
        return $this->slug;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $title = str_replace('/', '-', $title);

        $this->title = $title;

        return $this;
    }

    public function setSlug(string $slug): self
    {
        if (false !== strpos(self::PATH_SEPARATOR, $slug)) {
            throw new InvalidArgumentException('Invalid character "'.self::PATH_SEPARATOR.'" in resource name.');
        }

        $this->slug = $slug;

        return $this;
    }

    /**
     * Convert a path for display: remove ids.
     *
     * @return string
     */
    public function convertPathForDisplay(string $path)
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
        //var_dump($this->getTitle(), $path);
        $pathForDisplay = preg_replace(
            '/-\d+\\'.self::PATH_SEPARATOR.'/',
            '/',
            $path
        );

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

    /**
     * @return Collection|ResourceLink[]
     */
    public function getResourceLinks()
    {
        return $this->resourceLinks;
    }

    public function addResourceLink(ResourceLink $link): self
    {
        $link->setResourceNode($this);
        $this->resourceLinks[] = $link;

        return $this;
    }

    public function setResourceLinks($resourceLinks): self
    {
        $this->resourceLinks = $resourceLinks;

        return $this;
    }

    /**
     * @return Collection|ResourceLink[]
     */
    public function hasSession(Session $session = null)
    {
        $links = $this->getResourceLinks();
        $criteria = Criteria::create();

        $criteria->andWhere(
            Criteria::expr()->eq('session', $session)
        );

        return $links->matching($criteria);
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
                $url = $router->generate(
                    'chamilo_core_resource_view',
                    $params
                );

                return sprintf("<img src='%s'/>", $url);
            }
            if ($this->isResourceFileAVideo()) {
                $class = sprintf('far fa-file-video %s', $size);
            }
        }

        return '<i class="'.$class.'"></i>';
    }

    public function getContent()
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
}
