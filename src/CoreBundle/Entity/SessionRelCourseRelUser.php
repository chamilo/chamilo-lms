<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class SessionRelCourseRelUser.
 *
 * @ApiResource(
 *     shortName="SessionSubscription",
 *     normalizationContext={"groups"={"session_rel_course_rel_user:read", "user:read"}}
 * )
 * @ORM\Table(
 *     name="session_rel_course_rel_user",
 *     indexes={
 *         @ORM\Index(name="idx_session_rel_course_rel_user_id_user", columns={"user_id"}),
 *         @ORM\Index(name="idx_session_rel_course_rel_user_course_id", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class SessionRelCourseRelUser
{
    use UserTrait;

    public const STATUS_STUDENT = 0;
    public const STATUS_COURSE_COACH = 2;
    /**
     * @var string[]
     */
    public array $statusList = [
        0 => 'student',
        2 => 'course_coach',
    ];

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="sessionCourseSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected User $user;

    /**
     * @Groups({"session_rel_course_rel_user:read"})
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="userCourseSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=false)
     */
    protected Session $session;

    /**
     * @Groups({"session_rel_course_rel_user:read"})
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="sessionUserSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected Course $course;

    /**
     * @ORM\Column(name="visibility", type="integer")
     */
    protected int $visibility;

    /**
     * @ORM\Column(name="status", type="integer")
     */
    protected int $status;

    /**
     * @ORM\Column(name="legal_agreement", type="integer", nullable=false, unique=false)
     */
    protected int $legalAgreement;

    public function __construct()
    {
        $this->visibility = 1;
        $this->legalAgreement = 0;
        $this->status = self::STATUS_STUDENT;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function setSession(Session $session): self
    {
        $this->session = $session;

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

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set legalAgreement.
     *
     * @param int $legalAgreement
     *
     * @return $this
     */
    public function setLegalAgreement($legalAgreement)
    {
        $this->legalAgreement = $legalAgreement;

        return $this;
    }

    /**
     * Get legalAgreement.
     *
     * @return int
     */
    public function getLegalAgreement()
    {
        return $this->legalAgreement;
    }
}
