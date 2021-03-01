<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CStudentPublication.
 *
 * @ORM\Table(
 *  name="c_student_publication",
 *  indexes={
 *      @ORM\Index(name="idx_csp_u", columns={"user_id"})
 *  }
 * )
 * @ORM\Entity()
 */
class CStudentPublication extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="url", type="string", length=500, nullable=true)
     */
    protected ?string $url;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description;

    /**
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    protected ?string $author;

    /**
     * @ORM\Column(name="active", type="integer", nullable=true)
     */
    protected ?int $active;

    /**
     * @ORM\Column(name="accepted", type="boolean", nullable=true)
     */
    protected ?bool $accepted;

    /**
     * @ORM\Column(name="post_group_id", type="integer", nullable=false)
     */
    protected int $postGroupId;

    /**
     * @ORM\Column(name="sent_date", type="datetime", nullable=true)
     */
    protected ?DateTime $sentDate;

    /**
     * @ORM\Column(name="filetype", type="string", length=10, nullable=false)
     */
    protected string $filetype;

    /**
     * @ORM\Column(name="has_properties", type="integer", nullable=false)
     */
    protected int $hasProperties;

    /**
     * @ORM\Column(name="view_properties", type="boolean", nullable=true)
     */
    protected ?bool $viewProperties;

    /**
     * @ORM\Column(name="qualification", type="float", precision=6, scale=2, nullable=false)
     */
    protected float $qualification;

    /**
     * @ORM\Column(name="date_of_qualification", type="datetime", nullable=true)
     */
    protected ?DateTime $dateOfQualification;

    /**
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    protected int $parentId;

    /**
     * @ORM\Column(name="qualificator_id", type="integer", nullable=false)
     */
    protected int $qualificatorId;

    /**
     * @ORM\Column(name="weight", type="float", precision=6, scale=2, nullable=false)
     */
    protected float $weight;

    /**
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected int $userId;

    /**
     * @ORM\Column(name="allow_text_assignment", type="integer", nullable=false)
     */
    protected int $allowTextAssignment;

    /**
     * @ORM\Column(name="contains_file", type="integer", nullable=false)
     */
    protected int $containsFile;

    /**
     * @ORM\Column(name="document_id", type="integer", nullable=false)
     */
    protected int $documentId;

    /**
     * @ORM\Column(name="filesize", type="integer", nullable=true)
     */
    protected ?int $fileSize;

    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CourseBundle\Entity\CStudentPublicationAssignment", mappedBy="publication")
     */
    protected ?CStudentPublicationAssignment $assignment;

    public function __construct()
    {
        $this->description = '';
        $this->documentId = 0;
        $this->hasProperties = 0;
        $this->containsFile = 0;
        $this->parentId = 0;
        $this->qualificatorId = 0;
        $this->qualification = 0;
        $this->sentDate = new DateTime();
    }

    public function __toString(): string
    {
        return (string) $this->getTitle();
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
     */
    public function setTitle($title): self
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

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set author.
     *
     * @param string $author
     */
    public function setAuthor($author): self
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

    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set accepted.
     *
     * @param bool $accepted
     */
    public function setAccepted($accepted): self
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
     * @param DateTime $sentDate
     */
    public function setSentDate($sentDate): self
    {
        $this->sentDate = $sentDate;

        return $this;
    }

    /**
     * Get sentDate.
     *
     * @return DateTime
     */
    public function getSentDate()
    {
        return $this->sentDate;
    }

    /**
     * Set filetype.
     *
     * @param string $filetype
     */
    public function setFiletype($filetype): self
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
     */
    public function setHasProperties($hasProperties): self
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
     */
    public function setViewProperties($viewProperties): self
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
     * @param DateTime $dateOfQualification
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
     * @return DateTime
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

    public function getQualificatorId(): int
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
     */
    public function setAllowTextAssignment($allowTextAssignment): self
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
     */
    public function setContainsFile($containsFile): self
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
     * @return int
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @param int $documentId
     */
    public function setDocumentId($documentId): self
    {
        $this->documentId = $documentId;

        return $this;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getCorrection(): ?ResourceNode
    {
        if ($this->hasResourceNode()) {
            $children = $this->getResourceNode()->getChildren();
            foreach ($children as $child) {
                $name = $child->getResourceType()->getName();
                if ('student_publications_corrections' === $name) {
                    return $child;
                }
            }
        }

        return null;
    }

    public function getAssignment(): ?CStudentPublicationAssignment
    {
        return $this->assignment;
    }

    public function setAssignment(?CStudentPublicationAssignment $assignment): self
    {
        $this->assignment = $assignment;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
