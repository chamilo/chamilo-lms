<?php

namespace ChamiloLMS\CoreBundle\Entity;

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


}
