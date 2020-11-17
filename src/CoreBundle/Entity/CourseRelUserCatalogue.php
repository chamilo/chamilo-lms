<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * CourseRelUserCatalogue.
 *
 * @ORM\Table(
 *      name="course_rel_user_catalogue",
 *      indexes={
 *          @ORM\Index(name="course_rel_user_catalogue_user_id", columns={"user_id"}),
 *          @ORM\Index(name="course_rel_user_catalogue_c_id", columns={"c_id"})
 *      }
 * )
 * @ORM\Entity
 * @ORM\Table(name="course_rel_user_catalogue")
 */
class CourseRelUserCatalogue
{
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="courses", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @var int
     *
     * @ORM\Column(name="visible", type="integer", nullable=false, unique=false)
     */
    protected $visible;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getCourse()->getCode();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get Course.
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set relationType.
     *
     * @param int $relationType
     *
     * @return CourseRelUserCatalogue
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType.
     *
     * @return int
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * Set visible.
     *
     * @param bool $visible
     *
     * @return CourseRelUserCatalogue
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }
}
