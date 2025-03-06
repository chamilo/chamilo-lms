<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'plugin')]
class Plugin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups(['plugin:read', 'plugin:write'])]
    private string $title;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['plugin:read', 'plugin:write'])]
    private bool $installed = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['plugin:read', 'plugin:write'])]
    private bool $active = false;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['plugin:read', 'plugin:write'])]
    private string $version;

    #[ORM\Column(type: 'integer')]
    #[Groups(['plugin:read', 'plugin:write'])]
    private int $accessUrlId;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['plugin:read', 'plugin:write'])]
    private ?array $configuration = [];

    #[ORM\Column(type: 'string', length: 20, options: ["default" => "third_party"])]
    #[Groups(['plugin:read', 'plugin:write'])]
    private string $source = 'third_party';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function isInstalled(): bool
    {
        return $this->installed;
    }

    public function setInstalled(bool $installed): self
    {
        $this->installed = $installed;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getAccessUrlId(): int
    {
        return $this->accessUrlId;
    }

    public function setAccessUrlId(int $accessUrlId): self
    {
        $this->accessUrlId = $accessUrlId;
        return $this;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): self
    {
        $this->configuration = $configuration;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }
}
