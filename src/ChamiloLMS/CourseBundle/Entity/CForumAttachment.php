<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumAttachment
 *
 * @ORM\Table(name="c_forum_attachment")
 * @ORM\Entity
 */
class CForumAttachment
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
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $comment;

    /**
     * @var integer
     *
     * @ORM\Column(name="size", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $size;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $postId;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $filename;


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
     * @return CForumAttachment
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
     * Set path
     *
     * @param string $path
     * @return CForumAttachment
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return CForumAttachment
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
     * Set size
     *
     * @param integer $size
     * @return CForumAttachment
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set postId
     *
     * @param integer $postId
     * @return CForumAttachment
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
     * Set filename
     *
     * @param string $filename
     * @return CForumAttachment
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }
}
