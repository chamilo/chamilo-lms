<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublication
 *
 * @ORM\Table(
 *  name="c_student_publication",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"}),
 *      @ORM\Index(name="idx_csp_u", columns={"user_id"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Entity\Repository\CStudentPublicationRepository")
 */
class CStudentPublication
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="url_correction", type="string", length=255, nullable=true)
     */
    private $urlCorrection;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="title_correction", type="string", length=255, nullable=true)
     */
    private $titleCorrection;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    private $author;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active;

    /**
     * @var boolean
     *
     * @ORM\Column(name="accepted", type="boolean", nullable=true)
     */
    private $accepted;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_group_id", type="integer", nullable=false)
     */
    private $postGroupId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_date", type="datetime", nullable=true)
     */
    private $sentDate;

    /**
     * @var string
     *
     * @ORM\Column(name="filetype", type="string", length=10, nullable=false)
     */
    private $filetype;

    /**
     * @var integer
     *
     * @ORM\Column(name="has_properties", type="integer", nullable=false)
     */
    private $hasProperties;

    /**
     * @var boolean
     *
     * @ORM\Column(name="view_properties", type="boolean", nullable=true)
     */
    private $viewProperties;

    /**
     * @var float
     *
     * @ORM\Column(name="qualification", type="float", precision=6, scale=2, nullable=false)
     */
    private $qualification;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_qualification", type="datetime", nullable=true)
     */
    private $dateOfQualification;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="qualificator_id", type="integer", nullable=false)
     */
    private $qualificatorId;

    /**
     * @var float
     *
     * @ORM\Column(name="weight", type="float", precision=6, scale=2, nullable=false)
     */
    private $weight;

    /**
     * @var Session
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="studentPublications")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_text_assignment", type="integer", nullable=false)
     */
    private $allowTextAssignment;

    /**
     * @var integer
     *
     * @ORM\Column(name="contains_file", type="integer", nullable=false)
     */
    private $containsFile;


    /**
     * @var integer
     *
     * @ORM\Column(name="document_id", type="integer", nullable=false)
     */
    private $documentId;

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
     * Set session
     * @param Session $session
     * @return CStudentPublication
     */
    public function setSession(Session $session = null)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
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
     * @return string
     */
    public function getUrlCorrection()
    {
        return $this->urlCorrection;
    }

    /**
     * @param string $urlCorrection
     */
    public function setUrlCorrection($urlCorrection)
    {
        $this->urlCorrection = $urlCorrection;
    }

    /**
     * @return string
     */
    public function getTitleCorrection()
    {
        return $this->titleCorrection;
    }

    /**
     * @param string $titleCorrection
     */
    public function setTitleCorrection($titleCorrection)
    {
        $this->titleCorrection = $titleCorrection;
    }

    /**
     * @return int
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @param int $documentId
     */
    public function setDocumentId($documentId)
    {
        $this->documentId = $documentId;
    }

    /**
     * Get iid
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }
}
