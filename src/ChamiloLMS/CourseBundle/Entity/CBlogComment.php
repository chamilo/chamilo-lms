<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CBlogComment
 *
 * @ORM\Table(name="c_blog_comment")
 * @ORM\Entity
 */
class CBlogComment
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="comment_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $commentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $comment;

    /**
     * @var integer
     *
     * @ORM\Column(name="author_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $authorId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dateCreation;

    /**
     * @var integer
     *
     * @ORM\Column(name="blog_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $blogId;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $postId;

    /**
     * @var integer
     *
     * @ORM\Column(name="task_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $taskId;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_comment_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $parentCommentId;


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
     * Set commentId
     *
     * @param integer $commentId
     * @return CBlogComment
     */
    public function setCommentId($commentId)
    {
        $this->commentId = $commentId;

        return $this;
    }

    /**
     * Get commentId
     *
     * @return integer
     */
    public function getCommentId()
    {
        return $this->commentId;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CBlogComment
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
     * Set title
     *
     * @param string $title
     * @return CBlogComment
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return CBlogComment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set authorId
     *
     * @param integer $authorId
     * @return CBlogComment
     */
    public function setAuthorId($authorId)
    {
        $this->authorId = $authorId;

        return $this;
    }

    /**
     * Get authorId
     *
     * @return integer
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     * @return CBlogComment
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set blogId
     *
     * @param integer $blogId
     * @return CBlogComment
     */
    public function setBlogId($blogId)
    {
        $this->blogId = $blogId;

        return $this;
    }

    /**
     * Get blogId
     *
     * @return integer
     */
    public function getBlogId()
    {
        return $this->blogId;
    }

    /**
     * Set postId
     *
     * @param integer $postId
     * @return CBlogComment
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
     * Set taskId
     *
     * @param integer $taskId
     * @return CBlogComment
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;

        return $this;
    }

    /**
     * Get taskId
     *
     * @return integer
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * Set parentCommentId
     *
     * @param integer $parentCommentId
     * @return CBlogComment
     */
    public function setParentCommentId($parentCommentId)
    {
        $this->parentCommentId = $parentCommentId;

        return $this;
    }

    /**
     * Get parentCommentId
     *
     * @return integer
     */
    public function getParentCommentId()
    {
        return $this->parentCommentId;
    }
}
