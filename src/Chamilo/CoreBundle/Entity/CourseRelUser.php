<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseRelUser
 *
 * @ORM\Table(name="course_rel_user", indexes={@ORM\Index(name="course_rel_user_user_id", columns={"id", "user_id"}), @ORM\Index(name="course_rel_user_c_id_user_id", columns={"id", "c_id", "user_id"})})
 * @ORM\Entity
 * @ORM\Table(name="course_rel_user")
 */
class CourseRelUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    //private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $relationType;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=60, precision=0, scale=0, nullable=true, unique=false)
     */
    private $role;

    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    //private $groupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="tutor_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $tutorId;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sort;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_course_cat", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $userCourseCat;

    /**
     * @var integer
     *
     * @ORM\Column(name="legal_agreement", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $legalAgreement;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    //protected $cId;

    /**
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User", inversedBy="courses", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Course", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroupInfo", inversedBy="course", cascade={"persist"})
     * @ORM\JoinColumn(name="group_id", referencedColumnName="iid")
     */
    protected $group;

    public function __toString()
    {
        return strval($this->getCourse()->getCode());
    }

    public function __construct()
    {
        $this->userCourseCat = 0;
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
     * @param $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return integer
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CourseRelUser
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * @param $course
     * @return $this
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get Course
     *
     * @return string
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get User
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return CourseRelUser
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set relationType
     *
     * @param integer $relationType
     * @return CourseRelUser
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType
     *
     * @return integer
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return CourseRelUser
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set role
     *
     * @param string $role
     * @return CourseRelUser
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return CourseRelUser
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return integer
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set tutorId
     *
     * @param integer $tutorId
     * @return CourseRelUser
     */
    public function setTutorId($tutorId)
    {
        $this->tutorId = $tutorId;

        return $this;
    }

    /**
     * Get tutorId
     *
     * @return integer
     */
    public function getTutorId()
    {
        return $this->tutorId;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     * @return CourseRelUser
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set userCourseCat
     *
     * @param integer $userCourseCat
     * @return CourseRelUser
     */
    public function setUserCourseCat($userCourseCat)
    {
        $this->userCourseCat = $userCourseCat;

        return $this;
    }

    /**
     * Get userCourseCat
     *
     * @return integer
     */
    public function getUserCourseCat()
    {
        return $this->userCourseCat;
    }

    /**
     * Set legalAgreement
     *
     * @param integer $legalAgreement
     * @return CourseRelUser
     */
    public function setLegalAgreement($legalAgreement)
    {
        $this->legalAgreement = $legalAgreement;

        return $this;
    }

    /**
     * Get legalAgreement
     *
     * @return integer
     */
    public function getLegalAgreement()
    {
        return $this->legalAgreement;
    }

    /**
     * Get relation_type list
     *
     * @return array
     */
    public static function getRelationTypeList()
    {
        return array(
            '0' => '',
            COURSE_RELATION_TYPE_RRHH => 'drh',
        );
    }

    /**
     * Get status list
     * @return array
     */
    public static function getStatusList()
    {
        return array(
            COURSEMANAGER => 'Teacher',
            STUDENT => 'Student'
        );
    }
}
