<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Repository\ToolRepository;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\CourseBundle\Repository\CToolIntroRepository;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20210930130343 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'CToolIntro';
    }

    public function up(Schema $schema): void
    {
        $introRepo = $this->container->get(CToolIntroRepository::class);
        $cToolRepo = $this->container->get(CToolRepository::class);
        $toolRepo = $this->container->get(ToolRepository::class);
        $sessionRepo = $this->container->get(SessionRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $sql = "SELECT * FROM c_tool_intro WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();

            if (empty($items)) {
                $admin = $this->getAdmin();
                $tool = $toolRepo->findOneBy(['title' => 'course_homepage']);
                $cTool = (new CTool())
                    ->setTitle('course_homepage')
                    ->setCourse($course)
                    ->setTool($tool)
                    ->setCreator($admin)
                    ->setParent($course)
                    ->addCourseLink($course)
                ;
                $this->entityManager->persist($cTool);
                $this->entityManager->flush();

                continue;
            }

            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $sessionId = (int) $itemData['session_id'];
                $toolName = $itemData['id'];

                /** @var CToolIntro $intro */
                $intro = $introRepo->find($id);

                if ($intro->hasResourceNode()) {
                    continue;
                }

                $session = null;
                if (!empty($sessionId)) {
                    $session = $sessionRepo->find($sessionId);
                }

                $admin = $this->getAdmin();

                $cTool = null;
                if ('course_homepage' === $toolName) {
                    $tool = $toolRepo->findOneBy(['title' => $toolName]);

                    if (null === $tool) {
                        continue;
                    }

                    $course = $courseRepo->find($courseId);

                    $cTool = (new CTool())
                        ->setTitle('course_homepage')
                        ->setCourse($course)
                        ->setSession($session)
                        ->setTool($tool)
                        ->setCreator($admin)
                        ->setParent($course)
                        ->addCourseLink($course, $session)
                    ;
                    $this->entityManager->persist($cTool);
                } else {
                    $cTool = $cToolRepo->findCourseResourceByTitle($toolName, $course->getResourceNode(), $course, $session);
                }

                if (null === $cTool) {
                    continue;
                }

                $intro
                    ->setParent($course)
                    ->setCourseTool($cTool)
                ;
                $introRepo->addResourceNode($intro, $admin, $course);
                $intro->addCourseLink($course, $session);

                $this->entityManager->persist($intro);
                $this->entityManager->flush();
            }

            $this->entityManager->flush();
        }
    }

    public function down(Schema $schema): void {}
}
