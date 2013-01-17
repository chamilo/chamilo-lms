<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCGroupCategory
 *
 * @Table(name="c_group_category")
 * @Entity
 */
class EntityCGroupCategory
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $description;

    /**
     * @var boolean
     *
     * @Column(name="doc_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $docState;

    /**
     * @var boolean
     *
     * @Column(name="calendar_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $calendarState;

    /**
     * @var boolean
     *
     * @Column(name="work_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $workState;

    /**
     * @var boolean
     *
     * @Column(name="announcements_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $announcementsState;

    /**
     * @var boolean
     *
     * @Column(name="forum_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $forumState;

    /**
     * @var boolean
     *
     * @Column(name="wiki_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $wikiState;

    /**
     * @var boolean
     *
     * @Column(name="chat_state", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $chatState;

    /**
     * @var integer
     *
     * @Column(name="max_student", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $maxStudent;

    /**
     * @var boolean
     *
     * @Column(name="self_reg_allowed", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $selfRegAllowed;

    /**
     * @var boolean
     *
     * @Column(name="self_unreg_allowed", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $selfUnregAllowed;

    /**
     * @var integer
     *
     * @Column(name="groups_per_user", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $groupsPerUser;

    /**
     * @var integer
     *
     * @Column(name="display_order", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayOrder;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
     * @return EntityCGroupCategory
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
