<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCourseRelClass
 *
 * @Table(name="course_rel_class")
 * @Entity
 */
class EntityCourseRelClass
{
    /**
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $courseCode;

    /**
     * @var integer
     *
     * @Column(name="class_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $classId;


    /**
     * Set courseCode
     *
     * @param string $courseCode
     * @return EntityCourseRelClass
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

    /**
     * Set classId
     *
     * @param integer $classId
     * @return EntityCourseRelClass
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;

        return $this;
    }

    /**
     * Get classId
     *
     * @return integer 
     */
    public function getClassId()
    {
        return $this->classId;
    }
}
