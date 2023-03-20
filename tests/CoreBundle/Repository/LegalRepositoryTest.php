<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class LegalRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(LegalRepository::class);

        $item = (new Legal())
            ->setContent('content')
            ->setType(1)
            ->setChanges('changes')
            ->setDate(1)
            ->setLanguageId(1)
            ->setVersion(1)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame(1, $repo->count([]));
    }

    public function testFindOneByTypeAndLanguage(): void
    {
        $this->testCreate();

        $repo = self::getContainer()->get(LegalRepository::class);
        $legal = $repo->findOneByTypeAndLanguage(1, 1);

        $this->assertCount(1, $legal);
    }
}
