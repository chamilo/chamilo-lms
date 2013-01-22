<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCBlogComment
 *
 * @Table(name="c_blog_comment")
 * @Entity
 */
class EntityCBlogComment
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
     * @Column(name="comment_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $commentId;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="comment", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $comment;

    /**
     * @var integer
     *
     * @Column(name="author_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $authorId;

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
     * @Column(name="post_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $postId;

    /**
     * @var integer
     *
     * @Column(name="task_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $taskId;

    /**
     * @var integer
     *
     * @Column(name="parent_comment_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $parentCommentId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCBlogComment
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
     * Set commentId
     *
     * @param integer $commentId
     * @return EntityCBlogComment
     */
    public function setCommentId($commentId)
    {
        $this->commentId = $commentId;

        return $this;
    }

    /**
     * Get commentId
     *
     * @return integer 
     */
    public function getCommentId()
    {
        return $this->commentId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityCBlogComment
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
     * Set comment
     *
     * @param string $comment
     * @return EntityCBlogComment
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
     * Set authorId
     *
     * @param integer $authorId
     * @return EntityCBlogComment
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

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     * @return EntityCBlogComment
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
     * @return EntityCBlogComment
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
     * Set postId
     *
     * @param integer $postId
     * @return EntityCBlogComment
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
     * Set taskId
     *
     * @param integer $taskId
     * @return EntityCBlogComment
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;

        return $this;
    }

    /**
     * Get taskId
     *
     * @return integer 
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * Set parentCommentId
     *
     * @param integer $parentCommentId
     * @return EntityCBlogComment
     */
    public function setParentCommentId($parentCommentId)
    {
        $this->parentCommentId = $parentCommentId;

        return $this;
    }

    /**
     * Get parentCommentId
     *
     * @return integer 
     */
    public function getParentCommentId()
    {
        return $this->parentCommentId;
    }
}
