<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCBlog
 *
 * @Table(name="c_blog")
 * @Entity
 */
class EntityCBlog
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
     * @Column(name="blog_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $blogId;

    /**
     * @var string
     *
     * @Column(name="blog_name", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $blogName;

    /**
     * @var string
     *
     * @Column(name="blog_subtitle", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $blogSubtitle;

    /**
     * @var \DateTime
     *
     * @Column(name="date_creation", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dateCreation;

    /**
     * @var boolean
     *
     * @Column(name="visibility", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCBlog
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
     * @return EntityCBlog
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
     * Set blogName
     *
     * @param string $blogName
     * @return EntityCBlog
     */
    public function setBlogName($blogName)
    {
        $this->blogName = $blogName;

        return $this;
    }

    /**
     * Get blogName
     *
     * @return string 
     */
    public function getBlogName()
    {
        return $this->blogName;
    }

    /**
     * Set blogSubtitle
     *
     * @param string $blogSubtitle
     * @return EntityCBlog
     */
    public function setBlogSubtitle($blogSubtitle)
    {
        $this->blogSubtitle = $blogSubtitle;

        return $this;
    }

    /**
     * Get blogSubtitle
     *
     * @return string 
     */
    public function getBlogSubtitle()
    {
        return $this->blogSubtitle;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     * @return EntityCBlog
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
     * Set visibility
     *
     * @param boolean $visibility
     * @return EntityCBlog
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return boolean 
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCBlog
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
