<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookEvaluationResults;
use Chamilo\CoreBundle\Helpers\Gradebook\GradebookEvaluationResultsHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<GradebookEvaluationResults>
 */
final readonly class GradebookEvaluationResultsProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookCriteriaFactory $criteriaFactory,
        private GradebookEvaluationResultsHelper $evaluationResultsHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookEvaluationResults
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        return $this->evaluationResultsHelper->buildReport(
            $this->contextResolver->resolve($request),
            $this->criteriaFactory->evaluationResultsFrom($request),
        );
    }
}
