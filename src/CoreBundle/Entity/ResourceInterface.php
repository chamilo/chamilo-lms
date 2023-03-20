<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Symfony\Component\Uid\Uuid;

interface ResourceInterface
{
    public function __toString(): string;

    /**
     * Returns the resource id identifier. Example for CDocument it will be the value of the field iid.
     */
    public function getResourceIdentifier(): int | Uuid;

    /**
     * Returns the resource name. Example for CDocument it will be the field "title".
     */
    public function getResourceName(): string;

    public function setResourceName(string $name);

    public function getResourceNode(): ?ResourceNode;

    public function setResourceNode(ResourceNode $resourceNode);
}
