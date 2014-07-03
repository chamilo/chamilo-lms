<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CGroupCategory
 *
 * @ORM\Table(name="c_group_category")
 * @ORM\Entity
 */
class CGroupCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="doc_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $docState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="calendar_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $calendarState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="work_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $workState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="announcements_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $announcementsState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="forum_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $forumState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="wiki_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $wikiState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="chat_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $chatState;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_student", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $maxStudent;

    /**
     * @var boolean
     *
     * @ORM\Column(name="self_reg_allowed", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $selfRegAllowed;

    /**
     * @var boolean
     *
     * @ORM\Column(name="self_unreg_allowed", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $selfUnregAllowed;

    /**
     * @var integer
     *
     * @ORM\Column(name="groups_per_user", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $groupsPerUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="display_order", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayOrder;


    /**
     * Get iid
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CGroupCategory
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CGroupCategory
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return CGroupCategory
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return CGroupCategory
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set docState
     *
     * @param boolean $docState
     * @return CGroupCategory
     */
    public function setDocState($docState)
    {
        $this->docState = $docState;

        return $this;
    }

    /**
     * Get docState
     *
     * @return boolean
     */
    public function getDocState()
    {
        return $this->docState;
    }

    /**
     * Set calendarState
     *
     * @param boolean $calendarState
     * @return CGroupCategory
     */
    public function setCalendarState($calendarState)
    {
        $this->calendarState = $calendarState;

        return $this;
    }

    /**
     * Get calendarState
     *
     * @return boolean
     */
    public function getCalendarState()
    {
        return $this->calendarState;
    }

    /**
     * Set workState
     *
     * @param boolean $workState
     * @return CGroupCategory
     */
    public function setWorkState($workState)
    {
        $this->workState = $workState;

        return $this;
    }

    /**
     * Get workState
     *
     * @return boolean
     */
    public function getWorkState()
    {
        return $this->workState;
    }

    /**
     * Set announcementsState
     *
     * @param boolean $announcementsState
     * @return CGroupCategory
     */
    public function setAnnouncementsState($announcementsState)
    {
        $this->announcementsState = $announcementsState;

        return $this;
    }

    /**
     * Get announcementsState
     *
     * @return boolean
     */
    public function getAnnouncementsState()
    {
        return $this->announcementsState;
    }

    /**
     * Set forumState
     *
     * @param boolean $forumState
     * @return CGroupCategory
     */
    public function setForumState($forumState)
    {
        $this->forumState = $forumState;

        return $this;
    }

    /**
     * Get forumState
     *
     * @return boolean
     */
    public function getForumState()
    {
        return $this->forumState;
    }

    /**
     * Set wikiState
     *
     * @param boolean $wikiState
     * @return CGroupCategory
     */
    public function setWikiState($wikiState)
    {
        $this->wikiState = $wikiState;

        return $this;
    }

    /**
     * Get wikiState
     *
     * @return boolean
     */
    public function getWikiState()
    {
        return $this->wikiState;
    }

    /**
     * Set chatState
     *
     * @param boolean $chatState
     * @return CGroupCategory
     */
    public function setChatState($chatState)
    {
        $this->chatState = $chatState;

        return $this;
    }

    /**
     * Get chatState
     *
     * @return boolean
     */
    public function getChatState()
    {
        return $this->chatState;
    }

    /**
     * Set maxStudent
     *
     * @param integer $maxStudent
     * @return CGroupCategory
     */
    public function setMaxStudent($maxStudent)
    {
        $this->maxStudent = $maxStudent;

        return $this;
    }

    /**
     * Get maxStudent
     *
     * @return integer
     */
    public function getMaxStudent()
    {
        return $this->maxStudent;
    }

    /**
     * Set selfRegAllowed
     *
     * @param boolean $selfRegAllowed
     * @return CGroupCategory
     */
    public function setSelfRegAllowed($selfRegAllowed)
    {
        $this->selfRegAllowed = $selfRegAllowed;

        return $this;
    }

    /**
     * Get selfRegAllowed
     *
     * @return boolean
     */
    public function getSelfRegAllowed()
    {
        return $this->selfRegAllowed;
    }

    /**
     * Set selfUnregAllowed
     *
     * @param boolean $selfUnregAllowed
     * @return CGroupCategory
     */
    public function setSelfUnregAllowed($selfUnregAllowed)
    {
        $this->selfUnregAllowed = $selfUnregAllowed;

        return $this;
    }

    /**
     * Get selfUnregAllowed
     *
     * @return boolean
     */
    public function getSelfUnregAllowed()
    {
        return $this->selfUnregAllowed;
    }

    /**
     * Set groupsPerUser
     *
     * @param integer $groupsPerUser
     * @return CGroupCategory
     */
    public function setGroupsPerUser($groupsPerUser)
    {
        $this->groupsPerUser = $groupsPerUser;

        return $this;
    }

    /**
     * Get groupsPerUser
     *
     * @return integer
     */
    public function getGroupsPerUser()
    {
        return $this->groupsPerUser;
    }

    /**
     * Set displayOrder
     *
     * @param integer $displayOrder
     * @return CGroupCategory
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder
     *
     * @return integer
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }
}
