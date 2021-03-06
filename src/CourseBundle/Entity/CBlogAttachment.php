<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CBlogAttachment.
 *
 * @ORM\Table(
 *     name="c_blog_attachment",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CBlogAttachment
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="path", type="string", length=255, nullable=false)
     */
    protected string $path;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected ?string $comment;

    /**
     * @ORM\Column(name="size", type="integer", nullable=false)
     */
    protected int $size;

    /**
     * @ORM\Column(name="post_id", type="integer", nullable=false)
     */
    protected int $postId;

    /**
     * @ORM\Column(name="filename", type="string", length=255, nullable=false)
     */
    protected string $filename;

    /**
     * @ORM\Column(name="blog_id", type="integer", nullable=false)
     */
    protected int $blogId;

    /**
     * @ORM\Column(name="comment_id", type="integer", nullable=false)
     */
    protected int $commentId;

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return CBlogAttachment
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     *
     * @return CBlogAttachment
     */
    public function setComment($comment)
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
     * Set size.
     *
     * @param int $size
     *
     * @return CBlogAttachment
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set postId.
     *
     * @param int $postId
     *
     * @return CBlogAttachment
     */
    public function setPostId($postId)
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
     * Set filename.
     *
     * @param string $filename
     *
     * @return CBlogAttachment
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set blogId.
     *
     * @param int $blogId
     *
     * @return CBlogAttachment
     */
    public function setBlogId($blogId)
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
     * Set commentId.
     *
     * @param int $commentId
     *
     * @return CBlogAttachment
     */
    public function setCommentId($commentId)
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
     * @param int $cId
     *
     * @return CBlogAttachment
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
