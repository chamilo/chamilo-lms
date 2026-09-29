<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupMembers;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseGroupMembers>
 */
final readonly class CourseGroupMembersProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseGroupMembers
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }
        $mode = str_contains((string) $operation->getName(), 'tutors') ? 'tutors' : 'members';
        $groupId = (int) ($uriVariables['groupId'] ?? $request->attributes->get('groupId', 0));
        $data = $this->manager->getMembersData($groupId, $mode);
        $resource = new CourseGroupMembers();
        foreach ($data as $property => $value) {
            $resource->{$property} = $value;
        }

        return $resource;
    }
}
