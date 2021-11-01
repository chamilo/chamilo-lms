<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testShowFile(): void
    {
        $client = static::createClient();
        $file = $this->getUploadedFile();

        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $em = $this->getEntityManager();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::WATERMARK)
            ->setFile($file)
        ;
        $em->persist($asset);
        $em->flush();

        $url = $assetRepo->getAssetUrl($asset);
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->request('GET', $url.'not-existed');
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }

    public function testShowFileNoImage(): void
    {
        $client = static::createClient();
        $file = $this->getUploadedZipFile();

        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $em = $this->getEntityManager();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::WATERMARK)
            ->setFile($file)
        ;
        $em->persist($asset);
        $em->flush();

        $url = $assetRepo->getAssetUrl($asset);
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }
}
