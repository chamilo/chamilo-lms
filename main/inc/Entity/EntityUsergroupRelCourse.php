<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityUsergroupRelCourse
 *
 * @Table(name="usergroup_rel_course")
 * @Entity
 */
class EntityUsergroupRelCourse
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="usergroup_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $usergroupId;

    /**
     * @var integer
     *
     * @Column(name="course_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseId;


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
     * Set usergroupId
     *
     * @param integer $usergroupId
     * @return EntityUsergroupRelCourse
     */
    public function setUsergroupId($usergroupId)
    {
        $this->usergroupId = $usergroupId;

        return $this;
    }

    /**
     * Get usergroupId
     *
     * @return integer 
     */
    public function getUsergroupId()
    {
        return $this->usergroupId;
    }

    /**
     * Set courseId
     *
     * @param integer $courseId
     * @return EntityUsergroupRelCourse
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * Get courseId
     *
     * @return integer 
     */
    public function getCourseId()
    {
        return $this->courseId;
    }
}
