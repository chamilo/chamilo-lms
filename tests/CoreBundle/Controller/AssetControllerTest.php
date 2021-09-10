<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class AssetControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

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

        $file = [
            'tmp_name' => $this->getUploadedFile()->getRealPath(),
            'name' => $this->getUploadedFile()->getFilename(),
            'type' => $this->getUploadedFile()->getMimeType(),
            'size' => $this->getUploadedFile()->getSize(),
            'error' => UPLOAD_ERR_OK,
        ];

        $assetRepo->createFromRequest($asset, $file);
        $this->assertHasNoEntityViolations($asset);
    }

    public function testCreateScormAsset(): void
    {
        $client = static::createClient();
        $file = $this->getUploadedFile();

        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $this->assertSame(0, $assetRepo->count([]));

        /*$zipPath = '/tmp/example.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFile($file->getRealPath());
        $zip->close();

        $asset = (new Asset())
            ->setTitle('test')
            ->setCategory(Asset::SCORM)
        ;
        $file = [
            'tmp_name' => $zipPath,
            'name' => 'example.zip',
            'type' => 'zip',
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
        ];
        $assetRepo->createFromRequest($asset, $file);

        $this->assertHasNoEntityViolations($asset);*/
        //$assetRepo->update($asset);

        //$url = $assetRepo->getAssetUrl($asset);
        //$client->request('GET', $url);
        //$this->assertResponseIsSuccessful();
    }

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
}
