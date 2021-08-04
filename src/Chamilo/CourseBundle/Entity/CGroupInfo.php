<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CGroupInfo.
 *
 * @ORM\Table(
 *  name="c_group_info",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CGroupInfo
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
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var string
     *
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
     * @ORM\Column(name="category_id", type="integer", nullable=false)
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
     * @var int needed for setting['group_document_access']
     *
     * ORM\Column(name="doc_access", type="integer", nullable=false, options={"default":0})
     */
    //protected $docAccess;

    /**
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
     * @return CGroupInfo
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
        return $this->name;
    }

    /**
     * Set status.
     *
     * @param bool $status
     *
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     *
     * @return CGroupInfo
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
     * Set maxStudent.
     *
     * @param int $maxStudent
     *
     * @return CGroupInfo
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
     * Set docState.
     *
     * @param bool $docState
     *
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
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
     * @return CGroupInfo
     */
    public function setSecretDirectory($secretDirectory)
    {
        $this->secretDirectory = $secretDirectory;

        return $this;
    }

    /**
     * Get secretDirectory.
     *
     * @return string
     */
    public function getSecretDirectory()
    {
        return $this->secretDirectory;
    }

    /**
     * Set selfRegistrationAllowed.
     *
     * @param bool $selfRegistrationAllowed
     *
     * @return CGroupInfo
     */
    public function setSelfRegistrationAllowed($selfRegistrationAllowed)
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
     *
     * @return CGroupInfo
     */
    public function setSelfUnregistrationAllowed($selfUnregistrationAllowed)
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
     *
     * @return CGroupInfo
     */
    public function setSessionId($sessionId)
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

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CGroupInfo
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
     * @return CGroupInfo
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
