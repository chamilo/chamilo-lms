<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Entity\CStudentPublicationCorrection;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationCommentRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationCorrectionRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Schema\Schema;

final class Version20201217124011 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_student_publication';
    }

    public function up(Schema $schema): void
    {
        $studentPublicationRepo = $this->container->get(CStudentPublicationRepository::class);
        $studentPublicationCommentRepo = $this->container->get(CStudentPublicationCommentRepository::class);
        $studentPublicationCorrectionRepo = $this->container->get(CStudentPublicationCorrectionRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $sessionRepo = $this->container->get(SessionRepository::class);
        $groupRepo = $this->container->get(CGroupRepository::class);

        // $userRepo = $container->get(UserRepository::class);
        /** @var Kernel $kernel */
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $admin = $this->getAdmin();

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            // Assignments folders.
            $sql = "SELECT * FROM c_student_publication WHERE contains_file = 0 AND c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];

                /** @var CStudentPublication $resource */
                $resource = $studentPublicationRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $result = $this->fixItemProperty(
                    'work',
                    $studentPublicationRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $result) {
                    continue;
                }

                $this->entityManager->persist($resource);
                $this->entityManager->flush();
            }

            $this->entityManager->flush();

            // Assignments files.
            $sql = "SELECT * FROM c_student_publication
                    WHERE
                          contains_file = 1 AND
                          c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $course = $courseRepo->find($courseId);
                $id = $itemData['iid'];
                $path = $itemData['url'];
                $title = $itemData['title'];
                $parentId = $itemData['parent_id'];

                /** @var CStudentPublication $resource */
                $resource = $studentPublicationRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $parent = $studentPublicationRepo->find($parentId);

                $result = $this->fixItemProperty(
                    'work',
                    $studentPublicationRepo,
                    $course,
                    $admin,
                    $resource,
                    $parent
                );

                if (false === $result) {
                    continue;
                }

                $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/'.$path;
                error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                $this->addLegacyFileToResource($filePath, $studentPublicationRepo, $resource, $id, $title);
                $this->entityManager->persist($resource);
                $this->entityManager->flush();
            }

            $this->entityManager->flush();

            $admin = $this->getAdmin();

            // Corrections.
            $sql = "SELECT * FROM c_student_publication
                    WHERE
                          (title_correction <> '' OR title_correction IS NOT NULL) AND
                          c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $title = $itemData['title_correction'];
                $path = $itemData['url_correction'];

                $course = $courseRepo->find($courseId);

                /** @var CStudentPublication $studentPublication */
                $studentPublication = $studentPublicationRepo->find($id);
                $correction = $studentPublication->getCorrection();
                if (null !== $correction) {
                    continue;
                }

                $correction = new CStudentPublicationCorrection();
                $correction->setTitle($title);
                $correction->setParent($studentPublication);
                $studentPublicationCorrectionRepo->addResourceNode($correction, $admin, $studentPublication);
                $this->entityManager->persist($correction);

                $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/'.$path;
                error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                $this->addLegacyFileToResource($filePath, $studentPublicationCorrectionRepo, $correction, null, $title);
                $this->entityManager->persist($correction);
                $this->entityManager->flush();
            }

            $this->entityManager->flush();

            // Comments.
            $sql = "SELECT * FROM c_student_publication_comment WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $file = $itemData['file'];
                $workId = $itemData['work_id'];

                /** @var CStudentPublicationComment $resource */
                $resource = $studentPublicationCommentRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                /** @var CStudentPublication $parent */
                $parent = $studentPublicationRepo->find($workId);
                $sql = "SELECT * FROM c_student_publication WHERE c_id = {$courseId} AND id = {$workId}";
                $result = $this->connection->executeQuery($sql);
                $work = $result->fetchAssociative();
                if (empty($work)) {
                    continue;
                }
                $session = empty($work['session_id']) ? null : $sessionRepo->find($work['session_id']);
                $group = empty($work['post_group_id']) ? null : $groupRepo->find($work['post_group_id']);

                $resource->setParent($parent);
                $resource->addCourseLink($course, $session, $group, ResourceLink::VISIBILITY_PUBLISHED);

                $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/'.$file;
                error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                $this->addLegacyFileToResource($filePath, $studentPublicationRepo, $resource, $id, $title);
                $this->entityManager->persist($resource);
                $this->entityManager->flush();
            }
        }
    }
}
