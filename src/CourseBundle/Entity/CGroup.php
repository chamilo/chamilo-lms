<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_ADMIN')"},
 *     normalizationContext={"groups"={"group:read"}},
 * )
 *
 * @ORM\Table(
 *     name="c_group_info",
 *     indexes={
 *     }
 * )
 *
 * @ORM\Entity
 */
class CGroup extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     * @Groups({"group:read", "group:write"})
     */
    protected int $iid;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     * @Groups({"group:read", "group:write"})
     */
    protected string $name;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected bool $status;

    /**
     * @ORM\ManyToOne(targetEntity="CGroupCategory", cascade={"persist"})
     * @ORM\JoinColumn(name="category_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected ?CGroupCategory $category = null;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="max_student", type="integer")
     */
    protected int $maxStudent;

    /**
     * @ORM\Column(name="doc_state", type="integer")
     */
    protected int $docState;

    /**
     * @ORM\Column(name="calendar_state", type="integer")
     */
    protected int $calendarState;

    /**
     * @ORM\Column(name="work_state", type="integer")
     */
    protected int $workState;

    /**
     * @ORM\Column(name="announcements_state", type="integer")
     */
    protected int $announcementsState;

    /**
     * @ORM\Column(name="forum_state", type="integer")
     */
    protected int $forumState;

    /**
     * @ORM\Column(name="wiki_state", type="integer")
     */
    protected int $wikiState;

    /**
     * @ORM\Column(name="chat_state", type="integer")
     */
    protected int $chatState;

    /**
     * @ORM\Column(name="self_registration_allowed", type="boolean")
     */
    protected bool $selfRegistrationAllowed;

    /**
     * @ORM\Column(name="self_unregistration_allowed", type="boolean")
     */
    protected bool $selfUnregistrationAllowed;

    /**
     * @ORM\Column(name="document_access", type="integer", options={"default":0})
     */
    protected int $documentAccess;

    /**
     * @var CGroupRelUser[]|Collection<int, CGroupRelUser>
     *
     * @ORM\OneToMany(targetEntity="CGroupRelUser", mappedBy="group")
     */
    protected Collection $members;

    /**
     * @var CGroupRelTutor[]|Collection<int, CGroupRelTutor>
     *
     * @ORM\OneToMany(targetEntity="CGroupRelTutor", mappedBy="group")
     */
    protected Collection $tutors;

    public function __construct()
    {
        $this->status = true;
        $this->members = new ArrayCollection();
        $this->tutors = new ArrayCollection();
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

    /**
     * Get status.
     *
     * @return bool
     */
    public function getStatus()
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

    public function getMembers(): Collection
    {
        return $this->members;
    }

    /**
     * @param CGroupRelUser[]|Collection<int, CGroupRelUser> $members
     */
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
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('user', $user)
        );

        return $this->members->matching($criteria)->count() > 0;
    }

    public function hasTutor(User $user): bool
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('user', $user)
        );

        return $this->tutors->matching($criteria)->count() > 0;
    }

    public function getTutors(): Collection
    {
        return $this->tutors;
    }

    /**
     * @param CGroupRelTutor[]|Collection<int, CGroupRelTutor> $tutors
     */
    public function setTutors(Collection $tutors): self
    {
        $this->tutors = $tutors;

        return $this;
    }

    public function hasTutors(): bool
    {
        return $this->tutors->count() > 0;
    }

    public function userIsTutor(User $user = null): bool
    {
        if (null === $user) {
            return false;
        }

        if (0 === $this->tutors->count()) {
            return false;
        }

        $criteria = Criteria::create()
            ->andWhere(
                Criteria::expr()->eq('user', $user)
            )
        ;

        $relation = $this->tutors->matching($criteria);

        return $relation->count() > 0;
    }

    public function getCategory(): CGroupCategory
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
