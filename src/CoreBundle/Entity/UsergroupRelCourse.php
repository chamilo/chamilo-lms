<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="usergroup_rel_course")
 * @ORM\Entity
 */
class UsergroupRelCourse
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Usergroup", inversedBy="courses")
     * @ORM\JoinColumn(name="usergroup_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Usergroup $usergroup;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Course $course;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getUsergroup(): Usergroup
    {
        return $this->usergroup;
    }

    public function setUsergroup(Usergroup $usergroup): self
    {
        $this->usergroup = $usergroup;

        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }
}
