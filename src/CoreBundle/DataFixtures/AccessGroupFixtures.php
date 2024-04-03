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
    public function __construct(
        private GroupRepository $groupRepository
    ) {}

    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->groupRepository = $container->get(GroupRepository::class);
    }

    public function load(ObjectManager $manager): void
    {
        $this->groupRepository->createDefaultGroups($this);
    }
}
