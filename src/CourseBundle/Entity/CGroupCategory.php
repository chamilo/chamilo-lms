<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CGroupCategory.
 *
 * @ORM\Table(
 *  name="c_group_category",
 *  indexes={
 *  }
 * )
 * @ORM\Entity
 */
class CGroupCategory extends AbstractResource implements ResourceInterface
{
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
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected $description;

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
     * @var int
     *
     * @ORM\Column(name="max_student", type="integer", nullable=false)
     */
    protected $maxStudent;

    /**
     * @var bool
     *
     * @ORM\Column(name="self_reg_allowed", type="boolean", nullable=false)
     */
    protected $selfRegAllowed;

    /**
     * @var bool
     *
     * @ORM\Column(name="self_unreg_allowed", type="boolean", nullable=false)
     */
    protected $selfUnregAllowed;

    /**
     * @var int
     *
     * @ORM\Column(name="groups_per_user", type="integer", nullable=false)
     */
    protected $groupsPerUser;

    /**
     * @var int
     *
     * @ORM\Column(name="document_access", type="integer", nullable=false, options={"default":0})
     */
    protected $documentAccess;

    public function __construct()
    {
        $this->maxStudent = 0;
        $this->description = '';
        $this->selfRegAllowed = 0;
        $this->selfUnregAllowed = 0;
        $this->groupsPerUser = 0;
        $this->announcementsState = 1;
        $this->calendarState = 1;
        $this->documentAccess = 1;
        $this->chatState = 1;
        $this->docState = 1;
        $this->forumState = 1;
        $this->wikiState = 1;
        $this->workState = 1;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CGroupCategory
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return (string) $this->title;
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

    /**
     * Set docState.
     *
     * @param bool $docState
     *
     * @return CGroupCategory
     */
    public function setDocState($docState)
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
     *
     * @return CGroupCategory
     */
    public function setCalendarState($calendarState)
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
     *
     * @return CGroupCategory
     */
    public function setWorkState($workState)
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
     *
     * @return CGroupCategory
     */
    public function setAnnouncementsState($announcementsState)
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
     *
     * @return CGroupCategory
     */
    public function setForumState($forumState)
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
     * @return CGroupCategory
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
     * @return CGroupCategory
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
     * Set maxStudent.
     *
     * @param int $maxStudent
     *
     * @return CGroupCategory
     */
    public function setMaxStudent($maxStudent)
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

    /**
     * Set selfRegAllowed.
     *
     * @param bool $selfRegAllowed
     *
     * @return CGroupCategory
     */
    public function setSelfRegAllowed($selfRegAllowed)
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

    /**
     * Set selfUnregAllowed.
     *
     * @param bool $selfUnregAllowed
     *
     * @return CGroupCategory
     */
    public function setSelfUnregAllowed($selfUnregAllowed)
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

    /**
     * Set groupsPerUser.
     *
     * @param int $groupsPerUser
     *
     * @return CGroupCategory
     */
    public function setGroupsPerUser($groupsPerUser)
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

    public function getResourceIdentifier(): int
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
