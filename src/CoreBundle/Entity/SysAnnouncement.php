<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SysAnnouncement.
 *
 * @ORM\Table(name="sys_announcement")
 * @ORM\Entity
 */
class SysAnnouncement
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\Column(name="date_start", type="datetime", nullable=false)
     */
    protected DateTime $dateStart;

    /**
     * @ORM\Column(name="date_end", type="datetime", nullable=false)
     */
    protected DateTime $dateEnd;

    /**
     * @ORM\Column(name="visible_teacher", type="boolean", nullable=false)
     */
    protected bool $visibleTeacher;

    /**
     * @ORM\Column(name="visible_student", type="boolean", nullable=false)
     */
    protected bool $visibleStudent;

    /**
     * @ORM\Column(name="visible_guest", type="boolean", nullable=false)
     */
    protected bool $visibleGuest;

    /**
     * @ORM\Column(name="visible_drh", type="boolean", nullable=false)
     */
    protected bool $visibleDrh;

    /**
     * @ORM\Column(name="visible_session_admin", type="boolean", nullable=false)
     */
    protected bool $visibleSessionAdmin;

    /**
     * @ORM\Column(name="visible_boss", type="boolean", nullable=false)
     */
    protected bool $visibleBoss;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=false)
     */
    protected string $title;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    protected string $content;

    /**
     * @ORM\Column(name="lang", type="string", length=70, nullable=true)
     */
    protected ?string $lang;

    /**
     * @ORM\Column(name="access_url_id", type="integer", nullable=false)
     */
    protected int $accessUrlId;

    /**
     * @ORM\Column(name="career_id", type="integer", nullable=true)
     */
    protected ?int $careerId;

    /**
     * @ORM\Column(name="promotion_id", type="integer", nullable=true)
     */
    protected ?int $promotionId;

    public function __construct()
    {
        $this->visibleBoss = false;
        $this->visibleDrh = false;
        $this->visibleGuest = false;
        $this->visibleSessionAdmin = false;
        $this->visibleStudent = false;
        $this->visibleTeacher = false;
        $this->careerId = 0;
        $this->promotionId = 0;
    }

    /**
     * Set dateStart.
     *
     * @param DateTime $dateStart
     *
     * @return SysAnnouncement
     */
    public function setDateStart($dateStart)
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * Get dateStart.
     *
     * @return DateTime
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * Set dateEnd.
     *
     * @param DateTime $dateEnd
     *
     * @return SysAnnouncement
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * Get dateEnd.
     *
     * @return DateTime
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * Set visibleTeacher.
     *
     * @param bool $visibleTeacher
     *
     * @return SysAnnouncement
     */
    public function setVisibleTeacher($visibleTeacher)
    {
        $this->visibleTeacher = $visibleTeacher;

        return $this;
    }

    /**
     * Get visibleTeacher.
     *
     * @return bool
     */
    public function getVisibleTeacher()
    {
        return $this->visibleTeacher;
    }

    /**
     * Set visibleStudent.
     *
     * @param bool $visibleStudent
     *
     * @return SysAnnouncement
     */
    public function setVisibleStudent($visibleStudent)
    {
        $this->visibleStudent = $visibleStudent;

        return $this;
    }

    /**
     * Get visibleStudent.
     *
     * @return bool
     */
    public function getVisibleStudent()
    {
        return $this->visibleStudent;
    }

    /**
     * Set visibleGuest.
     *
     * @param bool $visibleGuest
     *
     * @return SysAnnouncement
     */
    public function setVisibleGuest($visibleGuest)
    {
        $this->visibleGuest = $visibleGuest;

        return $this;
    }

    /**
     * Get visibleGuest.
     *
     * @return bool
     */
    public function getVisibleGuest()
    {
        return $this->visibleGuest;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return SysAnnouncement
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
     * Set content.
     *
     * @param string $content
     *
     * @return SysAnnouncement
     */
    public function setContent($content)
    {
        $this->content = $content;

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
     * Set lang.
     *
     * @param string $lang
     *
     * @return SysAnnouncement
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang.
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set accessUrlId.
     *
     * @param int $accessUrlId
     *
     * @return SysAnnouncement
     */
    public function setAccessUrlId($accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId.
     *
     * @return int
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
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

    public function isVisibleDrh(): bool
    {
        return $this->visibleDrh;
    }

    public function setVisibleDrh(bool $visibleDrh): self
    {
        $this->visibleDrh = $visibleDrh;

        return $this;
    }

    public function isVisibleSessionAdmin(): bool
    {
        return $this->visibleSessionAdmin;
    }

    public function setVisibleSessionAdmin(
        bool $visibleSessionAdmin
    ): self {
        $this->visibleSessionAdmin = $visibleSessionAdmin;

        return $this;
    }

    public function isVisibleBoss(): bool
    {
        return $this->visibleBoss;
    }

    public function setVisibleBoss(bool $visibleBoss): self
    {
        $this->visibleBoss = $visibleBoss;

        return $this;
    }
}
