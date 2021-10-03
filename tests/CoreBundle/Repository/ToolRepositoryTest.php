<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Repository\ToolRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class ToolRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(ToolRepository::class);
        $defaultCount = $repo->count([]);

        $tool = (new Tool())
            ->setName('test')
        ;
        $em->persist($tool);
        $em->flush();

        $this->assertSame($defaultCount + 1, $repo->count([]));
        $this->assertSame('test', $tool->getName());
    }
}
