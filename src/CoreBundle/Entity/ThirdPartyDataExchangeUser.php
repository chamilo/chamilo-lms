<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['third_party_user:read']],
    denormalizationContext: ['groups' => ['third_party_user:write']],
    paginationEnabled: false,
    security: "is_granted('ROLE_ADMIN')",
)]
#[ORM\Table(name: 'third_party_data_exchange_user')]
#[ORM\Entity]
class ThirdPartyDataExchangeUser
{
    #[Groups(['third_party_user:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[Groups(['third_party_user:read', 'third_party_user:write'])]
    #[ORM\ManyToOne(targetEntity: ThirdPartyDataExchange::class)]
    #[ORM\JoinColumn(name: 'third_party_data_exchange_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ThirdPartyDataExchange $dataExchange;

    #[Groups(['third_party_user:read', 'third_party_user:write'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataExchange(): ThirdPartyDataExchange
    {
        return $this->dataExchange;
    }

    public function setDataExchange(ThirdPartyDataExchange $dataExchange): static
    {
        $this->dataExchange = $dataExchange;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
