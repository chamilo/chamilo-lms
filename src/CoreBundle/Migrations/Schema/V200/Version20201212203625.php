<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\ResourceLink;
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
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
        $urlRepo = $em->getRepository(AccessUrl::class);
        $urls = $urlRepo->findAll();

        // Adding documents to the resource node tree.
        $admin = $this->getAdmin();
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

                $sql = "SELECT * FROM c_document WHERE c_id = $courseId ORDER BY filetype DESC";
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
                        /*$dirList = explode('/', $documentPath);
                        $dirList = array_filter($dirList);
                        $len = count($dirList) + 1;
                        $realDir = '';
                        for ($i = 1; $i < $len; $i++) {
                            $realDir .= '/'.(isset($dirList[$i]) ? $dirList[$i] : '');
                            $parentId = \DocumentManager::get_document_id(['real_id'=> $courseId], $realDir);
                            var_dump($parentId);
                            if (!empty($parentId)) {
                            }
                        }*/
                    }

                    if (null === $parent) {
                        $parent = $course;
                    }

                    $document->setParent($parent);
                    foreach ($items as $item) {
                        $sessionId = $item['session_id'];
                        $visibility = $item['visibility'];
                        $userId = $item['insert_user_id'];
                        $groupId = $item['to_group_id'];

                        $newVisibility = ResourceLink::VISIBILITY_PENDING;
                        switch ($visibility) {
                            case 0:
                                $newVisibility = ResourceLink::VISIBILITY_PENDING;
                                break;
                            case 1:
                                $newVisibility = ResourceLink::VISIBILITY_PUBLISHED;
                                break;
                            case 2:
                                $newVisibility = ResourceLink::VISIBILITY_DELETED;
                                break;
                        }

                        if (isset($userList[$userId])) {
                            $user = $userList[$userId];
                        } else {
                            $user = $userList[$userId] = $userRepo->find($userId);
                        }

                        if (null === $user) {
                            $user = $admin;
                        }

                        $session = null;
                        if (!empty($sessionId)) {
                            if (isset($sessionList[$sessionId])) {
                                $session = $sessionList[$sessionId];
                            } else {
                                $session = $sessionList[$sessionId] = $sessionRepo->find($sessionId);
                            }
                        }

                        $group = null;
                        if (!empty($groupId)) {
                            if (isset($groupList[$groupId])) {
                                $group = $groupList[$groupId];
                            } else {
                                $group = $groupList[$groupId] = $groupRepo->find($groupId);
                            }
                        }

                        if (null === $resourceNode) {
                            $resourceNode = $documentRepo->addResourceNode($document, $user, $parent);
                            $em->persist($resourceNode);
                        }
                        $document->addCourseLink($course, $session, $group, $newVisibility);
                        $em->persist($document);
                        $createNode = true;
                    }

                    $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/document/'.$documentPath;
                    if (!is_dir($filePath)) {
                        $createNode = false;
                        if (file_exists($filePath)) {
                            $mimeType = mime_content_type($filePath);
                            $file = new UploadedFile($filePath, basename($documentPath), $mimeType, null, true);
                            if ($file) {
                                $createNode = true;
                                $documentRepo->addFile($document, $file);
                            } else {
                                $this->warnIf(
                                    true,
                                    'Cannot migrate doc #'.$documentData['iid'].' path: '.$documentPath.' '
                                );
                                $createNode = false;
                            }
                        } else {
                            $this->warnIf(
                                true,
                                'Cannot migrate doc #'.$documentData['iid'].' file not found: '.$documentPath
                            );
                        }
                    }

                    //var_dump($createNode,$documentPath, $documentData['iid'], file_exists($filePath));
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
