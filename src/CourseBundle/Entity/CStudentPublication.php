<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CStudentPublication.
 *
 * @ORM\Table(
 *     name="c_student_publication",
 *     indexes={
 *     }
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
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description;

    /**
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    protected ?string $author = null;

    /**
     * @ORM\Column(name="active", type="integer", nullable=true)
     */
    protected ?int $active = null;

    /**
     * @ORM\Column(name="accepted", type="boolean", nullable=true)
     */
    protected ?bool $accepted = null;

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
    protected ?bool $viewProperties = null;

    /**
     * @ORM\Column(name="qualification", type="float", precision=6, scale=2, nullable=false)
     */
    protected float $qualification;

    /**
     * @ORM\Column(name="date_of_qualification", type="datetime", nullable=true)
     */
    protected ?DateTime $dateOfQualification = null;

    /**
     * @var Collection|CStudentPublication[]
     * @ORM\OneToMany(targetEntity="CStudentPublication", mappedBy="publicationParent")
     */
    protected Collection $children;

    /**
     * @var Collection|CStudentPublicationComment[]
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CStudentPublicationComment", mappedBy="publication")
     */
    protected Collection $comments;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CStudentPublication", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="iid")
     */
    protected ?CStudentPublication $publicationParent;

    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CourseBundle\Entity\CStudentPublicationAssignment", mappedBy="publication")
     */
    protected ?CStudentPublicationAssignment $assignment = null;

    /**
     * @ORM\Column(name="qualificator_id", type="integer", nullable=false)
     */
    protected int $qualificatorId;

    /**
     * @ORM\Column(name="weight", type="float", precision=6, scale=2, nullable=false)
     */
    protected float $weight;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected User $user;

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
    protected ?int $fileSize = null;

    public function __construct()
    {
        $this->description = '';
        $this->documentId = 0;
        $this->hasProperties = 0;
        $this->containsFile = 0;
        $this->publicationParent = null;
        $this->qualificatorId = 0;
        $this->qualification = 0;
        $this->assignment = null;
        $this->sentDate = new DateTime();
        $this->children = new ArrayCollection();
        $this->comments = new ArrayCollection();
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

    public function setTitle(string $title): self
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

    public function setAuthor(string $author): self
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

    public function setAccepted(bool $accepted): self
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
     * @return CStudentPublication
     */
    public function setPostGroupId(int $postGroupId)
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

    public function setSentDate(DateTime $sentDate): self
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

    public function setFiletype(string $filetype): self
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

    public function setHasProperties(int $hasProperties): self
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

    public function setViewProperties(bool $viewProperties): self
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
     * @return CStudentPublication
     */
    public function setQualification(float $qualification)
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
     * @return CStudentPublication
     */
    public function setDateOfQualification(DateTime $dateOfQualification)
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
     * Set qualificatorId.
     *
     * @return CStudentPublication
     */
    public function setQualificatorId(int $qualificatorId)
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
     * @return CStudentPublication
     */
    public function setWeight(float $weight)
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

    public function setAllowTextAssignment(int $allowTextAssignment): self
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

    public function setContainsFile(int $containsFile): self
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

    public function setDocumentId(int $documentId): self
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

    /**
     * @return CStudentPublication[]|Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function setChildren(Collection $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function getPublicationParent(): ?self
    {
        return $this->publicationParent;
    }

    public function setPublicationParent(?self $publicationParent): self
    {
        $this->publicationParent = $publicationParent;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return CStudentPublicationComment[]|Collection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param CStudentPublicationComment[]|Collection $comments
     */
    public function setComments(Collection $comments): self
    {
        $this->comments = $comments;

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
