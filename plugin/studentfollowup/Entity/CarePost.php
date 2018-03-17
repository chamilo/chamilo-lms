<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\StudentFollowUp;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CarePost.
 *
 * @ORM\Table(name="sfu_post")
 * @ORM\Entity
 * @Gedmo\Tree(type="nested")
 */
class CarePost
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @var string
     *
     * @ORM\Column(name="external_care_id", type="string", nullable=true)
     */
    protected $externalCareId;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="private", type="boolean")
     */
    protected $private;

    /**
     * @var bool
     *
     * @ORM\Column(name="external_source", type="boolean")
     */
    protected $externalSource;

    /**
     * @var array
     *
     * @ORM\Column(name="tags", type="array")
     */
    protected $tags;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment", type="string", length=255)
     */
    protected $attachment;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="insert_user_id", referencedColumnName="id", nullable=false)
     */
    private $insertUser;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="CarePost", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="CarePost", mappedBy="parent")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    private $children;

    /**
     * @var int
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", nullable=true, unique=false)
     */
    private $lft;

    /**
     * @var int
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", nullable=true, unique=false)
     */
    private $rgt;

    /**
     * @var int
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true, unique=false)
     */
    private $lvl;

    /**
     * @var int
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true, unique=false)
     */
    private $root;

    /**
     * Project constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->attachment = '';
    }

    /**
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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return CarePost
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return CarePost
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getExternalCareId()
    {
        return $this->externalCareId;
    }

    /**
     * @param string $externalCareId
     *
     * @return CarePost
     */
    public function setExternalCareId($externalCareId)
    {
        $this->externalCareId = $externalCareId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     *
     * @return CarePost
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * @param bool $private
     *
     * @return CarePost
     */
    public function setPrivate($private)
    {
        $this->private = $private;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExternalSource()
    {
        return $this->externalSource;
    }

    /**
     * @param bool $externalSource
     *
     * @return CarePost
     */
    public function setExternalSource($externalSource)
    {
        $this->externalSource = $externalSource;

        return $this;
    }

    /**
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param string $attachment
     *
     * @return CarePost
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     *
     * @return CarePost
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return int
     */
    public function hasParent()
    {
        return !empty($this->parent) ? 1 : 0;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param mixed $children
     *
     * @return CarePost
     */
    public function setChildren($children)
    {
        $this->children = $children;

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
     * @param mixed $createdAt
     *
     * @return CarePost
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
     * @param mixed $updatedAt
     *
     * @return CarePost
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     *
     * @return CarePost
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getInsertUser()
    {
        return $this->insertUser;
    }

    /**
     * @param mixed $insertUser
     *
     * @return CarePost
     */
    public function setInsertUser($insertUser)
    {
        $this->insertUser = $insertUser;

        return $this;
    }
}
