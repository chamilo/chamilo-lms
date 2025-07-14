<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    normalizationContext: ['groups' => ['page_layout_template:read']],
    denormalizationContext: ['groups' => ['page_layout_template:write']]
)]
#[ORM\Table(name: 'page_layout_template')]
#[ORM\Entity]
class PageLayoutTemplate
{
    #[Groups(['page_layout_template:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Groups(['page_layout_template:read', 'page_layout_template:write'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[Groups(['page_layout_template:read', 'page_layout_template:write'])]
    #[ORM\Column(type: 'text')]
    private string $layout;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function setLayout(string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }
}
