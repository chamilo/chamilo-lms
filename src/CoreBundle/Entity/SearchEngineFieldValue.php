<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * SearchEngineFieldValue.
 *
 * Stores a value for a specific search field (prefix) for a given ResourceNode.
 * This is typically used by the Xapian search layer.
 */
#[ORM\Table(name: 'search_engine_field_value')]
#[ORM\UniqueConstraint(name: 'uniq_search_field_value_node_field', columns: ['resource_node_id', 'field_id'])]
#[ORM\Index(name: 'idx_sefv_resource_node', columns: ['resource_node_id'])]
#[ORM\Index(name: 'idx_sefv_field', columns: ['field_id'])]
#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['sefv:read']],
            paginationEnabled: false
        ),
        new Get(
            normalizationContext: ['groups' => ['sefv:read']]
        ),
    ],
    normalizationContext: ['groups' => ['sefv:read']],
    security: "is_granted('ROLE_TEACHER') or is_granted('ROLE_ADMIN')"
)]
#[ApiFilter(SearchFilter::class, properties: [
    // Allows: ?resourceNode=/api/resource_nodes/20228
    'resourceNode' => 'exact',
    // Optional: allows filtering by field too: ?field=/api/search_engine_fields/1
    'field' => 'exact',
])]
class SearchEngineFieldValue
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['sefv:read'])]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ResourceNode::class)]
    #[ORM\JoinColumn(name: 'resource_node_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['sefv:read'])]
    #[ApiProperty(readableLink: false)]
    protected ResourceNode $resourceNode;

    #[ORM\ManyToOne(targetEntity: SearchEngineField::class)]
    #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['sefv:read'])]
    #[ApiProperty(readableLink: false)]
    protected SearchEngineField $field;

    #[ORM\Column(name: 'value', type: 'string', length: 200, nullable: false)]
    #[Groups(['sefv:read'])]
    protected string $value = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
    }

    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    public function getField(): SearchEngineField
    {
        return $this->field;
    }

    public function setField(SearchEngineField $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function setValue(string $value): self
    {
        $value = trim($value);

        // Avoid DB errors if someone passes a very long string.
        if (\strlen($value) > 200) {
            $value = substr($value, 0, 200);
        }

        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
