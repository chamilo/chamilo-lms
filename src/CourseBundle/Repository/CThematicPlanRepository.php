<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\ToolChain;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class CThematicPlanRepository extends ServiceEntityRepository
{
    protected EntityRepository $repository;

    protected FilesystemInterface $fs;

    protected EntityManager $entityManager;

    protected ?RouterInterface $router = null;

    protected ?ResourceNodeRepository $resourceNodeRepository = null;

    protected ?AuthorizationCheckerInterface $authorizationChecker = null;

    protected ?SlugifyInterface $slugify = null;

    protected ?ToolChain $toolChain = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CThematicPlan::class);
    }

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

    /*public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }*/
}
