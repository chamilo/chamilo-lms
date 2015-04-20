<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseRelClass
 *
 * @ORM\Table(name="course_rel_class")
 * @ORM\Entity
 */
class CourseRelClass
{
    /**
     * @var string
     *
     * @ORM\Column(name="course_code", type="string", length=40)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $courseCode;

    /**
     * @var integer
     *
     * @ORM\Column(name="class_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $classId;



    /**
     * Set courseCode
     *
     * @param string $courseCode
     * @return CourseRelClass
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
     * @return CourseRelClass
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
