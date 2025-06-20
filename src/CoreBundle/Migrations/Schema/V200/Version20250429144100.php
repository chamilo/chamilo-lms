<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\Schema\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

final class Version20250429144100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate SCORM packages into asset system.';
    }

    public function up(Schema $schema): void
    {
        $this->log('Starting SCORM migration...');

        $assetRepo = $this->container->get(AssetRepository::class);
        $lpRepo = $this->container->get(CLpRepository::class);

        $courses = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c')->toIterable();

        foreach ($courses as $course) {
            $courseId = $course->getId();
            $courseDir = $course->getDirectory();
            $this->log("Processing course ID: $courseId - Directory: $courseDir");

            $scorms = $lpRepo->createQueryBuilder('lp')
                ->join('lp.resourceNode', 'rn')
                ->join('rn.resourceLinks', 'rl')
                ->where('lp.lpType = :type')
                ->andWhere('rl.course = :course')
                ->setParameter('type', CLp::SCORM_TYPE)
                ->setParameter('course', $course)
                ->getQuery()
                ->getResult()
            ;

            if (empty($scorms)) {
                $this->log("No SCORMs found for course $courseDir");

                continue;
            }

            foreach ($scorms as $lp) {
                $lpId = $lp->getIid();
                $path = rtrim($lp->getPath(), '/.');

                $this->log("Processing SCORM LP id=$lpId path=$path");

                $folderPath = $this->getUpdateRootPath()."/app/courses/$courseDir/scorm/$path";

                if (!is_dir($folderPath)) {
                    $this->log("SCORM folder not found: $folderPath");

                    continue;
                }

                $zipName = basename($path).'.zip';
                $tmpZipPath = "/tmp/$zipName";

                if (!file_exists($tmpZipPath)) {
                    $this->log("Zipping SCORM folder: $folderPath -> $tmpZipPath");
                    $this->zipFolder($folderPath, $tmpZipPath);
                }

                if (!file_exists($tmpZipPath)) {
                    $this->log("Failed to create zip: $tmpZipPath");

                    continue;
                }

                $file = new UploadedFile($tmpZipPath, $zipName, 'application/zip', null, true);

                $asset = new Asset();
                $asset->setCategory(Asset::SCORM)
                    ->setTitle($zipName)
                    ->setFile($file)
                    ->setCompressed(true)
                ;

                $assetRepo->update($asset);

                $this->log('Asset created: id='.$asset->getId());

                $assetRepo->unZipFile($asset, basename($path));
                $this->log('Asset unzipped for: '.$asset->getTitle());

                $lp->setAsset($asset);
                $lp->setPath(basename($path).'/.');
                $this->entityManager->persist($lp);
                $this->entityManager->flush();

                $this->log("LP updated id=$lpId linked to asset_id=".$asset->getId());
            }
        }

        $this->log('Finished SCORM migration.');
    }

    private function zipFolder(string $folderPath, string $zipPath): void
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            $this->log("Cannot create ZIP file: $zipPath");

            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, \strlen($folderPath) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();
    }

    private function log(string $message): void
    {
        error_log('[SCORM Migration] '.$message);
    }

    public function down(Schema $schema): void {}
}
