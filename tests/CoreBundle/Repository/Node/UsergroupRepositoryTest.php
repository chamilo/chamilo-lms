<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UsergroupRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(UsergroupRepository::class);

        $group = (new Usergroup())
            ->setName('test')
            ->addAccessUrl($this->getAccessUrl())
            ->setCreator($this->getUser('admin'))
        ;

        $this->assertHasNoEntityViolations($group);
        $repo->create($group);
        $this->assertSame(1, $repo->count([]));

        $group->setName('test2');
        $repo->update($group);

        $this->assertSame(1, $repo->count([]));

        $repo->delete($group);
        $this->assertSame(0, $repo->count([]));
    }
}
