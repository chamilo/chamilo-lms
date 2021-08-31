<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Twig\Extension;

use Chamilo\CoreBundle\Twig\Extension\ChamiloExtension;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class ChamiloExtensionTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetIllustration(): void
    {
        self::bootKernel();

        $extension = self::getContainer()->get(ChamiloExtension::class);

        $user = $this->createUser('test');
        $illustrationUrl = $extension->getIllustration($user);

        $this->assertSame('/img/icons/32/unknown.png', $illustrationUrl);
    }
}
