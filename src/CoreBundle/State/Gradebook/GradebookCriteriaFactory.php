<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use Chamilo\CoreBundle\Dto\Gradebook\GradebookEvaluationResultsCriteria;
use Chamilo\CoreBundle\Dto\Gradebook\GradebookLearnerReportCriteria;
use Chamilo\CoreBundle\Dto\Gradebook\GradebookReportCriteria;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maps the query string onto the Gradebook criteria objects.
 *
 * The only place that reads those parameters, which keeps the criteria free of
 * HTTP and the clamping rules in one file rather than in every caller. It sits
 * next to GradebookContextResolver because both belong to the layer that still
 * knows about the request.
 */
final readonly class GradebookCriteriaFactory
{
    private const array SORT_FIELDS = ['fullName', 'firstName', 'lastName', 'username'];

    /**
     * @param bool|null $exportAll     null reads the "all" query parameter; callers that
     *                                 always need every row pass true
     * @param bool|null $includeScores null reads the "includeScores" query parameter
     */
    public function reportFrom(
        Request $request,
        ?bool $exportAll = null,
        ?bool $includeScores = null,
    ): GradebookReportCriteria {
        $sortBy = (string) $request->query->get('sortBy', 'fullName');

        return new GradebookReportCriteria(
            node: $request->query->getInt('node'),
            categoryId: $request->query->getInt('categoryId'),
            page: max(1, $request->query->getInt('page', 1)),
            itemsPerPage: min(100, max(1, $request->query->getInt('itemsPerPage', 20))),
            // Lowercased once here: the only reader matches it case-insensitively.
            search: mb_strtolower(trim((string) $request->query->get('search', ''))),
            sortBy: \in_array($sortBy, self::SORT_FIELDS, true) ? $sortBy : 'fullName',
            sortDirection: 'desc' === strtolower((string) $request->query->get('sortDirection', 'asc'))
                ? 'desc'
                : 'asc',
            exportAll: $exportAll ?? $this->readBoolean($request, 'all', false),
            includeScores: $includeScores ?? $this->readBoolean($request, 'includeScores', true),
        );
    }

    /**
     * @param int|null $forcedUserId the export controller names each learner instead
     *                               of reading one from the query string
     */
    public function learnerReportFrom(Request $request, ?int $forcedUserId = null): GradebookLearnerReportCriteria
    {
        return new GradebookLearnerReportCriteria(
            node: $request->query->getInt('node'),
            categoryId: $request->query->getInt('categoryId'),
            userId: $forcedUserId ?? $request->query->getInt('userId'),
        );
    }

    public function evaluationResultsFrom(Request $request): GradebookEvaluationResultsCriteria
    {
        return new GradebookEvaluationResultsCriteria(
            node: $request->query->getInt('node'),
            evaluationId: $request->query->getInt('evaluationId'),
        );
    }

    private function readBoolean(Request $request, string $name, bool $default): bool
    {
        $value = $request->query->get($name);
        if (null === $value || '' === $value) {
            return $default;
        }

        return \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
