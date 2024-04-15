<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\ColorThemeProcessor;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new Post(
            processor: ColorThemeProcessor::class,
        ),
        new GetCollection(),
    ],
    denormalizationContext: [
        'groups' => ['color_theme:write'],
    ],
    paginationEnabled: false,
    security: "is_granted('ROLE_ADMIN')",
)]
class ColorTheme
{
    use TimestampableTypedEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['color_theme:write'])]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['color_theme:write'])]
    #[ORM\Column]
    private array $variables = [];

    #[Gedmo\Slug(fields: ['title'])]
    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[Groups(['color_theme:write'])]
    #[ORM\Column]
    private ?bool $active = null;

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

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
