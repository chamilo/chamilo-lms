<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSession;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseSession\CourseSessionAction;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<CourseSessionAction, CourseSessionAction>
 */
final readonly class CourseSessionActionProcessor implements ProcessorInterface
{
    public function __construct(
        private CourseSessionManager $manager,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CourseSessionAction {
        if (!$data instanceof CourseSessionAction) {
            throw new BadRequestHttpException('Invalid session action payload.');
        }

        $success = match ($operation->getName()) {
            'post_course_session_subscribe_users' => $this->manager->subscribeUsers(
                $data->sessionId,
                $data->userIds,
            ),
            'post_course_session_unsubscribe_users' => $this->manager->unsubscribeUsers(
                $data->sessionId,
                $data->userIds,
            ),
            'post_course_session_add_user_to_url' => $this->manager->addUserToCurrentUrl(
                $data->sessionId,
                $data->userId,
            ),
            'post_course_session_update_user_courses' => $this->manager->updateUserCourses(
                $data->sessionId,
                $data->userId,
                $data->avoidedCourseIds,
            ),
            default => throw new BadRequestHttpException('Unsupported session action.'),
        };

        if (!$success) {
            throw new UnprocessableEntityHttpException('The session action could not be completed.');
        }

        $data->success = true;
        $data->message = match ($operation->getName()) {
            'post_course_session_subscribe_users' => 'Users subscribed',
            'post_course_session_unsubscribe_users' => 'Users unsubscribed',
            'post_course_session_add_user_to_url' => 'The user has been added',
            'post_course_session_update_user_courses' => 'Courses updated',
            default => '',
        };

        return $data;
    }
}
