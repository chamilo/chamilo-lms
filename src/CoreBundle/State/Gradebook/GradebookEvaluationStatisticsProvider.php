<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookEvaluationStatistics;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\GradebookScoreDisplay;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<GradebookEvaluationStatistics>
 */
final readonly class GradebookEvaluationStatisticsProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookEvaluationStatistics
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $resolved = $this->contextResolver->resolve($request);
        if (!$resolved['canManage']) {
            throw new AccessDeniedHttpException('You are not allowed to view Gradebook evaluation statistics.');
        }

        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $evaluationId = $request->query->getInt('evaluationId');
        if ($evaluationId <= 0) {
            throw new BadRequestHttpException('A valid evaluation id is required.');
        }

        $evaluation = $this->entityManager->getRepository(GradebookEvaluation::class)->find($evaluationId);
        if (!$evaluation instanceof GradebookEvaluation) {
            throw new NotFoundHttpException('The requested evaluation was not found.');
        }
        if ((int) $evaluation->getCourse()->getId() !== (int) $resolved['course']->getId()) {
            throw new AccessDeniedHttpException('The requested evaluation belongs to another course.');
        }

        $category = $evaluation->getCategory();
        $this->contextResolver->getCategoryInGradebook(
            (int) $category->getId(),
            $rootCategory,
            $resolved['course'],
            $resolved['session'],
        );

        $resource = new GradebookEvaluationStatistics();
        $resource->context = [
            'cid' => (int) $resolved['course']->getId(),
            'sid' => (int) ($resolved['session']?->getId() ?? 0),
            'gid' => $resolved['groupId'],
            'node' => $request->query->getInt('node'),
        ];
        $resource->evaluation = [
            'id' => (int) $evaluation->getId(),
            'categoryId' => (int) $category->getId(),
            'title' => (string) $evaluation->getTitle(),
            'maxScore' => (float) $evaluation->getMax(),
        ];

        $resource->customEnabled = $this->contextResolver->isSettingEnabled('gradebook.gradebook_score_display_custom');
        if (!$resource->customEnabled) {
            return $resource;
        }

        $displayRows = $this->entityManager->getRepository(GradebookScoreDisplay::class)->findBy(
            ['category' => $rootCategory],
            ['score' => 'ASC'],
        );
        if ([] === $displayRows) {
            return $resource;
        }

        $ranges = [];
        $highestScore = 0.0;
        foreach ($displayRows as $displayRow) {
            if (!$displayRow instanceof GradebookScoreDisplay) {
                continue;
            }
            $score = (float) $displayRow->getScore();
            $highestScore = max($highestScore, $score);
            $ranges[] = [
                'score' => $score,
                'display' => (string) $displayRow->getDisplay(),
            ];
        }
        if ([] === $ranges || $highestScore <= 0.0) {
            return $resource;
        }

        $upperLimitIncluded = $this->contextResolver->isSettingEnabled('gradebook.gradebook_score_display_upperlimit');
        $counts = [];
        foreach ($ranges as $range) {
            $counts[$range['display']] = 0;
        }

        $results = $this->entityManager->getRepository(GradebookResult::class)->findBy(['evaluation' => $evaluation]);
        foreach ($results as $result) {
            if (!$result instanceof GradebookResult || null === $result->getScore()) {
                continue;
            }

            $label = $this->resolveDisplayLabel(
                (float) $result->getScore(),
                (float) $evaluation->getMax(),
                $ranges,
                $highestScore,
                $upperLimitIncluded,
            );
            if (null === $label) {
                continue;
            }

            $counts[$label] = ($counts[$label] ?? 0) + 1;
            ++$resource->resultCount;
        }

        $highestCount = [] === $counts ? 0 : max($counts);
        foreach ($ranges as $range) {
            $label = $range['display'];
            $count = $counts[$label] ?? 0;
            $resource->rows[] = [
                'label' => $label,
                'barPercent' => $highestCount > 0 ? ($count / $highestCount) * 100 : 0.0,
                'count' => $count,
            ];
        }

        return $resource;
    }

    /**
     * @param list<array{score: float, display: string}> $ranges
     */
    private function resolveDisplayLabel(
        float $score,
        float $maxScore,
        array $ranges,
        float $highestScore,
        bool $upperLimitIncluded,
    ): ?string {
        $denominator = $maxScore > 0.0 ? $maxScore : 1.0;
        $scaledScore = $score / $denominator;

        foreach ($ranges as $range) {
            $normalizedBoundary = $range['score'] / $highestScore;
            if ($upperLimitIncluded) {
                if ($scaledScore <= $normalizedBoundary) {
                    return $range['display'];
                }

                continue;
            }

            if ($scaledScore < $normalizedBoundary || 1.0 === $normalizedBoundary) {
                return $range['display'];
            }
        }

        return null;
    }
}
