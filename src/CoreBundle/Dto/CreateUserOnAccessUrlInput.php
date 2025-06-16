<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']],
)]
class CreateUserOnAccessUrlInput
{
    #[Assert\NotBlank]
    #[Groups(['write'])]
    private string $username;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['write'])]
    private string $email;

    #[Assert\NotBlank]
    #[Groups(['write'])]
    private string $firstname;

    #[Assert\NotBlank]
    #[Groups(['write'])]
    private string $lastname;

    #[Assert\NotBlank]
    #[Groups(['write'])]
    private string $password;

    #[Assert\NotBlank]
    #[Groups(['write'])]
    private int $accessUrlId;

    #[Groups(['write'])]
    private ?string $locale = null;

    #[Groups(['write'])]
    private ?string $timezone = null;

    #[Groups(['write'])]
    private ?int $status = null;

    public function getUsername(): string
    {
        return $this->username;
    }
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }
    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }
    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getAccessUrlId(): int
    {
        return $this->accessUrlId;
    }
    public function setAccessUrlId(int $accessUrlId): void
    {
        $this->accessUrlId = $accessUrlId;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }
    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }
    public function setTimezone(?string $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }
    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }
}
