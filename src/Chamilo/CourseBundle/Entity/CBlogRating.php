<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CBlogRating.
 *
 * @ORM\Table(
 *  name="c_blog_rating",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CBlogRating
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
     * @ORM\Column(name="rating_id", type="integer")
     */
    protected $ratingId;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="blog_id", type="integer", nullable=false)
     */
    protected $blogId;

    /**
     * @var string
     *
     * @ORM\Column(name="rating_type", type="string", length=40, nullable=false)
     */
    protected $ratingType;

    /**
     * @var int
     *
     * @ORM\Column(name="item_id", type="integer", nullable=false)
     */
    protected $itemId;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="rating", type="integer", nullable=false)
     */
    protected $rating;

    /**
     * Set blogId.
     *
     * @param int $blogId
     *
     * @return CBlogRating
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
     * Set ratingType.
     *
     * @param string $ratingType
     *
     * @return CBlogRating
     */
    public function setRatingType($ratingType)
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
     * @param int $itemId
     *
     * @return CBlogRating
     */
    public function setItemId($itemId)
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
     * @param int $userId
     *
     * @return CBlogRating
     */
    public function setUserId($userId)
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
     * @param int $rating
     *
     * @return CBlogRating
     */
    public function setRating($rating)
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
     * @param int $ratingId
     *
     * @return CBlogRating
     */
    public function setRatingId($ratingId)
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
     * @param int $cId
     *
     * @return CBlogRating
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
