<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CForumCategory.
 *
 * @ORM\Table(
 *  name="c_forum_category",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CForumCategory extends AbstractResource implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="cat_title", type="string", length=255, nullable=false)
     */
    protected $catTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="cat_comment", type="text", nullable=true)
     */
    protected $catComment;

    /**
     * @var int
     *
     * @ORM\Column(name="cat_order", type="integer", nullable=false)
     */
    protected $catOrder;

    /**
     * @var int
     *
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected $locked;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="cat_id", type="integer")
     */
    protected $catId;

    /**
     * @var ArrayCollection|CForumForum[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CForumForum", mappedBy="forumCategory")
     */
    protected $forums;

    public function __construct()
    {
        $this->locked = 0;
        $this->catId = 0;
        $this->forums = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getCatTitle();
    }

    /**
     * Get iid.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set catTitle.
     *
     * @param string $catTitle
     *
     * @return CForumCategory
     */
    public function setCatTitle($catTitle)
    {
        $this->catTitle = $catTitle;

        return $this;
    }

    /**
     * Get catTitle.
     *
     * @return string
     */
    public function getCatTitle()
    {
        return $this->catTitle;
    }

    /**
     * Set catComment.
     *
     * @param string $catComment
     *
     * @return CForumCategory
     */
    public function setCatComment($catComment)
    {
        $this->catComment = $catComment;

        return $this;
    }

    /**
     * Get catComment.
     *
     * @return string
     */
    public function getCatComment()
    {
        return $this->catComment;
    }

    /**
     * Set catOrder.
     *
     * @param int $catOrder
     *
     * @return CForumCategory
     */
    public function setCatOrder($catOrder)
    {
        $this->catOrder = $catOrder;

        return $this;
    }

    /**
     * Get catOrder.
     *
     * @return int
     */
    public function getCatOrder()
    {
        return $this->catOrder;
    }

    /**
     * Set locked.
     *
     * @param int $locked
     *
     * @return CForumCategory
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked.
     *
     * @return int
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CForumCategory
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return (int) $this->sessionId;
    }

    /**
     * Set catId.
     *
     * @param int $catId
     *
     * @return CForumCategory
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;

        return $this;
    }

    /**
     * Get catId.
     *
     * @return int
     */
    public function getCatId()
    {
        return $this->catId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CForumCategory
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Get forums.
     *
     * @return ArrayCollection|CForumForum[]
     */
    public function getForums()
    {
        return $this->forums;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getCatTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setCatTitle($name);
    }
}
