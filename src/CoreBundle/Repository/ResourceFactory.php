<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\ToolChain;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

class ResourceFactory
{
    protected $mountManager;
    protected $toolChain;
    protected $slugify;
    protected $entityManager;
    protected $authorizationChecker;

    public function __construct(ToolChain $toolChain)
    {
        $this->toolChain = $toolChain;
    }

    public function getRepositoryService(string $tool, string $type): string
    {
        $tool = $this->toolChain->getToolFromName($tool);

        $resourceTypeList = $tool->getResourceTypes();
        if (!isset($resourceTypeList[$type])) {
            throw new InvalidArgumentException("Resource type doesn't exist: $type");
        }

        $typeConfig = $resourceTypeList[$type];
        $repo = $typeConfig['repository'];

        if (false === class_exists($repo)) {
            throw new InvalidArgumentException("Check that this classes exists: $repo");
        }

        return $repo;
    }
}
