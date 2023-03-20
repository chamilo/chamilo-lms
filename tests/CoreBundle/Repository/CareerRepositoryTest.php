<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Career;
use Chamilo\CoreBundle\Repository\CareerRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CareerRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CareerRepository::class);

        $career = (new Career())
            ->setName('Julio')
            ->setDescription('test')
            ->setStatus(1)
        ;
        $this->assertHasNoEntityViolations($career);
        $em->persist($career);
        $em->flush();

        $this->assertSame(1, $repo->count([]));
        $this->assertSame('Julio', $career->getName());
        $this->assertNotNull($career->getId());
    }
}
