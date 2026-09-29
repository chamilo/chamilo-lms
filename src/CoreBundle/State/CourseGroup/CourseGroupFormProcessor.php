<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupForm;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<CourseGroupForm, CourseGroupForm>
 */
final readonly class CourseGroupFormProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseGroupForm
    {
        if (!$data instanceof CourseGroupForm) {
            throw new BadRequestHttpException('Invalid group form payload.');
        }
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }
        $groupId = (int) ($uriVariables['groupId'] ?? $request->attributes->get('groupId', 0));
        $data->groupId = $this->manager->saveGroup($data, $groupId);
        $data->success = true;
        $data->message = 'Saved';

        return $data;
    }
}
