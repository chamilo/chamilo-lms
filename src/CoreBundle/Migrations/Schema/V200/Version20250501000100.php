<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Repository\CStudentPublicationCommentRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20250501000100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate student publications (works), corrections, and comments to ResourceNode/ResourceFile';
    }

    public function up(Schema $schema): void
    {
        $publicationRepo = $this->container->get(CStudentPublicationRepository::class);
        $commentRepo = $this->container->get(CStudentPublicationCommentRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $kernel = $this->container->get('kernel');
        $root = $kernel->getProjectDir();

        $courses = $this->entityManager
            ->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c')
            ->toIterable()
        ;

        foreach ($courses as $course) {
            $courseDir = $course->getDirectory();
            $workPath = "{$root}/app/courses/{$courseDir}/work";
            error_log("[MIGRATION] Processing course '{$course->getCode()}' (ID: {$course->getId()})");

            $publications = $publicationRepo->createQueryBuilder('sp')
                ->join('sp.resourceNode', 'rn')
                ->join('rn.resourceLinks', 'rl')
                ->where('rl.course = :course')
                ->andWhere('sp.filetype = :file')
                ->setParameter('course', $course)
                ->setParameter('file', 'file')
                ->getQuery()
                ->getResult()
            ;

            foreach ($publications as $publication) {
                if (!$publication instanceof CStudentPublication || !$publication->getResourceNode()) {
                    continue;
                }

                $row = $this->connection->fetchAssociative(
                    'SELECT * FROM c_student_publication WHERE iid = ?',
                    [$publication->getIid()]
                );
                $resourceNode = $publication->getResourceNode();

                $url = $row['url'] ?? null;
                if (!empty($url) && str_starts_with($url, 'work/') && !$this->resourceNodeHasFile($resourceNode, basename($url))) {
                    $filename = basename($url);
                    $source = "{$workPath}/{$url}";
                    error_log("[MIGRATION] Submission source: $source");

                    if ($this->fileExists($source)) {
                        $this->addLegacyFileToResource($source, $publicationRepo, $publication, $row['iid'], $filename);
                        $this->entityManager->persist($publication);
                    } else {
                        error_log("[MIGRATION][ERROR] Submission file not found: $source");
                    }
                }

                $correctionUrl = $row['url_correction'] ?? null;
                if (!empty($correctionUrl) && str_starts_with($correctionUrl, 'work/') && !$this->resourceNodeHasFile($resourceNode, basename($correctionUrl))) {
                    $filename = basename($correctionUrl);
                    $source = "{$workPath}/{$correctionUrl}";
                    error_log("[MIGRATION] Correction source: $source");

                    if ($this->fileExists($source)) {
                        $this->addLegacyFileToResource($source, $publicationRepo, $publication, $row['iid'], $filename);
                        $publication->setExtensions($filename);
                        $this->entityManager->persist($publication);
                    } else {
                        error_log("[MIGRATION][WARN] Correction file not found: $source");
                    }
                }

                $this->entityManager->flush();
            }

            $comments = $commentRepo->createQueryBuilder('c')
                ->join('c.publication', 'sp')
                ->join('sp.resourceNode', 'rn')
                ->join('rn.resourceLinks', 'rl')
                ->where('rl.course = :course')
                ->andWhere('c.file IS NOT NULL')
                ->setParameter('course', $course)
                ->getQuery()
                ->getResult()
            ;

            foreach ($comments as $comment) {
                if (!$comment instanceof CStudentPublicationComment || !$comment->getResourceNode()) {
                    continue;
                }

                $row = $this->connection->fetchAssociative(
                    'SELECT * FROM c_student_publication_comment WHERE iid = ?',
                    [$comment->getIid()]
                );

                $filename = basename($row['file']);
                $source = "{$workPath}/{$filename}";
                $resourceNode = $comment->getResourceNode();
                error_log("[MIGRATION] Comment source: $source");

                if (!$this->resourceNodeHasFile($resourceNode, $filename)) {
                    if ($this->fileExists($source)) {
                        $this->addLegacyFileToResource($source, $commentRepo, $comment, $row['iid'], $filename);
                        $this->entityManager->persist($comment);
                        $this->entityManager->flush();
                    } else {
                        error_log("[MIGRATION][WARN] Comment file not found: $source");
                    }
                }
            }

            $this->entityManager->clear();
            error_log("[MIGRATION] Finished processing course '{$course->getCode()}'");
        }
    }

    private function resourceNodeHasFile($resourceNode, string $filename): bool
    {
        foreach ($resourceNode->getResourceFiles() as $file) {
            if ($file->getTitle() === $filename || $file->getOriginalName() === $filename) {
                return true;
            }
        }

        return false;
    }
}
