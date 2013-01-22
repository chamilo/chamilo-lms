<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCBlogTaskRelUser
 *
 * @Table(name="c_blog_task_rel_user")
 * @Entity
 */
class EntityCBlogTaskRelUser
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
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="task_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $taskId;

    /**
     * @var \DateTime
     *
     * @Column(name="target_date", type="date", precision=0, scale=0, nullable=false, unique=false)
     */
    private $targetDate;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCBlogTaskRelUser
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
     * @return EntityCBlogTaskRelUser
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
     * Set userId
     *
     * @param integer $userId
     * @return EntityCBlogTaskRelUser
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
     * Set taskId
     *
     * @param integer $taskId
     * @return EntityCBlogTaskRelUser
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
     * Set targetDate
     *
     * @param \DateTime $targetDate
     * @return EntityCBlogTaskRelUser
     */
    public function setTargetDate($targetDate)
    {
        $this->targetDate = $targetDate;

        return $this;
    }

    /**
     * Get targetDate
     *
     * @return \DateTime 
     */
    public function getTargetDate()
    {
        return $this->targetDate;
    }
}
