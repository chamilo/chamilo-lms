<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Controller\Api\CreateStudentPublicationFileAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceRestrictToGroupContextInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Filter\CidFilter;
use Chamilo\CoreBundle\Filter\ParentNullFilter;
use Chamilo\CoreBundle\Filter\SidFilter;
use Chamilo\CoreBundle\State\CStudentPublicationDeleteProcessor;
use Chamilo\CoreBundle\State\CStudentPublicationPostStateProcessor;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_student_publication')]
#[ORM\Entity(repositoryClass: CStudentPublicationRepository::class)]
#[ApiResource(
    operations: [
        new Put(
            security: "is_granted('EDIT', object.resourceNode)",
            processor: CStudentPublicationPostStateProcessor::class
        ),
        new Get(
            normalizationContext: [
                'groups' => ['student_publication:read', 'student_publication:item:get'],
            ],
            security: "is_granted('VIEW', object.resourceNode)",
        ),
        new GetCollection(),
        new Delete(
            security: "is_granted('DELETE', object.resourceNode)",
            processor: CStudentPublicationDeleteProcessor::class
        ),
        new Post(
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_TEACHER')",
            processor: CStudentPublicationPostStateProcessor::class
        ),
        new Post(
            uriTemplate: '/c_student_publications/upload',
            controller: CreateStudentPublicationFileAction::class,
            security: "is_granted('ROLE_STUDENT') or is_granted('ROLE_STUDENT_BOSS')",
            validationContext: ['groups' => ['Default', 'c_student_publication:write']],
            deserialize: false
        ),
    ],
    normalizationContext: [
        'groups' => ['student_publication:read'],
    ],
    denormalizationContext: [
        'groups' => ['c_student_publication:write'],
    ],
    order: ['sentDate' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'title',
        'sentDate' => ['nulls_comparison' => OrderFilterInterface::NULLS_SMALLEST],
        'assignment.expiresOn' => ['nulls_comparison' => OrderFilterInterface::NULLS_SMALLEST],
        'assingment.endsOn' => ['nulls_comparison' => OrderFilterInterface::NULLS_SMALLEST],
    ]
)]
#[ApiFilter(ParentNullFilter::class, properties: ['publicationParent.iid' => null])]
#[ApiFilter(filterClass: CidFilter::class)]
#[ApiFilter(filterClass: SidFilter::class)]
class CStudentPublication extends AbstractResource implements ResourceInterface, ResourceRestrictToGroupContextInterface, Stringable
{
    #[Groups(['c_student_publication:write', 'student_publication:read'])]
    public bool $addToGradebook = false;

    #[Groups(['c_student_publication:write'])]
    public int $gradebookCategoryId = 0;

    #[Groups(['c_student_publication:write', 'student_publication:read'])]
    public bool $addToCalendar = false;
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    #[Groups(['c_student_publication:write', 'student_publication:read'])]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Groups(['c_student_publication:write', 'student_publication:item:get', 'student_publication:read'])]
    protected ?string $description;

    #[ORM\Column(name: 'author', type: 'string', length: 255, nullable: true)]
    protected ?string $author = null;

    #[ORM\Column(name: 'active', type: 'integer', nullable: true)]
    protected ?int $active = null;

    #[ORM\Column(name: 'accepted', type: 'boolean', nullable: true)]
    protected ?bool $accepted = null;

    #[ORM\Column(name: 'post_group_id', type: 'integer', nullable: false)]
    protected int $postGroupId;

