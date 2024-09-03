<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\TemplatesRepository;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Version20240130161800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate template images to Asset entities.';
    }

    public function up(Schema $schema): void
    {
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $templatesRepo = $this->container->get(TemplatesRepository::class);

        $sql = 'SELECT id, image, c_id FROM templates WHERE image IS NOT NULL';
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $imagePath = $row['image'];
            $templateId = $row['id'];
            $courseId = $row['c_id'];

            $courseDirectorySql = 'SELECT directory FROM course WHERE id = :courseId';
            $courseStmt = $this->connection->prepare($courseDirectorySql);
            $courseResult = $courseStmt->executeQuery(['courseId' => $courseId]);

            $courseRow = $courseResult->fetchAssociative();

            if ($courseRow) {
                $directory = $courseRow['directory'];
                $thumbPath = $this->getUpdateRootPath().'/app/courses/'.$directory.'/upload/template_thumbnails/'.$imagePath;

                if (file_exists($thumbPath)) {
                    $mimeType = mime_content_type($thumbPath);
                    $fileName = basename($thumbPath);
                    $file = new UploadedFile($thumbPath, $fileName, $mimeType, null, true);

                    $asset = new Asset();
                    $asset->setCategory(Asset::TEMPLATE);
                    $asset->setTitle($fileName);
                    $asset->setFile($file);

                    $this->entityManager->persist($asset);
                    $this->entityManager->flush();

                    $template = $templatesRepo->find($templateId);
                    if ($template) {
                        $template->setImage($asset);
                        $this->entityManager->persist($template);
                        $this->entityManager->flush();
                    }
                }
            }
        }
    }
}
