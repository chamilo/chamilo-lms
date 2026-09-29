<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseSession;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CourseSession\CourseSessionActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseSessionAction',
    operations: [
        new Post(
            uriTemplate: '/course-sessions/actions/subscribe-users',
            openapi: new Operation(summary: 'Subscribe students to a manageable session'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_session_subscribe_users',
            processor: CourseSessionActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/course-sessions/actions/unsubscribe-users',
            openapi: new Operation(summary: 'Unsubscribe students from a manageable session'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_session_unsubscribe_users',
            processor: CourseSessionActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/course-sessions/actions/add-user-to-url',
            openapi: new Operation(summary: 'Add a session student to the current access URL'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_session_add_user_to_url',
            processor: CourseSessionActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/course-sessions/actions/update-user-courses',
            openapi: new Operation(summary: 'Update inaccessible courses for a session student'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_session_update_user_courses',
            processor: CourseSessionActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_session_action:read']],
    denormalizationContext: ['groups' => ['course_session_action:write']],
)]
final class CourseSessionAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_session_action:read'])]
    public string $id = 'course_session_action';

    #[Groups(['course_session_action:write'])]
    public int $sessionId = 0;

    /**
     * @var int[]
     */
    #[Groups(['course_session_action:write'])]
    public array $userIds = [];

    #[Groups(['course_session_action:write'])]
    public int $userId = 0;

    /**
     * @var int[]
     */
    #[Groups(['course_session_action:write'])]
    public array $avoidedCourseIds = [];

    #[Groups(['course_session_action:read'])]
    public bool $success = false;

    #[Groups(['course_session_action:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
