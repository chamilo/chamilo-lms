<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseReporting;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseReporting\CourseReportingOverview;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContextResolver;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingQueryService;

/** @implements ProviderInterface<CourseReportingOverview> */
final readonly class CourseReportingOverviewProvider implements ProviderInterface
{
    public function __construct(
        private CourseReportingContextResolver $contextResolver,
        private CourseReportingQueryService $queryService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CourseReportingOverview {
        $data = $this->queryService->getOverview($this->contextResolver->resolve());

        $resource = new CourseReportingOverview();
        $resource->numberStudents = (int) $data['numberStudents'];
        $resource->completedLearningPaths = (int) $data['completedLearningPaths'];
        $resource->exerciseAverage = (float) $data['exerciseAverage'];
        $resource->certificateCount = (int) $data['certificateCount'];
        $resource->scoreDistribution = $data['scoreDistribution'];
        $resource->topStudents = $data['topStudents'];
        $resource->timeStudents = $data['timeStudents'];

        return $resource;
    }
}
