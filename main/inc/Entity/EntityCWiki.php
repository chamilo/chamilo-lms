<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCWiki
 *
 * @Table(name="c_wiki")
 * @Entity
 */
class EntityCWiki
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
     * @var integer
     *
     * @Column(name="page_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $pageId;

    /**
     * @var string
     *
     * @Column(name="reflink", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $reflink;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="content", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $content;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="group_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $groupId;

    /**
     * @var \DateTime
     *
     * @Column(name="dtime", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dtime;

    /**
     * @var integer
     *
     * @Column(name="addlock", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $addlock;

    /**
     * @var integer
     *
     * @Column(name="editlock", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $editlock;

    /**
     * @var integer
     *
     * @Column(name="visibility", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @Column(name="addlock_disc", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $addlockDisc;

    /**
     * @var integer
     *
     * @Column(name="visibility_disc", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibilityDisc;

    /**
     * @var integer
     *
     * @Column(name="ratinglock_disc", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $ratinglockDisc;

    /**
     * @var integer
     *
     * @Column(name="assignment", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $assignment;

    /**
     * @var string
     *
     * @Column(name="comment", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $comment;

    /**
     * @var string
     *
     * @Column(name="progress", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $progress;

    /**
     * @var integer
     *
     * @Column(name="score", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $score;

    /**
     * @var integer
     *
     * @Column(name="version", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $version;

    /**
     * @var integer
     *
     * @Column(name="is_editing", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $isEditing;

    /**
     * @var \DateTime
     *
     * @Column(name="time_edit", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $timeEdit;

    /**
     * @var integer
     *
     * @Column(name="hits", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $hits;

    /**
     * @var string
     *
     * @Column(name="linksto", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $linksto;

    /**
     * @var string
     *
     * @Column(name="tag", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $tag;

    /**
     * @var string
     *
     * @Column(name="user_ip", type="string", length=39, precision=0, scale=0, nullable=false, unique=false)
     */
    private $userIp;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCWiki
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
     * @return EntityCWiki
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
     * Set pageId
     *
     * @param integer $pageId
     * @return EntityCWiki
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Get pageId
     *
     * @return integer 
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Set reflink
     *
     * @param string $reflink
     * @return EntityCWiki
     */
    public function setReflink($reflink)
    {
        $this->reflink = $reflink;

        return $this;
    }

    /**
     * Get reflink
     *
     * @return string 
     */
    public function getReflink()
    {
        return $this->reflink;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityCWiki
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
     * Set content
     *
     * @param string $content
     * @return EntityCWiki
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return EntityCWiki
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return EntityCWiki
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return integer 
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set dtime
     *
     * @param \DateTime $dtime
     * @return EntityCWiki
     */
    public function setDtime($dtime)
    {
        $this->dtime = $dtime;

        return $this;
    }

    /**
     * Get dtime
     *
     * @return \DateTime 
     */
    public function getDtime()
    {
        return $this->dtime;
    }

    /**
     * Set addlock
     *
     * @param integer $addlock
     * @return EntityCWiki
     */
    public function setAddlock($addlock)
    {
        $this->addlock = $addlock;

        return $this;
    }

    /**
     * Get addlock
     *
     * @return integer 
     */
    public function getAddlock()
    {
        return $this->addlock;
    }

    /**
     * Set editlock
     *
     * @param integer $editlock
     * @return EntityCWiki
     */
    public function setEditlock($editlock)
    {
        $this->editlock = $editlock;

        return $this;
    }

    /**
     * Get editlock
     *
     * @return integer 
     */
    public function getEditlock()
    {
        return $this->editlock;
    }

    /**
     * Set visibility
     *
     * @param integer $visibility
     * @return EntityCWiki
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return integer 
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set addlockDisc
     *
     * @param integer $addlockDisc
     * @return EntityCWiki
     */
    public function setAddlockDisc($addlockDisc)
    {
        $this->addlockDisc = $addlockDisc;

        return $this;
    }

    /**
     * Get addlockDisc
     *
     * @return integer 
     */
    public function getAddlockDisc()
    {
        return $this->addlockDisc;
    }

    /**
     * Set visibilityDisc
     *
     * @param integer $visibilityDisc
     * @return EntityCWiki
     */
    public function setVisibilityDisc($visibilityDisc)
    {
        $this->visibilityDisc = $visibilityDisc;

        return $this;
    }

    /**
     * Get visibilityDisc
     *
     * @return integer 
     */
    public function getVisibilityDisc()
    {
        return $this->visibilityDisc;
    }

    /**
     * Set ratinglockDisc
     *
     * @param integer $ratinglockDisc
     * @return EntityCWiki
     */
    public function setRatinglockDisc($ratinglockDisc)
    {
        $this->ratinglockDisc = $ratinglockDisc;

        return $this;
    }

    /**
     * Get ratinglockDisc
     *
     * @return integer 
     */
    public function getRatinglockDisc()
    {
        return $this->ratinglockDisc;
    }

    /**
     * Set assignment
     *
     * @param integer $assignment
     * @return EntityCWiki
     */
    public function setAssignment($assignment)
    {
        $this->assignment = $assignment;

        return $this;
    }

    /**
     * Get assignment
     *
     * @return integer 
     */
    public function getAssignment()
    {
        return $this->assignment;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return EntityCWiki
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set progress
     *
     * @param string $progress
     * @return EntityCWiki
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress
     *
     * @return string 
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set score
     *
     * @param integer $score
     * @return EntityCWiki
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return integer 
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return EntityCWiki
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set isEditing
     *
     * @param integer $isEditing
     * @return EntityCWiki
     */
    public function setIsEditing($isEditing)
    {
        $this->isEditing = $isEditing;

        return $this;
    }

    /**
     * Get isEditing
     *
     * @return integer 
     */
    public function getIsEditing()
    {
        return $this->isEditing;
    }

    /**
     * Set timeEdit
     *
     * @param \DateTime $timeEdit
     * @return EntityCWiki
     */
    public function setTimeEdit($timeEdit)
    {
        $this->timeEdit = $timeEdit;

        return $this;
    }

    /**
     * Get timeEdit
     *
     * @return \DateTime 
     */
    public function getTimeEdit()
    {
        return $this->timeEdit;
    }

    /**
     * Set hits
     *
     * @param integer $hits
     * @return EntityCWiki
     */
    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get hits
     *
     * @return integer 
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Set linksto
     *
     * @param string $linksto
     * @return EntityCWiki
     */
    public function setLinksto($linksto)
    {
        $this->linksto = $linksto;

        return $this;
    }

    /**
     * Get linksto
     *
     * @return string 
     */
    public function getLinksto()
    {
        return $this->linksto;
    }

    /**
     * Set tag
     *
     * @param string $tag
     * @return EntityCWiki
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return string 
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set userIp
     *
     * @param string $userIp
     * @return EntityCWiki
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get userIp
     *
     * @return string 
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCWiki
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
