<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20240128205500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate certificate files of users to personal_files';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $em = $this->getEntityManager();

        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $q = $em->createQuery('SELECT u FROM Chamilo\CoreBundle\Entity\User u');

        foreach ($q->toIterable() as $userEntity) {
            $id = $userEntity->getId();
            $path = 'users/' . substr((string) $id, 0, 1) . '/' . $id . '/';

            $certificateDir = $rootPath . '/app/upload/' . $path . 'certificate/';

            if (!is_dir($certificateDir)) {
                continue;
            }

            $files = glob($certificateDir . '*');

            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $originalTitle = basename($file);

                // Search in gradebook_certificate for a record with a path_certificate that matches $originalTitle
                $certificate = $em->getRepository(GradebookCertificate::class)->findOneBy(['pathCertificate' => '/' . $originalTitle]);
                if (!$certificate) {
                    // If not found, continue with the next file
                    continue;
                }

                $catId = null !== $certificate->getCategory() ? $certificate->getCategory()->getId() : 0;
                $newTitle = hash('sha256', $id . $catId) . '.html';

                $existingFile = $em->getRepository(PersonalFile::class)->findOneBy(['title' => $newTitle]);
                if ($existingFile) {
                    error_log('MIGRATIONS :: Skipping file -- ' . $file . ' (Already exists)');
                    continue;
                }

                error_log('MIGRATIONS :: Processing file -- ' . $file);

                $personalFile = new PersonalFile();
                $personalFile->setTitle($newTitle);
                $personalFile->setCreator($userEntity);
                $personalFile->setParentResourceNode($userEntity->getResourceNode()->getId());
                $personalFile->setResourceName($newTitle);
                $mimeType = mime_content_type($file);
                $uploadedFile = new UploadedFile($file, $newTitle, $mimeType, null, true);
                $personalFile->setUploadFile($uploadedFile);
                $personalFile->addUserLink($userEntity);

                $em->persist($personalFile);
                $em->flush();

                // Update the record in gradebook_certificate with the new title
                $certificate->setPathCertificate('/' . $newTitle);
                $em->flush();

            }
        }
    }
}
