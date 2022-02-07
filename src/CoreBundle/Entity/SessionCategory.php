<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="session_category")
 * @ORM\Entity
 */
#[ApiResource(
    attributes: [
        'security' => "is_granted('ROLE_USER')",
    ],
    collectionOperations: [
        'get' => [
            'security' => "is_granted('ROLE_USER')",
        ],
        'post' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    normalizationContext: [
        'groups' => ['session_category:read'],
    ],
    denormalizationContext: [
        'groups' => ['session_category:write'],
    ],
)]
class SessionCategory
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[Groups(['session_category:read', 'session_rel_user:read'])]
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\AccessUrl", inversedBy="sessionCategories", cascade={"persist"})
     * @ORM\JoinColumn(name="access_url_id", referencedColumnName="id")
     */
    protected AccessUrl $url;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Session", mappedBy="category")
     */
    protected Collection $sessions;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=false, unique=false)
     */
    #[Groups(['session_category:read', 'session_category:write', 'session:read', 'session_rel_user:read'])]
    #[Assert\NotBlank]
    protected string $name;

    /**
     * @ORM\Column(name="date_start", type="date", nullable=true, unique=false)
     */
    protected ?DateTime $dateStart = null;

    /**
     * @ORM\Column(name="date_end", type="date", nullable=true, unique=false)
     */
    protected ?DateTime $dateEnd = null;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): AccessUrl
    {
        return $this->url;
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

    public function setDateStart(DateTime $dateStart): self
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

    public function setDateEnd(DateTime $dateEnd): self
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
}
