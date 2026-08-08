<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupList;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseGroupList>
 */
final readonly class CourseGroupListProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseGroupList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }

        $data = $this->manager->getListData($request);
        $resource = new CourseGroupList();
        $resource->categories = (array) $data['categories'];
        $resource->totalGroups = (int) $data['totalGroups'];
        $resource->courseId = (int) $data['courseId'];
        $resource->sessionId = isset($data['sessionId']) ? (int) $data['sessionId'] : null;
        $resource->allowCategories = !empty($data['allowCategories']);
        $resource->canManageCourse = !empty($data['canManageCourse']);
        $resource->canCreateCategory = !empty($data['canCreateCategory']);
        $resource->defaultCategoryId = (int) ($data['defaultCategoryId'] ?? 0);
        $resource->showSubscriptionTabs = !empty($data['showSubscriptionTabs']);
        $resource->showClasses = !empty($data['showClasses']);
        $query = http_build_query(array_filter([
            'cid' => $resource->courseId,
            'sid' => $resource->sessionId,
        ], static fn (mixed $value): bool => null !== $value && 0 !== $value));
        $resource->csvExportUrl = '/api/course-groups/export.csv?'.$query;
        $resource->xlsxExportUrl = '/api/course-groups/export.xlsx?'.$query;
        $resource->pdfExportUrl = '/api/course-groups/export.pdf?'.$query;

        return $resource;
    }
}
