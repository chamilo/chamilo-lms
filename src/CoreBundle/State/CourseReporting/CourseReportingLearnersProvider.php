<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseReporting;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseReporting\CourseReportingLearners;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContextResolver;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProviderInterface<CourseReportingLearners> */
final readonly class CourseReportingLearnersProvider implements ProviderInterface
{
    public function __construct(
        private CourseReportingContextResolver $contextResolver,
        private CourseReportingQueryService $queryService,
        private RequestStack $requestStack,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CourseReportingLearners {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $data = $this->queryService->getLearners(
            $this->contextResolver->resolve(),
            [
                'page' => $request->query->getInt('page', 1),
                'itemsPerPage' => $request->query->getInt('itemsPerPage', 20),
                'keyword' => (string) $request->query->get('keyword', ''),
                'groupFilter' => (string) $request->query->get('groupFilter', ''),
                'showTeachers' => $request->query->getBoolean('showTeachers'),
                'showActiveUsers' => $request->query->getBoolean('showActiveUsers'),
                'sort' => (string) $request->query->get('sort', 'lastname'),
                'direction' => (string) $request->query->get('direction', 'ASC'),
                'extraFieldIds' => (string) $request->query->get('extraFieldIds', ''),
                'extraFieldFilters' => (string) $request->query->get('extraFieldFilters', ''),
            ]
        );

        $resource = new CourseReportingLearners();
        $resource->total = $data['total'];
        $resource->page = $data['page'];
        $resource->itemsPerPage = $data['itemsPerPage'];
        $resource->items = $data['items'];
        $resource->groupSummary = $data['groupSummary'];

        return $resource;
    }
}
