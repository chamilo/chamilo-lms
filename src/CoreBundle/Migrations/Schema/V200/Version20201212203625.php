<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201212203625 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate documents';
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
        $sessionRepo = $container->get(SessionRepository::class);
        $groupRepo = $container->get(CGroupRepository::class);
        $userRepo = $container->get(UserRepository::class);

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $userList = [];
        $groupList = [];
        $sessionList = [];
        $batchSize = self::BATCH_SIZE;

        $admin = $this->getAdmin();
        $urlRepo = $em->getRepository(AccessUrl::class);
        $urls = $urlRepo->findAll();

        /** @var AccessUrl $url */
        foreach ($urls as $url) {
            $accessUrlRelCourses = $url->getCourses();
            /** @var AccessUrlRelCourse $accessUrlRelCourse */
            foreach ($accessUrlRelCourses as $accessUrlRelCourse) {
                $counter = 1;
                $course = $accessUrlRelCourse->getCourse();
                $courseId = $course->getId();
                $courseCode = $course->getCode();
                $course = $courseRepo->find($courseId);

                $sql = "SELECT * FROM c_document WHERE c_id = $courseId
                        ORDER BY filetype DESC";
                $result = $connection->executeQuery($sql);
                $documents = $result->fetchAllAssociative();
                foreach ($documents as $documentData) {
                    $documentId = $documentData['iid'];
                    $documentPath = $documentData['path'];

                    /** @var CDocument $document */
                    $document = $documentRepo->find($documentId);
                    if ($document->hasResourceNode()) {
                        continue;
                    }

                    $sql = "SELECT * FROM c_item_property
                            WHERE tool = 'document' AND c_id = $courseId AND ref = $documentId";
                    $result = $connection->executeQuery($sql);
                    $items = $result->fetchAllAssociative();

                    // For some reason this document doesnt have a c_item_property value.
                    if (empty($items)) {
                        continue;
                    }

                    $createNode = false;
                    $resourceNode = null;
                    $parent = null;
                    if ('.' !== dirname($documentPath)) {
                        $parentId = \DocumentManager::get_document_id(
                            ['real_id' => $courseId],
                            dirname($documentPath)
                        );
                        $parent = $documentRepo->find($parentId);
                    }

                    if (null === $parent) {
                        $parent = $course;
                    }

                    $this->fixItemProperty($documentRepo, $course, $admin, $document, $parent, $items);
                    $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/document/'.$documentPath;
                    $this->addLegacyFileToResource($filePath, $documentRepo, $document, $documentId);

                    $em->persist($document);
                    $em->flush();
                    //$em->clear();
                    /*if ($createNode) {
                        $em->persist($document);
                        if (0 === $counter % $batchSize) {
                            $em->flush();
                            $em->clear();
                        }

                        $counter++;
                    } else {
                        $em->clear();
                    }*/
                }
                $em->clear();
            }

            $em->flush();
            $em->clear();
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
