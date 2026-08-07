<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupMembers;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<CourseGroupMembers, CourseGroupMembers>
 */
final readonly class CourseGroupMembersProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseGroupMembers
    {
        if (!$data instanceof CourseGroupMembers) {
            throw new BadRequestHttpException('Invalid group membership payload.');
        }
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }
        $mode = str_contains((string) $operation->getName(), 'tutors') ? 'tutors' : 'members';
        $groupId = (int) ($uriVariables['groupId'] ?? $request->attributes->get('groupId', 0));
        $this->manager->saveMembers($request, $groupId, $mode, $data->selectedIds);
        $data->success = true;
        $data->message = 'Saved';

        return $data;
    }
}
