<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\ToolChain;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

class ResourceFactory
{
    protected ToolChain $toolChain;

    public function __construct(ToolChain $toolChain)
    {
        $this->toolChain = $toolChain;
    }

    public function getRepositoryService(string $tool, string $type): string
    {
        $tool = $this->toolChain->getToolFromName($tool);

        $resourceTypeList = $tool->getResourceTypes();
        if (!isset($resourceTypeList[$type])) {
            throw new InvalidArgumentException(sprintf('Resource type doesn\'t exist: %s', $type));
        }

        $typeConfig = $resourceTypeList[$type];
        $repo = $typeConfig['repository'];

        if (!class_exists($repo)) {
            throw new InvalidArgumentException(sprintf('Check that this classes exists: %s', $repo));
        }

        return $repo;
    }
}
