<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseClass;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseClass\CourseClassAction;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<CourseClassAction, CourseClassAction>
 */
final readonly class CourseClassActionProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseClassManager $manager,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CourseClassAction {
        if (!$data instanceof CourseClassAction) {
            throw new BadRequestHttpException('Invalid class action payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }

        [$course, $session] = $this->manager->resolveContext();
        $this->manager->assertCanManage($course, $session);
        $usergroup = $this->manager->findAccessibleGroup($data->usergroupId);

        $success = match ($operation->getName()) {
            'post_course_class_add' => $this->manager->add($usergroup, $course, $session),
            'post_course_class_remove' => $this->manager->remove($usergroup, $course, $session),
            'post_course_class_remove_only' => $this->manager->removeOnly($usergroup, $course, $session),
            default => throw new BadRequestHttpException('Unsupported class action.'),
        };

        if (!$success) {
            throw new UnprocessableEntityHttpException('The class action could not be completed.');
        }

        $data->success = true;
        $data->message = match ($operation->getName()) {
            'post_course_class_add' => 'Added',
            'post_course_class_remove' => 'Deleted',
            'post_course_class_remove_only' => 'Removed',
            default => '',
        };

        return $data;
    }
}
