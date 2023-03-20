<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceFactory;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\ChamiloTestTrait;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResourceFactoryTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testGetRepositoryService(): void
    {
        self::bootKernel();
        $factory = self::getContainer()->get(ResourceFactory::class);

        $this->expectException(InvalidArgumentException::class);
        $factory->getRepositoryService('aaa', 'bbb');

        $repository = $factory->getRepositoryService('document', 'files');

        $this->assertInstanceOf(ResourceRepository::class, $repository);
        $this->assertInstanceOf(CDocumentRepository::class, $repository);

        $this->expectException(InvalidArgumentException::class);
        $factory->getRepositoryService('document', 'xxx');

        $this->expectException(InvalidArgumentException::class);
        $factory->getRepositoryService('xxx', 'xxx');
    }
}
