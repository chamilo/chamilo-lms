<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CBlogComment.
 *
 * @ORM\Table(
 *     name="c_blog_comment",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CBlogComment
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="comment_id", type="integer")
     */
    protected int $commentId;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="title", type="string", length=250, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="comment", type="text", nullable=false)
     */
    protected string $comment;

    /**
     * @ORM\Column(name="author_id", type="integer", nullable=false)
     */
    protected int $authorId;

    /**
     * @ORM\Column(name="date_creation", type="datetime", nullable=false)
     */
    protected DateTime $dateCreation;

    /**
     * @ORM\Column(name="blog_id", type="integer", nullable=false)
     */
    protected int $blogId;

    /**
     * @ORM\Column(name="post_id", type="integer", nullable=false)
     */
    protected int $postId;

    /**
     * @ORM\Column(name="task_id", type="integer", nullable=true)
     */
    protected ?int $taskId = null;

    /**
     * @ORM\Column(name="parent_comment_id", type="integer", nullable=false)
     */
    protected int $parentCommentId;

    /**
     * Set title.
     *
     * @return CBlogComment
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set comment.
     *
     * @return CBlogComment
     */
    public function setComment(string $comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set authorId.
     *
     * @return CBlogComment
     */
    public function setAuthorId(int $authorId)
    {
        $this->authorId = $authorId;

        return $this;
    }

    /**
     * Get authorId.
     *
     * @return int
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }

    /**
     * Set dateCreation.
     *
     * @return CBlogComment
     */
    public function setDateCreation(DateTime $dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     *
     * @return DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set blogId.
     *
     * @return CBlogComment
     */
    public function setBlogId(int $blogId)
    {
        $this->blogId = $blogId;

        return $this;
    }

    /**
     * Get blogId.
     *
     * @return int
     */
    public function getBlogId()
    {
        return $this->blogId;
    }

    /**
     * Set postId.
     *
     * @return CBlogComment
     */
    public function setPostId(int $postId)
    {
        $this->postId = $postId;

        return $this;
    }

    /**
     * Get postId.
     *
     * @return int
     */
    public function getPostId()
    {
        return $this->postId;
    }

    /**
     * Set taskId.
     *
     * @return CBlogComment
     */
    public function setTaskId(int $taskId)
    {
        $this->taskId = $taskId;

        return $this;
    }

    /**
     * Get taskId.
     *
     * @return int
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * Set parentCommentId.
     *
     * @return CBlogComment
     */
    public function setParentCommentId(int $parentCommentId)
    {
        $this->parentCommentId = $parentCommentId;

        return $this;
    }

    /**
     * Get parentCommentId.
     *
     * @return int
     */
    public function getParentCommentId()
    {
        return $this->parentCommentId;
    }

    /**
     * Set commentId.
     *
     * @return CBlogComment
     */
    public function setCommentId(int $commentId)
    {
        $this->commentId = $commentId;

        return $this;
    }

    /**
     * Get commentId.
     *
     * @return int
     */
    public function getCommentId()
    {
        return $this->commentId;
    }

    /**
     * Set cId.
     *
     * @return CBlogComment
     */
    public function setCId(int $cId)
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
