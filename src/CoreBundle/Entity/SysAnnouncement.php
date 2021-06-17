<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Portal announcements.
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
    protected ?string $lang = null;

    /**
     * @ORM\ManyToOne(targetEntity="AccessUrl")
     * @ORM\JoinColumn(name="access_url_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected AccessUrl $url;

    /**
     * An array of roles. Example: ROLE_USER, ROLE_TEACHER, ROLE_ADMIN.
     *
     * @ORM\Column(type="array")
     *
     * @var string[]
     */
    protected array $roles = [];

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Career")
     * @ORM\JoinColumn(name="career_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Career $career = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Promotion")
     * @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Promotion $promotion = null;

    public function __construct()
    {
        $this->roles = [];
        $this->visibleBoss = false;
        $this->visibleDrh = false;
        $this->visibleGuest = false;
        $this->visibleSessionAdmin = false;
        $this->visibleStudent = false;
        $this->visibleTeacher = false;
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

    public function setDateStart(DateTime $dateStart): self
    {
        $this->dateStart = $dateStart;

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

    public function setDateEnd(DateTime $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

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

    public function setVisibleTeacher(bool $visibleTeacher): self
    {
        $this->visibleTeacher = $visibleTeacher;

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

    public function setVisibleStudent(bool $visibleStudent): self
    {
        $this->visibleStudent = $visibleStudent;

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

    public function setVisibleGuest(bool $visibleGuest): self
    {
        $this->visibleGuest = $visibleGuest;

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

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function setContent(string $content): self
    {
        $this->content = $content;

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

    public function setLang(string $lang): self
    {
        $this->lang = $lang;

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

    public function setVisibleSessionAdmin(bool $visibleSessionAdmin): self
    {
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

    public function getUrl(): AccessUrl
    {
        return $this->url;
    }

    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getCareer(): ?Career
    {
        return $this->career;
    }

    public function setCareer(?Career $career): self
    {
        $this->career = $career;

        return $this;
    }

    public function getPromotion(): ?Promotion
    {
        return $this->promotion;
    }

    public function setPromotion(?Promotion $promotion): self
    {
        $this->promotion = $promotion;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = [];
        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function addRole(string $role): self
    {
        $role = strtoupper($role);

        if (!\in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function isVisible(): bool
    {
        $now = new DateTime();

        return $this->getDateStart() <= $now && $now <= $this->getDateEnd();
    }
}
