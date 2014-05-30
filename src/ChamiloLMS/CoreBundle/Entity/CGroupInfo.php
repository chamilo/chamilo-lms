<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CGroupInfo
 *
 * @ORM\Table(name="c_group_info")
 * @ORM\Entity
 */
class CGroupInfo
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

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
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $categoryId;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_student", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $maxStudent;

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
     * @var string
     *
     * @ORM\Column(name="secret_directory", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $secretDirectory;

    /**
     * @var boolean
     *
     * @ORM\Column(name="self_registration_allowed", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $selfRegistrationAllowed;

    /**
     * @var boolean
     *
     * @ORM\Column(name="self_unregistration_allowed", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $selfUnregistrationAllowed;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


    /**
     *  @ORM\OneToMany(targetEntity="CItemProperty", mappedBy="group")
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
     * @return ArrayCollection
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
