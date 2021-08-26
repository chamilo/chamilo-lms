<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Repository\BranchSyncRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class BranchSyncRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get('doctrine')->getManager();
        $repo = self::getContainer()->get(BranchSyncRepository::class);

        $item = (new BranchSync())
            ->setBranchName('Branch')
            ->setAdminName('Julio')
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        // By default there's a root branch.
        $this->assertSame(2, $repo->count([]));
    }
}
