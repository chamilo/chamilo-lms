<?php

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CBlogPost.
 *
 * @ORM\Table(
 *     name="c_blog_post",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CBlogPost
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="blog_id", type="integer", nullable=false)
     */
    protected int $blogId;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="title", type="string", length=250, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="full_text", type="text", nullable=false)
     */
    protected string $fullText;

    /**
     * @ORM\Column(name="date_creation", type="datetime", nullable=false)
     */
    protected DateTime $dateCreation;

    /**
     * @ORM\Column(name="author_id", type="integer", nullable=false)
     */
    protected int $authorId;

    /**
     * @ORM\Column(name="post_id", type="integer")
     */
    protected int $postId;

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CBlogPost
     */
    public function setTitle($title)
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
     * Set fullText.
     *
     * @param string $fullText
     *
     * @return CBlogPost
     */
    public function setFullText($fullText)
    {
        $this->fullText = $fullText;

        return $this;
    }

    /**
     * Get fullText.
     *
     * @return string
     */
    public function getFullText()
    {
        return $this->fullText;
    }

    /**
     * Set dateCreation.
     *
     * @param DateTime $dateCreation
     *
     * @return CBlogPost
     */
    public function setDateCreation($dateCreation)
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
     * @param int $blogId
     *
     * @return CBlogPost
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
     * Set authorId.
     *
     * @param int $authorId
     *
     * @return CBlogPost
     */
    public function setAuthorId($authorId)
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
     * Set postId.
     *
     * @param int $postId
     *
     * @return CBlogPost
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
     * Set cId.
     *
     * @param int $cId
     *
     * @return CBlogPost
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
