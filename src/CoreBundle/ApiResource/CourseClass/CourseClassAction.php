<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseClass;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CourseClass\CourseClassActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseClassAction',
    operations: [
        new Post(
            uriTemplate: '/course-classes/actions/add',
            openapi: new Operation(
                summary: 'Link a class to the current course or session',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_class_add',
            processor: CourseClassActionProcessor::class,
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
            uriTemplate: '/course-classes/actions/remove',
            openapi: new Operation(
                summary: 'Remove a class and apply legacy user unsubscription behavior',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_class_remove',
            processor: CourseClassActionProcessor::class,
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
            uriTemplate: '/course-classes/actions/remove-only',
            openapi: new Operation(
                summary: 'Remove only the class relation and keep users subscribed',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_class_remove_only',
            processor: CourseClassActionProcessor::class,
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
    normalizationContext: ['groups' => ['course_class_action:read']],
    denormalizationContext: ['groups' => ['course_class_action:write']],
)]
final class CourseClassAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_class_action:read'])]
    public string $id = 'course_class_action';

    #[Groups(['course_class_action:write'])]
    public int $usergroupId = 0;

    #[Groups(['course_class_action:read'])]
    public bool $success = false;

    #[Groups(['course_class_action:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
