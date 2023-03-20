<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class AssetRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreateAsset(): void
    {
        $em = $this->getEntityManager();
        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $file = $this->getUploadedFile();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setDescription('desc')
            ->setCategory(Asset::WATERMARK)
            ->setFile($file)
        ;
        $this->assertHasNoEntityViolations($asset);
        $em->persist($asset);
        $em->flush();

        $this->assertSame(1, $assetRepo->count([]));

        $this->assertSame(Asset::WATERMARK.'/'.$asset->getOriginalName(), $asset->getFolder());
        $this->assertTrue($asset->isImage());
        $this->assertFalse($asset->isVideo());
        $this->assertSame($file->getSize(), $asset->getSize());
        $this->assertSame(24, $asset->getWidth());
        $this->assertSame(24, $asset->getHeight());
        $this->assertSame([], $asset->getMetadata());
        $this->assertFalse($asset->getCompressed());

        $this->assertSame($file->getFilename(), (string) $asset);
    }

    public function testCreateWatermark(): void
    {
        $client = static::createClient();
        $file = $this->getUploadedFile();

        $assetRepo = self::getContainer()->get(AssetRepository::class);

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::WATERMARK)
            ->setCrop('100,100,100,100')
            ->setFile($file)
        ;
        $this->assertHasNoEntityViolations($asset);
        $assetRepo->update($asset);

        $folder = $assetRepo->getFolder($asset);
        $this->assertSame('/watermark/'.$asset->getFile()->getFilename().'/', $folder);

        $url = $assetRepo->getAssetUrl($asset);
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $asset = (new Asset())
            ->setTitle('test2')
            ->setCategory(Asset::WATERMARK)
            ->setCrop('100,100,100,100')
        ;
        $assetRepo->createFromString($asset, 'text/html', 'hello');
        $this->assertHasNoEntityViolations($asset);

        $asset = (new Asset())
            ->setTitle('test3')
            ->setCategory(Asset::WATERMARK)
        ;

        $file = $this->getUploadedFileArray();
        $assetRepo->createFromRequest($asset, $file);
        $this->assertHasNoEntityViolations($asset);
    }

    public function testUnZipFile(): void
    {
        $client = static::createClient();
        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $this->assertSame(0, $assetRepo->count([]));
        $file = $this->getUploadedZipFile();

        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::SCORM)
            ->setFile($file)
            ->setCompressed(true)
        ;
        $assetRepo->update($asset);

        $this->assertHasNoEntityViolations($asset);

        $assetRepo->unZipFile($asset);

        $url = $assetRepo->getAssetUrl($asset);

        $client->request('GET', $url.'/logo.png');
        $this->assertResponseIsSuccessful();
    }

    public function testUnZipFileSubFolder(): void
    {
        $client = static::createClient();
        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $this->assertSame(0, $assetRepo->count([]));
        $file = $this->getUploadedZipFile();

        $asset = (new Asset())
            ->setTitle('my file')
            ->setCategory(Asset::SCORM)
            ->setFile($file)
            ->setCompressed(true)
        ;
        $assetRepo->update($asset);

        $this->assertHasNoEntityViolations($asset);
        $subDir = 'test/test';

        $assetRepo->unZipFile($asset, $subDir);
        $url = $assetRepo->getAssetUrl($asset);

        $client->request('GET', $url.'/'.$subDir.'/logo.png');
        $this->assertResponseIsSuccessful();
    }

    public function testDelete(): void
    {
        $assetRepo = self::getContainer()->get(AssetRepository::class);

        $file = $this->getUploadedFile();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::WATERMARK)
            ->setFile($file)
        ;
        $assetRepo->update($asset);

        $asset = $assetRepo->find($asset->getId());
        $assetRepo->delete($asset);

        $this->assertSame(0, $assetRepo->count([]));
    }
}
