<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AccessUrlRepositoryTest extends KernelTestCase
{
    public function testCount()
    {
        self::bootKernel();
        $count = self::getContainer()->get(AccessUrlRepository::class)->count([]);
        // localhost default URL (Added in AccessUrlFixtures.php)
        $this->assertEquals(1, $count);
    }
}
