<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\AccessUrlRelPluginRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessUrlRelPluginRepository::class)]
class AccessUrlRelPlugin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'configurationsInUrl')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Plugin $plugin = null;

    #[ORM\ManyToOne(inversedBy: 'plugins')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AccessUrl $url = null;

    #[ORM\Column]
    private bool $active = false;

    #[ORM\Column(nullable: true)]
    private ?array $configuration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlugin(): ?Plugin
    {
        return $this->plugin;
    }

    public function setPlugin(?Plugin $plugin): static
    {
        $this->plugin = $plugin;

        return $this;
    }

    public function getUrl(): ?AccessUrl
    {
        return $this->url;
    }

    public function setUrl(?AccessUrl $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active ?: false;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
