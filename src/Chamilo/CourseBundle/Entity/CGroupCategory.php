<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CGroupCategory.
 *
 * @ORM\Table(
 *  name="c_group_category",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CGroupCategory
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
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var string
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
     * @ORM\Column(name="display_order", type="integer", nullable=false)
     */
    protected $displayOrder;

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
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CGroupCategory
     */
    public function setDescription($description)
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

    /**
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return CGroupCategory
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CGroupCategory
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CGroupCategory
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }
}
