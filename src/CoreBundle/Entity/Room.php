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
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Room.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
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
    #[ORM\Column(name: 'title', type: 'text')]
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
    #[ORM\Column(name: 'coordinates', type: 'string', length: 255, nullable: true, unique: false)]
    protected ?string $coordinates = null;

    // Coordinates are normalized to [0, 1] relative to the branch's floor-plan image.
    #[Groups(['room:list', 'room:read', 'room:write'])]
    #[ORM\Column(name: 'room_map', type: 'json', nullable: true)]
    protected ?array $roomMap = null;

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
    #[ORM\ManyToOne(targetEntity: Branch::class)]
    #[ORM\JoinColumn(name: 'branch_id', referencedColumnName: 'id')]
    protected ?Branch $branch = null;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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

    public function getDescription(): ?string
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

    #[Assert\Callback]
    public function validateCoordinates(ExecutionContextInterface $context): void
    {
        if (null === $this->coordinates || '' === trim($this->coordinates)) {
            return;
        }

        if (1 !== preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $this->coordinates, $matches)
            || (float) $matches[1] < -90 || (float) $matches[1] > 90
            || (float) $matches[2] < -180 || (float) $matches[2] > 180) {
            $context->buildViolation('Coordinates must be a valid latitude and longitude pair.')
                ->atPath('coordinates')
                ->addViolation()
            ;
        }
    }

    public function getCoordinates(): ?string
    {
        return $this->coordinates;
    }

    public function setCoordinates(?string $coordinates): self
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    public function getRoomMap(): ?array
    {
        return $this->roomMap;
    }

    public function setRoomMap(?array $roomMap): self
    {
        $this->roomMap = $roomMap;

        return $this;
    }

    #[Assert\Callback]
    public function validateRoomMap(ExecutionContextInterface $context): void
    {
        if (null === $this->roomMap) {
            return;
        }

        // The format is shared by the image overlay editor and the student viewer.
        // Reject arbitrary JSON to avoid persistent invalid or oversized geometries.
        $map = $this->roomMap;
        if (2 !== \count($map) || 'polygon' !== ($map['type'] ?? null)
            || !isset($map['points']) || !\is_array($map['points'])
            || !array_is_list($map['points'])
            || \count($map['points']) < 3 || \count($map['points']) > 100) {
            $context->buildViolation('Room map must be a polygon with 3 to 100 points.')
                ->atPath('roomMap')
                ->addViolation()
            ;

            return;
        }

        foreach ($map['points'] as $point) {
            if (!\is_array($point) || !array_is_list($point) || 2 !== \count($point)) {
                $this->addRoomMapPointViolation($context);

                return;
            }

            foreach ($point as $coordinate) {
                if ((!\is_int($coordinate) && !\is_float($coordinate))
                    || !is_finite((float) $coordinate)
                    || $coordinate < 0 || $coordinate > 1) {
                    $this->addRoomMapPointViolation($context);

                    return;
                }
            }
        }
    }

    private function addRoomMapPointViolation(ExecutionContextInterface $context): void
    {
        $context->buildViolation('Room map points must contain normalized numeric coordinates between 0 and 1.')
            ->atPath('roomMap')
            ->addViolation()
        ;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIpMask(): ?string
    {
        return $this->ipMask;
    }

    public function setIpMask(?string $ipMask): self
    {
        $this->ipMask = $ipMask;

        return $this;
    }

    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    public function setBranch(?Branch $branch): self
    {
        $this->branch = $branch;

        return $this;
    }
}
