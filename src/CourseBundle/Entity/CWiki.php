<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CWiki.
 *
 * @ORM\Table(
 *     name="c_wiki",
 *     options={"row_format":"DYNAMIC"},
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="reflink", columns={"reflink"}),
 *         @ORM\Index(name="group_id", columns={"group_id"}),
 *         @ORM\Index(name="page_id", columns={"page_id"}),
 *         @ORM\Index(name="session_id", columns={"session_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Repository\CWikiRepository")
 */
class CWiki extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="page_id", type="integer", nullable=true)
     */
    protected ?int $pageId = null;

    /**
     * @ORM\Column(name="reflink", type="string", length=255, nullable=false)
     */
    protected string $reflink;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    protected string $content;

    /**
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected int $userId;

    /**
     * @ORM\Column(name="group_id", type="integer", nullable=true)
     */
    protected ?int $groupId = null;

    /**
     * @ORM\Column(name="dtime", type="datetime", nullable=true)
     */
    protected ?DateTime $dtime = null;

    /**
     * @ORM\Column(name="addlock", type="integer", nullable=false)
     */
    protected int $addlock;

    /**
     * @ORM\Column(name="editlock", type="integer", nullable=false)
     */
    protected int $editlock;

    /**
     * @ORM\Column(name="visibility", type="integer", nullable=false)
     */
    protected int $visibility;

    /**
     * @ORM\Column(name="addlock_disc", type="integer", nullable=false)
     */
    protected int $addlockDisc;

    /**
     * @ORM\Column(name="visibility_disc", type="integer", nullable=false)
     */
    protected int $visibilityDisc;

    /**
     * @ORM\Column(name="ratinglock_disc", type="integer", nullable=false)
     */
    protected int $ratinglockDisc;

    /**
     * @ORM\Column(name="assignment", type="integer", nullable=false)
     */
    protected int $assignment;

    /**
     * @ORM\Column(name="comment", type="text", nullable=false)
     */
    protected string $comment;

    /**
     * @ORM\Column(name="progress", type="text", nullable=false)
     */
    protected string $progress;

    /**
     * @ORM\Column(name="score", type="integer", nullable=true)
     */
    protected ?int $score = null;

    /**
     * @ORM\Column(name="version", type="integer", nullable=true)
     */
    protected ?int $version = null;

    /**
     * @ORM\Column(name="is_editing", type="integer", nullable=false)
     */
    protected int $isEditing;

    /**
     * @ORM\Column(name="time_edit", type="datetime", nullable=true)
     */
    protected ?DateTime $timeEdit = null;

    /**
     * @ORM\Column(name="hits", type="integer", nullable=true)
     */
    protected ?int $hits = null;

    /**
     * @ORM\Column(name="linksto", type="text", nullable=false)
     */
    protected string $linksto;

    /**
     * @ORM\Column(name="tag", type="text", nullable=false)
     */
    protected string $tag;

    /**
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    protected string $userIp;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected ?int $sessionId = null;

    public function __toString(): string
    {
        return $this->getTitle();
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
     * Set title.
     *
     * @return CWiki
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get pageId.
     *
     * @return int
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Set pageId.
     *
     * @return CWiki
     */
    public function setPageId(int $pageId)
    {
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Get reflink.
     *
     * @return string
     */
    public function getReflink()
    {
        return $this->reflink;
    }

    /**
     * Set reflink.
     *
     * @return CWiki
     */
    public function setReflink(string $reflink)
    {
        $this->reflink = $reflink;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set content.
     *
     * @return CWiki
     */
    public function setContent(string $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set userId.
     *
     * @return CWiki
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set groupId.
     *
     * @return CWiki
     */
    public function setGroupId(int $groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get dtime.
     *
     * @return DateTime
     */
    public function getDtime()
    {
        return $this->dtime;
    }

    /**
     * Set dtime.
     *
     * @return CWiki
     */
    public function setDtime(DateTime $dtime)
    {
        $this->dtime = $dtime;

        return $this;
    }

    /**
     * Get addlock.
     *
     * @return int
     */
    public function getAddlock()
    {
        return $this->addlock;
    }

    /**
     * Set addlock.
     *
     * @return CWiki
     */
    public function setAddlock(int $addlock)
    {
        $this->addlock = $addlock;

        return $this;
    }

    /**
     * Get editlock.
     *
     * @return int
     */
    public function getEditlock()
    {
        return $this->editlock;
    }

    /**
     * Set editlock.
     *
     * @return CWiki
     */
    public function setEditlock(int $editlock)
    {
        $this->editlock = $editlock;

        return $this;
    }

    /**
     * Get visibility.
     *
     * @return int
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set visibility.
     *
     * @return CWiki
     */
    public function setVisibility(int $visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get addlockDisc.
     *
     * @return int
     */
    public function getAddlockDisc()
    {
        return $this->addlockDisc;
    }

    /**
     * Set addlockDisc.
     *
     * @return CWiki
     */
    public function setAddlockDisc(int $addlockDisc)
    {
        $this->addlockDisc = $addlockDisc;

        return $this;
    }

    /**
     * Get visibilityDisc.
     *
     * @return int
     */
    public function getVisibilityDisc()
    {
        return $this->visibilityDisc;
    }

    /**
     * Set visibilityDisc.
     *
     * @return CWiki
     */
    public function setVisibilityDisc(int $visibilityDisc)
    {
        $this->visibilityDisc = $visibilityDisc;

        return $this;
    }

    /**
     * Get ratinglockDisc.
     *
     * @return int
     */
    public function getRatinglockDisc()
    {
        return $this->ratinglockDisc;
    }

    /**
     * Set ratinglockDisc.
     *
     * @return CWiki
     */
    public function setRatinglockDisc(int $ratinglockDisc)
    {
        $this->ratinglockDisc = $ratinglockDisc;

        return $this;
    }

    /**
     * Get assignment.
     *
     * @return int
     */
    public function getAssignment()
    {
        return $this->assignment;
    }

    /**
     * Set assignment.
     *
     * @return CWiki
     */
    public function setAssignment(int $assignment)
    {
        $this->assignment = $assignment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set comment.
     *
     * @return CWiki
     */
    public function setComment(string $comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get progress.
     *
     * @return string
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set progress.
     *
     * @return CWiki
     */
    public function setProgress(string $progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get score.
     *
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set score.
     *
     * @return CWiki
     */
    public function setScore(int $score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set version.
     *
     * @return CWiki
     */
    public function setVersion(int $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get isEditing.
     *
     * @return int
     */
    public function getIsEditing()
    {
        return $this->isEditing;
    }

    /**
     * Set isEditing.
     *
     * @return CWiki
     */
    public function setIsEditing(int $isEditing)
    {
        $this->isEditing = $isEditing;

        return $this;
    }

    /**
     * Get timeEdit.
     *
     * @return DateTime
     */
    public function getTimeEdit()
    {
        return $this->timeEdit;
    }

    /**
     * Set timeEdit.
     *
     * @return CWiki
     */
    public function setTimeEdit(DateTime $timeEdit)
    {
        $this->timeEdit = $timeEdit;

        return $this;
    }

    /**
     * Get hits.
     *
     * @return int
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Set hits.
     *
     * @return CWiki
     */
    public function setHits(int $hits)
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get linksto.
     *
     * @return string
     */
    public function getLinksto()
    {
        return $this->linksto;
    }

    /**
     * Set linksto.
     *
     * @return CWiki
     */
    public function setLinksto(string $linksto)
    {
        $this->linksto = $linksto;

        return $this;
    }

    /**
     * Get tag.
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set tag.
     *
     * @return CWiki
     */
    public function setTag(string $tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get userIp.
     *
     * @return string
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Set userIp.
     *
     * @return CWiki
     */
    public function setUserIp(string $userIp)
    {
        $this->userIp = $userIp;

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
     * Set sessionId.
     *
     * @return CWiki
     */
    public function setSessionId(int $sessionId)
    {
        $this->sessionId = $sessionId;

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

    /**
     * Set cId.
     *
     * @return CWiki
     */
    public function setCId(int $cId)
    {
        $this->cId = $cId;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getIid(): int
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
