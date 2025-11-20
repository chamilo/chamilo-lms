<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Groups',
    operations: [
        new GetCollection(
            uriTemplate: '/groups',
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'resourceNode.parent',
                        in: 'query',
                        required: true,
                        description: 'Filter groups by the parent resource node (course)',
                        schema: ['type' => 'integer'],
                    ),
                ],
            )
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
    ],
    normalizationContext: ['groups' => ['group:read']],
    denormalizationContext: ['groups' => ['group:write']],
    paginationEnabled: true
)]
#[ApiFilter(SearchFilter::class, properties: ['resourceNode.parent' => 'exact'])]
#[ORM\Table(name: 'c_group_info')]
#[ORM\Entity(repositoryClass: CGroupRepository::class)]
class CGroup extends AbstractResource implements ResourceInterface, Stringable
{
    public const TOOL_NOT_AVAILABLE = 0;
    public const TOOL_PUBLIC = 1;
    public const TOOL_PRIVATE = 2;
    public const TOOL_PRIVATE_BETWEEN_USERS = 3;
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['group:read', 'group:write'])]
    protected ?int $iid = null;
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 100, nullable: false)]
    #[Groups(['group:read', 'group:write'])]
    protected string $title;
    #[Assert\NotNull]
    #[ORM\Column(name: 'status', type: 'boolean', nullable: false)]
    #[Groups(['group:read', 'group:write'])]
    protected bool $status;
    #[ORM\ManyToOne(targetEntity: CGroupCategory::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CGroupCategory $category = null;
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Groups(['group:read', 'group:write'])]
    protected ?string $description = null;
    #[Assert\NotBlank]
    #[ORM\Column(name: 'max_student', type: 'integer')]
    protected int $maxStudent;
    #[ORM\Column(name: 'doc_state', type: 'integer')]
    protected int $docState;
    #[ORM\Column(name: 'calendar_state', type: 'integer')]
    protected int $calendarState;
    #[ORM\Column(name: 'work_state', type: 'integer')]
    protected int $workState;
    #[ORM\Column(name: 'announcements_state', type: 'integer')]
    protected int $announcementsState;
    #[ORM\Column(name: 'forum_state', type: 'integer')]
    protected int $forumState;
    #[ORM\Column(name: 'wiki_state', type: 'integer')]
    protected int $wikiState;
    #[ORM\Column(name: 'chat_state', type: 'integer')]
    protected int $chatState;
    #[ORM\Column(name: 'self_registration_allowed', type: 'boolean')]
    protected bool $selfRegistrationAllowed;
    #[ORM\Column(name: 'self_unregistration_allowed', type: 'boolean')]
    protected bool $selfUnregistrationAllowed;
    #[ORM\Column(name: 'document_access', type: 'integer', options: ['default' => 0])]
    protected int $documentAccess;

    /**
     * @var Collection<int, CGroupRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: CGroupRelUser::class)]
    protected Collection $members;

    /**
     * @var Collection<int, CGroupRelTutor>
     */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: CGroupRelTutor::class)]
    protected Collection $tutors;
    public function __construct()
    {
        $this->status = true;
        $this->members = new ArrayCollection();
        $this->tutors = new ArrayCollection();
        // Default values
        $defaultVisibility = self::TOOL_PRIVATE;
        $this->docState = $defaultVisibility;
        $this->calendarState = $defaultVisibility;
        $this->workState = $defaultVisibility;
        $this->announcementsState = $defaultVisibility;
        $this->forumState = $defaultVisibility;
        $this->wikiState = $defaultVisibility;
        $this->chatState = $defaultVisibility;
        $this->documentAccess = $defaultVisibility;
        $this->selfRegistrationAllowed = false;
        $this->selfUnregistrationAllowed = false;
    }
    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }
    public function getStatus(): bool
    {
        return $this->status;
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
    public function setMaxStudent(int $maxStudent): self
    {
        $this->maxStudent = $maxStudent;

        return $this;
    }
    public function getMaxStudent(): int
    {
        return $this->maxStudent;
    }
    public function setDocState(int $docState): self
    {
        $this->docState = $docState;

        return $this;
    }

    public function getDocState(): int
    {
        return $this->docState;
    }
    public function setCalendarState(int $calendarState): self
    {
        $this->calendarState = $calendarState;

        return $this;
    }

    public function getCalendarState(): int
    {
        return $this->calendarState;
    }
    public function setWorkState(int $workState): self
    {
        $this->workState = $workState;

        return $this;
    }

    public function getWorkState(): int
    {
        return $this->workState;
    }
    public function setAnnouncementsState(int $announcementsState): self
    {
        $this->announcementsState = $announcementsState;

        return $this;
    }

    public function getAnnouncementsState(): int
    {
        return $this->announcementsState;
    }
    public function setForumState(int $forumState): self
    {
        $this->forumState = $forumState;

        return $this;
    }
    public function getForumState(): int
    {
        return $this->forumState;
    }
    public function setWikiState(int $wikiState): self
    {
        $this->wikiState = $wikiState;

        return $this;
    }

    public function getWikiState(): int
    {
        return $this->wikiState;
    }
    public function setChatState(int $chatState): self
    {
        $this->chatState = $chatState;

        return $this;
    }

    public function getChatState(): int
    {
        return $this->chatState;
    }
    public function setSelfRegistrationAllowed(bool $selfRegistrationAllowed): self
    {
        $this->selfRegistrationAllowed = $selfRegistrationAllowed;

        return $this;
    }

    public function getSelfRegistrationAllowed(): bool
    {
        return $this->selfRegistrationAllowed;
    }
    public function setSelfUnregistrationAllowed(bool $selfUnregistrationAllowed): self
    {
        $this->selfUnregistrationAllowed = $selfUnregistrationAllowed;

        return $this;
    }

    public function getSelfUnregistrationAllowed(): bool
    {
        return $this->selfUnregistrationAllowed;
    }
    public function getDocumentAccess(): int
    {
        return $this->documentAccess;
    }
    public function setDocumentAccess(int $documentAccess): self
    {
        $this->documentAccess = $documentAccess;

        return $this;
    }

    /**
     * @return Collection<int, CGroupRelUser>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function setMembers(Collection $members): self
    {
        $this->members = $members;

        return $this;
    }
    public function hasMembers(): bool
    {
        return $this->members->count() > 0;
    }
    public function hasMember(User $user): bool
    {
        if (!$this->hasMembers()) {
            return false;
        }
        $list = $this->members->filter(fn (CGroupRelUser $member) => $member->getUser()->getId() === $user->getId());

        return $list->count() > 0;
    }
    public function hasTutor(User $user): bool
    {
        if (!$this->hasTutors()) {
            return false;
        }
        $list = $this->tutors->filter(fn (CGroupRelTutor $tutor) => $tutor->getUser()->getId() === $user->getId());

        return $list->count() > 0;
    }

    /**
     * @return Collection<int, CGroupRelTutor>
     */
    public function getTutors(): Collection
    {
        return $this->tutors;
    }

    public function setTutors(Collection $tutors): self
    {
        $this->tutors = $tutors;

        return $this;
    }
    public function hasTutors(): bool
    {
        return $this->tutors->count() > 0;
    }
    public function getCategory(): ?CGroupCategory
    {
        return $this->category;
    }
    public function setCategory(?CGroupCategory $category = null): self
    {
        $this->category = $category;

        return $this;
    }

    public function getResourceIdentifier(): int|Uuid
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
}
