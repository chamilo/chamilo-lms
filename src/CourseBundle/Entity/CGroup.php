<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Course groups.
 */
#[ApiResource(security: 'is_granted(\'ROLE_ADMIN\')', normalizationContext: ['groups' => ['group:read']])]
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
    protected int $iid;
    #[Assert\NotBlank]
    #[ORM\Column(name: 'name', type: 'string', length: 100, nullable: false)]
    #[Groups(['group:read', 'group:write'])]
    protected string $name;
    #[Assert\NotNull]
    #[ORM\Column(name: 'status', type: 'boolean', nullable: false)]
    protected bool $status;
    #[ORM\ManyToOne(targetEntity: CGroupCategory::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CGroupCategory $category = null;
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
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
     * @var CGroupRelUser[]|Collection<int, CGroupRelUser>
     */
    #[ORM\OneToMany(targetEntity: CGroupRelUser::class, mappedBy: 'group')]
    protected Collection $members;
    /**
     * @var CGroupRelTutor[]|Collection<int, CGroupRelTutor>
     */
    #[ORM\OneToMany(targetEntity: CGroupRelTutor::class, mappedBy: 'group')]
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
        return $this->getName();
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
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getName(): string
    {
        return $this->name;
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
    /**
     * Get docState.
     *
     * @return int
     */
    public function getDocState()
    {
        return $this->docState;
    }
    public function setCalendarState(int $calendarState): self
    {
        $this->calendarState = $calendarState;

        return $this;
    }
    /**
     * Get calendarState.
     *
     * @return int
     */
    public function getCalendarState()
    {
        return $this->calendarState;
    }
    public function setWorkState(int $workState): self
    {
        $this->workState = $workState;

        return $this;
    }
    /**
     * Get workState.
     *
     * @return int
     */
    public function getWorkState()
    {
        return $this->workState;
    }
    public function setAnnouncementsState(int $announcementsState): self
    {
        $this->announcementsState = $announcementsState;

        return $this;
    }
    /**
     * Get announcementsState.
     *
     * @return int
     */
    public function getAnnouncementsState()
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
    /**
     * Get wikiState.
     *
     * @return int
     */
    public function getWikiState()
    {
        return $this->wikiState;
    }
    public function setChatState(int $chatState): self
    {
        $this->chatState = $chatState;

        return $this;
    }
    /**
     * Get chatState.
     *
     * @return int
     */
    public function getChatState()
    {
        return $this->chatState;
    }
    public function setSelfRegistrationAllowed(bool $selfRegistrationAllowed): self
    {
        $this->selfRegistrationAllowed = $selfRegistrationAllowed;

        return $this;
    }
    /**
     * Get selfRegistrationAllowed.
     *
     * @return bool
     */
    public function getSelfRegistrationAllowed()
    {
        return $this->selfRegistrationAllowed;
    }
    public function setSelfUnregistrationAllowed(bool $selfUnregistrationAllowed): self
    {
        $this->selfUnregistrationAllowed = $selfUnregistrationAllowed;

        return $this;
    }
    /**
     * Get selfUnregistrationAllowed.
     *
     * @return bool
     */
    public function getSelfUnregistrationAllowed()
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
     * @return CGroupRelUser[]|Collection
     */
    public function getMembers(): array|Collection
    {
        return $this->members;
    }
    /**
     * @param CGroupRelUser[]|Collection<int, CGroupRelUser> $members
     */
    public function setMembers(array|Collection $members): self
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
     * @return CGroupRelTutor[]|Collection
     */
    public function getTutors(): array|Collection
    {
        return $this->tutors;
    }
    /**
     * @param CGroupRelTutor[]|Collection<int, CGroupRelTutor> $tutors
     */
    public function setTutors(array|Collection $tutors): self
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
    public function setCategory(CGroupCategory $category = null): self
    {
        $this->category = $category;

        return $this;
    }
    public function getResourceIdentifier(): int
    {
        return $this->iid;
    }
    public function getResourceName(): string
    {
        return $this->getName();
    }
    public function setResourceName(string $name): self
    {
        return $this->setName($name);
    }
}