    #[ORM\Column(name: 'sent_date', type: 'datetime', nullable: true)]
    #[Groups(['student_publication:read'])]
    protected ?DateTime $sentDate;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: 'getFileTypes')]
    #[ORM\Column(name: 'filetype', type: 'string', length: 10, nullable: false)]
    protected string $filetype;

    #[ORM\Column(name: 'has_properties', type: 'integer', nullable: false)]
    protected int $hasProperties;

    #[ORM\Column(name: 'view_properties', type: 'boolean', nullable: true)]
    protected ?bool $viewProperties = null;

    #[ORM\Column(name: 'qualification', type: 'float', precision: 6, scale: 2, nullable: false)]
    #[Groups(['c_student_publication:write', 'student_publication:read'])]
    protected float $qualification;

    #[ORM\Column(name: 'date_of_qualification', type: 'datetime', nullable: true)]
    protected ?DateTime $dateOfQualification = null;

    /**
     * @var Collection<int, CStudentPublication>
     */
    #[ORM\OneToMany(mappedBy: 'publicationParent', targetEntity: self::class, cascade: ['remove'], orphanRemoval: true)]
    protected Collection $children;

    /**
     * @var Collection<int, CStudentPublicationComment>
     */
    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: CStudentPublicationComment::class)]
    #[Groups(['student_publication:read'])]
    protected Collection $comments;

    #[Groups(['c_student_publication:write', 'student_publication:read', 'student_publication_comment:read'])]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'iid')]
    protected ?CStudentPublication $publicationParent;

    #[Groups(['student_publication:read', 'student_publication_comment:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[Groups(['c_student_publication:write', 'student_publication:read'])]
    #[ORM\OneToOne(mappedBy: 'publication', targetEntity: CStudentPublicationAssignment::class, cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    protected ?CStudentPublicationAssignment $assignment = null;

    #[ORM\Column(name: 'qualificator_id', type: 'integer', nullable: false)]
    protected int $qualificatorId;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'weight', type: 'float', precision: 6, scale: 2, nullable: false)]
    #[Groups(['c_student_publication:write', 'student_publication:read'])]
    protected float $weight = 0;

    #[ORM\Column(name: 'allow_text_assignment', type: 'integer', nullable: false)]
    #[Groups(['c_student_publication:write', 'student_publication:item:get', 'student_publication:read'])]
    protected int $allowTextAssignment;

    #[Groups(['student_publication:read'])]
    #[ORM\Column(name: 'contains_file', type: 'integer', nullable: false)]
    protected int $containsFile;

    #[ORM\Column(name: 'document_id', type: 'integer', nullable: false)]
    protected int $documentId;

    #[ORM\Column(name: 'filesize', type: 'integer', nullable: true)]
    protected ?int $fileSize = null;

    #[ORM\Column(name: 'duration', type: 'integer', nullable: true)]
    protected ?int $duration = null;

    #[ORM\ManyToOne(targetEntity: CGroupCategory::class)]
    #[ORM\JoinColumn(name: 'group_category_id', referencedColumnName: 'iid', nullable: true)]
    protected ?CGroupCategory $groupCategory = null;

    #[ORM\Column(name: 'student_delete_own_publication', type: 'boolean', nullable: true, options: ['default' => 0])]
    protected ?bool $studentDeleteOwnPublication = null;

    #[ORM\Column(name: 'default_visibility', type: 'boolean', nullable: true, options: ['default' => 0])]
    protected ?bool $defaultVisibility = null;

    #[Groups(['c_student_publication:write', 'student_publication:read'])]
    #[ORM\Column(name: 'extensions', type: 'text', nullable: true)]
    protected ?string $extensions = null;

    #[ORM\Column(name: 'group_category_work_id', type: 'integer', nullable: false, options: ['default' => 0])]
    #[Groups(['c_student_publication:write', 'student_publication:read'])]
    protected int $groupCategoryWorkId = 0;

    public function __construct()
    {
        $this->description = '';
        $this->documentId = 0;
        $this->active = 1;
        $this->hasProperties = 0;
        $this->containsFile = 0;
        $this->publicationParent = null;
        $this->qualificatorId = 0;
        $this->qualification = 0;
        $this->assignment = null;
        $this->postGroupId = 0;
        $this->allowTextAssignment = 0;
        $this->filetype = 'folder';
        $this->sentDate = new DateTime();
        $this->children = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getFileTypes(): array
    {
        return ['file', 'folder'];
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getAccepted(): ?bool
    {
        return $this->accepted;
    }

    public function setAccepted(bool $accepted): self
    {
        $this->accepted = $accepted;

        return $this;
    }

    public function getPostGroupId(): int
    {
        return $this->postGroupId;
    }

    public function setPostGroupId(int $postGroupId): static
    {
        $this->postGroupId = $postGroupId;

        return $this;
    }

    public function getSentDate(): ?DateTime
    {
        return $this->sentDate;
    }

    public function setSentDate(DateTime $sentDate): self
    {
        $this->sentDate = $sentDate;

        return $this;
    }

    public function getFiletype(): string
    {
        return $this->filetype;
    }

    public function setFiletype(string $filetype): self
    {
        $this->filetype = $filetype;

        return $this;
    }

    public function getHasProperties(): int
    {
        return $this->hasProperties;
    }

    public function setHasProperties(int $hasProperties): self
    {
        $this->hasProperties = $hasProperties;

        return $this;
    }

    public function getViewProperties(): ?bool
    {
        return $this->viewProperties;
    }

    public function setViewProperties(bool $viewProperties): self
    {
        $this->viewProperties = $viewProperties;

        return $this;
    }

    public function getQualification(): float
    {
        return $this->qualification;
    }

    public function setQualification(float $qualification): self
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function getDateOfQualification(): ?DateTime
    {
        return $this->dateOfQualification;
    }

    public function setDateOfQualification(DateTime $dateOfQualification): self
    {
        $this->dateOfQualification = $dateOfQualification;

        return $this;
    }

    public function getQualificatorId(): int
    {
        return $this->qualificatorId;
    }

    public function setQualificatorId(int $qualificatorId): static
    {
        $this->qualificatorId = $qualificatorId;

        return $this;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getAllowTextAssignment(): int
    {
        return $this->allowTextAssignment;
    }

    public function setAllowTextAssignment(int $allowTextAssignment): self
    {
        $this->allowTextAssignment = $allowTextAssignment;

        return $this;
    }

    public function getContainsFile(): int
    {
        return $this->containsFile;
    }

    public function setContainsFile(int $containsFile): self
    {
        $this->containsFile = $containsFile;

        return $this;
    }

    public function getDocumentId(): int
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

    #[Groups(['student_publication:read'])]
    public function getCorrection(): ?ResourceNode
    {
        if (!$this->hasResourceNode()) {
            return null;
        }

        $children = $this->getResourceNode()->getChildren();

        foreach ($children as $child) {
            $resourceType = $child->getResourceType();
            if (!$resourceType) {
                continue;
            }

            $name = $resourceType->getTitle();

            if ('student_publications_corrections' === $name) {
                return $child;
            }
        }

        return null;
    }

    #[Groups(['student_publication:read'])]
    public function getCorrectionTitle(): ?string
    {
        return $this->getExtensions();
    }

    #[Groups(['student_publication:read'])]
    public function getChildFileCount(): int
    {
        return $this->children
            ->filter(fn (self $child) => 'file' === $child->getFiletype() && 2 !== $child->getActive())
            ->count()
        ;
    }

    /**
     * @return Collection<int, CStudentPublication>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function getAssignment(): ?CStudentPublicationAssignment
    {
        return $this->assignment;
    }

    public function setAssignment(?CStudentPublicationAssignment $assignment): self
    {
        $this->assignment = $assignment;

        if ($assignment) {
            $assignment->setPublication($this);
        }

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
     * @return Collection<int, CStudentPublicationComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function setComments(Collection $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getGroupCategory(): ?CGroupCategory
    {
        return $this->groupCategory;
    }

    public function setGroupCategory(?CGroupCategory $groupCategory): self
    {
        $this->groupCategory = $groupCategory;

        return $this;
    }

    public function getStudentDeleteOwnPublication(): ?bool
    {
        return $this->studentDeleteOwnPublication;
    }

    public function setStudentDeleteOwnPublication(?bool $studentDeleteOwnPublication): self
    {
        $this->studentDeleteOwnPublication = $studentDeleteOwnPublication;

        return $this;
    }

    public function getDefaultVisibility(): ?bool
    {
        return $this->defaultVisibility;
    }

    public function setDefaultVisibility(?bool $defaultVisibility): self
    {
        $this->defaultVisibility = $defaultVisibility;

        return $this;
    }

    public function getExtensions(): ?string
    {
        return $this->extensions;
    }

    public function setExtensions(?string $extensions): self
    {
        $this->extensions = $extensions;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    #[Groups(['student_publication:read'])]
    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }

    #[Groups(['student_publication:read'])]
    public function getUniqueStudentAttemptsTotal(): int
    {
        $userIdList = [];

        $reduce = $this->children
            ->filter(function (self $child) {
                return $child->postGroupId === $this->postGroupId;
            })
            ->reduce(function (int $accumulator, self $child) use (&$userIdList): int {
                $user = $child->getUser();

                if (!\in_array($user->getId(), $userIdList, true)) {
                    $userIdList[] = $user->getId();

                    return $accumulator + 1;
                }

                return $accumulator;
            }, 0)
        ;

        return $reduce ?: 0;
    }

    #[Groups(['student_publication:read'])]
    public function getStudentSubscribedToWork(): int
    {
        $firstLink = $this->getFirstResourceLink();

        $course = $firstLink->getCourse();
        $session = $firstLink->getSession();
        $group = $firstLink->getGroup();

        if ($group) {
            return $group->getMembers()->count();
        }

        if ($session) {
            return $session->getSessionRelCourseRelUsersByStatus($course, Session::STUDENT)->count();
        }

        if ($course) {
            return $course->getStudentSubscriptions()->count();
        }

        return 0;
    }

    #[Groups(['student_publication:read'])]
    public function getCorrectionDownloadUrl(): ?string
    {
        $correctionNode = $this->getCorrection();
        if ($correctionNode && $correctionNode->getFirstResourceFile()) {
            $uuid = $correctionNode->getUuid();
            if ($uuid) {
                return '/r/student_publication/student_publications_corrections/'.$uuid.'/download';
            }
        }

        return null;
    }

    public function getGroupCategoryWorkId(): int
    {
        return $this->groupCategoryWorkId;
    }

    public function setGroupCategoryWorkId(int $groupCategoryWorkId): self
    {
        $this->groupCategoryWorkId = $groupCategoryWorkId;

        return $this;
    }
}
