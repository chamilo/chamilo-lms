<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\JoinColumn;


/**
 * EntityCourseRelUser
 *
 * @Table(name="course_rel_user")
 * @Entity
 */
class EntityCourseRelUser
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="course_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseCode;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="relation_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $relationType;

    /**
     * @var boolean
     *
     * @Column(name="status", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var string
     *
     * @Column(name="role", type="string", length=60, precision=0, scale=0, nullable=true, unique=false)
     */
    private $role;

    /**
     * @var integer
     *
     * @Column(name="group_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $groupId;

    /**
     * @var integer
     *
     * @Column(name="tutor_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $tutorId;

    /**
     * @var integer
     *
     * @Column(name="sort", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sort;

    /**
     * @var integer
     *
     * @Column(name="user_course_cat", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $userCourseCat;

    /**
     * @var integer
     *
     * @Column(name="legal_agreement", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $legalAgreement;

    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @ManyToOne(targetEntity="EntityUser")
     * @JoinColumn(name="user_id", referencedColumnName="user_id")
     */
    private $user;

    /**
     * @ManyToOne(targetEntity="EntityCourse")
     * @JoinColumn(name="c_id", referencedColumnName="id")
     */
    private $course;

    public function __construct(EntityCourse $course, EntityUser $user)
    {
        $this->course = $course;
        $this->user = $user;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCourseRelUser
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
     * Set courseCode
     *
     * @param string $courseCode
     * @return EntityCourseRelUser
     */
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode
     *
     * @return string
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return EntityCourseRelUser
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
     * @return EntityCourseRelUser
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
     * @return EntityCourseRelUser
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
     * @return EntityCourseRelUser
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
     * @return EntityCourseRelUser
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
     * @return EntityCourseRelUser
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
     * @return EntityCourseRelUser
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
     * @return EntityCourseRelUser
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
     * @return EntityCourseRelUser
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
}
