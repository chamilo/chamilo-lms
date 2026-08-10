<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSession;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseSession\CourseSessionUserList;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseSessionUserList>
 */
final readonly class CourseSessionUserListProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseSessionManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseSessionUserList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }

        $sessionId = (int) ($uriVariables['sessionId'] ?? 0);
        $data = $this->manager->getUsersData($request, $sessionId);
        $resource = new CourseSessionUserList();
        $resource->items = (array) $data['items'];
        $resource->totalItems = (int) $data['totalItems'];
        $resource->sessionId = (int) $data['sessionId'];
        $resource->view = (string) $data['view'];
        $resource->scope = (string) $data['scope'];
        $resource->profilingFields = (array) $data['profilingFields'];

        return $resource;
    }
}
