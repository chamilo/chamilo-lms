<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublication.
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
    /** @var int 255 chars from url column - 5 chars from 'work/' - 32 chars from filename hash - 1 char */
    public const WORK_TITLE_MAX_LENGTH = 217;

    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="url_correction", type="string", length=255, nullable=true)
     */
    protected $urlCorrection;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="title_correction", type="string", length=255, nullable=true)
     */
    protected $titleCorrection;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    protected $author;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    protected $active;

    /**
     * @var bool
     *
     * @ORM\Column(name="accepted", type="boolean", nullable=true)
     */
    protected $accepted;

    /**
     * @var int
     *
     * @ORM\Column(name="post_group_id", type="integer", nullable=false)
     */
    protected $postGroupId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_date", type="datetime", nullable=true)
     */
    protected $sentDate;

    /**
     * @var string
     *
     * @ORM\Column(name="filetype", type="string", length=10, nullable=false)
     */
    protected $filetype;

    /**
     * @var int
     *
     * @ORM\Column(name="has_properties", type="integer", nullable=false)
     */
    protected $hasProperties;

    /**
     * @var bool
     *
     * @ORM\Column(name="view_properties", type="boolean", nullable=true)
     */
    protected $viewProperties;

    /**
     * @var float
     *
     * @ORM\Column(name="qualification", type="float", precision=6, scale=2, nullable=false)
     */
    protected $qualification;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_qualification", type="datetime", nullable=true)
     */
    protected $dateOfQualification;

    /**
     * @var int
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    protected $parentId;

    /**
     * @var int
     *
     * @ORM\Column(name="qualificator_id", type="integer", nullable=false)
     */
    protected $qualificatorId;

    /**
     * @var float
     *
     * @ORM\Column(name="weight", type="float", precision=6, scale=2, nullable=false)
     */
    protected $weight;

    /**
     * @var Session
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="studentPublications")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    protected $session;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="allow_text_assignment", type="integer", nullable=false)
     */
    protected $allowTextAssignment;

    /**
     * @var int
     *
     * @ORM\Column(name="contains_file", type="integer", nullable=false)
     */
    protected $containsFile;

    /**
     * @var int
     *
     * @ORM\Column(name="document_id", type="integer", nullable=false)
     */
    protected $documentId;

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return CStudentPublication
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CStudentPublication
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CStudentPublication
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set author.
     *
     * @param string $author
     *
     * @return CStudentPublication
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author.
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set active.
     *
     * @param bool $active
     *
     * @return CStudentPublication
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set accepted.
     *
     * @param bool $accepted
     *
     * @return CStudentPublication
     */
    public function setAccepted($accepted)
    {
        $this->accepted = $accepted;

        return $this;
    }

    /**
     * Get accepted.
     *
     * @return bool
     */
    public function getAccepted()
    {
        return $this->accepted;
    }

    /**
     * Set postGroupId.
     *
     * @param int $postGroupId
     *
     * @return CStudentPublication
     */
    public function setPostGroupId($postGroupId)
    {
        $this->postGroupId = $postGroupId;

        return $this;
    }

    /**
     * Get postGroupId.
     *
     * @return int
     */
    public function getPostGroupId()
    {
        return $this->postGroupId;
    }

    /**
     * Set sentDate.
     *
     * @param \DateTime $sentDate
     *
     * @return CStudentPublication
     */
    public function setSentDate($sentDate)
    {
        $this->sentDate = $sentDate;

        return $this;
    }

    /**
     * Get sentDate.
     *
     * @return \DateTime
     */
    public function getSentDate()
    {
        return $this->sentDate;
    }

    /**
     * Set filetype.
     *
     * @param string $filetype
     *
     * @return CStudentPublication
     */
    public function setFiletype($filetype)
    {
        $this->filetype = $filetype;

        return $this;
    }

    /**
     * Get filetype.
     *
     * @return string
     */
    public function getFiletype()
    {
        return $this->filetype;
    }

    /**
     * Set hasProperties.
     *
     * @param int $hasProperties
     *
     * @return CStudentPublication
     */
    public function setHasProperties($hasProperties)
    {
        $this->hasProperties = $hasProperties;

        return $this;
    }

    /**
     * Get hasProperties.
     *
     * @return int
     */
    public function getHasProperties()
    {
        return $this->hasProperties;
    }

    /**
     * Set viewProperties.
     *
     * @param bool $viewProperties
     *
     * @return CStudentPublication
     */
    public function setViewProperties($viewProperties)
    {
        $this->viewProperties = $viewProperties;

        return $this;
    }

    /**
     * Get viewProperties.
     *
     * @return bool
     */
    public function getViewProperties()
    {
        return $this->viewProperties;
    }

    /**
     * Set qualification.
     *
     * @param float $qualification
     *
     * @return CStudentPublication
     */
    public function setQualification($qualification)
    {
        $this->qualification = $qualification;

        return $this;
    }

    /**
     * Get qualification.
     *
     * @return float
     */
    public function getQualification()
    {
        return $this->qualification;
    }

    /**
     * Set dateOfQualification.
     *
     * @param \DateTime $dateOfQualification
     *
     * @return CStudentPublication
     */
    public function setDateOfQualification($dateOfQualification)
    {
        $this->dateOfQualification = $dateOfQualification;

        return $this;
    }

    /**
     * Get dateOfQualification.
     *
     * @return \DateTime
     */
    public function getDateOfQualification()
    {
        return $this->dateOfQualification;
    }

    /**
     * Set parentId.
     *
     * @param int $parentId
     *
     * @return CStudentPublication
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId.
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set qualificatorId.
     *
     * @param int $qualificatorId
     *
     * @return CStudentPublication
     */
    public function setQualificatorId($qualificatorId)
    {
        $this->qualificatorId = $qualificatorId;

        return $this;
    }

    /**
     * Get qualificatorId.
     *
     * @return int
     */
    public function getQualificatorId()
    {
        return $this->qualificatorId;
    }

    /**
     * Set weight.
     *
     * @param float $weight
     *
     * @return CStudentPublication
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight.
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set session.
     *
     * @param Session $session
     *
     * @return CStudentPublication
     */
    public function setSession(Session $session = null)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session.
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return CStudentPublication
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set allowTextAssignment.
     *
     * @param int $allowTextAssignment
     *
     * @return CStudentPublication
     */
    public function setAllowTextAssignment($allowTextAssignment)
    {
        $this->allowTextAssignment = $allowTextAssignment;

        return $this;
    }

    /**
     * Get allowTextAssignment.
     *
     * @return int
     */
    public function getAllowTextAssignment()
    {
        return $this->allowTextAssignment;
    }

    /**
     * Set containsFile.
     *
     * @param int $containsFile
     *
     * @return CStudentPublication
     */
    public function setContainsFile($containsFile)
    {
        $this->containsFile = $containsFile;

        return $this;
    }

    /**
     * Get containsFile.
     *
     * @return int
     */
    public function getContainsFile()
    {
        return $this->containsFile;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CStudentPublication
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CStudentPublication
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
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
     * Get iid.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }
}
