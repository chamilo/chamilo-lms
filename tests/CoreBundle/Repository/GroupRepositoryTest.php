<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Group;
use Chamilo\CoreBundle\Repository\GroupRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class GroupRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(GroupRepository::class);
        $defaultGroups = $repo->count([]);
        $item = (new Group('new_group'))
            ->setCode('new_group')
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame($defaultGroups + 1, $repo->count([]));
    }

    public function testGetAdmins(): void
    {
        $repo = self::getContainer()->get(GroupRepository::class);
        $admins = $repo->getAdmins();
        $this->assertCount(0, $admins);
    }

    public function testCreateDefaultGroups(): void
    {
        $repo = self::getContainer()->get(GroupRepository::class);
        $groups = $repo->findAll();
        $defaultCount = $repo->count([]);
        $repo->createDefaultGroups();
        $count = $repo->count([]);
        $this->assertSame($defaultCount, $count);

        foreach ($groups as $group) {
            $repo->delete($group);
        }

        $repo->createDefaultGroups();
        $count = $repo->count([]);
        $this->assertSame($defaultCount, $count);
    }
}
