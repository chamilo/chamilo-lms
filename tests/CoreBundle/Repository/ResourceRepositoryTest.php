<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ResourceRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testGetCount(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(AccessUrlRepository::class);
        $qb = $repo->createQueryBuilder('resource');
        $count = $repo->getCount($qb);
        // In a fresh installation, Chamilo has one default AccessUrl.
        // Added in AccessUrlFixtures.php
        $this->assertSame(1, $count);
    }

    public function testGetResourceByResourceNode(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(AccessUrlRepository::class);
        $url = $this->getAccessUrl();

        $resource = $repo->getResourceByResourceNode($url->getResourceNode());

        $this->assertInstanceOf(AccessUrl::class, $resource);
    }

    public function testGetResourceFromResourceNode(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(AccessUrlRepository::class);
        $url = $this->getAccessUrl();

        $resource = $repo->getResourceFromResourceNode($url->getResourceNode()->getId());

        $this->assertInstanceOf(AccessUrl::class, $resource);
    }

    public function testAddFileFromString(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(AccessUrlRepository::class);
        $url = $this->getAccessUrl();
        $repo->addFileFromString($url, 'test', 'text/html', 'my string', true);

        $url = $this->getAccessUrl();
        $this->assertTrue($url->getResourceNode()->hasResourceFile());

        $content = $repo->getResourceFileContent($url);
        $this->assertSame('my string', $content);

        $content = $repo->getResourceNodeFileContent($url->getResourceNode());
        $this->assertSame('my string', $content);

        $resource = $repo->getResourceNodeFileStream($url->getResourceNode());
        $this->assertIsResource($resource);

        $downloadUrl = $repo->getResourceFileDownloadUrl($url);
        $this->assertNotEmpty($downloadUrl);
    }

    public function testUpdateResourceFileContent(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(AccessUrlRepository::class);
        $url = $this->getAccessUrl();
        $repo->addFileFromString($url, 'test', 'text/html', 'my string', true);

        $url = $this->getAccessUrl();
        $repo->updateResourceFileContent($url, 'my string modified');
        $repo->update($url);

        $content = $repo->getResourceFileContent($url);
        $this->assertSame('my string modified', $content);
    }
}
