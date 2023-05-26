<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User subscriptions to a course.
 */
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_ADMIN') or object.user == user"),
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: [
        'groups' => ['course_rel_user:read', 'user:read'],
        'enable_max_depth' => true,
    ],
    security: "is_granted('ROLE_USER')"
)]
#[ORM\Table(name: 'course_rel_user')]
#[ORM\Index(name: 'course_rel_user_user_id', columns: ['id', 'user_id'])]
#[ORM\Index(name: 'course_rel_user_c_id_user_id', columns: ['id', 'c_id', 'user_id'])]
#[ORM\Entity]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'status' => 'exact',
        'user' => 'exact',
        'user.username' => 'partial',
    ]
)]
#[ApiResource(
    uriTemplate: '/courses/{id}/users.{_format}',
    operations: [
        new GetCollection(),
    ],
    uriVariables: [
        'id' => new Link(
            fromClass: Course::class,
            identifiers: ['id']
        ),
    ],
    status: 200,
    normalizationContext: [
        'groups' => ['course_rel_user:read', 'user:read'],
    ],
)]
#[ApiResource(
    uriTemplate: '/users/{id}/courses.{_format}',
    operations: [
        new GetCollection(),
    ],
    uriVariables: [
        'id' => new Link(
            fromClass: User::class,
            identifiers: ['id']
        ),
    ],
    status: 200,
    normalizationContext: [
        'groups' => ['course_rel_user:read', 'user:read'],
    ],
)]
class CourseRelUser implements Stringable
{
    use UserTrait;

    public const TEACHER = 1;
    //public const SESSION_ADMIN = 3;
    //public const DRH = 4;
    public const STUDENT = 5;
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[MaxDepth(1)]
    #[Groups(['course:read', 'user:read', 'course_rel_user:read'])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'courses', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected User $user;
    #[Groups(['user:read'])]
    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'users', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id')]
    protected Course $course;
    #[Groups(['course:read', 'user:read'])]
    #[ORM\Column(name: 'relation_type', type: 'integer')]
    protected int $relationType;
    #[Groups(['user:read'])]
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
    #[Groups(['course:read', 'user:read'])]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'Progress from {{ min }} to {{ max }} only')]
    #[ORM\Column(name: 'progress', type: 'integer')]
    protected int $progress;

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
}
