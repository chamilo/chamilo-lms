<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

class AssetRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreateAsset(): void
    {
        self::bootKernel();

        $em = $this->getManager();
        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $file = $this->getUploadedFile();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::WATERMARK)
            ->setFile($file)
        ;
        $this->assertHasNoEntityViolations($asset);
        $em->persist($asset);
        $em->flush();

        // 1 asset
        $this->assertSame(1, $assetRepo->count([]));
    }

    public function testAssetWatermark(): void
    {
        self::bootKernel();

        $em = $this->getManager();
        /** @var AssetRepository $assetRepo */
        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $file = $this->getUploadedFile();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::WATERMARK)
            ->setFile($file)
        ;
        $em->persist($asset);
        $em->flush();

        $url = $assetRepo->getAssetUrl($asset);

        // Check Asset URL.
        $this->assertNotEmpty($url);

        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }

    public function testAssetDelete(): void
    {
        self::bootKernel();

        $em = $this->getManager();
        /** @var AssetRepository $assetRepo */
        $assetRepo = self::getContainer()->get(AssetRepository::class);

        $file = $this->getUploadedFile();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::WATERMARK)
            ->setFile($file)
        ;
        $em->persist($asset);
        $em->flush();

        $url = $assetRepo->getAssetUrl($asset);

        $client = static::createClient();
        $response = $client->request('GET', $url);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->assertResponseIsSuccessful();

        $assetRepo->delete($asset);

        /*$client->request('GET', $url);
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());*/
    }
}
