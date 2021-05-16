<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use DocumentManager;

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

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $batchSize = self::BATCH_SIZE;

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $counter = 1;
            $courseId = $course->getId();

            $sql = "SELECT * FROM c_document WHERE c_id = {$courseId}
                    ORDER BY filetype DESC";
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
                    $parentId = DocumentManager::get_document_id(
                        [
                            'real_id' => $courseId,
                        ],
                        \dirname($documentPath)
                    );
                    $parent = $documentRepo->find($parentId);
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
