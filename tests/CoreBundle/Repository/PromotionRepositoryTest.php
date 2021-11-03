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
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(PromotionRepository::class);
        $defaultCount = $repo->count([]);

        $career = (new Career())
            ->setName('Doctor')
        ;
        $em->persist($career);
        $em->flush();

        $promotion = (new Promotion())
            ->setName('2000')
            ->setDescription('Promotion of 2000')
            ->setCareer($career)
            ->setStatus(1)
        ;
        $this->assertHasNoEntityViolations($promotion);
        $em->persist($promotion);
        $em->flush();

        $this->assertSame('2000', $promotion->getName());
        $this->assertSame('Promotion of 2000', $promotion->getDescription());
        $this->assertNotNull($promotion->getId());
        $this->assertSame(0, $promotion->getAnnouncements()->count());
        $this->assertSame(0, $promotion->getSessions()->count());

        $this->assertSame($defaultCount + 1, $repo->count([]));
    }
}
