<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathLayoutInput;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<LearningPathLayoutInput, JsonResponse>
 */
final readonly class LearningPathLayoutProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        if (!$data instanceof LearningPathLayoutInput) {
            throw new BadRequestHttpException('The learning path layout payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        $this->assertLearningPathTeacher($this->security);

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

        if (null !== $session || null !== $group) {
            throw new AccessDeniedHttpException(
                'Learning path category layout can only be changed in the base course context.'
            );
        }

        $categoryRows = $data->categories;

        $uncategorizedIds = $this->normalizeIdList(
            $data->uncategorized,
            'The uncategorized learning path order is invalid.'
        );

        $categoryIds = [];
        $learningPathIdsByCategory = [];
        $allLearningPathIds = $uncategorizedIds;

        foreach ($categoryRows as $categoryRow) {
            if (!\is_array($categoryRow)) {
                throw new BadRequestHttpException('A learning path category layout entry is invalid.');
            }

            $categoryId = $this->normalizeIdList(
                [$categoryRow['id']],
                'The learning path category order contains invalid identifiers.'
            )[0];

            if (\in_array($categoryId, $categoryIds, true)) {
                throw new BadRequestHttpException('The learning path category order contains invalid identifiers.');
            }

            $learningPathIds = $this->normalizeIdList(
                $categoryRow['learningPathIds'],
                'A learning path category order is invalid.'
            );

            $categoryIds[] = $categoryId;
            $learningPathIdsByCategory[$categoryId] = $learningPathIds;
            $allLearningPathIds = [...$allLearningPathIds, ...$learningPathIds];
        }

        if (\count($allLearningPathIds) !== \count(array_unique($allLearningPathIds))) {
            throw new BadRequestHttpException('Each learning path must appear exactly once in the layout.');
        }

        $courseId = (int) $course->getId();
        $connection = $this->entityManager->getConnection();

        $connection->transactional(function (Connection $connection) use (
            $courseId,
            $categoryIds,
            $learningPathIdsByCategory,
            $uncategorizedIds,
            $allLearningPathIds,
        ): void {
            $categoryLayout = $this->loadCategoryLayout($connection, $courseId);
            $learningPathLayout = $this->loadLearningPathLayout($connection, $courseId);

            $this->assertSameIdentifiers(
                array_keys($categoryLayout['linkIds']),
                $categoryIds,
                'The layout must contain every learning path category from the base course context and no others.'
            );
            $this->assertSameIdentifiers(
                array_keys($learningPathLayout['linkIds']),
                $allLearningPathIds,
                'The layout must contain every learning path from the base course context exactly once.'
            );

            $categoryPosition = $this->firstPosition($categoryLayout['positions']);
            foreach ($categoryIds as $categoryId) {
                $this->updateResourceLinkPosition(
                    $connection,
                    $categoryLayout['linkIds'][$categoryId],
                    $courseId,
                    $categoryPosition,
                );
                ++$categoryPosition;
            }

            $learningPathPosition = $this->firstPosition($learningPathLayout['positions']);

            foreach ($uncategorizedIds as $learningPathId) {
                $this->updateLearningPathCategory($connection, $learningPathId, null);
                $this->updateResourceLinkPosition(
                    $connection,
                    $learningPathLayout['linkIds'][$learningPathId],
                    $courseId,
                    $learningPathPosition,
                );
                ++$learningPathPosition;
            }

            foreach ($categoryIds as $categoryId) {
                foreach ($learningPathIdsByCategory[$categoryId] as $learningPathId) {
                    $this->updateLearningPathCategory($connection, $learningPathId, $categoryId);
                    $this->updateResourceLinkPosition(
                        $connection,
                        $learningPathLayout['linkIds'][$learningPathId],
                        $courseId,
                        $learningPathPosition,
                    );
                    ++$learningPathPosition;
                }
            }
        });

        return new JsonResponse(null, 204);
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value, string $errorMessage): array
    {
        if (!\is_array($value)) {
            throw new BadRequestHttpException($errorMessage);
        }

        $ids = [];

        foreach ($value as $rawId) {
            if (\is_int($rawId)) {
                $id = $rawId;
            } elseif (\is_string($rawId) && ctype_digit($rawId)) {
                $id = (int) $rawId;
            } else {
                throw new BadRequestHttpException($errorMessage);
            }

            if ($id <= 0 || \in_array($id, $ids, true)) {
                throw new BadRequestHttpException($errorMessage);
            }

            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * @param array<int, int> $expectedIds
     * @param array<int, int> $receivedIds
     */
    private function assertSameIdentifiers(array $expectedIds, array $receivedIds, string $errorMessage): void
    {
        sort($expectedIds);
        sort($receivedIds);

        if ($expectedIds !== $receivedIds) {
            throw new BadRequestHttpException($errorMessage);
        }
    }

    /**
     * @return array{linkIds: array<int, int>, positions: array<int, int>}
     */
    private function loadCategoryLayout(Connection $connection, int $courseId): array
    {
        $rows = $connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    category.iid AS resource_id,
                    link.id AS resource_link_id,
                    link.display_order
                FROM c_lp_category category
                INNER JOIN resource_link link
                    ON link.resource_node_id = category.resource_node_id
                WHERE link.c_id = :courseId
                  AND link.session_id IS NULL
                  AND link.group_id IS NULL
                  AND link.usergroup_id IS NULL
                  AND link.user_id IS NULL
                  AND link.deleted_at IS NULL
                ORDER BY link.display_order, category.iid
                FOR UPDATE
                SQL,
            ['courseId' => $courseId],
            ['courseId' => ParameterType::INTEGER],
        );

        return $this->indexLayoutRows($rows, 'A learning path category has an invalid base course link.');
    }

    /**
     * @return array{linkIds: array<int, int>, positions: array<int, int>}
     */
    private function loadLearningPathLayout(Connection $connection, int $courseId): array
    {
        $rows = $connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    learning_path.iid AS resource_id,
                    link.id AS resource_link_id,
                    link.display_order
                FROM c_lp learning_path
                INNER JOIN resource_link link
                    ON link.resource_node_id = learning_path.resource_node_id
                WHERE link.c_id = :courseId
                  AND link.session_id IS NULL
                  AND link.group_id IS NULL
                  AND link.usergroup_id IS NULL
                  AND link.user_id IS NULL
                  AND link.deleted_at IS NULL
                ORDER BY link.display_order, learning_path.iid
                FOR UPDATE
                SQL,
            ['courseId' => $courseId],
            ['courseId' => ParameterType::INTEGER],
        );

        return $this->indexLayoutRows($rows, 'A learning path has an invalid base course link.');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array{linkIds: array<int, int>, positions: array<int, int>}
     */
    private function indexLayoutRows(array $rows, string $duplicateMessage): array
    {
        $linkIds = [];
        $positions = [];

        foreach ($rows as $row) {
            $resourceId = (int) ($row['resource_id'] ?? 0);
            $resourceLinkId = (int) ($row['resource_link_id'] ?? 0);
            $position = (int) ($row['display_order'] ?? 0);

            if ($resourceId <= 0 || $resourceLinkId <= 0 || isset($linkIds[$resourceId])) {
                throw new BadRequestHttpException($duplicateMessage);
            }

            $linkIds[$resourceId] = $resourceLinkId;
            $positions[] = $position;
        }

        return [
            'linkIds' => $linkIds,
            'positions' => $positions,
        ];
    }

    /**
     * @param array<int, int> $positions
     */
    private function firstPosition(array $positions): int
    {
        if ([] === $positions) {
            return 0;
        }

        sort($positions);

        return $positions[0];
    }

    private function updateLearningPathCategory(
        Connection $connection,
        int $learningPathId,
        ?int $categoryId,
    ): void {
        $connection->executeStatement(
            'UPDATE c_lp SET category_id = :categoryId WHERE iid = :learningPathId',
            [
                'categoryId' => $categoryId,
                'learningPathId' => $learningPathId,
            ],
            [
                'categoryId' => null === $categoryId ? ParameterType::NULL : ParameterType::INTEGER,
                'learningPathId' => ParameterType::INTEGER,
            ],
        );
    }

    private function updateResourceLinkPosition(
        Connection $connection,
        int $resourceLinkId,
        int $courseId,
        int $position,
    ): void {
        $connection->executeStatement(
            <<<'SQL'
                UPDATE resource_link
                SET display_order = :position,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :resourceLinkId
                  AND c_id = :courseId
                  AND session_id IS NULL
                  AND group_id IS NULL
                  AND usergroup_id IS NULL
                  AND user_id IS NULL
                  AND deleted_at IS NULL
                SQL,
            [
                'position' => $position,
                'resourceLinkId' => $resourceLinkId,
                'courseId' => $courseId,
            ],
            [
                'position' => ParameterType::INTEGER,
                'resourceLinkId' => ParameterType::INTEGER,
                'courseId' => ParameterType::INTEGER,
            ],
        );
    }
}
