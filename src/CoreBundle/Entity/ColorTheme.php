<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\State\ColorThemeStateProcessor;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new Post(),
        new Put(),
    ],
    denormalizationContext: [
        'groups' => ['color_theme:write'],
    ],
    paginationEnabled: false,
    security: "is_granted('ROLE_ADMIN')",
    processor: ColorThemeStateProcessor::class,
)]
class ColorTheme
{
    use TimestampableTypedEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['color_theme:write', 'access_url_rel_color_theme:read'])]
    #[ORM\Column(length: 255)]
    private string $title;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['color_theme:write', 'access_url_rel_color_theme:read'])]
    #[ORM\Column]
    private array $variables = [];

    #[Gedmo\Slug(fields: ['title'])]
    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    /**
     * @var Collection<int, AccessUrlRelColorTheme>
     */
    #[ORM\OneToMany(mappedBy: 'colorTheme', targetEntity: AccessUrlRelColorTheme::class, orphanRemoval: true)]
    private Collection $urls;

    public function __construct()
    {
        $this->urls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function setVariables(array $variables): static
    {
        $this->variables = $variables;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, AccessUrlRelColorTheme>
     */
    public function getUrls(): Collection
    {
        return $this->urls;
    }

    public function addUrl(AccessUrlRelColorTheme $url): static
    {
        if (!$this->urls->contains($url)) {
            $this->urls->add($url);
            $url->setColorTheme($this);
        }

        return $this;
    }

    public function removeUrl(AccessUrlRelColorTheme $url): static
    {
        if ($this->urls->removeElement($url)) {
            // set the owning side to null (unless already changed)
            if ($url->getColorTheme() === $this) {
                $url->setColorTheme(null);
            }
        }

        return $this;
    }
}
