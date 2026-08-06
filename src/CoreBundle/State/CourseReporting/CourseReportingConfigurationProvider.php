<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseReporting;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseReporting\CourseReportingConfiguration;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContextResolver;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingQueryService;

/** @implements ProviderInterface<CourseReportingConfiguration> */
final readonly class CourseReportingConfigurationProvider implements ProviderInterface
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
    ): CourseReportingConfiguration {
        $data = $this->queryService->getConfiguration($this->contextResolver->resolve());

        $resource = new CourseReportingConfiguration();
        $resource->courseId = (int) $data['courseId'];
        $resource->courseResourceNodeId = (int) $data['courseResourceNodeId'];
        $resource->courseCode = (string) $data['courseCode'];
        $resource->courseTitle = (string) $data['courseTitle'];
        $resource->sessionId = (int) $data['sessionId'];
        $resource->sessionTitle = (string) $data['sessionTitle'];
        $resource->groupId = (int) $data['groupId'];
        $resource->currentUserId = (int) $data['currentUserId'];
        $resource->allowMessageTracking = (bool) $data['allowMessageTracking'];
        $resource->showEmailAddresses = (bool) $data['showEmailAddresses'];
        $resource->showCharts = (bool) $data['showCharts'];
        $resource->groups = $data['groups'];
        $resource->classes = $data['classes'];
        $resource->teachers = $data['teachers'];
        $resource->sessions = $data['sessions'];
        $resource->extraFields = $data['extraFields'];
        $resource->configuredExercises = $data['configuredExercises'];
        $resource->hiddenColumnIndexes = $data['hiddenColumnIndexes'];
        $resource->defaultExtraFieldVariables = $data['defaultExtraFieldVariables'];
        $resource->inactiveDayOptions = $data['inactiveDayOptions'];
        $resource->tabs = $data['tabs'];

        return $resource;
    }
}
