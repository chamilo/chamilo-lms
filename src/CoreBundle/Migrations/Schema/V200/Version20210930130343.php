<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
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
        /** @var CToolIntroRepository $introRepo */
        $introRepo = $this->container->get(CToolIntroRepository::class);

        /** @var CToolRepository $cToolRepo */
        $cToolRepo = $this->container->get(CToolRepository::class);

        /** @var ToolRepository $toolRepo */
        $toolRepo = $this->container->get(ToolRepository::class);

        /** @var SessionRepository $sessionRepo */
        $sessionRepo = $this->container->get(SessionRepository::class);

        /** @var CourseRepository $courseRepo */
        $courseRepo = $this->container->get(CourseRepository::class);

        /** @var Tool|null $homepageToolEntity */
        $homepageToolEntity = $toolRepo->findOneBy(['title' => 'course_homepage']);
        if (null === $homepageToolEntity) {
            error_log('[Migration Version20210930130343] Tool "course_homepage" not found. Skipping migration.');

            return;
        }

        // We store the Tool ID and always re-create a managed reference after EntityManager::clear().
        // Otherwise Doctrine may treat the Tool as a "new/detached entity" and fail on flush.
        $homepageToolId = (int) $homepageToolEntity->getId();

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = (int) $course->getId();

            // Re-load course from repository to ensure it is managed in this context.
            /** @var Course|null $managedCourse */
            $managedCourse = $courseRepo->find($courseId);
            if (null === $managedCourse) {
                error_log('[Migration Version20210930130343] Course not found for course_id='.$courseId.'. Skipping.');

                continue;
            }

            // Always get a managed reference for the homepage Tool (safe after clear()).
            /** @var Tool $homepageToolRef */
            $homepageToolRef = $this->entityManager->getReference(Tool::class, $homepageToolId);

            $sql = "SELECT * FROM c_tool_intro WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();

            // If the course had no legacy intro rows, we still ensure the base homepage tool exists.
            if (empty($items)) {
                $admin = $this->getAdmin();
                $existingBaseTool = $cToolRepo->findOneBy([
                    'title' => 'course_homepage',
                    'course' => $managedCourse,
                    'session' => null,
                ]);

                if ($existingBaseTool) {
                    // Nothing else to do for this course.
                    continue;
                }

                $baseTool = (new CTool())
                    ->setTitle('course_homepage')
                    ->setCourse($managedCourse)
                    ->setSession(null)
                    ->setTool($homepageToolRef)
                    ->setCreator($admin)
                    ->setParent($managedCourse)
                    ->addCourseLink($managedCourse)
                ;
                $this->entityManager->persist($baseTool);
                $this->entityManager->flush();

                continue;
            }

            foreach ($items as $itemData) {
                $id = (int) ($itemData['iid'] ?? 0);
                if ($id <= 0) {
                    error_log('[Migration Version20210930130343] Skipping legacy row with invalid iid. course_id='.$courseId);

                    continue;
                }

                $sessionId = (int) ($itemData['session_id'] ?? 0);
                $toolName = (string) ($itemData['id'] ?? '');

                /** @var CToolIntro|null $intro */
                $intro = $introRepo->find($id);
                if (null === $intro) {
                    error_log('[Migration Version20210930130343] CToolIntro entity not found for iid='.$id.' course_id='.$courseId);

                    continue;
                }

                // Already migrated.
                if ($intro->hasResourceNode()) {
                    continue;
                }

                /** @var Session|null $session */
                $session = null;
                if ($sessionId > 0) {
                    $session = $sessionRepo->find($sessionId);
                    if (null === $session) {
                        error_log('[Migration Version20210930130343] Skipping intro iid='.$id.' because session_id='.$sessionId.' does not exist.');

                        continue;
                    }
                }

                $admin = $this->getAdmin();

                // We may have cleared the EntityManager at the end of a previous course.
                // So we must always use a managed Tool reference for associations.
                /** @var Tool $homepageToolRef */
                $homepageToolRef = $this->entityManager->getReference(Tool::class, $homepageToolId);

                /** @var CTool|null $cTool */
                $cTool = null;

                if ('course_homepage' === $toolName) {
                    // Avoid creating duplicate homepage tools.
                    // Reuse the existing tool for the same (course + session) context if it exists.
                    $cTool = $cToolRepo->findOneBy([
                        'title' => 'course_homepage',
                        'course' => $managedCourse,
                        'session' => $session,
                    ]);

                    if (null === $cTool) {
                        $cTool = (new CTool())
                            ->setTitle('course_homepage')
                            ->setCourse($managedCourse)
                            ->setSession($session)
                            ->setTool($homepageToolRef)
                            ->setCreator($admin)
                            ->setParent($managedCourse)
                            ->addCourseLink($managedCourse, $session)
                        ;

                        $this->entityManager->persist($cTool);
                        $this->entityManager->flush();
                    }
                } else {
                    $cTool = $cToolRepo->findCourseResourceByTitle(
                        $toolName,
                        $managedCourse->getResourceNode(),
                        $managedCourse,
                        $session
                    );
                }

                if (null === $cTool) {
                    error_log('[Migration Version20210930130343] Could not resolve CTool for tool="'.$toolName.'" course_id='.$courseId.' session_id='.$sessionId);

                    continue;
                }

                // Attach intro to the correct tool/context and add resource node/links.
                $intro
                    ->setParent($managedCourse)
                    ->setCourseTool($cTool)
                ;

                $introRepo->addResourceNode($intro, $admin, $managedCourse);
                $intro->addCourseLink($managedCourse, $session);

                $this->entityManager->persist($intro);
                $this->entityManager->flush();
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }
    }

    public function down(Schema $schema): void {}
}
