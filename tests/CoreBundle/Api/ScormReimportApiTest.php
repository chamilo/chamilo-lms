<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Service\LearningPath\ScormRuntimeManager;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

final class ScormReimportApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTheSameScormZipCanBeImportedAfterRepeatedDeletion(): void
    {
        $course = $this->createCourse('SCORM reimport '.bin2hex(random_bytes(4)));
        self::assertNotNull($course);
        $courseId = $course->getId();
        $context = '?cid='.$courseId.'&sid=0&gid=0';
        $path = tempnam(sys_get_temp_dir(), 'scorm-reimport-');
        self::assertNotFalse($path);
        $html = '<!DOCTYPE html><html><body>SCORM reimport regression</body></html>';
        $manifest = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="qa" xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2" xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_rootv1p2">
  <metadata><schema>ADL SCORM</schema><schemaversion>1.2</schemaversion></metadata>
  <organizations default="org"><organization identifier="org"><title>SCORM reimport QA</title><item identifier="item" identifierref="resource"><title>QA page</title></item></organization></organizations>
  <resources><resource identifier="resource" type="webcontent" adlcp:scormtype="sco" href="index.html"><file href="index.html" /></resource></resources>
</manifest>
XML;
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        self::assertTrue($zip->addFromString('imsmanifest.xml', $manifest));
        self::assertTrue($zip->addFromString('index.html', $html));
        self::assertTrue($zip->close());

        $token = $this->getUserToken();
        $client = $this->createClientWithCredentials($token);
        $lpIds = [];
        $storagePaths = [];
        $assetIds = [];

        try {
            for ($cycle = 0; $cycle < 4; ++$cycle) {
                // The client filename and package bytes are identical on every import.
                $response = $client->request('POST', '/api/learning_paths/scorm/import'.$context, [
                    'headers' => ['Content-Type' => 'multipart/form-data'],
                    'extra' => ['files' => ['package' => new UploadedFile($path, 'same-package.zip', 'application/zip', null, true)]],
                ]);
                self::assertResponseStatusCodeSame(201);
                $payload = $response->toArray();
                self::assertSame(1, $payload['count']);
                $lpId = (int) $payload['items'][0]['id'];
                self::assertNotContains($lpId, $lpIds);
                $lpIds[] = $lpId;

                $em = $this->getEntityManager();
                $lp = $em->find(CLp::class, $lpId);
                self::assertInstanceOf(CLp::class, $lp);
                self::assertSame(CLp::SCORM_TYPE, $lp->getLpType());
                $asset = $lp->getAsset();
                self::assertNotNull($asset);
                $assetId = (string) $asset->getId();
                self::assertNotContains($assetId, $assetIds);
                $assetIds[] = $assetId;
                $storagePath = self::getContainer()->get(ScormRuntimeManager::class)->resolveAssetFilePath($lp, 'index.html');
                self::assertNotContains($storagePath, $storagePaths);
                $storagePaths[] = $storagePath;
                $storage = self::getContainer()->get(AssetRepository::class)->getFileSystem();
                self::assertTrue($storage->fileExists($storagePath));
                self::assertSame($html, $storage->read($storagePath));
                $currentCourse = $em->find($course::class, $courseId);
                self::assertNotNull(self::getContainer()->get(CDocumentRepository::class)->findScormZipDocument($currentCourse, $lp));

                $client->request('POST', '/api/learning_paths/'.$lpId.'/manage'.$context, [
                    'json' => ['action' => 'delete'],
                ]);
                self::assertResponseStatusCodeSame(204);
                $em = $this->getEntityManager();
                $deleted = $em->find(CLp::class, $lpId);
                if ($deleted instanceof CLp) {
                    self::assertNull($deleted->getAsset());
                    foreach ($deleted->getResourceNode()?->getResourceLinks() ?? [] as $link) {
                        self::assertNotNull($link->getDeletedAt(), 'Deleted SCORM must not keep an active course link.');
                    }
                }
            }
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
