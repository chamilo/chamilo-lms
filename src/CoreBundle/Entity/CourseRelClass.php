<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Course subscriptions to a class.
 */
#[ORM\Table(name: 'course_rel_class')]
#[ORM\Entity]
class CourseRelClass
{
    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\Id]
    protected Course $course;

    #[ORM\Column(name: 'class_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    protected int $classId;

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getClassId(): int
    {
        return $this->classId;
    }

    public function setClassId(int $classId): self
    {
        $this->classId = $classId;

        return $this;
    }
}
