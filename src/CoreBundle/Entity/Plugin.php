<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'plugin')]
class Plugin
{
    public const SOURCE_THIRD_PARTY = 'third_party';
    public const SOURCE_OFFICIAL = 'official';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $title;

    #[ORM\Column(type: 'boolean')]
    private bool $installed = false;

    #[ORM\Column(type: 'string', length: 20)]
    private string $installedVersion;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => self::SOURCE_THIRD_PARTY])]
    private string $source = 'third_party';

    /**
     * @var Collection<int, AccessUrlRelPlugin>
     */
    #[ORM\OneToMany(mappedBy: 'plugin', targetEntity: AccessUrlRelPlugin::class, orphanRemoval: true)]
    private Collection $urls;

    public function __construct()
    {
        $this->urls = new ArrayCollection();
    }

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

    public function getInstalledVersion(): string
    {
        return $this->installedVersion;
    }

    public function setInstalledVersion(string $installedVersion): self
    {
        $this->installedVersion = $installedVersion;

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

    /**
     * @return Collection<int, AccessUrlRelPlugin>
     */
    public function getUrls(): Collection
    {
        return $this->urls;
    }

    public function addUrl(AccessUrlRelPlugin $url): static
    {
        if (!$this->urls->contains($url)) {
            $this->urls->add($url);
            $url->setPlugin($this);
        }

        return $this;
    }

    public function removeUrl(AccessUrlRelPlugin $url): static
    {
        if ($this->urls->removeElement($url)) {
            // set the owning side to null (unless already changed)
            if ($url->getPlugin() === $this) {
                $url->setPlugin(null);
            }
        }

        return $this;
    }
}
