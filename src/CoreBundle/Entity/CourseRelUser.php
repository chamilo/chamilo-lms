<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User subscriptions to a course.
 *
 * @ORM\Table(
 *     name="course_rel_user",
 *     indexes={
 *         @ORM\Index(name="course_rel_user_user_id", columns={"id", "user_id"}),
 *         @ORM\Index(name="course_rel_user_c_id_user_id", columns={"id", "c_id", "user_id"})
 *     }
 * )
 * @ORM\Entity
 */
#[ApiResource(
    attributes: [
        'security' => "is_granted('ROLE_USER')",
    ],
    collectionOperations: [
        'get' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
        'post' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    itemOperations: [
        'get' => [
            'security' => "is_granted('ROLE_ADMIN') or object.user == user",
        ],
    ],
    subresourceOperations: [
        'api_users_courses_get_subresource' => [
            'security' => "is_granted('ROLE_USER')",
        ],
    ],
    normalizationContext: [
        'groups' => ['course_rel_user:read', 'user:read'],
        'enable_max_depth' => true,
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'status' => 'exact',
    'user' => 'exact',
    'user.username' => 'partial',
])]

class CourseRelUser
{
    use UserTrait;

    public const TEACHER = 1;
    //public const SESSION_ADMIN = 3;
    //public const DRH = 4;
    public const STUDENT = 5;

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
    #[MaxDepth(1)]
    #[Groups(['course:read', 'user:read'])]
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    #[Groups(['user:read'])]
    protected Course $course;

    /**
     * @ORM\Column(name="relation_type", type="integer")
     */
    #[Groups(['course:read', 'user:read'])]
    protected int $relationType;

    /**
     * @ORM\Column(name="status", type="integer")
     */
    #[Groups(['user:read'])]
    protected int $status;

    /**
     * @ORM\Column(name="is_tutor", type="boolean", nullable=true, unique=false)
     */
    protected ?bool $tutor;

    /**
     * @ORM\Column(name="sort", type="integer", nullable=true, unique=false)
     */
    protected ?int $sort;

    /**
     * @ORM\Column(name="user_course_cat", type="integer", nullable=true, unique=false)
     */
    protected ?int $userCourseCat;

    /**
     * @ORM\Column(name="legal_agreement", type="integer", nullable=true, unique=false)
     */
    protected ?int $legalAgreement = null;

    /**
     * @Assert\Range(
     *     min = 0,
     *     max = 100,
     *     notInRangeMessage = "Progress from {{ min }} to {{ max }} only",
     * )
     * @ORM\Column(name="progress", type="integer")
     */
    #[Groups(['course:read', 'user:read'])]
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setRelationType(int $relationType): self
    {
        $this->relationType = $relationType;

        return $this;
    }

    public function getRelationType(): int
    {
        return $this->relationType;
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

    public function setSort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
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

    public function setUserCourseCat(int $userCourseCat): self
    {
        $this->userCourseCat = $userCourseCat;

        return $this;
    }

    public function getUserCourseCat(): ?int
    {
        return $this->userCourseCat;
    }

    public function setLegalAgreement(int $legalAgreement): self
    {
        $this->legalAgreement = $legalAgreement;

        return $this;
    }

    public function getLegalAgreement(): ?int
    {
        return $this->legalAgreement;
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
