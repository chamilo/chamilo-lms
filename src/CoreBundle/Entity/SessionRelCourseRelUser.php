<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User subscriptions to a session course.
 *
 * @ORM\Table(
 *     name="session_rel_course_rel_user",
 *     uniqueConstraints={
 *        @ORM\UniqueConstraint(name="course_session_unique",
 *            columns={"session_id", "c_id", "user_id", "status"})
 *     },
 *     indexes={
 *         @ORM\Index(name="idx_session_rel_course_rel_user_id_user", columns={"user_id"}),
 *         @ORM\Index(name="idx_session_rel_course_rel_user_course_id", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
#[ApiResource(
    normalizationContext: [
        'groups' => ['user:read', 'session_rel_course_rel_user:read'],
        'enable_max_depth' => true,
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'user' => 'exact',
    'session' => 'exact',
    'course' => 'exact',
    'user.username' => 'partial',
])]
#[ApiFilter(
    DateFilter::class,
    properties: [
        'session.displayStartDate' => null,
        'session.displayEndDate' => null,
        'session.accessStartDate' => null,
        'session.accessEndDate' => null,
        'session.coachAccessStartDate' => null,
        'session.coachAccessEndDate' => null,
    ]
)]
class SessionRelCourseRelUser
{
    use UserTrait;

    /**
     * @var string[]
     */
    public array $statusList = [
        Session::STUDENT => 'student',
        Session::COURSE_COACH => 'course_coach',
    ];

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="sessionRelCourseRelUsers", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    #[Groups(['session:read', 'session_rel_course_rel_user:read'])]
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="sessionRelCourseRelUsers", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=false)
     */
    #[Groups(['session_rel_course_rel_user:read'])]
    protected Session $session;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="sessionRelCourseRelUsers", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    #[Groups(['session:read', 'session_rel_course_rel_user:read', 'session_rel_user:read'])]
    #[MaxDepth(1)]
    protected Course $course;

    /**
     * @ORM\Column(name="status", type="integer")
     */
    protected int $status;

    /**
     * @ORM\Column(name="visibility", type="integer")
     */
    protected int $visibility;

    /**
     * @ORM\Column(name="legal_agreement", type="integer", nullable=false, unique=false)
     */
    protected int $legalAgreement;

    /**
     * @Assert\Range(
     *     min = 0,
     *     max = 100,
     *     notInRangeMessage = "Progress from {{ min }} to {{ max }} only",
     * )
     *
     * @ORM\Column(name="progress", type="integer")
     */
    protected int $progress;

    public function __construct()
    {
        $this->progress = 0;
        $this->visibility = 1;
        $this->legalAgreement = 0;
        $this->status = Session::STUDENT;
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

    public function getId(): ?int
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

    public function setLegalAgreement(int $legalAgreement): self
    {
        $this->legalAgreement = $legalAgreement;

        return $this;
    }

    public function getLegalAgreement(): int
    {
        return $this->legalAgreement;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }
}
