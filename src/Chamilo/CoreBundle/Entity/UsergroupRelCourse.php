<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsergroupRelCourse
 *
 * @ORM\Table(name="usergroup_rel_course")
 * @ORM\Entity
 */
class UsergroupRelCourse
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="usergroup_id", type="integer", nullable=false)
     */
    private $usergroupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="course_id", type="integer", nullable=false)
     */
    private $courseId;

    /**
     * Set usergroupId
     *
     * @param integer $usergroupId
     * @return UsergroupRelCourse
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
     * @return UsergroupRelCourse
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

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
