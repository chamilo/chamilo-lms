<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * EntityCGroupInfo
 *
 * @Table(name="c_group_info")
 * @Entity
 */
class EntityCGroupInfo
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $iid;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="name", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @Column(name="category_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $categoryId;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var integer
     *
     * @Column(name="max_student", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $maxStudent;

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
     * @var string
     *
     * @Column(name="secret_directory", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $secretDirectory;

    /**
     * @var boolean
     *
     * @Column(name="self_registration_allowed", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $selfRegistrationAllowed;

    /**
     * @var boolean
     *
     * @Column(name="self_unregistration_allowed", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $selfUnregistrationAllowed;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


    /**
     * @OneToMany(targetEntity="EntityCItemProperty", mappedBy="group")
     **/
    private $items;

    /**
     *
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     *
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     *
     * @return EntityCGroupInfo
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
     * Set iid
     *
     * @param integer $id
     * @return EntityCGroupInfo
     */
    public function setIid($iid)
    {
        $this->iid = $iid;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCGroupInfo
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
     * Set name
     *
     * @param string $name
     * @return EntityCGroupInfo
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     * @return EntityCGroupInfo
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EntityCGroupInfo
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
     * Set maxStudent
     *
     * @param integer $maxStudent
     * @return EntityCGroupInfo
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
     * Set docState
     *
     * @param boolean $docState
     * @return EntityCGroupInfo
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
     * @return EntityCGroupInfo
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
     * @return EntityCGroupInfo
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
     * @return EntityCGroupInfo
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
     * @return EntityCGroupInfo
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
     * @return EntityCGroupInfo
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
     * @return EntityCGroupInfo
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
     * Set secretDirectory
     *
     * @param string $secretDirectory
     * @return EntityCGroupInfo
     */
    public function setSecretDirectory($secretDirectory)
    {
        $this->secretDirectory = $secretDirectory;

        return $this;
    }

    /**
     * Get secretDirectory
     *
     * @return string
     */
    public function getSecretDirectory()
    {
        return $this->secretDirectory;
    }

    /**
     * Set selfRegistrationAllowed
     *
     * @param boolean $selfRegistrationAllowed
     * @return EntityCGroupInfo
     */
    public function setSelfRegistrationAllowed($selfRegistrationAllowed)
    {
        $this->selfRegistrationAllowed = $selfRegistrationAllowed;

        return $this;
    }

    /**
     * Get selfRegistrationAllowed
     *
     * @return boolean
     */
    public function getSelfRegistrationAllowed()
    {
        return $this->selfRegistrationAllowed;
    }

    /**
     * Set selfUnregistrationAllowed
     *
     * @param boolean $selfUnregistrationAllowed
     * @return EntityCGroupInfo
     */
    public function setSelfUnregistrationAllowed($selfUnregistrationAllowed)
    {
        $this->selfUnregistrationAllowed = $selfUnregistrationAllowed;

        return $this;
    }

    /**
     * Get selfUnregistrationAllowed
     *
     * @return boolean
     */
    public function getSelfUnregistrationAllowed()
    {
        return $this->selfUnregistrationAllowed;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCGroupInfo
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
