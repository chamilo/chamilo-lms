<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CWiki
 *
 * @ORM\Table(
 *  name="c_wiki",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="reflink", columns={"reflink"}),
 *      @ORM\Index(name="group_id", columns={"group_id"}),
 *      @ORM\Index(name="page_id", columns={"page_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CWiki
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="page_id", type="integer", nullable=true)
     */
    private $pageId;

    /**
     * @var string
     *
     * @ORM\Column(name="reflink", type="string", length=255, nullable=false)
     */
    private $reflink;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="integer", nullable=true)
     */
    private $groupId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dtime", type="datetime", nullable=true)
     */
    private $dtime;

    /**
     * @var integer
     *
     * @ORM\Column(name="addlock", type="integer", nullable=false)
     */
    private $addlock;

    /**
     * @var integer
     *
     * @ORM\Column(name="editlock", type="integer", nullable=false)
     */
    private $editlock;

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility", type="integer", nullable=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @ORM\Column(name="addlock_disc", type="integer", nullable=false)
     */
    private $addlockDisc;

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility_disc", type="integer", nullable=false)
     */
    private $visibilityDisc;

    /**
     * @var integer
     *
     * @ORM\Column(name="ratinglock_disc", type="integer", nullable=false)
     */
    private $ratinglockDisc;

    /**
     * @var integer
     *
     * @ORM\Column(name="assignment", type="integer", nullable=false)
     */
    private $assignment;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=false)
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="progress", type="text", nullable=false)
     */
    private $progress;

    /**
     * @var integer
     *
     * @ORM\Column(name="score", type="integer", nullable=true)
     */
    private $score;

    /**
     * @var integer
     *
     * @ORM\Column(name="version", type="integer", nullable=true)
     */
    private $version;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_editing", type="integer", nullable=false)
     */
    private $isEditing;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_edit", type="datetime", nullable=true)
     */
    private $timeEdit;

    /**
     * @var integer
     *
     * @ORM\Column(name="hits", type="integer", nullable=true)
     */
    private $hits;

    /**
     * @var string
     *
     * @ORM\Column(name="linksto", type="text", nullable=false)
     */
    private $linksto;

    /**
     * @var string
     *
     * @ORM\Column(name="tag", type="text", nullable=false)
     */
    private $tag;

    /**
     * @var string
     *
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    private $userIp;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    private $sessionId;

    /**
     * Set pageId
     *
     * @param integer $pageId
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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
     * @return CWiki
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

    /**
     * Set id
     *
     * @param integer $id
     * @return CWiki
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
     * Set cId
     *
     * @param integer $cId
     * @return CWiki
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
}
