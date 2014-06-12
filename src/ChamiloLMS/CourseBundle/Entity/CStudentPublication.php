<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublication
 *
 * @ORM\Table(name="c_student_publication", indexes={@ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity
 */
class CStudentPublication
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $author;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $active;

    /**
     * @var boolean
     *
     * @ORM\Column(name="accepted", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $accepted;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_group_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $postGroupId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sentDate;

    /**
     * @var string
     *
     * @ORM\Column(name="filetype", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $filetype;

    /**
     * @var integer
     *
     * @ORM\Column(name="has_properties", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hasProperties;

    /**
     * @var boolean
     *
     * @ORM\Column(name="view_properties", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $viewProperties;

    /**
     * @var float
     *
     * @ORM\Column(name="qualification", type="float", precision=10, scale=0, nullable=false, unique=false)
     */
    private $qualification;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_qualification", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dateOfQualification;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="qualificator_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $qualificatorId;

    /**
     * @var float
     *
     * @ORM\Column(name="weight", type="float", precision=10, scale=0, nullable=false, unique=false)
     */
    private $weight;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_text_assignment", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $allowTextAssignment;

    /**
     * @var integer
     *
     * @ORM\Column(name="contains_file", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $containsFile;


    /**
     * Get iid
     *
     * @return integer 
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CStudentPublication
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
     * Set id
     *
     * @param integer $id
     * @return CStudentPublication
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set url
     *
     * @param string $url
     * @return CStudentPublication
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return CStudentPublication
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
     * Set filename
     *
     * @param string $filename
     * @return CStudentPublication
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string 
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return CStudentPublication
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
     * Set author
     *
     * @param string $author
     * @return CStudentPublication
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string 
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return CStudentPublication
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set accepted
     *
     * @param boolean $accepted
     * @return CStudentPublication
     */
    public function setAccepted($accepted)
    {
        $this->accepted = $accepted;

        return $this;
    }

    /**
     * Get accepted
     *
     * @return boolean 
     */
    public function getAccepted()
    {
        return $this->accepted;
    }

    /**
     * Set postGroupId
     *
     * @param integer $postGroupId
     * @return CStudentPublication
     */
    public function setPostGroupId($postGroupId)
    {
        $this->postGroupId = $postGroupId;

        return $this;
    }

    /**
     * Get postGroupId
     *
     * @return integer 
     */
    public function getPostGroupId()
    {
        return $this->postGroupId;
    }

    /**
     * Set sentDate
     *
     * @param \DateTime $sentDate
     * @return CStudentPublication
     */
    public function setSentDate($sentDate)
    {
        $this->sentDate = $sentDate;

        return $this;
    }

    /**
     * Get sentDate
     *
     * @return \DateTime 
     */
    public function getSentDate()
    {
        return $this->sentDate;
    }

    /**
     * Set filetype
     *
     * @param string $filetype
     * @return CStudentPublication
     */
    public function setFiletype($filetype)
    {
        $this->filetype = $filetype;

        return $this;
    }

    /**
     * Get filetype
     *
     * @return string 
     */
    public function getFiletype()
    {
        return $this->filetype;
    }

    /**
     * Set hasProperties
     *
     * @param integer $hasProperties
     * @return CStudentPublication
     */
    public function setHasProperties($hasProperties)
    {
        $this->hasProperties = $hasProperties;

        return $this;
    }

    /**
     * Get hasProperties
     *
     * @return integer 
     */
    public function getHasProperties()
    {
        return $this->hasProperties;
    }

    /**
     * Set viewProperties
     *
     * @param boolean $viewProperties
     * @return CStudentPublication
     */
    public function setViewProperties($viewProperties)
    {
        $this->viewProperties = $viewProperties;

        return $this;
    }

    /**
     * Get viewProperties
     *
     * @return boolean 
     */
    public function getViewProperties()
    {
        return $this->viewProperties;
    }

    /**
     * Set qualification
     *
     * @param float $qualification
     * @return CStudentPublication
     */
    public function setQualification($qualification)
    {
        $this->qualification = $qualification;

        return $this;
    }

    /**
     * Get qualification
     *
     * @return float 
     */
    public function getQualification()
    {
        return $this->qualification;
    }

    /**
     * Set dateOfQualification
     *
     * @param \DateTime $dateOfQualification
     * @return CStudentPublication
     */
    public function setDateOfQualification($dateOfQualification)
    {
        $this->dateOfQualification = $dateOfQualification;

        return $this;
    }

    /**
     * Get dateOfQualification
     *
     * @return \DateTime 
     */
    public function getDateOfQualification()
    {
        return $this->dateOfQualification;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     * @return CStudentPublication
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer 
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set qualificatorId
     *
     * @param integer $qualificatorId
     * @return CStudentPublication
     */
    public function setQualificatorId($qualificatorId)
    {
        $this->qualificatorId = $qualificatorId;

        return $this;
    }

    /**
     * Get qualificatorId
     *
     * @return integer 
     */
    public function getQualificatorId()
    {
        return $this->qualificatorId;
    }

    /**
     * Set weight
     *
     * @param float $weight
     * @return CStudentPublication
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return float 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CStudentPublication
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return CStudentPublication
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
     * Set allowTextAssignment
     *
     * @param integer $allowTextAssignment
     * @return CStudentPublication
     */
    public function setAllowTextAssignment($allowTextAssignment)
    {
        $this->allowTextAssignment = $allowTextAssignment;

        return $this;
    }

    /**
     * Get allowTextAssignment
     *
     * @return integer 
     */
    public function getAllowTextAssignment()
    {
        return $this->allowTextAssignment;
    }

    /**
     * Set containsFile
     *
     * @param integer $containsFile
     * @return CStudentPublication
     */
    public function setContainsFile($containsFile)
    {
        $this->containsFile = $containsFile;

        return $this;
    }

    /**
     * Get containsFile
     *
     * @return integer 
     */
    public function getContainsFile()
    {
        return $this->containsFile;
    }
}
