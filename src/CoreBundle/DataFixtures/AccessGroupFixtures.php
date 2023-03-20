<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Repository\GroupRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AccessGroupFixtures extends Fixture implements ContainerAwareInterface
{
    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager): void
    {
        /** @var GroupRepository $repo */
        $repo = $this->container->get(GroupRepository::class);
        $repo->createDefaultGroups($this);
    }
}
