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
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\State\CStudentPublicationPostProcessor;
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
        new Put(security: "is_granted('EDIT', object.resourceNode)"),
        new Get(
            normalizationContext: [
                'groups' => ['student_publication:read', 'student_publication:item:get'],
            ],
            security: "is_granted('VIEW', object.resourceNode)",
        ),
        new GetCollection(),
        new Delete(security: "is_granted('DELETE', object.resourceNode)"),
        new Post(
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            processor: CStudentPublicationPostProcessor::class
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
class CStudentPublication extends AbstractResource implements ResourceInterface, Stringable
{
    #[Groups(['c_student_publication:write'])]
    public bool $addToGradebook = false;

    #[Groups(['c_student_publication:write'])]
    public int $gradebookCategoryId = 0;

    #[Groups(['c_student_publication:write'])]
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
    #[Groups(['c_student_publication:write', 'student_publication:item:get'])]
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
    #[ORM\OneToMany(mappedBy: 'publicationParent', targetEntity: self::class)]
    protected Collection $children;

    /**
     * @var Collection<int, CStudentPublicationComment>
     */
    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: CStudentPublicationComment::class)]
    protected Collection $comments;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'iid')]
    protected ?CStudentPublication $publicationParent;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected User $user;

    #[Groups(['c_student_publication:write', 'student_publication:read'])]
    #[ORM\OneToOne(mappedBy: 'publication', targetEntity: CStudentPublicationAssignment::class, cascade: ['persist'])]
    #[Assert\Valid]
    protected ?CStudentPublicationAssignment $assignment = null;

    #[ORM\Column(name: 'qualificator_id', type: 'integer', nullable: false)]
    protected int $qualificatorId;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'weight', type: 'float', precision: 6, scale: 2, nullable: false)]
    #[Groups(['c_student_publication:write', 'student_publication:read'])]
    protected float $weight = 0;

    #[ORM\Column(name: 'allow_text_assignment', type: 'integer', nullable: false)]
    #[Groups(['c_student_publication:write', 'student_publication:item:get'])]
    protected int $allowTextAssignment;

    #[ORM\Column(name: 'contains_file', type: 'integer', nullable: false)]
    protected int $containsFile;

    #[ORM\Column(name: 'document_id', type: 'integer', nullable: false)]
    protected int $documentId;

    #[ORM\Column(name: 'filesize', type: 'integer', nullable: true)]
    protected ?int $fileSize = null;

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

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

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
            })
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
}
