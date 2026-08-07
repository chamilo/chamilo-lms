<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseClass;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseClass\CourseClassList;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseClassList>
 */
final readonly class CourseClassListProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseClassManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseClassList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }

        $data = $this->manager->getListData($request);
        $resource = new CourseClassList();
        $resource->items = (array) $data['items'];
        $resource->totalItems = (int) $data['totalItems'];
        $resource->courseId = (int) $data['courseId'];
        $resource->sessionId = isset($data['sessionId']) ? (int) $data['sessionId'] : null;
        $resource->view = (string) $data['view'];
        $resource->groupFilter = (int) $data['groupFilter'];
        $resource->canManage = !empty($data['canManage']);
        $resource->groupsUrl = (string) ($data['groupsUrl'] ?? '');
        $resource->information = (string) $data['information'];

        return $resource;
    }
}
