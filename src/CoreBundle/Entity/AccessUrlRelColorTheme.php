<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Repository\AccessUrlRelColorThemeRepository;
use Chamilo\CoreBundle\State\AccessUrlRelColorThemeStateProcessor;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(),
    ],
    denormalizationContext: [
        'groups' => ['access_url_rel_color_theme:write'],
    ],
    security: "is_granted('ROLE_ADMIN')",
    processor: AccessUrlRelColorThemeStateProcessor::class,
)]
#[ORM\Entity(repositoryClass: AccessUrlRelColorThemeRepository::class)]
class AccessUrlRelColorTheme
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'colorThemes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AccessUrl $url = null;

    #[Groups(['access_url_rel_color_theme:write'])]
    #[ORM\ManyToOne(inversedBy: 'urls')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ColorTheme $colorTheme = null;

    #[ORM\Column]
    private bool $active = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getColorTheme(): ?ColorTheme
    {
        return $this->colorTheme;
    }

    public function setColorTheme(?ColorTheme $colorTheme): static
    {
        $this->colorTheme = $colorTheme;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
