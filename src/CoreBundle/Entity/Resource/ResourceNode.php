<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Base entity for all resources.
 *
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\MaterializedPathRepository")
 *
 * @ORM\Table(name="resource_node")
 *
 * @Gedmo\Tree(type="materializedPath")
 */
class ResourceNode
{
    public const PATH_SEPARATOR = '`';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Gedmo\TreePathSource
     *
     * @ORM\Column()
     *
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable = true)
     */
    protected $description;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceType", inversedBy="resourceNodes")
     * @ORM\JoinColumn(name="resource_type_id", referencedColumnName="id", nullable=false)
     */
    protected $resourceType;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceLink", mappedBy="resourceNode", cascade={"remove"})
     */
    protected $resourceLinks;

    /**
     * @var ResourceFile
     *
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceFile", inversedBy="resourceNode", cascade={"remove"})
     * @ORM\JoinColumn(name="resource_file_id", referencedColumnName="id")
     */
    protected $resourceFile;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\UserBundle\Entity\User",
     *     inversedBy="resourceNodes",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    protected $creator;

    /**
     * @Gedmo\TreeParent
     *
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode",
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
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode",
     *     mappedBy="parent"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $children;

    /**
     * @Gedmo\TreePath(separator="`")
     *
     * @ORM\Column(name="path", type="string", length=3000, nullable=true)
     */
    protected $path;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     *
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     *
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    //protected $pathForCreationLog = '';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
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
     * @param \DateTime|null $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime|null $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Returns the resource creator.
     *
     * @return User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Sets the resource creator.
     *
     * @param User $creator
     *
     * @return $this
     */
    public function setCreator(User $creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Returns the children resource instances.
     *
     * @return ArrayCollection
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
    public function setParent(ResourceNode $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns the parent resource.
     *
     * @return AbstractResource
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
     * Returns the path cleaned from its ids.
     * Eg.: "Root/subdir/file.txt".
     *
     * @return
     */
    public function getPathForDisplay()
    {
        return self::convertPathForDisplay($this->path);
    }

    /**
     * Sets the resource name.
     *
     * @param string $name
     *
     * @throws an exception if the name contains the path separator ('/')
     *
     * @return $this
     */
    public function setName($name)
    {
        if (strpos(self::PATH_SEPARATOR, $name) !== false) {
            throw new \InvalidArgumentException(
                'Invalid character "'.self::PATH_SEPARATOR.'" in resource name.'
            );
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Returns the resource name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
        $pathForDisplay = preg_replace(
            '/-\d+'.self::PATH_SEPARATOR.'/',
            ' / ',
            $path
        );

        if ($pathForDisplay !== null && strlen($pathForDisplay) > 0) {
            $pathForDisplay = substr_replace($pathForDisplay, "", -3);
        }

        return $pathForDisplay;
    }

    /**
     * This is required for logging the resource path at the creation.
     * Do not use this function otherwise.
     *
     * @return type
     */
    public function setPathForCreationLog($path)
    {
        $this->pathForCreationLog = $path;
    }

    /**
     * This is required for logging the resource path at the creation.
     * Do not use this function otherwise.
     *
     * @return type
     */
    public function getPathForCreationLog()
    {
        return $this->pathForCreationLog;
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
     * @return ArrayCollection
     */
    public function getResourceLinks()
    {
        return $this->resourceLinks;
    }

    /**
     * @param mixed $resourceLinks
     *
     * @return ResourceNode
     */
    public function setResourceLinks($resourceLinks)
    {
        $this->resourceLinks = $resourceLinks;

        return $this;
    }

    /**
     * @return ResourceFile
     */
    public function getResourceFile()
    {
        return $this->resourceFile;
    }

    /**
     * @param ResourceFile $resourceFile
     *
     * @return ResourceNode
     */
    public function setResourceFile(ResourceFile $resourceFile): ResourceNode
    {
        $this->resourceFile = $resourceFile;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return (string) $this->description;
    }

    /**
     * @param string $description
     *
     * @return ResourceNode
     */
    public function setDescription(string $description): ResourceNode
    {
        $this->description = $description;

        return $this;
    }
}
