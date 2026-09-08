<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookGraph;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookScoreDisplay;
use Chamilo\CoreBundle\Helpers\Gradebook\GradebookReportHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<GradebookGraph>
 */
final readonly class GradebookGraphProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GradebookReportHelper $reportHelper,
        private GradebookContextResolver $contextResolver,
        private GradebookCriteriaFactory $criteriaFactory,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookGraph
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $report = $this->reportHelper->buildReport(
            $this->contextResolver->resolve($request),
            $this->criteriaFactory->reportFrom($request, exportAll: true, includeScores: true),
        );
        $resource = new GradebookGraph();
        $resource->context = $report->context;
        $resource->category = $report->category;

        if (!$this->contextResolver->isSettingEnabled('gradebook.gradebook_score_display_custom')
            || true === ($report->settings['hideGraph'] ?? false)
            || null === $report->category
        ) {
            return $resource;
        }

        $category = $this->entityManager->getRepository(GradebookCategory::class)->find((int) ($report->category['id'] ?? 0));
        if (!$category instanceof GradebookCategory) {
            return $resource;
        }

        $rows = $this->entityManager->getRepository(GradebookScoreDisplay::class)->findBy(
            ['category' => $category],
            ['score' => 'ASC'],
        );
        $ranges = [];
        foreach ($rows as $row) {
            if ($row instanceof GradebookScoreDisplay) {
                $ranges[] = ['score' => (float) $row->getScore(), 'display' => (string) $row->getDisplay()];
            }
        }
        if ([] === $ranges) {
            return $resource;
        }

        $upperLimitIncluded = $this->contextResolver->isSettingEnabled('gradebook.gradebook_score_display_upperlimit');
        foreach ($report->columns as $column) {
            $key = (string) ($column['key'] ?? '');
            if ('' === $key) {
                continue;
            }
            $resource->resources[] = $this->buildDistribution(
                (string) ($column['title'] ?? ''),
                $key,
                $ranges,
                $upperLimitIncluded,
                $report->rows,
                $report->settings,
            );
        }
        $resource->resources[] = $this->buildDistribution(
            'Total',
            'total',
            $ranges,
            $upperLimitIncluded,
            $report->rows,
            $report->settings,
            true,
        );
        $resource->enabled = true;

        return $resource;
    }

    /**
     * @param list<array{score: float, display: string}> $ranges
     * @param list<array<string, mixed>>                 $rows
     * @param array<string, mixed>                       $settings
     *
     * @return array<string, mixed>
     */
    private function buildDistribution(
        string $title,
        string $key,
        array $ranges,
        bool $upperLimitIncluded,
        array $rows,
        array $settings,
        bool $withAverage = false,
    ): array {
        $counts = [];
        foreach ($ranges as $range) {
            $counts[$range['display']] = 0;
        }

        $percentages = [];
        foreach ($rows as $row) {
            $result = 'total' === $key ? ($row['total'] ?? null) : ($row['scores'][$key] ?? null);
            $percentage = 0.0;
            if (\is_array($result) && true === ($result['hasResult'] ?? false) && is_numeric($result['percentage'] ?? null)) {
                if (str_starts_with($key, 'category:')
                    && true === ($settings['useExerciseScoreSettingsInCategories'] ?? false)
                    && true !== ($settings['useExerciseScoreSettingsInTotal'] ?? false)
                ) {
                    $result = $this->convertCategoryToExerciseScale($result, $settings);
                }
                $percentage = is_numeric($result['percentage'] ?? null) ? (float) $result['percentage'] : 0.0;
            }
            $percentages[] = $percentage;
            $label = $this->resolveDisplay($percentage, $ranges, $upperLimitIncluded);
            if (null !== $label) {
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }
        }

        // Ascending, so the lowest range is the leftmost bar of the chart.
        $distribution = [];
        foreach ($ranges as $range) {
            $distribution[] = [
                'label' => $range['display'],
                'count' => $counts[$range['display']] ?? 0,
            ];
        }

        return [
            'key' => $key,
            'title' => $title,
            'distribution' => $distribution,
            'average' => $withAverage ? $this->buildAverage($percentages, $ranges, $upperLimitIncluded) : null,
        ];
    }

    /**
     * Averages the same values the bars count, so the point never contradicts them:
     * a row without a result weighs as 0, exactly as it does in the lowest bucket.
     *
     * @param list<float>                                $percentages
     * @param list<array{score: float, display: string}> $ranges
     *
     * @return array<string, mixed>|null
     */
    private function buildAverage(array $percentages, array $ranges, bool $upperLimitIncluded): ?array
    {
        if ([] === $percentages) {
            return null;
        }

        $average = array_sum($percentages) / \count($percentages);

        return [
            'percentage' => round($average, 2),
            'label' => $this->resolveDisplay($average, $ranges, $upperLimitIncluded),
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    private function convertCategoryToExerciseScale(array $result, array $settings): array
    {
        $minimum = $settings['exerciseMinScore'] ?? null;
        $maximum = $settings['exerciseMaxScore'] ?? null;
        $score = $result['score'] ?? null;
        $weight = $result['maxScore'] ?? null;
        if (!is_numeric($minimum) || !is_numeric($maximum) || !is_numeric($score) || !is_numeric($weight)) {
            return $result;
        }

        $minimum = (float) $minimum;
        $maximum = (float) $maximum;
        $weight = (float) $weight;
        if ($maximum <= $minimum) {
            return $result;
        }

        $score = 0.0 !== $weight
            ? $minimum + (($maximum - $minimum) * (float) $score / $weight)
            : $minimum;
        $result['score'] = $score;
        $result['maxScore'] = $maximum;
        $result['percentage'] = 0.0 !== $maximum ? ($score / $maximum) * 100.0 : null;

        return $result;
    }

    /**
     * @param list<array{score: float, display: string}> $ranges
     */
    private function resolveDisplay(float $percentage, array $ranges, bool $upperLimitIncluded): ?string
    {
        $score = max(0.0, min(100.0, $percentage));
        foreach ($ranges as $range) {
            $limit = $range['score'];
            if (($upperLimitIncluded && $score <= $limit)
                || (!$upperLimitIncluded && ($score < $limit || 100.0 === $limit))
            ) {
                return $range['display'];
            }
        }

        return $ranges[array_key_last($ranges)]['display'] ?? null;
    }
}
