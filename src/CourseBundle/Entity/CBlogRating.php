<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CBlogRating.
 *
 * @ORM\Table(
 *     name="c_blog_rating",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CBlogRating
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="rating_id", type="integer")
     */
    protected int $ratingId;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="blog_id", type="integer", nullable=false)
     */
    protected int $blogId;

    /**
     * @ORM\Column(name="rating_type", type="string", length=40, nullable=false)
     */
    protected string $ratingType;

    /**
     * @ORM\Column(name="item_id", type="integer", nullable=false)
     */
    protected int $itemId;

    /**
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected int $userId;

    /**
     * @ORM\Column(name="rating", type="integer", nullable=false)
     */
    protected int $rating;

    /**
     * Set blogId.
     *
     * @return CBlogRating
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
     * Set ratingType.
     *
     * @return CBlogRating
     */
    public function setRatingType(string $ratingType)
    {
        $this->ratingType = $ratingType;

        return $this;
    }

    /**
     * Get ratingType.
     *
     * @return string
     */
    public function getRatingType()
    {
        return $this->ratingType;
    }

    /**
     * Set itemId.
     *
     * @return CBlogRating
     */
    public function setItemId(int $itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set userId.
     *
     * @return CBlogRating
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set rating.
     *
     * @return CBlogRating
     */
    public function setRating(int $rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating.
     *
     * @return int
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set ratingId.
     *
     * @return CBlogRating
     */
    public function setRatingId(int $ratingId)
    {
        $this->ratingId = $ratingId;

        return $this;
    }

    /**
     * Get ratingId.
     *
     * @return int
     */
    public function getRatingId()
    {
        return $this->ratingId;
    }

    /**
     * Set cId.
     *
     * @return CBlogRating
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
