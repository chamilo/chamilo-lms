<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: ['groups' => ['third_party_exchange:read']],
    denormalizationContext: ['groups' => ['third_party_exchange:write']],
    paginationEnabled: false,
    security: "is_granted('ROLE_ADMIN')",
)]
#[ApiFilter(SearchFilter::class, properties: ['thirdParty' => 'exact'])]
#[ORM\Table(name: 'third_party_data_exchange')]
#[ORM\Entity]
class ThirdPartyDataExchange
{
    #[Groups(['third_party_exchange:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[Groups(['third_party_exchange:read', 'third_party_exchange:write'])]
    #[ORM\ManyToOne(targetEntity: ThirdParty::class)]
    #[ORM\JoinColumn(name: 'third_party_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ThirdParty $thirdParty;

    #[Groups(['third_party_exchange:read', 'third_party_exchange:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(type: 'datetime')]
    protected DateTimeInterface $sentAt;

    #[Groups(['third_party_exchange:read', 'third_party_exchange:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    #[Groups(['third_party_exchange:read', 'third_party_exchange:write'])]
    #[ORM\Column(type: 'boolean')]
    protected bool $allUsers = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getThirdParty(): ThirdParty
    {
        return $this->thirdParty;
    }

    public function setThirdParty(ThirdParty $thirdParty): static
    {
        $this->thirdParty = $thirdParty;

        return $this;
    }

    public function getSentAt(): DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isAllUsers(): bool
    {
        return $this->allUsers;
    }

    public function setAllUsers(bool $allUsers): static
    {
        $this->allUsers = $allUsers;

        return $this;
    }
}
