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
    protected ?int $id = null;

    /**
     * @ORM\Column(name="date_start", type="datetime", nullable=false)
     */
    protected DateTime $dateStart;

    /**
     * @ORM\Column(name="date_end", type="datetime", nullable=false)
     */
    protected DateTime $dateEnd;

    /**
     * @ORM\Column(name="title", type="string", length=250, nullable=false)
     */
    #[Assert\NotBlank]
    protected string $title;

    /**
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    #[Assert\NotBlank]
    protected string $content;

    /**
     * @ORM\Column(name="lang", type="string", length=70, nullable=true)
     */
    protected ?string $lang = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\AccessUrl")
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
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Promotion", inversedBy="announcements")
     * @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Promotion $promotion = null;

    public function __construct()
    {
        $this->roles = [];
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

    public function getUrl(): AccessUrl
    {
        return $this->url;
    }

    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function hasCareer(): bool
    {
        return null !== $this->career;
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

    public function hasPromotion(): bool
    {
        return null !== $this->promotion;
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
