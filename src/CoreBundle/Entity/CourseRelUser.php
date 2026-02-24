<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Filter\PartialSearchOrFilter;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\State\UserCourseSubscriptionsStateProvider;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User subscriptions to a course.
 */
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_ADMIN') or object.user == user"),
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')"),
        new GetCollection(
            uriTemplate: '/me/courses.{_format}',
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationClientEnabled: true,
            paginationClientItemsPerPage: true,
            normalizationContext: [
                'groups' => ['course_rel_user:read'],
                'enable_max_depth' => true,
            ],
            security: "is_granted('ROLE_USER')",
            name: 'me_courses',
            provider: UserCourseSubscriptionsStateProvider::class
        ),
    ],
    normalizationContext: [
        'groups' => ['course_rel_user:read'],
        'enable_max_depth' => true,
    ],
    paginationClientEnabled: true,
    security: "is_granted('ROLE_USER')",
)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'status' => 'exact',
        'user' => 'exact',
        'user.username' => 'partial',
        'course' => 'exact',
    ]
)]
#[ApiFilter(
    PartialSearchOrFilter::class,
    properties: [
        'user.username',
        'user.firstname',
        'user.lastname',
        'course.title',
        'course.code',
    ]
)]
#[ApiFilter(OrderFilter::class, properties: ['cru.sort' => 'ASC', 'c.title' => 'ASC'])]
#[ORM\Table(name: 'course_rel_user')]
#[ORM\Index(columns: ['id', 'user_id'], name: 'course_rel_user_user_id')]
#[ORM\Index(columns: ['id', 'c_id', 'user_id'], name: 'course_rel_user_c_id_user_id')]
#[ORM\Entity(repositoryClass: CourseRelUserRepository::class)]
class CourseRelUser implements Stringable
{
    use UserTrait;

