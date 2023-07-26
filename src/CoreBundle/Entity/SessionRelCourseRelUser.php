<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Entity\User as UserAlias;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User subscriptions to a session course.
 */
#[ApiResource(
    normalizationContext: [
        'groups' => [
            'user:read',
            'session_rel_course_rel_user:read',
        ],
        'enable_max_depth' => true,
    ]
)]
#[ORM\Table(name: 'session_rel_course_rel_user')]
#[ORM\Index(columns: ['user_id'], name: 'idx_session_rel_course_rel_user_id_user')]
#[ORM\Index(columns: ['c_id'], name: 'idx_session_rel_course_rel_user_course_id')]
#[ORM\UniqueConstraint(name: 'course_session_unique', columns: ['session_id', 'c_id', 'user_id', 'status'])]
#[ORM\Entity]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'user' => 'exact',
        'session' => 'exact',
        'course' => 'exact',
        'user.username' => 'partial',
    ]
)]
#[ApiFilter(
    filterClass: DateFilter::class,
    properties: [
        'session.displayStartDate',
        'session.displayEndDate',
        'session.accessStartDate',
        'session.accessEndDate',
        'session.coachAccessStartDate',
        'session.coachAccessEndDate',
    ]
)]
class SessionRelCourseRelUser
{
    use UserTrait;

    /**
     * @var array<int, string>
     */
    public array $statusList = [
        Session::STUDENT => 'student',
        Session::COURSE_COACH => 'course_coach',
    ];

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Groups([
        'session:read',
        'session_rel_course_rel_user:read',
    ])]
    #[ORM\ManyToOne(targetEntity: UserAlias::class, cascade: ['persist'], inversedBy: 'sessionRelCourseRelUsers')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected User $user;

    #[Groups([
        'session_rel_course_rel_user:read',
    ])]
    #[ORM\ManyToOne(targetEntity: Session::class, cascade: ['persist'], inversedBy: 'sessionRelCourseRelUsers')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    protected Session $session;

    #[Groups([
        'session:read',
        'session_rel_course_rel_user:read',
        'session_rel_user:read',
    ])]
    #[MaxDepth(1)]
    #[ORM\ManyToOne(targetEntity: Course::class, cascade: ['persist'], inversedBy: 'sessionRelCourseRelUsers')]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Course $course;

    #[Groups(['session:item:read'])]
    #[ORM\Column(name: 'status', type: 'integer')]
    protected int $status;

    #[ORM\Column(name: 'visibility', type: 'integer')]
    protected int $visibility;

    #[ORM\Column(name: 'legal_agreement', type: 'integer', unique: false, nullable: false)]
    protected int $legalAgreement;

    #[Assert\Range(notInRangeMessage: 'Progress from {{ min }} to {{ max }} only', min: 0, max: 100)]
    #[ORM\Column(name: 'progress', type: 'integer')]
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

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getLegalAgreement(): int
    {
        return $this->legalAgreement;
    }

    public function setLegalAgreement(int $legalAgreement): self
    {
        $this->legalAgreement = $legalAgreement;

        return $this;
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
