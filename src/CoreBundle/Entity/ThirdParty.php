<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: ['groups' => ['third_party:read']],
    denormalizationContext: ['groups' => ['third_party:write']],
    paginationEnabled: false
)]
#[ORM\Table(name: 'third_party')]
#[ORM\Entity]
class ThirdParty
{
    #[Groups(['third_party:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[Groups(['third_party:read', 'third_party:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(type: 'text')]
    protected string $name;

    #[Groups(['third_party:read', 'third_party:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    #[Groups(['third_party:read', 'third_party:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $address = null;

    #[Groups(['third_party:read', 'third_party:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $website = null;

    #[Groups(['third_party:read', 'third_party:write'])]
    #[ORM\Column(type: 'boolean')]
    protected bool $dataExchangeParty = false;

    #[Groups(['third_party:read', 'third_party:write'])]
    #[ORM\Column(type: 'boolean')]
    protected bool $recruiter = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function isDataExchangeParty(): bool
    {
        return $this->dataExchangeParty;
    }

    public function setDataExchangeParty(bool $value): static
    {
        $this->dataExchangeParty = $value;

        return $this;
    }

    public function isRecruiter(): bool
    {
        return $this->recruiter;
    }

    public function setRecruiter(bool $value): static
    {
        $this->recruiter = $value;

        return $this;
    }
}
