<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookWeightReport;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<GradebookWeightReport>
 */
final readonly class GradebookWeightReportProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookLinkResourceResolver $linkResourceResolver,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookWeightReport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $resolved = $this->contextResolver->resolve($request);
        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $category = $this->contextResolver->getSelectedCategory(
            $request,
            $resolved['course'],
            $resolved['session'],
            $rootCategory,
        );

        $report = new GradebookWeightReport();
        $report->context = [
            'cid' => (int) $resolved['course']->getId(),
            'sid' => (int) ($resolved['session']?->getId() ?? 0),
            'gid' => $resolved['groupId'],
            'node' => $request->query->getInt('node'),
        ];
        $report->category = [
            'id' => (int) $category->getId(),
            'title' => $category->getTitle(),
            'weight' => (float) $category->getWeight(),
            'hasGradeModel' => null !== $category->getGradeModel(),
        ];
        $report->expectedTotal = (float) $category->getWeight();
        $report->canManage = $resolved['canManage'] && null === $category->getGradeModel();
        $report->locked = 1 === (int) $category->getLocked();
        $report->csrfToken = (string) $this->csrfTokenManager->getToken(GradebookWeightActionProcessor::CSRF_TOKEN_ID);

        foreach ($category->getLinks() as $link) {
            if (!$link instanceof GradebookLink) {
                continue;
            }

            $summary = $this->linkResourceResolver->normalizeLink(
                $link,
                $resolved['course'],
                $resolved['session'],
                $resolved['groupId'],
                $resolved['canManage'],
            );
            $weight = (float) $link->getWeight();
            $report->currentTotal += $weight;
            $report->items[] = [
                'kind' => 'link',
                'id' => (int) $link->getId(),
                'title' => (string) ($summary['title'] ?? 'Online activity'),
                'typeLabel' => (string) ($summary['linkTypeLabel'] ?? 'Online activity'),
                'weight' => $weight,
                'locked' => 1 === (int) $link->getLocked(),
            ];
        }

        foreach ($category->getEvaluations() as $evaluation) {
            if (!$evaluation instanceof GradebookEvaluation) {
                continue;
            }

            $weight = (float) $evaluation->getWeight();
            $report->currentTotal += $weight;
            $report->items[] = [
                'kind' => 'evaluation',
                'id' => (int) $evaluation->getId(),
                'title' => (string) $evaluation->getTitle(),
                'typeLabel' => 'Score',
                'weight' => $weight,
                'locked' => 1 === (int) $evaluation->getLocked(),
            ];
        }

        usort(
            $report->items,
            static fn (array $left, array $right): int => strcasecmp((string) $left['title'], (string) $right['title']),
        );

        return $report;
    }
}
