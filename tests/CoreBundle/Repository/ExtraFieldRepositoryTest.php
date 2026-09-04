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

    public function testGetExtraFields(): void
    {
        $repo = static::getContainer()->get(ExtraFieldRepository::class);
        $this->assertNotNull($repo->getExtraFields(ExtraField::USER_FIELD_TYPE));
    }
}
