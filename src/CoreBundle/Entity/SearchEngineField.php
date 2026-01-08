<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * SearchEngineField.
 *
 * Stores the list of "specific search fields" (Xapian prefixes).
 * "code" is a single-letter prefix used by the search engine.
 */
#[ApiResource(
    shortName: 'SearchEngineField',
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER')"),
        // Keep write operations admin-only (optional, but safe).
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['search_engine_field:read']],
    denormalizationContext: ['groups' => ['search_engine_field:write']],
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'code', 'title'])]
#[ORM\Table(name: 'search_engine_field')]
#[ORM\UniqueConstraint(name: 'unique_specific_field__code', columns: ['code'])]
#[ORM\Entity]
class SearchEngineField
{
    #[Groups(['search_engine_field:read', 'search_engine_field:write', 'resource_node:read', 'document:read'])]
    #[ORM\Column(name: 'code', type: 'string', length: 1, nullable: false)]
    protected string $code;

    #[Groups(['search_engine_field:read', 'search_engine_field:write', 'resource_node:read', 'document:read'])]
    #[ORM\Column(name: 'title', type: 'string', length: 200, nullable: false)]
    protected string $title;

    #[Groups(['search_engine_field:read', 'resource_node:read', 'document:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
