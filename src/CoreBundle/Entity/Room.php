<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\State\RoomStateProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Room.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['room:list']],
        ),
        new Get(
            security: "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('VIEW', object)",
            normalizationContext: ['groups' => ['room:read']],
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['room:write']],
            processor: RoomStateProcessor::class,
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN') and is_granted('EDIT', object)",
            denormalizationContext: ['groups' => ['room:write']],
            processor: RoomStateProcessor::class,
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN') and is_granted('EDIT', object)",
            denormalizationContext: ['groups' => ['room:write']],
            processor: RoomStateProcessor::class,
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') and is_granted('DELETE', object)",
        ),
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial'])]
#[ORM\Table(name: 'room')]
#[ORM\Entity]
class Room
{
    #[Groups(['room:list', 'room:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Groups(['room:list', 'room:read', 'room:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title;

    #[Groups(['room:read', 'room:write'])]
    #[Assert\Length(max: 2000)]
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[Groups(['room:list', 'room:read', 'room:write'])]
    #[ORM\Column(name: 'floor_number', type: 'integer', nullable: true)]
    protected ?int $floorNumber = null;

    #[Groups(['room:list', 'room:read', 'room:write'])]
    #[Assert\Positive]
    #[ORM\Column(name: 'capacity', type: 'integer', nullable: true)]
    protected ?int $capacity = null;

    #[Groups(['room:read', 'room:write'])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(name: 'geolocation', type: 'string', length: 255, nullable: true, unique: false)]
    protected ?string $geolocation = null;

    #[Groups(['room:read', 'room:write'])]
    #[Assert\Length(max: 45)]
    #[ORM\Column(name: 'ip', type: 'string', length: 45, nullable: true, unique: false)]
    protected ?string $ip = null;

    #[Groups(['room:read', 'room:write'])]
    #[Assert\Length(max: 6)]
    #[Assert\Regex(pattern: '/^\/\d{1,3}$/', message: 'Must be in CIDR format (e.g. /24).')]
    #[ORM\Column(name: 'ip_mask', type: 'string', length: 6, nullable: true, unique: false)]
    protected ?string $ipMask = null;

    #[Groups(['room:list', 'room:read', 'room:write'])]
    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: BranchSync::class)]
    #[ORM\JoinColumn(name: 'branch_id', referencedColumnName: 'id')]
    protected ?BranchSync $branch = null;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setTitle(string $title): self
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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getFloorNumber(): ?int
    {
        return $this->floorNumber;
    }

    public function setFloorNumber(?int $floorNumber): self
    {
        $this->floorNumber = $floorNumber;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): self
    {
        $this->capacity = $capacity;

        return $this;
    }

    /**
     * @return string
     */
    public function getGeolocation()
    {
        return $this->geolocation;
    }

    public function setGeolocation(?string $geolocation): self
    {
        $this->geolocation = $geolocation;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getIpMask()
    {
        return $this->ipMask;
    }

    public function setIpMask(?string $ipMask): self
    {
        $this->ipMask = $ipMask;

        return $this;
    }

    public function getBranch(): ?BranchSync
    {
        return $this->branch;
    }

    public function setBranch(?BranchSync $branch): self
    {
        $this->branch = $branch;

        return $this;
    }
}
