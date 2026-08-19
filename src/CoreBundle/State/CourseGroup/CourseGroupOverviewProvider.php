<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupOverview;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseGroupOverview>
 */
final readonly class CourseGroupOverviewProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseGroupOverview
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }

        $resource = new CourseGroupOverview();
        $resource->groups = $this->manager->getOverviewData($request);
        $query = http_build_query(array_filter([
            'cid' => (int) $this->cidReqHelper->getCourseId(),
            'sid' => (int) $this->cidReqHelper->getSessionId(),
        ], static fn (int $value): bool => $value > 0));
        $resource->csvExportUrl = '/api/course-groups/export.csv?'.$query;
        $resource->xlsxExportUrl = '/api/course-groups/export.xlsx?'.$query;
        $resource->pdfExportUrl = '/api/course-groups/export.pdf?'.$query;

        return $resource;
    }
}
