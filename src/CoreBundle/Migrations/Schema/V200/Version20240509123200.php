<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\ToolRepository;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\DBAL\Schema\Schema;

final class Version20240509123200 extends AbstractMigrationChamilo
{
    private const FLUSH_BATCH_SIZE = 200;

    private const REQUIRED_TOOLS = [
        'course_description',
        'document',
        'learnpath',
        'link',
        'quiz',
        'announcement',
        'gradebook',
        'glossary',
        'attendance',
        'course_progress',
        'agenda',
        'forum',
        'dropbox',
        'member',
        'group',
        'chat',
        'student_publication',
        'survey',
        'wiki',
        'notebook',
        'course_tool',
        'course_homepage',
        'tracking',
        'course_setting',
        'course_maintenance',
        'portfolio',
    ];

    public function getDescription(): string
    {
        return 'Ensure base course tools without loading complete tool collections, then remove session tools in bounded ORM batches.';
    }

    public function up(Schema $schema): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = $this->container->get(CourseRepository::class);
        /** @var ToolRepository $toolRepo */
        $toolRepo = $this->container->get(ToolRepository::class);

        $admin = $this->getAdmin();
        $courseIds = array_map(
            'intval',
            $this->connection->fetchFirstColumn('SELECT id FROM course ORDER BY id')
        );

        /** @var array<string, Tool> $toolsByTitle */
        $toolsByTitle = [];
        foreach ($toolRepo->findBy(['title' => self::REQUIRED_TOOLS]) as $tool) {
            if ($tool instanceof Tool) {
                $toolsByTitle[$tool->getTitle()] = $tool;
            }
        }

        $existingRows = $this->entityManager->createQuery(
            'SELECT IDENTITY(ct.course) AS course_id, tool.title AS tool_title
             FROM Chamilo\\CourseBundle\\Entity\\CTool ct
             INNER JOIN ct.tool tool
             WHERE ct.session IS NULL
               AND tool.title IN (:titles)'
        )->setParameter('titles', self::REQUIRED_TOOLS)->getArrayResult();

        $existing = [];
        foreach ($existingRows as $row) {
            $existing[(int) $row['course_id']][(string) $row['tool_title']] = true;
        }

        $created = 0;
        $missingDefinitions = [];
        $startedAt = microtime(true);

        foreach ($courseIds as $courseId) {
            $missingTitles = array_values(array_filter(
                self::REQUIRED_TOOLS,
                static fn (string $title): bool => !isset($existing[$courseId][$title])
            ));

            if ([] === $missingTitles) {
                continue;
            }

            $course = $courseRepo->find($courseId);
            if (!$course instanceof Course) {
                $this->getLogger()->warning('Course was not found while ensuring base tools.', [
                    'course_id' => $courseId,
                ]);
                continue;
            }

            foreach ($missingTitles as $toolName) {
                $tool = $toolsByTitle[$toolName] ?? null;
                if (!$tool instanceof Tool) {
                    $missingDefinitions[$toolName] = true;
                    continue;
                }

                $visibility = in_array($toolName, ['course_setting', 'course_maintenance'], true)
                    ? ResourceLink::VISIBILITY_DRAFT
                    : ResourceLink::VISIBILITY_PUBLISHED;

                $baseTool = (new CTool())
                    ->setTool($tool)
                    ->setTitle($toolName)
                    ->setVisibility(true)
                    ->setCourse($course)
                    ->setParent($course)
                    ->setSession(null)
                    ->setCreator($admin)
                ;
                $baseTool->addCourseLink($course, null, null, $visibility);
                $this->entityManager->persist($baseTool);
                ++$created;

                if (0 === $created % self::FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->getLogger()->info('Base course-tool creation progress.', [
                        'created' => $created,
                        'elapsed_seconds' => (int) (microtime(true) - $startedAt),
                    ]);
                }
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $removed = 0;
        $query = $this->entityManager->createQuery(
            'SELECT ct FROM Chamilo\\CourseBundle\\Entity\\CTool ct WHERE ct.session IS NOT NULL'
        );

        foreach ($query->toIterable() as $sessionTool) {
            if (!$sessionTool instanceof CTool) {
                continue;
            }

            $this->entityManager->remove($sessionTool);
            ++$removed;

            if (0 === $removed % self::FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $this->getLogger()->info('Session-specific course-tool removal progress.', [
                    'removed' => $removed,
                    'elapsed_seconds' => (int) (microtime(true) - $startedAt),
                ]);
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Course-tool normalization completed.', [
            'courses' => count($courseIds),
            'created_base_tools' => $created,
            'removed_session_tools' => $removed,
            'missing_tool_definitions' => array_keys($missingDefinitions),
            'elapsed_seconds' => (int) (microtime(true) - $startedAt),
        ]);
    }
}
