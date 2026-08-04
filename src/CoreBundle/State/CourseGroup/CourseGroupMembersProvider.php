<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupMembers;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<CourseGroupMembers>
 */
final readonly class CourseGroupMembersProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseGroupMembers
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }
        $mode = str_contains((string) $operation->getName(), 'tutors') ? 'tutors' : 'members';
        $groupId = (int) ($uriVariables['groupId'] ?? $request->attributes->get('groupId', 0));
        $data = $this->manager->getMembersData($request, $groupId, $mode);
        $resource = new CourseGroupMembers();
        foreach ($data as $property => $value) {
            $resource->{$property} = $value;
        }
        $resource->csrfToken = $this->csrfTokenManager->getToken($this->manager->getCsrfIntention())->getValue();

        return $resource;
    }
}
