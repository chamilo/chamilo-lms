<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseUser;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseUser\CourseUserActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseUserAction',
    operations: [
        new Post(
            uriTemplate: '/course-users/actions/subscribe',
            openapi: new Operation(
                summary: 'Subscribe users to the current course or session-course context',
                parameters: [
                    new Parameter(name: 'type', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_user_subscribe',
            processor: CourseUserActionProcessor::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
            ],
        ),
        new Post(
            uriTemplate: '/course-users/actions/unsubscribe',
            openapi: new Operation(
                summary: 'Unsubscribe users from the current course or session-course context',
                parameters: [
                    new Parameter(name: 'type', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_user_unsubscribe',
            processor: CourseUserActionProcessor::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
            ],
        ),
        new Post(
            uriTemplate: '/course-users/actions/tutor',
            openapi: new Operation(
                summary: 'Enable or disable the course tutor role for a student',
                parameters: [
                    new Parameter(name: 'type', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_user_tutor',
            processor: CourseUserActionProcessor::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['course_user_action:read']],
    denormalizationContext: ['groups' => ['course_user_action:write']],
)]
final class CourseUserAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_user_action:read'])]
    public string $id = 'course_user_action';

    /**
     * @var int[]
     */
    #[Groups(['course_user_action:write'])]
    public array $userIds = [];

    #[Groups(['course_user_action:write'])]
    public bool $tutor = false;

    #[Groups(['course_user_action:read'])]
    public bool $success = false;

    #[Groups(['course_user_action:read'])]
    public string $message = '';

    /**
     * @var int[]
     */
    #[Groups(['course_user_action:read'])]
    public array $affectedIds = [];

    /**
     * @var array<int, array{id: int, message: string}>
     */
    #[Groups(['course_user_action:read'])]
    public array $failed = [];

    public function getId(): string
    {
        return $this->id;
    }
}
