<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Career;
use Chamilo\CoreBundle\Entity\Promotion;
use Chamilo\CoreBundle\Repository\PromotionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class PromotionRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(PromotionRepository::class);
        $defaultCount = $repo->count([]);

        $career = (new Career())
            ->setName('Doctor')
        ;
        $em->persist($career);
        $em->flush();

        $item = (new Promotion())
            ->setName('2000')
            ->setDescription('Promotion of 2000')
            ->setCareer($career)
            ->setStatus(1)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame($defaultCount + 1, $repo->count([]));
    }
}
