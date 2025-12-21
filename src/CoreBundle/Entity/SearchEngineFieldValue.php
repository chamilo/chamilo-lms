<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchEngineFieldValue.
 */
#[ORM\Table(name: 'search_engine_field_value')]
#[ORM\Entity]
class SearchEngineFieldValue
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    #[ORM\Column(name: 'resource_node_id', type: 'integer', nullable: false)]
    protected int $resourceNodeId;

    #[ORM\Column(name: 'field_id', type: 'integer', nullable: false)]
    protected int $fieldId;

    #[ORM\Column(name: 'value', type: 'string', length: 200, nullable: false)]
    protected string $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setResourceNodeId(int $resourceNodeId): self
    {
        $this->resourceNodeId = $resourceNodeId;

        return $this;
    }

    public function getResourceNodeId(): int
    {
        return $this->resourceNodeId;
    }

    public function setFieldId(int $fieldId): self
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    public function getFieldId(): int
    {
        return $this->fieldId;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
