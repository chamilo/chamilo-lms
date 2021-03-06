<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseRelClass.
 *
 * @ORM\Table(name="course_rel_class")
 * @ORM\Entity
 */
class CourseRelClass
{
    /**
     * @ORM\Column(name="course_code", type="string", length=40)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected string $courseCode;

    /**
     * @ORM\Column(name="class_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected int $classId;

    /**
     * Set courseCode.
     *
     * @return CourseRelClass
     */
    public function setCourseCode(string $courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode.
     *
     * @return string
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * Set classId.
     *
     * @return CourseRelClass
     */
    public function setClassId(int $classId)
    {
        $this->classId = $classId;

        return $this;
    }

    /**
     * Get classId.
     *
     * @return int
     */
    public function getClassId()
    {
        return $this->classId;
    }
}
