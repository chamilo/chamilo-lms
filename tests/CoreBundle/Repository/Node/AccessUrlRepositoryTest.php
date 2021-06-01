<?php

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AccessUrlRepositoryTest extends KernelTestCase
{
    public function testCount()
    {
        self::bootKernel();
        $count = self::getContainer()->get(AccessUrlRepository::class)->count([]);
        $this->assertEquals(1, $count);
    }
}
