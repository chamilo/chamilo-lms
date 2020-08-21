<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\CourseTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *  name="c_group_info",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 *
 * @ApiResource()
 * @ORM\Entity
 */
class CGroup extends AbstractResource implements ResourceInterface
{
    use CourseTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    protected $name;

    /**
     * @var bool
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    protected $status;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    protected $categoryId;

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
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="document_access", type="integer", nullable=false, options={"default":0})
     */
    protected $documentAccess;

    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="groups", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    protected $course;

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
     *
     * @return CGroup
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return (string) $this->name;
    }

    /**
     * Set status.
     *
     * @param bool $status
     *
     * @return CGroup
     */
    public function setStatus($status)
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
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return CGroup
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
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

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     */
    public function setSessionId($sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
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

    public function getMembers(): ArrayCollection
    {
        return $this->members;
    }

    public function setMembers(ArrayCollection $members): self
    {
        $this->members = $members;

        return $this;
    }

    public function getTutors(): ArrayCollection
    {
        return $this->tutors;
    }

    public function setTutors(ArrayCollection $tutors): self
    {
        $this->tutors = $tutors;

        return $this;
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

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }
}
