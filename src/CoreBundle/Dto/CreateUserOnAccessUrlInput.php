<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CreateUserOnAccessUrlInput
{
    #[Assert\NotBlank]
    #[Groups(['user:write'])]
    private string $username;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:write'])]
    private string $email;

    #[Groups(['user:write'])]
    private ?string $firstname = null;

    #[Groups(['user:write'])]
    private ?string $lastname = null;

    #[Groups(['user:write'])]
    private ?string $password = null;

    #[Groups(['user:write'])]
    private ?string $locale = null;

    #[Groups(['user:write'])]
    private ?string $timezone = null;

    #[Groups(['user:write'])]
    private ?int $status = null;

    #[Groups(['user:write'])]
    public ?array $extraFields = null;

    #[Groups(['user:write'])]
    private bool $sendEmail = true;

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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }
    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }
    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
    public function setPassword(?string $password): void
    {
        $this->password = $password;
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

    public function getSendEmail(): ?bool
    {
        return $this->sendEmail;
    }

    public function setSendEmail(bool $sendEmail): void
    {
        $this->sendEmail = $sendEmail;
    }
}
