<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\ToolChain;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\MountManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ResourceFactory
{
    protected $mountManager;
    protected $toolChain;
    protected $fs;
    protected $slugify;
    protected $entityManager;
    protected $authorizationChecker;

    public function __construct(
        MountManager $mountManager,
        ToolChain $toolChain,
        SlugifyInterface $slugify,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        Container $container
    ) {
        $this->mountManager = $mountManager;
        // @todo create a service to remove hardcode value of "resources_fs"
        $this->fs = $mountManager->getFilesystem('resources_fs');
        $this->toolChain = $toolChain;
        $this->slugify = $slugify;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->container = $container;
    }

    public function createRepository(string $tool, string $type): ResourceRepository
    {
        $tool = $this->toolChain->getToolFromName($tool);

        $resourceTypeList = $tool->getResourceTypes();
        if (!isset($resourceTypeList[$type])) {
            throw new InvalidArgumentException("Resource type doesn't exist: $type");
        }

        $typeConfig = $resourceTypeList[$type];
        $repo = $typeConfig['repository'];

        if (class_exists($repo) === false) {
            throw new InvalidArgumentException("Check that this classes exists: $repo");
        }

        return $this->container->get($repo);

        /*return new $repo(
            $this->authorizationChecker,
            $this->entityManager,
            $this->mountManager,
            $this->router,
            $this->slugify,
            $entity
        );*/
    }
}
