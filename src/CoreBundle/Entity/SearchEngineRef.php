<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchEngineRef.
 */
#[ORM\Table(name: 'search_engine_ref')]
#[ORM\Entity]
class SearchEngineRef
{
    #[ORM\Column(name: 'resource_node_id', type: 'integer', nullable: true)]
    protected ?int $resourceNodeId = null;

    #[ORM\Column(name: 'search_did', type: 'integer', nullable: false)]
    protected int $searchDid;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResourceNodeId(): ?int
    {
        return $this->resourceNodeId;
    }

    public function setResourceNodeId(?int $resourceNodeId): self
    {
        $this->resourceNodeId = $resourceNodeId;

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
