<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSession;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseSession\CourseSessionList;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseSessionList>
 */
final readonly class CourseSessionListProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseSessionManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseSessionList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }

        $data = $this->manager->getListData($request);
        $resource = new CourseSessionList();
        $resource->items = (array) $data['items'];
        $resource->totalItems = (int) $data['totalItems'];
        $resource->active = (int) $data['active'];
        $resource->canCreate = !empty($data['canCreate']);
        $resource->createSessionUrl = (string) $data['createSessionUrl'];
        $resource->addToCategoryUrl = (string) $data['addToCategoryUrl'];
        $resource->categoriesUrl = (string) $data['categoriesUrl'];

        return $resource;
    }
}
