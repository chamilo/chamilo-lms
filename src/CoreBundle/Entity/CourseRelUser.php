<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * User subscriptions to a course.
 *
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_USER')"},
 *     normalizationContext={"groups"={"course_rel_user:read", "user:read"}},
 *     collectionOperations={
 *         "get"={"security"="is_granted('ROLE_ADMIN') or object.user == user"},
 *         "post"={"security"="is_granted('ROLE_ADMIN') or object.user == user"}
 *     },
 *     itemOperations={
 *         "get"={"security"="is_granted('ROLE_ADMIN') or object.user == user"},
 *     }
 * )
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
class CourseRelUser
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @Groups({"course:read"})
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="courses", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected User $user;

    /**
     * @Groups({"course:read", "user:read"})
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected Course $course;

    /**
     * @Groups({"course:read", "user:read"})
     * @ORM\Column(name="relation_type", type="integer")
     */
    protected int $relationType;

    /**
     * @Groups({"user:read"})
     * @ORM\Column(name="status", type="integer")
     */
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

    public function __construct()
    {
        $this->userCourseCat = 0;
        $this->sort = 0;
        $this->tutor = false;
        $this->status = User::STUDENT;
    }

    public function __toString(): string
    {
        return (string) $this->getCourse()->getCode();
    }

    public function getId(): int
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

    /**
     * Get sort.
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    public function isTutor(): bool
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

    /**
     * Get userCourseCat.
     *
     * @return int
     */
    public function getUserCourseCat()
    {
        return $this->userCourseCat;
    }

    public function setLegalAgreement(int $legalAgreement): self
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

    /**
     * Get relation_type list.
     */
    public static function getRelationTypeList(): array
    {
        return [
            '0' => '',
            COURSE_RELATION_TYPE_RRHH => 'drh',
        ];
    }

    /**
     * Get status list.
     */
    public static function getStatusList(): array
    {
        return [
            User::COURSE_MANAGER => 'Teacher',
            User::STUDENT => 'Student',
            //User::DRH => 'DRH'
        ];
    }
}
