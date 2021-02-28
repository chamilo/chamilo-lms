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
    protected int $id;

    /**
     * @ORM\Column(name="usergroup_id", type="integer", nullable=false)
     */
    protected int $usergroupId;

    /**
     * @ORM\Column(name="course_id", type="integer", nullable=false)
     */
    protected int $courseId;

    /**
     * Set usergroupId.
     *
     * @param int $usergroupId
     *
     * @return UsergroupRelCourse
     */
    public function setUsergroupId($usergroupId)
    {
        $this->usergroupId = $usergroupId;

        return $this;
    }

    /**
     * Get usergroupId.
     *
     * @return int
     */
    public function getUsergroupId()
    {
        return $this->usergroupId;
    }

    /**
     * Set courseId.
     *
     * @param int $courseId
     *
     * @return UsergroupRelCourse
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * Get courseId.
     *
     * @return int
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
