<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseUser;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseUser\CourseUserAction;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<CourseUserAction, CourseUserAction>
 */
final readonly class CourseUserActionProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseUserManager $courseUserManager,
        private CourseUserWriteManager $writeManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseUserAction
    {
        if (!$data instanceof CourseUserAction) {
            throw new BadRequestHttpException('A valid course user action payload is required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        [$course, $session] = $this->courseUserManager->resolveContext();
        $type = $this->courseUserManager->normalizeType($request);
        $operationName = (string) $operation->getName();

        if ('post_course_user_subscribe' === $operationName) {
            $result = $this->writeManager->subscribe($course, $session, $data->userIds, $type);
            $data->affectedIds = $result['affectedIds'];
            $data->failed = $result['failed'];
            $data->success = [] !== $data->affectedIds;
            $data->message = [] === $data->failed
                ? get_lang('The selected users have been subscribed to the course')
                : get_lang('Some users could not be subscribed');

            return $data;
        }

        if ('post_course_user_unsubscribe' === $operationName) {
            $data->affectedIds = $this->writeManager->unsubscribe($course, $session, $data->userIds, $type);
            $data->success = true;
            $data->message = get_lang('The selected users have been unsubscribed from the course');

            return $data;
        }

        if ('post_course_user_tutor' === $operationName) {
            $userId = (int) ($data->userIds[0] ?? 0);
            if ($userId <= 0) {
                throw new BadRequestHttpException('A valid user id is required.');
            }

            $data->success = $this->writeManager->setTutor($course, $session, $userId, $data->tutor);
            $data->affectedIds = $data->success ? [$userId] : [];
            $data->message = $data->success ? get_lang('Update successful') : get_lang('No change has been made');

            return $data;
        }

        throw new BadRequestHttpException('The requested course user action is not supported.');
    }
}
