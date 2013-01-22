<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCourse
 *
 * @Table(name="course")
 * @Entity
 */
class EntityCourse
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
     * @Column(name="code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $code;

    /**
     * @var string
     *
     * @Column(name="directory", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $directory;

    /**
     * @var string
     *
     * @Column(name="db_name", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $dbName;

    /**
     * @var string
     *
     * @Column(name="course_language", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $courseLanguage;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @Column(name="category_code", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $categoryCode;

    /**
     * @var boolean
     *
     * @Column(name="visibility", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @Column(name="show_score", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $showScore;

    /**
     * @var string
     *
     * @Column(name="tutor_name", type="string", length=200, precision=0, scale=0, nullable=true, unique=false)
     */
    private $tutorName;

    /**
     * @var string
     *
     * @Column(name="visual_code", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $visualCode;

    /**
     * @var string
     *
     * @Column(name="department_name", type="string", length=30, precision=0, scale=0, nullable=true, unique=false)
     */
    private $departmentName;

    /**
     * @var string
     *
     * @Column(name="department_url", type="string", length=180, precision=0, scale=0, nullable=true, unique=false)
     */
    private $departmentUrl;

    /**
     * @var integer
     *
     * @Column(name="disk_quota", type="bigint", precision=0, scale=0, nullable=true, unique=false)
     */
    private $diskQuota;

    /**
     * @var \DateTime
     *
     * @Column(name="last_visit", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $lastVisit;

    /**
     * @var \DateTime
     *
     * @Column(name="last_edit", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $lastEdit;

    /**
     * @var \DateTime
     *
     * @Column(name="creation_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $creationDate;

    /**
     * @var \DateTime
     *
     * @Column(name="expiration_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $expirationDate;

    /**
     * @var string
     *
     * @Column(name="target_course_code", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $targetCourseCode;

    /**
     * @var boolean
     *
     * @Column(name="subscribe", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $subscribe;

    /**
     * @var boolean
     *
     * @Column(name="unsubscribe", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $unsubscribe;

    /**
     * @var string
     *
     * @Column(name="registration_code", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $registrationCode;

    /**
     * @var string
     *
     * @Column(name="legal", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $legal;

    /**
     * @var integer
     *
     * @Column(name="activate_legal", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $activateLegal;

    /**
     * @var integer
     *
     * @Column(name="course_type_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $courseTypeId;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return EntityCourse
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set directory
     *
     * @param string $directory
     * @return EntityCourse
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Get directory
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set dbName
     *
     * @param string $dbName
     * @return EntityCourse
     */
    public function setDbName($dbName)
    {
        $this->dbName = $dbName;

        return $this;
    }

    /**
     * Get dbName
     *
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * Set courseLanguage
     *
     * @param string $courseLanguage
     * @return EntityCourse
     */
    public function setCourseLanguage($courseLanguage)
    {
        $this->courseLanguage = $courseLanguage;

        return $this;
    }

    /**
     * Get courseLanguage
     *
     * @return string
     */
    public function getCourseLanguage()
    {
        return $this->courseLanguage;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityCourse
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EntityCourse
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set categoryCode
     *
     * @param string $categoryCode
     * @return EntityCourse
     */
    public function setCategoryCode($categoryCode)
    {
        $this->categoryCode = $categoryCode;

        return $this;
    }

    /**
     * Get categoryCode
     *
     * @return string
     */
    public function getCategoryCode()
    {
        return $this->categoryCode;
    }

    /**
     * Set visibility
     *
     * @param boolean $visibility
     * @return EntityCourse
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return boolean
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set showScore
     *
     * @param integer $showScore
     * @return EntityCourse
     */
    public function setShowScore($showScore)
    {
        $this->showScore = $showScore;

        return $this;
    }

    /**
     * Get showScore
     *
     * @return integer
     */
    public function getShowScore()
    {
        return $this->showScore;
    }

    /**
     * Set tutorName
     *
     * @param string $tutorName
     * @return EntityCourse
     */
    public function setTutorName($tutorName)
    {
        $this->tutorName = $tutorName;

        return $this;
    }

    /**
     * Get tutorName
     *
     * @return string
     */
    public function getTutorName()
    {
        return $this->tutorName;
    }

    /**
     * Set visualCode
     *
     * @param string $visualCode
     * @return EntityCourse
     */
    public function setVisualCode($visualCode)
    {
        $this->visualCode = $visualCode;

        return $this;
    }

    /**
     * Get visualCode
     *
     * @return string
     */
    public function getVisualCode()
    {
        return $this->visualCode;
    }

    /**
     * Set departmentName
     *
     * @param string $departmentName
     * @return EntityCourse
     */
    public function setDepartmentName($departmentName)
    {
        $this->departmentName = $departmentName;

        return $this;
    }

    /**
     * Get departmentName
     *
     * @return string
     */
    public function getDepartmentName()
    {
        return $this->departmentName;
    }

    /**
     * Set departmentUrl
     *
     * @param string $departmentUrl
     * @return EntityCourse
     */
    public function setDepartmentUrl($departmentUrl)
    {
        $this->departmentUrl = $departmentUrl;

        return $this;
    }

    /**
     * Get departmentUrl
     *
     * @return string
     */
    public function getDepartmentUrl()
    {
        return $this->departmentUrl;
    }

    /**
     * Set diskQuota
     *
     * @param integer $diskQuota
     * @return EntityCourse
     */
    public function setDiskQuota($diskQuota)
    {
        $this->diskQuota = $diskQuota;

        return $this;
    }

    /**
     * Get diskQuota
     *
     * @return integer
     */
    public function getDiskQuota()
    {
        return $this->diskQuota;
    }

    /**
     * Set lastVisit
     *
     * @param \DateTime $lastVisit
     * @return EntityCourse
     */
    public function setLastVisit($lastVisit)
    {
        $this->lastVisit = $lastVisit;

        return $this;
    }

    /**
     * Get lastVisit
     *
     * @return \DateTime
     */
    public function getLastVisit()
    {
        return $this->lastVisit;
    }

    /**
     * Set lastEdit
     *
     * @param \DateTime $lastEdit
     * @return EntityCourse
     */
    public function setLastEdit($lastEdit)
    {
        $this->lastEdit = $lastEdit;

        return $this;
    }

    /**
     * Get lastEdit
     *
     * @return \DateTime
     */
    public function getLastEdit()
    {
        return $this->lastEdit;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     * @return EntityCourse
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set expirationDate
     *
     * @param \DateTime $expirationDate
     * @return EntityCourse
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Get expirationDate
     *
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Set targetCourseCode
     *
     * @param string $targetCourseCode
     * @return EntityCourse
     */
    public function setTargetCourseCode($targetCourseCode)
    {
        $this->targetCourseCode = $targetCourseCode;

        return $this;
    }

    /**
     * Get targetCourseCode
     *
     * @return string
     */
    public function getTargetCourseCode()
    {
        return $this->targetCourseCode;
    }

    /**
     * Set subscribe
     *
     * @param boolean $subscribe
     * @return EntityCourse
     */
    public function setSubscribe($subscribe)
    {
        $this->subscribe = $subscribe;

        return $this;
    }

    /**
     * Get subscribe
     *
     * @return boolean
     */
    public function getSubscribe()
    {
        return $this->subscribe;
    }

    /**
     * Set unsubscribe
     *
     * @param boolean $unsubscribe
     * @return EntityCourse
     */
    public function setUnsubscribe($unsubscribe)
    {
        $this->unsubscribe = $unsubscribe;

        return $this;
    }

    /**
     * Get unsubscribe
     *
     * @return boolean
     */
    public function getUnsubscribe()
    {
        return $this->unsubscribe;
    }

    /**
     * Set registrationCode
     *
     * @param string $registrationCode
     * @return EntityCourse
     */
    public function setRegistrationCode($registrationCode)
    {
        $this->registrationCode = $registrationCode;

        return $this;
    }

    /**
     * Get registrationCode
     *
     * @return string
     */
    public function getRegistrationCode()
    {
        return $this->registrationCode;
    }

    /**
     * Set legal
     *
     * @param string $legal
     * @return EntityCourse
     */
    public function setLegal($legal)
    {
        $this->legal = $legal;

        return $this;
    }

    /**
     * Get legal
     *
     * @return string
     */
    public function getLegal()
    {
        return $this->legal;
    }

    /**
     * Set activateLegal
     *
     * @param integer $activateLegal
     * @return EntityCourse
     */
    public function setActivateLegal($activateLegal)
    {
        $this->activateLegal = $activateLegal;

        return $this;
    }

    /**
     * Get activateLegal
     *
     * @return integer
     */
    public function getActivateLegal()
    {
        return $this->activateLegal;
    }

    /**
     * Set courseTypeId
     *
     * @param integer $courseTypeId
     * @return EntityCourse
     */
    public function setCourseTypeId($courseTypeId)
    {
        $this->courseTypeId = $courseTypeId;

        return $this;
    }

    /**
     * Get courseTypeId
     *
     * @return integer
     */
    public function getCourseTypeId()
    {
        return $this->courseTypeId;
    }
}
