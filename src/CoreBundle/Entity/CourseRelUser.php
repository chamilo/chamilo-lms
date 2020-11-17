<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * CourseRelUser.
 *
 * @ApiResource(
 *      attributes={"security"="is_granted('ROLE_USER')"},
 *      shortName="CourseSubscription",
 *      normalizationContext={"groups"={"course_rel_user:read", "user:read"}},
 *      collectionOperations={
 *         "get"={"security"="is_granted('ROLE_ADMIN') or object.user == user"},
 *         "post"={"security"="is_granted('ROLE_ADMIN') or object.user == user"}
 *     },
 *      itemOperations={
 *         "get"={"security"="is_granted('ROLE_ADMIN') or object.user == user"},
 *     }
 * )
 *
 * @ORM\Table(
 *      name="course_rel_user",
 *      indexes={
 *          @ORM\Index(name="course_rel_user_user_id", columns={"id", "user_id"}),
 *          @ORM\Index(name="course_rel_user_c_id_user_id", columns={"id", "c_id", "user_id"})
 *      }
 * )
 * @ORM\Entity
 */
class CourseRelUser
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
     * @Groups({"course:read"})
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="courses", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @Groups({"course:read", "user:read"})
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @var int
     *
     * @Groups({"user:read", "course:read"})
     * @ORM\Column(name="relation_type", type="integer", nullable=false, unique=false)
     */
    protected $relationType;

    /**
     * @var int
     *
     * @Groups({"user:read"})
     * @ORM\Column(name="status", type="integer", nullable=false, unique=false)
     */
    protected $status;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_tutor", type="boolean", nullable=true, unique=false)
     */
    protected $tutor;

    /**
     * @var int
     *
     * @ORM\Column(name="sort", type="integer", nullable=true, unique=false)
     */
    protected $sort;

    /**
     * @var int
     *
     * @ORM\Column(name="user_course_cat", type="integer", nullable=true, unique=false)
     */
    protected $userCourseCat;

    /**
     * @var int
     *
     * @ORM\Column(name="legal_agreement", type="integer", nullable=true, unique=false)
     */
    protected $legalAgreement;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->userCourseCat = 0;
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

    /**
     * Set relationType.
     */
    public function setRelationType(int $relationType): self
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType.
     */
    public function getRelationType(): int
    {
        return $this->relationType;
    }

    /**
     * Set status.
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set sort.
     *
     * @param int $sort
     */
    public function setSort($sort): self
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

    /**
     * Set userCourseCat.
     *
     * @param int $userCourseCat
     */
    public function setUserCourseCat($userCourseCat): self
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

    /**
     * Set legalAgreement.
     *
     * @param int $legalAgreement
     */
    public function setLegalAgreement($legalAgreement): self
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
