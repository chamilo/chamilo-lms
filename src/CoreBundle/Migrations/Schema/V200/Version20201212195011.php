<?php

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201212195011 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate courses, c_tool ';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();
        $courseRepo = $container->get(CourseRepository::class);
        $toolRepo = $container->get(CToolRepository::class);
        $urlRepo = $em->getRepository(AccessUrl::class);

        $batchSize = self::BATCH_SIZE;
        $admin = $this->getAdmin();

        // Adding courses to the resource node tree.
        $urls = $urlRepo->findAll();
        /** @var AccessUrl $url */
        foreach ($urls as $url) {
            $counter = 1;
            $url = $urlRepo->find($url->getId());
            $accessUrlRelCourses = $url->getCourses();
            /** @var AccessUrlRelCourse $accessUrlRelCourse */
            foreach ($accessUrlRelCourses as $accessUrlRelCourse) {
                $course = $accessUrlRelCourse->getCourse();
                $course = $courseRepo->find($course->getId());
                if ($course->hasResourceNode()) {
                    continue;
                }
                $courseRepo->addResourceNode($course, $admin, $url);
                $em->persist($course);

                // Add groups.
                //$course = $course->getGroups();
                if (0 === $counter % $batchSize) {
                    $em->flush();
                    $em->clear(); // Detaches all objects from Doctrine!
                }
                $counter++;
            }
        }
        $em->flush();
        $em->clear();

        foreach ($urls as $url) {
            $accessUrlRelCourses = $url->getCourses();
            /** @var AccessUrlRelCourse $accessUrlRelCourse */
            foreach ($accessUrlRelCourses as $accessUrlRelCourse) {
                $counter = 1;
                $course = $accessUrlRelCourse->getCourse();
                $courseId = $course->getId();
                $sql = "SELECT * FROM c_tool
                        WHERE c_id = $courseId ";
                $result = $connection->executeQuery($sql);
                $tools = $result->fetchAllAssociative();
                foreach ($tools as $tool) {
                    /** @var CTool $tool */
                    $tool = $toolRepo->find($tool['iid']);
                    if ($tool->hasResourceNode()) {
                        continue;
                    }
                    $course = $courseRepo->find($course->getId());
                    $admin = $this->getAdmin();
                    $tool->setParent($course);
                    $toolRepo->addResourceNode($tool, $admin, $course);
                    $em->persist($tool);
                    if (0 === $counter % $batchSize) {
                        $em->flush();
                        $em->clear(); // Detaches all objects from Doctrine!
                    }
                    $counter++;
                }
            }
        }

        $em->flush();
        $em->clear();
    }
}
