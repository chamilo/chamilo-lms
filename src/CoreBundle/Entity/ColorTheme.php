<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\ColorThemeProcessor;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new Post(
            processor: ColorThemeProcessor::class,
        ),
    ],
    denormalizationContext: [
        'groups' => ['color_theme:write'],
    ],
    security: "is_granted('ROLE_ADMIN')",
)]
class ColorTheme
{
    use TimestampableTypedEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['color_theme:write'])]
    #[ORM\Column]
    private array $variables = [];

    public function getId(): ?int
    {
        return $this->id;
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
}
