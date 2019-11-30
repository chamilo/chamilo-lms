<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Block\BreadcrumbBlockService;
use Chamilo\CoreBundle\ToolChain;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\MountManager;
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
        RouterInterface $router
    ) {
        $this->mountManager = $mountManager;
        $this->fs = $mountManager->getFilesystem('resources_fs');
        $this->toolChain = $toolChain;
        $this->slugify = $slugify;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function createRepository(string $tool, string $type): ResourceRepository
    {
        $tool = $this->toolChain->getToolFromName($tool);
        $resourceTypeList = $tool->getResourceTypes();
        //$this->fs = $mountManager->getFilesystem('resources_fs');

        if (!isset($resourceTypeList[$type])) {
            throw new InvalidArgumentException("Resource type doesn't exist: $type");
        }

        $type = $resourceTypeList[$type];
        $repo = $type['repository'];
        $entity = $type['entity'];

        if (class_exists($repo) === false || class_exists($entity) === false) {
            throw new InvalidArgumentException("Check the configuration of the type: $type");
        }

        return new $repo(
            $this->authorizationChecker,
            $this->entityManager,
            $this->mountManager,
            $this->router,
            $this->slugify,
            $entity
        );
    }
}
