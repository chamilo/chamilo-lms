<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UsergroupRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCount()
    {
        self::bootKernel();
        $repo = self::getContainer()->get(UsergroupRepository::class);

        $usergroup = (new Usergroup())
            ->setName('test')
            ->addAccessUrl($this->getAccessUrl())
            ->setCreator($this->getUser('admin'))
        ;

        $this->assertHasNoEntityViolations($usergroup);
        $repo->create($usergroup);
        $this->assertEquals(1, $repo->count([]));
    }
}
