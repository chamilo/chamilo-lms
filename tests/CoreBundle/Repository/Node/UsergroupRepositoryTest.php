<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \UsergroupRepository
 */
class UsergroupRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCount(): void
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
        $this->assertSame(1, $repo->count([]));
    }
}