    public const TEACHER = 1;
    public const STUDENT = 5;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Groups([
        'course:read',
        'user:read',
        'course_rel_user:read',
        'course_catalogue:read',
    ])]
    #[MaxDepth(1)]
    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'courses')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[Groups(['course_rel_user:read'])]
    #[MaxDepth(1)]
    #[ORM\ManyToOne(targetEntity: Course::class, cascade: ['persist'], inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Course $course;

    #[Groups(['course:read', 'user:read'])]
    #[ORM\Column(name: 'relation_type', type: 'integer')]
    protected int $relationType;

    #[Groups(['course_rel_user:read'])]
    #[ORM\Column(name: 'status', type: 'integer')]
    protected int $status;

    #[ORM\Column(name: 'is_tutor', type: 'boolean', nullable: true, unique: false)]
    protected ?bool $tutor;

    #[ORM\Column(name: 'sort', type: 'integer', nullable: true, unique: false)]
    protected ?int $sort;

    #[ORM\Column(name: 'user_course_cat', type: 'integer', nullable: true, unique: false)]
    protected ?int $userCourseCat;

    #[ORM\Column(name: 'legal_agreement', type: 'integer', nullable: true, unique: false)]
    protected ?int $legalAgreement = null;

    #[Groups(['course:read', 'user:read', 'course_rel_user:read'])]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'Progress from {{ min }} to {{ max }} only')]
    #[ORM\Column(name: 'progress', type: 'integer')]
    protected int $progress;

    #[Groups(['course_rel_user:read'])]
    private ?float $trackingProgress = null;

    #[Groups(['course_rel_user:read'])]
    private ?float $score = null;

    #[Groups(['course_rel_user:read'])]
    private ?float $bestScore = null;

    #[Groups(['course_rel_user:read'])]
    private ?int $timeSpentSeconds = null;

    #[Groups(['course_rel_user:read'])]
    private ?bool $certificateAvailable = null;

    #[Groups(['course_rel_user:read'])]
    private ?bool $completed = null;

    #[ApiProperty(readable: true, writable: false)]
    #[Groups(['course_rel_user:read'])]
    private ?bool $hasNewContent = null;

    /**
     * Indicates if the course has dependency requirements (computed at runtime, not persisted).
     */
    #[ApiProperty(readable: true, writable: false)]
    #[Groups(['course_rel_user:read'])]
    private ?bool $hasRequirements = null;

    /**
     * Indicates if subscription/access is allowed based on requirements (computed at runtime, not persisted).
     */
    #[ApiProperty(readable: true, writable: false)]
    #[Groups(['course_rel_user:read'])]
    private ?bool $allowSubscription = null;

    #[ApiProperty(readable: true, writable: false)]
    #[Groups(['course_rel_user:read', 'my_courses:read'])]
    private array $teachersLite = [];

    public function __construct()
    {
        $this->progress = 0;
        $this->userCourseCat = 0;
        $this->sort = 0;
        $this->tutor = false;
        $this->status = self::STUDENT;
        $this->relationType = 0;
    }

    public function __toString(): string
    {
        return $this->getCourse()->getCode();
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

    public function getRelationType(): int
    {
        return $this->relationType;
    }

    public function setRelationType(int $relationType): self
    {
        $this->relationType = $relationType;

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

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function isTutor(): ?bool
    {
        return $this->tutor;
    }

    public function setTutor(bool $tutor): self
    {
        $this->tutor = $tutor;

        return $this;
    }

    public function getUserCourseCat(): ?int
    {
        return $this->userCourseCat;
    }

    public function setUserCourseCat(int $userCourseCat): self
    {
        $this->userCourseCat = $userCourseCat;

        return $this;
    }

    public function getLegalAgreement(): ?int
    {
        return $this->legalAgreement;
    }

    public function setLegalAgreement(int $legalAgreement): self
    {
        $this->legalAgreement = $legalAgreement;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

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

    public function getTrackingProgress(): ?float
    {
        return $this->trackingProgress;
    }

    public function setTrackingProgress(?float $trackingProgress): void
    {
        $this->trackingProgress = $trackingProgress;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): void
    {
        $this->score = $score;
    }

    public function getBestScore(): ?float
    {
        return $this->bestScore;
    }

    public function setBestScore(?float $bestScore): void
    {
        $this->bestScore = $bestScore;
    }

    public function getTimeSpentSeconds(): ?int
    {
        return $this->timeSpentSeconds;
    }

    public function setTimeSpentSeconds(?int $timeSpentSeconds): void
    {
        $this->timeSpentSeconds = $timeSpentSeconds;
    }

    public function isCertificateAvailable(): ?bool
    {
        return $this->certificateAvailable;
    }

    public function setCertificateAvailable(?bool $certificateAvailable): void
    {
        $this->certificateAvailable = $certificateAvailable;
    }

    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(?bool $completed): void
    {
        $this->completed = $completed;
    }

    public function hasNewContent(): ?bool
    {
        return $this->hasNewContent;
    }

    public function setHasNewContent(?bool $hasNewContent): void
    {
        $this->hasNewContent = $hasNewContent;
    }

    public function getHasNewContent(): ?bool
    {
        return $this->hasNewContent;
    }

    public function isHasNewContent(): ?bool
    {
        return $this->hasNewContent;
    }

    public function getHasRequirements(): ?bool
    {
        return $this->hasRequirements;
    }

    public function isHasRequirements(): ?bool
    {
        return $this->hasRequirements;
    }

    public function setHasRequirements(?bool $hasRequirements): void
    {
        $this->hasRequirements = $hasRequirements;
    }

    public function getAllowSubscription(): ?bool
    {
        return $this->allowSubscription;
    }

    public function isAllowSubscription(): ?bool
    {
        return $this->allowSubscription;
    }

    public function setAllowSubscription(?bool $allowSubscription): void
    {
        $this->allowSubscription = $allowSubscription;
    }

    public function getTeachersLite(): array
    {
        return $this->teachersLite;
    }

    public function setTeachersLite(array $teachersLite): self
    {
        $this->teachersLite = $teachersLite;

        return $this;
    }
}
