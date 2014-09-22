<?php

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CBlogRating
 *
 * @ORM\Table(name="c_blog_rating")
 * @ORM\Entity
 */
class CBlogRating
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
     * @ORM\Column(name="rating_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $ratingId;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="blog_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $blogId;

    /**
     * @var string
     *
     * @ORM\Column(name="rating_type", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $ratingType;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $itemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="rating", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $rating;


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
     * Set ratingId
     *
     * @param integer $ratingId
     * @return CBlogRating
     */
    public function setRatingId($ratingId)
    {
        $this->ratingId = $ratingId;

        return $this;
    }

    /**
     * Get ratingId
     *
     * @return integer
     */
    public function getRatingId()
    {
        return $this->ratingId;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CBlogRating
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
     * Set blogId
     *
     * @param integer $blogId
     * @return CBlogRating
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
     * Set ratingType
     *
     * @param string $ratingType
     * @return CBlogRating
     */
    public function setRatingType($ratingType)
    {
        $this->ratingType = $ratingType;

        return $this;
    }

    /**
     * Get ratingType
     *
     * @return string
     */
    public function getRatingType()
    {
        return $this->ratingType;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     * @return CBlogRating
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return CBlogRating
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set rating
     *
     * @param integer $rating
     * @return CBlogRating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return integer
     */
    public function getRating()
    {
        return $this->rating;
    }
}
