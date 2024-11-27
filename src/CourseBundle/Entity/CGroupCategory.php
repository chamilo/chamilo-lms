<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Repository\CGroupCategoryRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Group categories inside a course.
 */
#[ORM\Table(name: 'c_group_category')]
#[ORM\Entity(repositoryClass: CGroupCategoryRepository::class)]
class CGroupCategory extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: false)]
    protected ?string $description;

    #[ORM\Column(name: 'doc_state', type: 'boolean', nullable: false)]
    protected bool $docState;

    #[ORM\Column(name: 'calendar_state', type: 'boolean', nullable: false)]
    protected bool $calendarState;

    #[ORM\Column(name: 'work_state', type: 'boolean', nullable: false)]
    protected bool $workState;

    #[ORM\Column(name: 'announcements_state', type: 'boolean', nullable: false)]
    protected bool $announcementsState;

    #[ORM\Column(name: 'forum_state', type: 'boolean', nullable: false)]
    protected bool $forumState;

    #[ORM\Column(name: 'wiki_state', type: 'boolean', nullable: false)]
    protected bool $wikiState;

    #[ORM\Column(name: 'chat_state', type: 'boolean', nullable: false)]
    protected bool $chatState;

    #[ORM\Column(name: 'max_student', type: 'integer', nullable: false)]
    protected int $maxStudent;

    #[ORM\Column(name: 'self_reg_allowed', type: 'boolean', nullable: false)]
    protected bool $selfRegAllowed;

    #[ORM\Column(name: 'self_unreg_allowed', type: 'boolean', nullable: false)]
    protected bool $selfUnregAllowed;

    #[ORM\Column(name: 'groups_per_user', type: 'integer', nullable: false)]
    protected int $groupsPerUser;

    #[ORM\Column(name: 'document_access', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $documentAccess;

    #[ORM\Column(name: 'min_student', type: 'integer', nullable: true)]
    protected ?int $minStudent = null;

    #[ORM\Column(name: 'begin_inscription_date', type: 'datetime', nullable: true)]
    protected ?DateTime $beginInscriptionDate = null;

    #[ORM\Column(name: 'end_inscription_date', type: 'datetime', nullable: true)]
    protected ?DateTime $endInscriptionDate = null;

    #[ORM\Column(name: 'only_me', type: 'boolean', options: ['default' => 0])]
    protected bool $onlyMe = false;

    #[ORM\ManyToOne(targetEntity: CPeerAssessment::class)]
    #[ORM\JoinColumn(name: 'peer_assessment', referencedColumnName: 'id', nullable: true)]
    protected ?CPeerAssessment $peerAssessment = null;

    #[ORM\Column(name: 'allow_coach_change_options_groups', type: 'boolean', options: ['default' => 0])]
    protected bool $allowCoachChangeOptionsGroups = false;

    #[ORM\Column(name: 'allow_change_group_name', type: 'integer', nullable: true, options: ['default' => 1])]
    protected ?int $allowChangeGroupName = 1;

    #[ORM\Column(name: 'allow_autogroup', type: 'boolean', options: ['default' => 0])]
    protected bool $allowAutogroup = false;

    public function __construct()
    {
        $this->maxStudent = 0;
        $this->description = '';
        $this->selfRegAllowed = false;
        $this->selfUnregAllowed = false;
        $this->groupsPerUser = 0;
        $this->announcementsState = true;
        $this->calendarState = true;
        $this->documentAccess = 0;
        $this->chatState = true;
        $this->docState = true;
        $this->forumState = true;
        $this->wikiState = true;
        $this->workState = true;
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

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDocState(bool $docState): self
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

    public function setCalendarState(bool $calendarState): self
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

    public function setWorkState(bool $workState): self
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

    public function setAnnouncementsState(bool $announcementsState): self
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

    public function setForumState(bool $forumState): self
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

    public function setWikiState(bool $wikiState): self
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

    public function setChatState(bool $chatState): self
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

    public function setMaxStudent(int $maxStudent): self
    {
        $this->maxStudent = $maxStudent;

        return $this;
    }

    /**
     * Get maxStudent.
     *
     * @return int
     */
    public function getMaxStudent()
    {
        return $this->maxStudent;
    }

    public function setSelfRegAllowed(bool $selfRegAllowed): self
    {
        $this->selfRegAllowed = $selfRegAllowed;

        return $this;
    }

    /**
     * Get selfRegAllowed.
     *
     * @return bool
     */
    public function getSelfRegAllowed()
    {
        return $this->selfRegAllowed;
    }

    public function setSelfUnregAllowed(bool $selfUnregAllowed): self
    {
        $this->selfUnregAllowed = $selfUnregAllowed;

        return $this;
    }

    /**
     * Get selfUnregAllowed.
     *
     * @return bool
     */
    public function getSelfUnregAllowed()
    {
        return $this->selfUnregAllowed;
    }

    public function setGroupsPerUser(int $groupsPerUser): self
    {
        $this->groupsPerUser = $groupsPerUser;

        return $this;
    }

    /**
     * Get groupsPerUser.
     *
     * @return int
     */
    public function getGroupsPerUser()
    {
        return $this->groupsPerUser;
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

    public function getMinStudent(): ?int
    {
        return $this->minStudent;
    }

    public function setMinStudent(?int $minStudent): self
    {
        $this->minStudent = $minStudent;

        return $this;
    }

    public function getBeginInscriptionDate(): ?DateTime
    {
        return $this->beginInscriptionDate;
    }

    public function setBeginInscriptionDate(?DateTime $beginInscriptionDate): self
    {
        $this->beginInscriptionDate = $beginInscriptionDate;

        return $this;
    }

    public function getEndInscriptionDate(): ?DateTime
    {
        return $this->endInscriptionDate;
    }

    public function setEndInscriptionDate(?DateTime $endInscriptionDate): self
    {
        $this->endInscriptionDate = $endInscriptionDate;

        return $this;
    }

    public function getOnlyMe(): bool
    {
        return $this->onlyMe;
    }

    public function setOnlyMe(bool $onlyMe): self
    {
        $this->onlyMe = $onlyMe;

        return $this;
    }

    public function getPeerAssessment(): ?CPeerAssessment
    {
        return $this->peerAssessment;
    }

    public function setPeerAssessment(?CPeerAssessment $peerAssessment): self
    {
        $this->peerAssessment = $peerAssessment;

        return $this;
    }

    public function getAllowCoachChangeOptionsGroups(): bool
    {
        return $this->allowCoachChangeOptionsGroups;
    }

    public function setAllowCoachChangeOptionsGroups(bool $allowCoachChangeOptionsGroups): self
    {
        $this->allowCoachChangeOptionsGroups = $allowCoachChangeOptionsGroups;

        return $this;
    }

    public function getAllowChangeGroupName(): ?int
    {
        return $this->allowChangeGroupName;
    }

    public function setAllowChangeGroupName(?int $allowChangeGroupName): self
    {
        $this->allowChangeGroupName = $allowChangeGroupName;

        return $this;
    }

    public function getAllowAutogroup(): bool
    {
        return $this->allowAutogroup;
    }

    public function setAllowAutogroup(bool $allowAutogroup): self
    {
        $this->allowAutogroup = $allowAutogroup;

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
