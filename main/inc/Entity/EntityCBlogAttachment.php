<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCBlogAttachment
 *
 * @Table(name="c_blog_attachment")
 * @Entity
 */
class EntityCBlogAttachment
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
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="path", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $path;

    /**
     * @var string
     *
     * @Column(name="comment", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $comment;

    /**
     * @var integer
     *
     * @Column(name="size", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $size;

    /**
     * @var integer
     *
     * @Column(name="post_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $postId;

    /**
     * @var string
     *
     * @Column(name="filename", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $filename;

    /**
     * @var integer
     *
     * @Column(name="blog_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $blogId;

    /**
     * @var integer
     *
     * @Column(name="comment_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $commentId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCBlogAttachment
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
     * Set id
     *
     * @param integer $id
     * @return EntityCBlogAttachment
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return EntityCBlogAttachment
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
     * @return EntityCBlogAttachment
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
     * @return EntityCBlogAttachment
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
     * @return EntityCBlogAttachment
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
     * @return EntityCBlogAttachment
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

    /**
     * Set blogId
     *
     * @param integer $blogId
     * @return EntityCBlogAttachment
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
     * Set commentId
     *
     * @param integer $commentId
     * @return EntityCBlogAttachment
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
}
