<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCBlogRating
 *
 * @Table(name="c_blog_rating")
 * @Entity
 */
class EntityCBlogRating
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="rating_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $ratingId;

    /**
     * @var integer
     *
     * @Column(name="blog_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $blogId;

    /**
     * @var string
     *
     * @Column(name="rating_type", type="string", precision=0, scale=0, nullable=false, unique=false)
     */
    private $ratingType;

    /**
     * @var integer
     *
     * @Column(name="item_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $itemId;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="rating", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $rating;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCBlogRating
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
     * Set ratingId
     *
     * @param integer $ratingId
     * @return EntityCBlogRating
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
     * Set blogId
     *
     * @param integer $blogId
     * @return EntityCBlogRating
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
     * @return EntityCBlogRating
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
     * @return EntityCBlogRating
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
     * @return EntityCBlogRating
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
     * @return EntityCBlogRating
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
