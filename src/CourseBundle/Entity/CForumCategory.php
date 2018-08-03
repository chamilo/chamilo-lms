<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
class CForumCategory
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
        return $this->sessionId;
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
}
