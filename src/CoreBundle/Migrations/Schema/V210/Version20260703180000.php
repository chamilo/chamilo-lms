<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20260703180000 extends AbstractMigrationChamilo
{
    private const ORM_FLUSH_BATCH_SIZE = 50;

    public function getDescription(): string
    {
        return 'Repair migrated learning-path categories missing resource nodes by using their child LP course context.';
    }

    public function up(Schema $schema): void
    {
        foreach (['c_lp_category', 'c_lp', 'course', 'resource_node', 'resource_link'] as $tableName) {
            if (!$schema->hasTable($tableName)) {
                throw new RuntimeException("Required table '{$tableName}' is missing.");
            }
        }

        $categoryIds = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT iid
                 FROM c_lp_category
                 WHERE resource_node_id IS NULL
                 ORDER BY iid'
            )
        );

        if ([] === $categoryIds) {
            $this->getLogger()->info('No learning-path categories require resource repair.');

            return;
        }

        $contextsByCategory = $this->loadSafeCourseContexts();

        /** @var CLpCategoryRepository $categoryRepository */
        $categoryRepository = $this->container->get(CLpCategoryRepository::class);

        $adminId = (int) $this->getAdmin()->getId();
        if ($adminId <= 0) {
            throw new RuntimeException('Unable to resolve the migration administrator.');
        }

        $repaired = 0;
        $alreadyDone = 0;
        $skippedNoContext = 0;
        $skippedAmbiguousContext = 0;
        $skippedInvalidParent = 0;
        $skippedMissingEntity = 0;

        foreach ($categoryIds as $categoryId) {
            $contexts = $contextsByCategory[$categoryId] ?? [];

            if ([] === $contexts) {
                ++$skippedNoContext;
                $this->getLogger()->warning('Learning-path category repair skipped: no safe course-level child LP context.', [
                    'category_id' => $categoryId,
                ]);

                continue;
            }

            if (1 !== \count($contexts)) {
                ++$skippedAmbiguousContext;
                $this->getLogger()->warning('Learning-path category repair skipped: ambiguous course context.', [
                    'category_id' => $categoryId,
                    'contexts' => \count($contexts),
                ]);

                continue;
            }

            $context = $contexts[0];
            $courseId = (int) $context['course_id'];
            $courseNodeId = (int) $context['course_node_id'];
            $childParentId = (int) $context['child_parent_id'];
            $parentCount = (int) $context['parent_count'];

            if (
                $courseId <= 0
                || $courseNodeId <= 0
                || 1 !== $parentCount
                || $courseNodeId !== $childParentId
            ) {
                ++$skippedInvalidParent;
                $this->getLogger()->warning('Learning-path category repair skipped: child LP parent does not match the course resource node.', [
                    'category_id' => $categoryId,
                    'course_id' => $courseId,
                    'course_node_id' => $courseNodeId,
                    'child_parent_id' => $childParentId,
                    'parent_count' => $parentCount,
                ]);

                continue;
            }

            $category = $categoryRepository->find($categoryId);
            if (!$category instanceof CLpCategory) {
                ++$skippedMissingEntity;
                $this->getLogger()->warning('Learning-path category repair skipped: category entity not found.', [
                    'category_id' => $categoryId,
                ]);

                continue;
            }

            if ($category->hasResourceNode()) {
                ++$alreadyDone;

                continue;
            }

            $course = $this->entityManager->find(Course::class, $courseId);
            if (!$course instanceof Course || !$course->hasResourceNode()) {
                ++$skippedMissingEntity;
                $this->getLogger()->warning('Learning-path category repair skipped: course or course resource node not found.', [
                    'category_id' => $categoryId,
                    'course_id' => $courseId,
                ]);

                continue;
            }

            if ((int) $course->getResourceNode()?->getId() !== $courseNodeId) {
                ++$skippedInvalidParent;
                $this->getLogger()->warning('Learning-path category repair skipped: loaded course resource node differs from inferred context.', [
                    'category_id' => $categoryId,
                    'course_id' => $courseId,
                    'expected_course_node_id' => $courseNodeId,
                    'actual_course_node_id' => (int) $course->getResourceNode()?->getId(),
                ]);

                continue;
            }

            $visibility = $this->normalizeVisibility((int) $context['visibility']);
            $admin = $this->entityManager->getReference(User::class, $adminId);
            $resourceType = $categoryRepository->getResourceType();

            $category->setParent($course);
            $categoryRepository->addResourceNode(
                $category,
                $admin,
                $course,
                $resourceType
            );
            $category->addCourseLink($course, null, null, $visibility);

            $this->entityManager->persist($category);
            ++$repaired;

            if (0 === $repaired % self::ORM_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $pending = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM c_lp_category WHERE resource_node_id IS NULL'
        );

        $this->getLogger()->info('Completed learning-path category resource repair.', [
            'candidates' => \count($categoryIds),
            'repaired' => $repaired,
            'already_done' => $alreadyDone,
            'skipped_no_context' => $skippedNoContext,
            'skipped_ambiguous_context' => $skippedAmbiguousContext,
            'skipped_invalid_parent' => $skippedInvalidParent,
            'skipped_missing_entity' => $skippedMissingEntity,
            'pending' => $pending,
        ]);
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty.
        // Removing repaired resource nodes would make migrated LP categories invisible again.
    }

    /**
     * Uses only active, course-level child LP links. Session/group/user-scoped
     * contexts are deliberately ignored because publishing a broader category
     * link from a narrower child context could expose restricted content.
     *
     * @return array<int, list<array<string, mixed>>>
     */
    private function loadSafeCourseContexts(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT
                 cat.iid AS category_id,
                 rl.c_id AS course_id,
                 c.resource_node_id AS course_node_id,
                 COUNT(DISTINCT rn.parent_id) AS parent_count,
                 MIN(rn.parent_id) AS child_parent_id,
                 MAX(rl.visibility) AS visibility
             FROM c_lp_category cat
             INNER JOIN c_lp lp
                 ON lp.category_id = cat.iid
             INNER JOIN resource_node rn
                 ON rn.id = lp.resource_node_id
             INNER JOIN resource_link rl
                 ON rl.resource_node_id = rn.id
             INNER JOIN course c
                 ON c.id = rl.c_id
             WHERE cat.resource_node_id IS NULL
               AND rl.deleted_at IS NULL
               AND rl.c_id IS NOT NULL
               AND rl.session_id IS NULL
               AND rl.usergroup_id IS NULL
               AND rl.group_id IS NULL
               AND rl.user_id IS NULL
             GROUP BY cat.iid, rl.c_id, c.resource_node_id
             ORDER BY cat.iid, rl.c_id'
        );

        $contextsByCategory = [];
        foreach ($rows as $row) {
            $categoryId = (int) $row['category_id'];
            if ($categoryId <= 0) {
                continue;
            }

            $contextsByCategory[$categoryId][] = $row;
        }

        return $contextsByCategory;
    }

    private function normalizeVisibility(int $visibility): int
    {
        return match ($visibility) {
            ResourceLink::VISIBILITY_PUBLISHED => ResourceLink::VISIBILITY_PUBLISHED,
            ResourceLink::VISIBILITY_PENDING => ResourceLink::VISIBILITY_PENDING,
            default => ResourceLink::VISIBILITY_DRAFT,
        };
    }
}
