<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityAccessUrlRelCourse
 *
 * @Table(name="access_url_rel_course")
 * @Entity
 */
class EntityAccessUrlRelCourse
{
    /**
     * @var integer
     *
     * @Column(name="access_url_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $accessUrlId;

    /**
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $courseCode;


    /**
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return EntityAccessUrlRelCourse
     */
    public function setAccessUrlId($accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId
     *
     * @return integer 
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }

    /**
     * Set courseCode
     *
     * @param string $courseCode
     * @return EntityAccessUrlRelCourse
     */
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode
     *
     * @return string 
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }
}
