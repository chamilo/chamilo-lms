<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [new Get(), new Put(), new Patch(), new Delete(), new GetCollection(security: 'is_granted(\'ROLE_USER\')'), new Post(security: 'is_granted(\'ROLE_ADMIN\')')], security: 'is_granted(\'ROLE_USER\')', denormalizationContext: ['groups' => ['session_category:write']], normalizationContext: ['groups' => ['session_category:read']])]
#[ORM\Table(name: 'session_category')]
#[ORM\Entity]
class SessionCategory implements Stringable
{
    #[Groups(['session_category:read', 'session_rel_user:read'])]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[ORM\ManyToOne(targetEntity: AccessUrl::class, inversedBy: 'sessionCategories', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id')]
    protected AccessUrl $url;
    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'category')]
    protected Collection $sessions;
    #[Groups(['session_category:read', 'session_category:write', 'session:read', 'session_rel_user:read'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'name', type: 'string', length: 100, nullable: false, unique: false)]
    protected string $name;
    #[ORM\Column(name: 'date_start', type: 'date', nullable: true, unique: false)]
    protected ?DateTime $dateStart = null;
    #[ORM\Column(name: 'date_end', type: 'date', nullable: true, unique: false)]
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
