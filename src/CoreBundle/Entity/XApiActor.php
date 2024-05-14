<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiActorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: XApiActorRepository::class)]
class XApiActor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $identifier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mbox = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mboxSha1Sum = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $openId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountHomePage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMbox(): ?string
    {
        return $this->mbox;
    }

    public function setMbox(?string $mbox): static
    {
        $this->mbox = $mbox;

        return $this;
    }

    public function getMboxSha1Sum(): ?string
    {
        return $this->mboxSha1Sum;
    }

    public function setMboxSha1Sum(?string $mboxSha1Sum): static
    {
        $this->mboxSha1Sum = $mboxSha1Sum;

        return $this;
    }

    public function getOpenId(): ?string
    {
        return $this->openId;
    }

    public function setOpenId(?string $openId): static
    {
        $this->openId = $openId;

        return $this;
    }

    public function getAccountName(): ?string
    {
        return $this->accountName;
    }

    public function setAccountName(?string $accountName): static
    {
        $this->accountName = $accountName;

        return $this;
    }

    public function getAccountHomePage(): ?string
    {
        return $this->accountHomePage;
    }

    public function setAccountHomePage(?string $accountHomePage): static
    {
        $this->accountHomePage = $accountHomePage;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
