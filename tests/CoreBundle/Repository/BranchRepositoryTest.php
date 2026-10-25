<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Branch;
use Chamilo\CoreBundle\Repository\BranchRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class BranchRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCoordinatesKeepDecimalPrecision(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(BranchRepository::class);

        $item = (new Branch())
            ->setTitle('Precise coordinates branch')
            ->setCoordinates('50.84655731, 4.35169742')
        ;

        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();
        $id = $item->getId();
        $em->clear();

        $stored = $repo->find($id);

        $this->assertInstanceOf(Branch::class, $stored);
        $this->assertSame('50.84655731, 4.35169742', $stored->getCoordinates());
    }

    public function testSearchByKeyword(): void
    {
        $repo = self::getContainer()->get(BranchRepository::class);

        $em = $this->getEntityManager();
        $item = (new Branch())
            ->setTitle('Branch')
            ->setAdminName('Julio')
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $items = $repo->searchByKeyword('Branch');

        $this->assertCount(1, $items);
    }
}
