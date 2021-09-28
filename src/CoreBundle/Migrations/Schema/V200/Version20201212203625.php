<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use DocumentManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20201212203625 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_document';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $documentRepo = $container->get(CDocumentRepository::class);
        $courseRepo = $container->get(CourseRepository::class);
        $attemptRepo = $em->getRepository(TrackEAttempt::class);

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $batchSize = self::BATCH_SIZE;

        // Migrate teacher exercise audio.
        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $sql = "SELECT iid, path
                    FROM c_document
                    WHERE
                          c_id = $courseId AND
                          path LIKE '/../exercises/teacher_audio%'
                    ";
            $result = $connection->executeQuery($sql);
            $documents = $result->fetchAllAssociative();

            foreach ($documents as $documentData) {
                $documentId = $documentData['iid'];
                $path = $documentData['path'];

                $path = str_replace('//', '/', $path);
                $path = str_replace('/../exercises/teacher_audio/', '', $path);

                $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/exercises/teacher_audio/'.$path;

                if ($this->fileExists($filePath)) {
                    preg_match('#/(.*)/#', '/'.$path, $matches);
                    if (isset($matches[1]) && !empty($matches[1])) {
                        $attemptId = $matches[1];
                        /** @var TrackEAttempt $attempt */
                        $attempt = $attemptRepo->find($attemptId);
                        if (null !== $attempt) {
                            if ($attempt->getAttemptFeedbacks()->count() > 0) {
                                continue;
                            }

                            $fileName = basename($filePath);
                            $mimeType = mime_content_type($filePath);
                            $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);
                            $asset = (new Asset())
                                ->setCategory(Asset::EXERCISE_FEEDBACK)
                                ->setTitle($fileName)
                                ->setFile($file)
                            ;
                            $em->persist($asset);
                            $em->flush();

                            $attemptFile = (new AttemptFile())
                                ->setAsset($asset)
                            ;
                            $attempt->addAttemptFile($attemptFile);
                            $em->persist($attemptFile);
                            $em->flush();

                            /*$sql = "UPDATE c_document
                                    SET comment = 'skip_migrate'
                                    WHERE iid = $documentId
                            ";
                            $connection->executeQuery($sql);*/
                        }
                    }
                }
            }
            $em->flush();
            $em->clear();
        }

        $em->flush();
        $em->clear();

        // Migrate student exercise audio
        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();

            $sql = "SELECT iid, path
                    FROM c_document
                    WHERE
                          c_id = $courseId AND
                          path NOT LIKE '/../exercises/teacher_audio%' AND
                          path LIKE '/../exercises/%'
                    ";
            $result = $connection->executeQuery($sql);
            $documents = $result->fetchAllAssociative();

            foreach ($documents as $documentData) {
                $documentId = $documentData['iid'];
                $path = $documentData['path'];

                $path = str_replace('//', '/', $path);
                $path = str_replace('/../exercises/', '', $path);

                $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/exercises/'.$path;
                if ($this->fileExists($filePath)) {
                    $fileName = basename($filePath);
                    preg_match('#/(.*)/(.*)/(.*)/(.*)/#', '/'.$path, $matches);
                    $sessionId = $matches[1] ?? 0;
                    $exerciseId = $matches[2] ?? 0;
                    $questionId = $matches[3] ?? 0;
                    $userId = $matches[4] ?? 0;

                    /** @var TrackEAttempt $attempt */
                    $attempt = $attemptRepo->findOneBy([
                        'user' => $userId,
                        'questionId' => $questionId,
                        'filename' => $fileName,
                    ]);
                    if (null !== $attempt) {
                        if ($attempt->getAttemptFiles()->count() > 0) {
                            continue;
                        }

                        $mimeType = mime_content_type($filePath);
                        $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);
                        $asset = (new Asset())
                            ->setCategory(Asset::EXERCISE_ATTEMPT)
                            ->setTitle($fileName)
                            ->setFile($file)
                        ;
                        $em->persist($asset);
                        $em->flush();

                        $attemptFile = (new AttemptFile())
                            ->setAsset($asset)
                        ;
                        $attempt->addAttemptFile($attemptFile);
                        $em->persist($attemptFile);
                        $em->flush();

                        /*$sql = "UPDATE c_document
                                SET comment = 'skip_migrate'
                                WHERE iid = $documentId
                        ";
                        $connection->executeQuery($sql);*/
                    }
                }
            }
            $em->flush();
            $em->clear();
        }

        $em->flush();
        $em->clear();

        // Migrate normal documents.
        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $counter = 1;
            $courseId = $course->getId();

            $sql = "SELECT iid, path FROM c_document
                    WHERE
                          c_id = {$courseId} AND
                          path NOT LIKE '/../exercises/%'
                    ORDER BY filetype DESC, path";
            $result = $connection->executeQuery($sql);
            $documents = $result->fetchAllAssociative();
            foreach ($documents as $documentData) {
                $documentId = $documentData['iid'];
                $documentPath = $documentData['path'];
                $course = $courseRepo->find($courseId);

                /** @var CDocument $document */
                $document = $documentRepo->find($documentId);
                if ($document->hasResourceNode()) {
                    continue;
                }

                $parent = null;
                if ('.' !== \dirname($documentPath)) {
                    $parentId = (int) DocumentManager::get_document_id(
                        [
                            'real_id' => $courseId,
                        ],
                        \dirname($documentPath)
                    );
                    if (!empty($parentId)) {
                        $parent = $documentRepo->find($parentId);
                    }
                }

                if (null === $parent) {
                    $parent = $course;
                }
                $admin = $this->getAdmin();
                $result = $this->fixItemProperty('document', $documentRepo, $course, $admin, $document, $parent);

                if (false === $result) {
                    continue;
                }

                $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/document/'.$documentPath;
                $this->addLegacyFileToResource($filePath, $documentRepo, $document, $documentId);
                $em->persist($document);

                if (($counter % $batchSize) === 0) {
                    $em->flush();
                    $em->clear();
                }
                $counter++;
            }
            $em->flush();
            $em->clear();
        }

        $em->flush();
        $em->clear();
    }

    public function down(Schema $schema): void
    {
    }
}
