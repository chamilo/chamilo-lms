<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCBlogPost
 *
 * @Table(name="c_blog_post")
 * @Entity
 */
class EntityCBlogPost
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
     * @Column(name="post_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $postId;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="full_text", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $fullText;

    /**
     * @var \DateTime
     *
     * @Column(name="date_creation", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dateCreation;

    /**
     * @var integer
     *
     * @Column(name="blog_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $blogId;

    /**
     * @var integer
     *
     * @Column(name="author_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $authorId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCBlogPost
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
     * @return EntityCBlogPost
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
     * Set title
     *
     * @param string $title
     * @return EntityCBlogPost
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
     * Set fullText
     *
     * @param string $fullText
     * @return EntityCBlogPost
     */
    public function setFullText($fullText)
    {
        $this->fullText = $fullText;

        return $this;
    }

    /**
     * Get fullText
     *
     * @return string 
     */
    public function getFullText()
    {
        return $this->fullText;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     * @return EntityCBlogPost
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
     * @return EntityCBlogPost
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
     * Set authorId
     *
     * @param integer $authorId
     * @return EntityCBlogPost
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
}
