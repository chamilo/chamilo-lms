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
        $container = $this->getContainer();
        $em = $this->getEntityManager();
        $connection = $em->getConnection();

        $introRepo = $container->get(CToolIntroRepository::class);
        $cToolRepo = $container->get(CToolRepository::class);
        $toolRepo = $container->get(ToolRepository::class);

        $sessionRepo = $container->get(SessionRepository::class);
        $courseRepo = $container->get(CourseRepository::class);

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $sql = "SELECT * FROM c_tool_intro WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();

            if (empty($items)) {
                $admin = $this->getAdmin();
                $tool = $toolRepo->findOneBy(['name' => 'course_homepage']);
                $cTool = (new CTool())
                    ->setName('course_homepage')
                    ->setCourse($course)
                    ->setTool($tool)
                    ->setCreator($admin)
                    ->setParent($course)
                    ->addCourseLink($course)
                ;
                $em->persist($cTool);
                $em->flush();

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
                    $tool = $toolRepo->findOneBy(['name' => $toolName]);

                    if (null === $tool) {
                        continue;
                    }

                    $course = $courseRepo->find($courseId);

                    $cTool = (new CTool())
                        ->setName('course_homepage')
                        ->setCourse($course)
                        ->setSession($session)
                        ->setTool($tool)
                        ->setCreator($admin)
                        ->setParent($course)
                        ->addCourseLink($course, $session)
                    ;
                    $em->persist($cTool);
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

                $em->persist($intro);
                $em->flush();
            }

            $em->flush();
            $em->clear();

            $table = $schema->getTable('c_tool_intro');

            if ($table->hasColumn('c_tool_id')) {
                if (!$table->hasForeignKey('FK_D705267B1DF6B517')) {
                    $this->addSql(
                        'ALTER TABLE c_tool_intro ADD CONSTRAINT FK_D705267B1DF6B517 FOREIGN KEY (c_tool_id) REFERENCES c_tool (iid);'
                    );
                }
                if (!$table->hasIndex('IDX_D705267B1DF6B517')) {
                    $this->addSql('CREATE INDEX IDX_D705267B1DF6B517 ON c_tool_intro (c_tool_id);');
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
