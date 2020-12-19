<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CBlog;
use Chamilo\CourseBundle\Repository\CBlogRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201219113351 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_blog ';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $blogRepo = $container->get(CBlogRepository::class);
        $courseRepo = $container->get(CourseRepository::class);
        $sessionRepo = $container->get(SessionRepository::class);
        $groupRepo = $container->get(CGroupRepository::class);
        $userRepo = $container->get(UserRepository::class);
        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $admin = $this->getAdmin();

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $sql = "SELECT * FROM c_blog WHERE c_id = $courseId ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                /** @var CBlog $resource */
                $resource = $blogRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $result = $this->fixItemProperty(
                    'blog_management',
                    $blogRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $result) {
                    continue;
                }

                $em->persist($resource);
                $em->flush();
            }

            $em->flush();
            $em->clear();

            $sql = "SELECT * FROM c_blog_attachment WHERE c_id = $courseId ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $blogId = $itemData['blog_id'];
                $path = $itemData['path'];
                $fileName = $itemData['filename'];
                $comment = $itemData['comment'];

                /** @var CBlog $resource */
                $resource = $blogRepo->find($blogId);
                if (false === $resource->hasResourceNode()) {
                    continue;
                }

                if (!empty($path)) {
                    $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/upload/blog/'.$path;
                    $this->addLegacyFileToResource($filePath, $blogRepo, $resource, $id, $fileName, $comment);
                }

                $em->persist($resource);
                $em->flush();
            }

            $em->flush();
            $em->clear();
        }
    }
}
