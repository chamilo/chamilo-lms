<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\TimestampableAgoTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Annotation\Groups;
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
 * @ApiFilter(SearchFilter::class, properties={"title": "partial"})
 * @ApiFilter(PropertyFilter::class)
 * @ApiFilter(OrderFilter::class, properties={"id", "title", "resourceFile", "createdAt", "updatedAt"})
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\ResourceNodeRepository")
 *
 * @ORM\Table(name="resource_node")
 *
 * @Gedmo\Tree(type="materializedPath")
 */
class ResourceNode
{
    public const PATH_SEPARATOR = '`';
    use TimestampableEntity;
    use TimestampableAgoTrait;

    /**
     * @Groups({"resource_node:read", "document:read"})
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Assert\NotBlank()
     * @Groups({"resource_node:read", "resource_node:write", "document:read"})
     * @Gedmo\TreePathSource
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @Assert\NotBlank()
     *
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(name="slug", type="string", length=255, nullable=false)
     */
    protected $slug;

    /**
     * @Groups({"resource_node:read", "resource_node:write"})
     *
     * @ORM\ManyToOne(targetEntity="ResourceType")
     * @ORM\JoinColumn(name="resource_type_id", referencedColumnName="id", nullable=false)
     */
    protected $resourceType;

    /**
     * @Groups({"resource_node:read", "resource_node:write"})
     *
     * @var ResourceLink[]
     *
     * @ORM\OneToMany(targetEntity="ResourceLink", mappedBy="resourceNode", cascade={"remove"})
     */
    protected $resourceLinks;

    /**
     * @var ResourceFile available file for this node
     *
     * @Groups({"resource_node:read", "resource_node:write", "document:read"})
     *
     * @ORM\OneToOne(targetEntity="ResourceFile", inversedBy="resourceNode", orphanRemoval=true)
     * @ORM\JoinColumn(name="resource_file_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $resourceFile;

    /**
     * @var User the creator of this node
     * @Assert\Valid()
     * @Groups({"resource_node:read", "resource_node:write"})
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="resourceNodes"
     * )
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $creator;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(
     *     targetEntity="ResourceNode",
     *     inversedBy="children"
     * )
     * @ORM\JoinColumns({@ORM\JoinColumn(onDelete="CASCADE")})
     */
    protected $parent;

    /**
     * @Gedmo\TreeLevel
     *
     * @ORM\Column(name="level", type="integer", nullable=true)
     */
    protected $level;

    /**
     * @var ResourceNode[]
     *
     * @ORM\OneToMany(
     *     targetEntity="ResourceNode",
     *     mappedBy="parent"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $children;

    /**
     * @Gedmo\TreePath(appendId=true,separator="`")
     *
     * @ORM\Column(name="path", type="text", nullable=true)
     */
    protected $path;

    /**
     * Shortcut to access Course resource from ResourceNode.
     *
     * ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\Illustration", mappedBy="resourceNode")
     */
    //protected $illustration;

    /**
     * @var ResourceComment[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ResourceComment", mappedBy="resourceNode", cascade={"persist", "remove"})
     */
    protected $comments;

    /**
     * @var \DateTime
     *
     * @Groups({"resource_node:read", "document:read"})
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @Groups({"resource_node:read", "document:read"})
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    protected $updatedAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->resourceLinks = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    /**
     * @return string
     */
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

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Returns the resource creator.
     *
     * @return User
     */
    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Returns the children resource instances.
     *
     * @return ResourceNode[]|ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Sets the parent resource.
     *
     * @param ResourceNode $parent
     *
     * @return $this
     */
    public function setParent(self $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns the parent resource.
     *
     * @return ResourceNode
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Return the lvl value of the resource in the tree.
     *
     * @return int
     */
    public function getLevel()
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
     * @return ResourceComment[]|ArrayCollection
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
        return self::convertPathForDisplay($this->path);
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

            if (!empty($baseRoot)) {
                if ($id < $baseRoot) {
                    continue;
                }
            }
            $list[$id] = $value;
        }

        return $list;
    }

    /**
     * @return string
     */
    public function getPathForDisplayRemoveBase(string $base)
    {
        $path = str_replace($base, '', $this->path);

        return self::convertPathForDisplay($path);
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
        $this->title = $title;

        return $this;
    }

    /**
     * @return ResourceNode
     */
    public function setSlug(string $slug)
    {
        if (false !== strpos(self::PATH_SEPARATOR, $slug)) {
            throw new \InvalidArgumentException('Invalid character "'.self::PATH_SEPARATOR.'" in resource name.');
        }

        $this->slug = $slug;

        return $this;
    }

    /**
     * Convert a path for display: remove ids.
     *
     * @param string $path
     *
     * @return string
     */
    public static function convertPathForDisplay($path)
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
        $pathForDisplay = preg_replace(
            '/-\d+'.self::PATH_SEPARATOR.'/',
            '/',
            $path
        );

        if (null !== $pathForDisplay && strlen($pathForDisplay) > 0) {
            $pathForDisplay = substr_replace($pathForDisplay, '', -1);
        }

        return $pathForDisplay;
    }

    /**
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * @param ResourceType $resourceType
     *
     * @return ResourceNode
     */
    public function setResourceType($resourceType)
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    /**
     * @return ArrayCollection|ResourceLink[]
     */
    public function getResourceLinks()
    {
        return $this->resourceLinks;
    }

    /**
     * @return ResourceNode
     */
    public function setResourceLinks($resourceLinks)
    {
        $this->resourceLinks = $resourceLinks;

        return $this;
    }

    /**
     * @param Session $session
     *
     * @return ArrayCollection
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

    /**
     * @return bool
     */
    public function hasResourceFile()
    {
        return null !== $this->resourceFile;
    }

    /**
     * @return ResourceFile
     */
    public function getResourceFile(): ?ResourceFile
    {
        return $this->resourceFile;
    }

    /**
     * @return bool
     */
    public function hasEditableContent()
    {
        if ($this->hasResourceFile()) {
            $mimeType = $this->getResourceFile()->getMimeType();
            if (false !== strpos($mimeType, 'text')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isResourceFileAnImage()
    {
        if ($this->hasResourceFile()) {
            $mimeType = $this->getResourceFile()->getMimeType();
            if (false !== strpos($mimeType, 'image')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isResourceFileAVideo()
    {
        if ($this->hasResourceFile()) {
            $mimeType = $this->getResourceFile()->getMimeType();
            if (false !== strpos($mimeType, 'video')) {
                return true;
            }
        }

        return false;
    }

    public function setResourceFile(ResourceFile $resourceFile = null): self
    {
        $this->resourceFile = $resourceFile;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
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

    /**
     * @return string
     */
    public function getThumbnail(RouterInterface $router)
    {
        $size = 'fa-3x';
        $class = "fa fa-folder $size";
        if ($this->hasResourceFile()) {
            $class = "far fa-file $size";

            if ($this->isResourceFileAnImage()) {
                $class = "far fa-file-image $size";

                $params = [
                    'id' => $this->getId(),
                    'tool' => $this->getResourceType()->getTool(),
                    'type' => $this->getResourceType()->getName(),
                    'filter' => 'editor_thumbnail',
                ];
                $url = $router->generate(
                    'chamilo_core_resource_view_file',
                    $params
                );

                return "<img src='$url'/>";
            }
            if ($this->isResourceFileAVideo()) {
                $class = "far fa-file-video $size";
            }
        }

        return '<i class="'.$class.'"></i>';
    }
}
