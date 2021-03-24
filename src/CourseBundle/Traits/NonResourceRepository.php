<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Traits;

use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\ToolChain;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

trait NonResourceRepository
{
    protected EntityRepository $repository;

    protected EntityManager $entityManager;

    protected ?RouterInterface $router = null;

    protected ?ResourceNodeRepository $resourceNodeRepository = null;

    protected ?AuthorizationCheckerInterface $authorizationChecker = null;

    protected ?SlugifyInterface $slugify = null;

    protected ?ToolChain $toolChain = null;

    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): self
    {
        $this->authorizationChecker = $authorizationChecker;

        return $this;
    }

    public function setRouter(RouterInterface $router): self
    {
        $this->router = $router;

        return $this;
    }

    public function setSlugify(SlugifyInterface $slugify): self
    {
        $this->slugify = $slugify;

        return $this;
    }

    public function setToolChain(ToolChain $toolChain): self
    {
        $this->toolChain = $toolChain;

        return $this;
    }

    public function setResourceNodeRepository(ResourceNodeRepository $resourceNodeRepository): self
    {
        $this->resourceNodeRepository = $resourceNodeRepository;

        return $this;
    }
}
