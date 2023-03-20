<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="course_rel_user_catalogue",
 *     indexes={
 *         @ORM\Index(name="course_rel_user_catalogue_user_id", columns={"user_id"}),
 *         @ORM\Index(name="course_rel_user_catalogue_c_id", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 * @ORM\Table(name="course_rel_user_catalogue")
 */
class CourseRelUserCatalogue
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="courses", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected ?User $user = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected ?Course $course = null;

    /**
     * @ORM\Column(name="visible", type="integer")
     */
    protected int $visible;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return $this->getCourse()->getCode();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setCourse(Course $course): self
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

    public function setVisible(int $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return int
     */
    public function getVisible()
    {
        return $this->visible;
    }
}
