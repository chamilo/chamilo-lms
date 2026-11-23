<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    /**
     * A user-uploaded file (e.g. a profile file field) must never render as markup in the
     * platform origin, whoever opens its link.
     */
    public function testShowFileDeliversUserMarkupAsDownload(): void
    {
        $client = static::createClient();
        $url = $this->createMarkupAsset(Asset::EXTRA_FIELD, 'FILE_1_profile.html');

        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $headers = $client->getResponse()->headers;
        $this->assertSame('application/octet-stream', $headers->get('Content-Type'));
        $this->assertStringStartsWith('attachment', (string) $headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
    }

    /**
     * SCORM packages are served from the assets too, and their pages must still render.
     */
    public function testShowFileRendersScormMarkupInline(): void
    {
        $client = static::createClient();
        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $filePath = Asset::SCORM.'/test_package_'.uniqid().'/index.html';
        $assetRepo->getFileSystem()->write($filePath, '<!doctype html><p>asset</p>');

        try {
            $client->request('GET', '/assets/'.$filePath);
            $this->assertResponseIsSuccessful();

            $headers = $client->getResponse()->headers;
            $this->assertStringContainsString('text/html', (string) $headers->get('Content-Type'));
            $this->assertStringStartsWith('inline', (string) $headers->get('Content-Disposition'));
        } finally {
            $assetRepo->getFileSystem()->deleteDirectory(\dirname($filePath));
        }
    }

    private function createMarkupAsset(string $category, string $title): string
    {
        $path = tempnam(sys_get_temp_dir(), 'asset');
        file_put_contents($path, '<!doctype html><p>asset</p>');

        $asset = (new Asset())
            ->setTitle($title)
            ->setCategory($category)
            ->setFile(new UploadedFile($path, $title, 'text/html', null, true))
        ;
        $em = $this->getEntityManager();
        $em->persist($asset);
        $em->flush();

        return self::getContainer()->get(AssetRepository::class)->getAssetUrl($asset);
    }
}
