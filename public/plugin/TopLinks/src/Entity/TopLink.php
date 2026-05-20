<?php

/* For license terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\TopLinks\Entity;

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\TopLinks\Entity\Repository\TopLinkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'toplinks_link')]
#[ORM\Entity(repositoryClass: TopLinkRepository::class)]
class TopLink
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string')]
    private string $title = '';

    #[ORM\Column(name: 'url', type: 'text')]
    private string $url = '';

    #[ORM\Column(name: 'target', type: 'string', length: 10, options: ['default' => '_blank'])]
    private string $target = '_blank';

    #[ORM\Column(name: 'icon', type: 'string', nullable: true)]
    private ?string $icon = null;

    #[ORM\OneToMany(mappedBy: 'link', targetEntity: TopLinkRelTool::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $tools;

    public function __construct()
    {
        $this->tools = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = trim($title);

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = trim($url);

        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): static
    {
        $this->target = '_self' === $target ? '_self' : '_blank';

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon = null): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getTools(): Collection
    {
        return $this->tools;
    }

    public function addTool(CTool $tool): void
    {
        $linkTool = new TopLinkRelTool();
        $linkTool
            ->setTool($tool)
            ->setLink($this)
        ;

        $this->tools->add($linkTool);
    }
}
