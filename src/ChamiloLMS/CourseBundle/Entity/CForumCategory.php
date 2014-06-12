<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumCategory
 *
 * @ORM\Table(name="c_forum_category", indexes={@ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity
 */
class CForumCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="cat_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $catId;

    /**
     * @var string
     *
     * @ORM\Column(name="cat_title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $catTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="cat_comment", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $catComment;

    /**
     * @var integer
     *
     * @ORM\Column(name="cat_order", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $catOrder;

    /**
     * @var integer
     *
     * @ORM\Column(name="locked", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $locked;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


    /**
     * Get iid
     *
     * @return integer 
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CForumCategory
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set catId
     *
     * @param integer $catId
     * @return CForumCategory
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;

        return $this;
    }

    /**
     * Get catId
     *
     * @return integer 
     */
    public function getCatId()
    {
        return $this->catId;
    }

    /**
     * Set catTitle
     *
     * @param string $catTitle
     * @return CForumCategory
     */
    public function setCatTitle($catTitle)
    {
        $this->catTitle = $catTitle;

        return $this;
    }

    /**
     * Get catTitle
     *
     * @return string 
     */
    public function getCatTitle()
    {
        return $this->catTitle;
    }

    /**
     * Set catComment
     *
     * @param string $catComment
     * @return CForumCategory
     */
    public function setCatComment($catComment)
    {
        $this->catComment = $catComment;

        return $this;
    }

    /**
     * Get catComment
     *
     * @return string 
     */
    public function getCatComment()
    {
        return $this->catComment;
    }

    /**
     * Set catOrder
     *
     * @param integer $catOrder
     * @return CForumCategory
     */
    public function setCatOrder($catOrder)
    {
        $this->catOrder = $catOrder;

        return $this;
    }

    /**
     * Get catOrder
     *
     * @return integer 
     */
    public function getCatOrder()
    {
        return $this->catOrder;
    }

    /**
     * Set locked
     *
     * @param integer $locked
     * @return CForumCategory
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return integer 
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CForumCategory
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
