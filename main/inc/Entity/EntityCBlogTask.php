<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCBlogTask
 *
 * @Table(name="c_blog_task")
 * @Entity
 */
class EntityCBlogTask
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
     * @Column(name="task_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $taskId;

    /**
     * @var integer
     *
     * @Column(name="blog_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $blogId;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @Column(name="color", type="string", length=10, precision=0, scale=0, nullable=false, unique=false)
     */
    private $color;

    /**
     * @var boolean
     *
     * @Column(name="system_task", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $systemTask;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCBlogTask
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
     * Set taskId
     *
     * @param integer $taskId
     * @return EntityCBlogTask
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
     * Set blogId
     *
     * @param integer $blogId
     * @return EntityCBlogTask
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
     * Set title
     *
     * @param string $title
     * @return EntityCBlogTask
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
     * Set description
     *
     * @param string $description
     * @return EntityCBlogTask
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set color
     *
     * @param string $color
     * @return EntityCBlogTask
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string 
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set systemTask
     *
     * @param boolean $systemTask
     * @return EntityCBlogTask
     */
    public function setSystemTask($systemTask)
    {
        $this->systemTask = $systemTask;

        return $this;
    }

    /**
     * Get systemTask
     *
     * @return boolean 
     */
    public function getSystemTask()
    {
        return $this->systemTask;
    }
}
