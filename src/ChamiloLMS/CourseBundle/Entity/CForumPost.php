<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumPost
 *
 * @ORM\Table(name="c_forum_post", indexes={@ORM\Index(name="poster_id", columns={"poster_id"}), @ORM\Index(name="forum_id", columns={"forum_id"}), @ORM\Index(name="idx_forum_post_thread_id", columns={"thread_id"}), @ORM\Index(name="idx_forum_post_visible", columns={"visible"})})
 * @ORM\Entity
 */
class CForumPost
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
     * @ORM\Column(name="post_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $postId;

    /**
     * @var string
     *
     * @ORM\Column(name="post_title", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $postTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="post_text", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $postText;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $threadId;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $forumId;

    /**
     * @var integer
     *
     * @ORM\Column(name="poster_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $posterId;

    /**
     * @var string
     *
     * @ORM\Column(name="poster_name", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $posterName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="post_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $postDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="post_notification", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $postNotification;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_parent_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $postParentId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $visible;


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
     * @return CForumPost
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
     * Set postId
     *
     * @param integer $postId
     * @return CForumPost
     */
    public function setPostId($postId)
    {
        $this->postId = $postId;

        return $this;
    }

    /**
     * Get postId
     *
     * @return integer 
     */
    public function getPostId()
    {
        return $this->postId;
    }

    /**
     * Set postTitle
     *
     * @param string $postTitle
     * @return CForumPost
     */
    public function setPostTitle($postTitle)
    {
        $this->postTitle = $postTitle;

        return $this;
    }

    /**
     * Get postTitle
     *
     * @return string 
     */
    public function getPostTitle()
    {
        return $this->postTitle;
    }

    /**
     * Set postText
     *
     * @param string $postText
     * @return CForumPost
     */
    public function setPostText($postText)
    {
        $this->postText = $postText;

        return $this;
    }

    /**
     * Get postText
     *
     * @return string 
     */
    public function getPostText()
    {
        return $this->postText;
    }

    /**
     * Set threadId
     *
     * @param integer $threadId
     * @return CForumPost
     */
    public function setThreadId($threadId)
    {
        $this->threadId = $threadId;

        return $this;
    }

    /**
     * Get threadId
     *
     * @return integer 
     */
    public function getThreadId()
    {
        return $this->threadId;
    }

    /**
     * Set forumId
     *
     * @param integer $forumId
     * @return CForumPost
     */
    public function setForumId($forumId)
    {
        $this->forumId = $forumId;

        return $this;
    }

    /**
     * Get forumId
     *
     * @return integer 
     */
    public function getForumId()
    {
        return $this->forumId;
    }

    /**
     * Set posterId
     *
     * @param integer $posterId
     * @return CForumPost
     */
    public function setPosterId($posterId)
    {
        $this->posterId = $posterId;

        return $this;
    }

    /**
     * Get posterId
     *
     * @return integer 
     */
    public function getPosterId()
    {
        return $this->posterId;
    }

    /**
     * Set posterName
     *
     * @param string $posterName
     * @return CForumPost
     */
    public function setPosterName($posterName)
    {
        $this->posterName = $posterName;

        return $this;
    }

    /**
     * Get posterName
     *
     * @return string 
     */
    public function getPosterName()
    {
        return $this->posterName;
    }

    /**
     * Set postDate
     *
     * @param \DateTime $postDate
     * @return CForumPost
     */
    public function setPostDate($postDate)
    {
        $this->postDate = $postDate;

        return $this;
    }

    /**
     * Get postDate
     *
     * @return \DateTime 
     */
    public function getPostDate()
    {
        return $this->postDate;
    }

    /**
     * Set postNotification
     *
     * @param boolean $postNotification
     * @return CForumPost
     */
    public function setPostNotification($postNotification)
    {
        $this->postNotification = $postNotification;

        return $this;
    }

    /**
     * Get postNotification
     *
     * @return boolean 
     */
    public function getPostNotification()
    {
        return $this->postNotification;
    }

    /**
     * Set postParentId
     *
     * @param integer $postParentId
     * @return CForumPost
     */
    public function setPostParentId($postParentId)
    {
        $this->postParentId = $postParentId;

        return $this;
    }

    /**
     * Get postParentId
     *
     * @return integer 
     */
    public function getPostParentId()
    {
        return $this->postParentId;
    }

    /**
     * Set visible
     *
     * @param boolean $visible
     * @return CForumPost
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return boolean 
     */
    public function getVisible()
    {
        return $this->visible;
    }
}

