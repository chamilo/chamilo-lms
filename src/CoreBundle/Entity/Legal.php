<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
#[ApiFilter(SearchFilter::class, properties: ['languageId' => 'exact'])]
#[ApiFilter(OrderFilter::class, properties: ['version' => 'DESC'])]
#[ORM\Table(name: 'legal')]
#[ORM\Entity(repositoryClass: LegalRepository::class)]
class Legal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'integer')]
    protected int $date;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $content = null;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'integer')]
    protected int $type;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'text')]
    protected string $changes;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $version = null;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'integer')]
    protected int $languageId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDate(int $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     */
    public function getDate(): int
    {
        return $this->date;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getChanges(): string
    {
        return $this->changes;
    }

    public function setChanges(string $changes): self
    {
        $this->changes = $changes;

        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function setLanguageId(int $languageId): self
    {
        $this->languageId = $languageId;

        return $this;
    }
}
