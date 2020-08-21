<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
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
     * @Groups({"user:read", "course:read"})
     * @ORM\Column(name="relation_type", type="integer", nullable=false, unique=false)
     */
    protected $relationType;

    /**
     * @var bool
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

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get User.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set relationType.
     *
     * @param int $relationType
     *
     * @return CourseRelUser
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
     * Set status.
     *
     * @param bool $status
     *
     * @return CourseRelUser
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set sort.
     *
     * @param int $sort
     *
     * @return CourseRelUser
     */
    public function setSort($sort)
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

    /**
     * @return bool
     */
    public function isTutor()
    {
        return $this->tutor;
    }

    /**
     * @param bool $tutor
     */
    public function setTutor($tutor)
    {
        $this->tutor = $tutor;
    }

    /**
     * Set userCourseCat.
     *
     * @param int $userCourseCat
     *
     * @return CourseRelUser
     */
    public function setUserCourseCat($userCourseCat)
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
     *
     * @return CourseRelUser
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

    /**
     * Get relation_type list.
     *
     * @return array
     */
    public static function getRelationTypeList()
    {
        return [
            '0' => '',
            COURSE_RELATION_TYPE_RRHH => 'drh',
        ];
    }

    /**
     * Get status list.
     *
     * @return array
     */
    public static function getStatusList()
    {
        return [
            User::COURSE_MANAGER => 'Teacher',
            User::STUDENT => 'Student',
            //User::DRH => 'DRH'
        ];
    }
}
