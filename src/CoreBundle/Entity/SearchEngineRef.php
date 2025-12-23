<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'search_engine_ref')]
#[ORM\Entity]
class SearchEngineRef
{
    #[ORM\ManyToOne(targetEntity: ResourceNode::class)]
    #[ORM\JoinColumn(name: 'resource_node_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?ResourceNode $resourceNode = null;

    #[ORM\Column(name: 'search_did', type: 'integer', nullable: false)]
    private int $searchDid;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResourceNode(): ?ResourceNode
    {
        return $this->resourceNode;
    }

    public function setResourceNode(?ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    public function getSearchDid(): int
    {
        return $this->searchDid;
    }

    public function setSearchDid(int $searchDid): self
    {
        $this->searchDid = $searchDid;

        return $this;
    }
}
