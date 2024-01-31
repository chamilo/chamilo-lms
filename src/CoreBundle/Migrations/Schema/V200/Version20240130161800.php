<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Templates;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\Kernel;
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
        $container = $this->getContainer();

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $doctrine = $container->get('doctrine');

        $em = $doctrine->getManager();
        $connection = $em->getConnection();

        $sql = "SELECT id, image, c_id FROM templates WHERE image IS NOT NULL";
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $imagePath = $row['image'];
            $templateId = $row['id'];
            $courseId = $row['c_id'];

            $courseDirectorySql = "SELECT directory FROM course WHERE id = :courseId";
            $courseStmt = $connection->prepare($courseDirectorySql);
            $courseResult = $courseStmt->executeQuery(['courseId' => $courseId]);

            $courseRow = $courseResult->fetchAssociative();

            if ($courseRow) {
                $directory = $courseRow['directory'];
                $thumbPath = $rootPath.'/app/courses/'.$directory.'/upload/template_thumbnails/'.$imagePath;

                if (file_exists($thumbPath)) {
                    $mimeType = mime_content_type($thumbPath);
                    $fileName = basename($thumbPath);
                    $file = new UploadedFile($thumbPath, $fileName, $mimeType, null, true);

                    $asset = new Asset();
                    $asset->setCategory(Asset::TEMPLATE);
                    $asset->setTitle($fileName);
                    $asset->setFile($file);

                    $em->persist($asset);
                    $em->flush();

                    $template = $em->getRepository(Templates::class)->find($templateId);
                    if ($template) {
                        $template->setImage($asset);
                        $em->persist($template);
                        $em->flush();
                    }
                }
            }
        }
    }
}
