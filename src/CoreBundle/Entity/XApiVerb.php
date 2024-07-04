<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiVerbRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: XApiVerbRepository::class)]
class XApiVerb
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $identifier = null;

    #[ORM\Column(length: 255)]
    private ?string $id = null;

    #[ORM\Column(nullable: true)]
    private ?array $display = null;

    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getDisplay(): ?array
    {
        return $this->display;
    }

    public function setDisplay(?array $display): static
    {
        $this->display = $display;

        return $this;
    }
}
