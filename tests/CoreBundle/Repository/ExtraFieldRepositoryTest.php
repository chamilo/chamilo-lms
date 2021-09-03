<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class ExtraFieldRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getManager();
        $repo = self::getContainer()->get(ExtraFieldRepository::class);

        $defaultCount = $repo->count([]);

        $item = (new ExtraField())
            ->setDisplayText('test')
            ->setVariable('test')
            ->setExtraFieldType(ExtraField::USER_FIELD_TYPE)
            ->setFieldType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame($defaultCount + 1, $repo->count([]));
    }
}
