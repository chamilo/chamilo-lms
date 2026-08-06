<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseReporting;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseReporting\CourseReportingSection;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContextResolver;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingSectionQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProviderInterface<CourseReportingSection> */
final readonly class CourseReportingSectionProvider implements ProviderInterface
{
    public function __construct(
        private CourseReportingContextResolver $contextResolver,
        private CourseReportingSectionQueryService $queryService,
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
    ): CourseReportingSection {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $section = match ($operation->getName()) {
            'get_course_reporting_activity' => 'activity',
            'get_course_reporting_groups' => 'groups',
            'get_course_reporting_resources' => 'resources',
            'get_course_reporting_tools' => 'tools',
            'get_course_reporting_exams' => 'exams',
            'get_course_reporting_audit' => 'audit',
            'get_course_reporting_learning_paths' => 'learning-paths',
            'get_course_reporting_total_time' => 'total-time',
            'get_course_reporting_session' => 'session',
            'get_course_reporting_messages' => 'messages',
            default => throw new BadRequestHttpException('Unknown reporting section.'),
        };

        $data = $this->queryService->getSection(
            $this->contextResolver->resolve(),
            $section,
            $request->query->all()
        );

        $resource = new CourseReportingSection();
        $resource->id = 'course_reporting_'.$section;
        $resource->section = (string) $data['section'];
        $resource->title = (string) $data['title'];
        $resource->total = (int) $data['total'];
        $resource->page = (int) $data['page'];
        $resource->itemsPerPage = (int) $data['itemsPerPage'];
        $resource->summary = $data['summary'];
        $resource->columns = $data['columns'];
        $resource->items = $data['items'];
        $resource->sections = $data['sections'];
        $resource->meta = $data['meta'];

        return $resource;
    }
}
