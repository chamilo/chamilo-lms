<?php

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Repository\UserRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201212195011 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate courses';
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

        $userList = [];
        $groupList = [];
        $sessionList = [];

        //$urlRepo = $container->get(AccessUrlRepository::class);
        $urlRepo = $em->getRepository(AccessUrl::class);
        $admin = $this->getAdmin();

        $urls = $urlRepo->findAll();
        /** @var AccessUrl $url */
        foreach ($urls as $url) {
            $accessUrlRelCourses = $url->getCourses();
            /** @var AccessUrlRelCourse $accessUrlRelCourse */
            foreach ($accessUrlRelCourses as $accessUrlRelCourse) {
                $course = $accessUrlRelCourse->getCourse();
                if ($course->hasResourceNode()) {
                    continue;
                }
                $courseRepo->addResourceNode($course, $admin, $url);
                $em->persist($course);
            }
        }
        $em->flush();

        $admin = $this->getAdmin();
        /** @var AccessUrl $url */
        foreach ($urls as $url) {
            $accessUrlRelCourses = $url->getCourses();
            /** @var AccessUrlRelCourse $accessUrlRelCourse */
            foreach ($accessUrlRelCourses as $accessUrlRelCourse) {
                $course = $accessUrlRelCourse->getCourse();
                $courseId = $course->getId();

                $sql = "SELECT * FROM c_document WHERE c_id = $courseId";
                $result = $connection->executeQuery($sql);
                $documents = $result->fetchAllAssociative();
                foreach ($documents as $document) {
                    $documentId = $document['iid'];
                    /** @var CDocument $document */
                    $document = $documentRepo->find($courseId);
                    if ($document->hasResourceNode()) {
                        continue;
                    }
                    $document->setParent($course);
                    $sql = "SELECT * FROM c_item_property
                            WHERE tool = 'document' AND c_id = $courseId AND ref = $documentId";
                    $result = $connection->executeQuery($sql);
                    $items = $result->fetchAllAssociative();

                    $resourceNode = null;
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
                            $documentRepo->addResourceNode($document, $user, $course);
                        }
                        $document->addCourseLink($course, $session, $group, $newVisibility);
                    }
                    $em->persist($document);
                }
                $em->flush();
            }
        }
    }
}
