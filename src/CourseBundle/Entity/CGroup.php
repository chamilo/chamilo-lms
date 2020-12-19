<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
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
 *  name="c_group_info",
 *  indexes={
 *  }
 * )
 *
 * @ORM\Entity
 */
class CGroup extends AbstractResource implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     * @Groups({"group:read", "group:write"})
     */
    protected $iid;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     * @Groups({"group:read", "group:write"})
     */
    protected $name;

    /**
     * @var bool
     * @Assert\NotBlank()
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    protected $status;

    /**
     * @var CGroupCategory
     *
     * @ORM\ManyToOne(targetEntity="CGroupCategory", cascade={"persist"})
     * @ORM\JoinColumn(name="category_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected $category;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="max_student", type="integer", nullable=false)
     */
    protected $maxStudent;

    /**
     * @var bool
     *
     * @ORM\Column(name="doc_state", type="boolean", nullable=false)
     */
    protected $docState;

    /**
     * @var bool
     *
     * @ORM\Column(name="calendar_state", type="boolean", nullable=false)
     */
    protected $calendarState;

    /**
     * @var bool
     *
     * @ORM\Column(name="work_state", type="boolean", nullable=false)
     */
    protected $workState;

    /**
     * @var bool
     *
     * @ORM\Column(name="announcements_state", type="boolean", nullable=false)
     */
    protected $announcementsState;

    /**
     * @var bool
     *
     * @ORM\Column(name="forum_state", type="boolean", nullable=false)
     */
    protected $forumState;

    /**
     * @var bool
     *
     * @ORM\Column(name="wiki_state", type="boolean", nullable=false)
     */
    protected $wikiState;

    /**
     * @var bool
     *
     * @ORM\Column(name="chat_state", type="boolean", nullable=false)
     */
    protected $chatState;

    /**
     * @var string
     *
     * @ORM\Column(name="secret_directory", type="string", length=255, nullable=true)
     */
    protected $secretDirectory;

    /**
     * @var bool
     *
     * @ORM\Column(name="self_registration_allowed", type="boolean", nullable=false)
     */
    protected $selfRegistrationAllowed;

    /**
     * @var bool
     *
     * @ORM\Column(name="self_unregistration_allowed", type="boolean", nullable=false)
     */
    protected $selfUnregistrationAllowed;

    /**
     * @var int
     *
     * @ORM\Column(name="document_access", type="integer", nullable=false, options={"default":0})
     */
    protected $documentAccess;

    /**
     * @var ArrayCollection|CGroupRelUser[]
     *
     * @ORM\OneToMany(targetEntity="CGroupRelUser", mappedBy="group")
     */
    protected $members;

    /**
     * @var ArrayCollection|CGroupRelTutor[]
     *
     * @ORM\OneToMany(targetEntity="CGroupRelTutor", mappedBy="group")
     */
    protected $tutors;

    public function __construct()
    {
        $this->status = 1;
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

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return (string) $this->name;
    }

    /**
     * Set status.
     *
     * @param bool $status
     */
    public function setStatus($status): self
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

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription($description): self
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

    public function setMaxStudent(int $maxStudent): self
    {
        $this->maxStudent = $maxStudent;

        return $this;
    }

    public function getMaxStudent(): int
    {
        return (int) $this->maxStudent;
    }

    /**
     * Set docState.
     *
     * @param bool $docState
     */
    public function setDocState($docState): self
    {
        $this->docState = $docState;

        return $this;
    }

    /**
     * Get docState.
     *
     * @return bool
     */
    public function getDocState()
    {
        return $this->docState;
    }

    /**
     * Set calendarState.
     *
     * @param bool $calendarState
     */
    public function setCalendarState($calendarState): self
    {
        $this->calendarState = $calendarState;

        return $this;
    }

    /**
     * Get calendarState.
     *
     * @return bool
     */
    public function getCalendarState()
    {
        return $this->calendarState;
    }

    /**
     * Set workState.
     *
     * @param bool $workState
     */
    public function setWorkState($workState): self
    {
        $this->workState = $workState;

        return $this;
    }

    /**
     * Get workState.
     *
     * @return bool
     */
    public function getWorkState()
    {
        return $this->workState;
    }

    /**
     * Set announcementsState.
     *
     * @param bool $announcementsState
     */
    public function setAnnouncementsState($announcementsState): self
    {
        $this->announcementsState = $announcementsState;

        return $this;
    }

    /**
     * Get announcementsState.
     *
     * @return bool
     */
    public function getAnnouncementsState()
    {
        return $this->announcementsState;
    }

    /**
     * Set forumState.
     *
     * @param bool $forumState
     */
    public function setForumState($forumState): self
    {
        $this->forumState = $forumState;

        return $this;
    }

    /**
     * Get forumState.
     *
     * @return bool
     */
    public function getForumState()
    {
        return $this->forumState;
    }

    /**
     * Set wikiState.
     *
     * @param bool $wikiState
     *
     * @return CGroup
     */
    public function setWikiState($wikiState)
    {
        $this->wikiState = $wikiState;

        return $this;
    }

    /**
     * Get wikiState.
     *
     * @return bool
     */
    public function getWikiState()
    {
        return $this->wikiState;
    }

    /**
     * Set chatState.
     *
     * @param bool $chatState
     *
     * @return CGroup
     */
    public function setChatState($chatState)
    {
        $this->chatState = $chatState;

        return $this;
    }

    /**
     * Get chatState.
     *
     * @return bool
     */
    public function getChatState()
    {
        return $this->chatState;
    }

    /**
     * Set secretDirectory.
     *
     * @param string $secretDirectory
     *
     * @return CGroup
     */
    public function setSecretDirectory($secretDirectory)
    {
        $this->secretDirectory = $secretDirectory;

        return $this;
    }

    public function getSecretDirectory(): string
    {
        return $this->secretDirectory;
    }

    /**
     * Set selfRegistrationAllowed.
     *
     * @param bool $selfRegistrationAllowed
     */
    public function setSelfRegistrationAllowed($selfRegistrationAllowed): self
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

    /**
     * Set selfUnregistrationAllowed.
     *
     * @param bool $selfUnregistrationAllowed
     */
    public function setSelfUnregistrationAllowed($selfUnregistrationAllowed): self
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

    public function getMembers()
    {
        return $this->members;
    }

    public function setMembers(ArrayCollection $members): self
    {
        $this->members = $members;

        return $this;
    }

    public function hasMembers(): bool
    {
        return $this->members->count() > 0;
    }

    public function getTutors()
    {
        return $this->tutors;
    }

    public function setTutors(ArrayCollection $tutors): self
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
        if (empty($user)) {
            return false;
        }

        if (0 === $this->tutors->count()) {
            return false;
        }

        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('cId', $this->course)
            )
            ->andWhere(
                Criteria::expr()->eq('user', $user)
            );

        $relation = $this->tutors->matching($criteria);

        return $relation->count() > 0;
    }

    public function getCategory(): CGroupCategory
    {
        return $this->category;
    }

    public function setCategory(CGroupCategory $category = null): CGroup
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
