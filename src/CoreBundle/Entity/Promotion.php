<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="promotion")
 * @ORM\Entity
 */
class Promotion
{
    use TimestampableEntity;

    public const PROMOTION_STATUS_ACTIVE = 1;
    public const PROMOTION_STATUS_INACTIVE = 0;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    #[Assert\NotBlank]
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected ?string $description = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Career", inversedBy="promotions")
     * @ORM\JoinColumn(name="career_id", referencedColumnName="id")
     */
    protected Career $career;

    /**
     * @var Collection|Session[]
     *
     * @ORM\OneToMany(targetEntity="Session", mappedBy="promotion", cascade={"persist"})
     */
    protected Collection $sessions;

    /**
     * @var Collection|SysAnnouncement[]
     *
     * @ORM\OneToMany(targetEntity="SysAnnouncement", mappedBy="promotion", cascade={"persist"})
     */
    protected Collection $announcements;

    /**
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected int $status;

    public function __construct()
    {
        $this->status = self::PROMOTION_STATUS_ACTIVE;
        $this->announcements = new ArrayCollection();
        $this->sessions = new ArrayCollection();
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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(string $description): self
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

    public function setCareer(Career $career): self
    {
        $this->career = $career;

        return $this;
    }

    public function getCareer(): Career
    {
        return $this->career;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return Session[]|Collection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    public function setSessions(Collection $sessions): self
    {
        $this->sessions = $sessions;

        return $this;
    }

    /**
     * @return SysAnnouncement[]|Collection
     */
    public function getAnnouncements()
    {
        return $this->announcements;
    }

    /**
     * @param SysAnnouncement[]|Collection $announcements
     */
    public function setAnnouncements($announcements): self
    {
        $this->announcements = $announcements;

        return $this;
    }
}
