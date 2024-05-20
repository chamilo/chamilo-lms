<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20240519210000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate exercise audio files';
    }

    public function up(Schema $schema): void
    {
        $attemptRepo = $this->entityManager->getRepository(TrackEAttempt::class);

        // Migrate exercise audio files
        $sql = "SELECT ta.exe_id, ta.question_id, ta.user_id, ta.session_id, te.exe_exo_id, ta.filename
                FROM track_e_attempt ta
                INNER JOIN track_e_exercises te ON te.exe_id = ta.exe_id
                WHERE (ta.filename IS NOT NULL AND ta.filename != '')";
        $result = $this->connection->executeQuery($sql);
        $attempts = $result->fetchAllAssociative();

        foreach ($attempts as $attemptData) {
            $sessionId = (int) $attemptData['session_id'];
            $exerciseId = (int) $attemptData['exe_exo_id'];
            $questionId = (int) $attemptData['question_id'];
            $userId = (int) $attemptData['user_id'];
            $filename = $attemptData['filename'];

            $pathPattern = "{$sessionId}/{$exerciseId}/{$questionId}/{$userId}/{$filename}";
            $courseDir = $this->findCourseDirectory($pathPattern);

            if ($courseDir) {
                $filePath = "app/courses/{$courseDir}/exercises/{$pathPattern}";
                $this->processFile($filePath, $attemptRepo, $attemptData);
            } else {
                error_log('MIGRATIONS :: File not found for pattern: ' . $pathPattern);
            }
        }

        $this->entityManager->flush();
    }

    private function findCourseDirectory(string $pathPattern): ?string
    {
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir() . '/app/courses/';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($rootPath));

        foreach ($iterator as $file) {
            if (str_contains($file->getPathname(), $pathPattern)) {
                $relativePath = str_replace($rootPath, '', $file->getPath());

                return explode('/', $relativePath)[0];
            }
        }

        return null;
    }

    private function processFile(string $filePath, $attemptRepo, array $attemptData): void
    {
        if (file_exists($filePath)) {
            $fileName = basename($filePath);

            /** @var TrackEAttempt $attempt */
            $attempt = $attemptRepo->findOneBy([
                'user' => $attemptData['user_id'],
                'questionId' => $attemptData['question_id'],
                'filename' => $fileName,
            ]);

            if (null !== $attempt) {
                if ($attempt->getAttemptFiles()->count() > 0) {
                    return;
                }

                $mimeType = mime_content_type($filePath);
                $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);

                $asset = (new Asset())
                    ->setCategory(Asset::EXERCISE_ATTEMPT)
                    ->setTitle($fileName)
                    ->setFile($file);
                $this->entityManager->persist($asset);
                $this->entityManager->flush();

                $attemptFile = (new AttemptFile())
                    ->setAsset($asset);
                $attempt->addAttemptFile($attemptFile);
                $this->entityManager->persist($attemptFile);
                $this->entityManager->flush();

                error_log('MIGRATIONS :: File processed and inserted as asset and attempt file: ' . $filePath);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // This migration is not reversible
    }
}
