<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Repository\GroupRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AccessGroupFixtures extends Fixture
{
    public function __construct(
        private readonly GroupRepository $groupRepository
    ) {}

    public function load(ObjectManager $manager): void
    {
        $this->groupRepository->createDefaultGroups($this);
    }
}
