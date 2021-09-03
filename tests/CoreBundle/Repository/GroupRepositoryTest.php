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
        self::bootKernel();

        $em = $this->getManager();
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
}
