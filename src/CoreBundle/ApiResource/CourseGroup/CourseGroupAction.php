<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseGroup;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseGroupAction',
    operations: [
        new Post(
            uriTemplate: '/course-groups/actions/create-groups',
            openapi: new Operation(summary: 'Create one or more course groups'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_create_groups',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/create-subgroups',
            openapi: new Operation(summary: 'Create subgroups from an existing group'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_create_subgroups',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/create-class-groups',
            openapi: new Operation(summary: 'Create course groups from subscribed classes'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_create_class_groups',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/delete',
            openapi: new Operation(summary: 'Delete selected course groups'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_delete',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/empty',
            openapi: new Operation(summary: 'Remove all members from selected course groups'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_empty',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/fill',
            openapi: new Operation(summary: 'Fill selected course groups with course learners'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_fill',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/toggle-visibility',
            openapi: new Operation(summary: 'Change group visibility'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_toggle_visibility',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/self-register',
            openapi: new Operation(summary: 'Register the authenticated learner in a group'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_self_register',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/self-unregister',
            openapi: new Operation(summary: 'Unregister the authenticated learner from a group'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_self_unregister',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/delete-category',
            openapi: new Operation(summary: 'Delete a course group category'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_delete_category',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/move-category',
            openapi: new Operation(summary: 'Move a course group category'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_move_category',
            processor: CourseGroupActionProcessor::class,
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
            uriTemplate: '/course-groups/actions/remove-class-link',
            openapi: new Operation(summary: 'Remove the consistent link between a course group and a class'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_remove_class_link',
            processor: CourseGroupActionProcessor::class,
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
    normalizationContext: ['groups' => ['course_group_action:read']],
    denormalizationContext: ['groups' => ['course_group_action:write']],
)]
final class CourseGroupAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_group_action:read'])]
    public string $id = 'course_group_action';

    /**
     * @var int[]
     */
    #[Groups(['course_group_action:write'])]
    public array $groupIds = [];

    #[Groups(['course_group_action:write'])]
    public int $groupId = 0;

    #[Groups(['course_group_action:write'])]
    public int $categoryId = 0;

    #[Groups(['course_group_action:write'])]
    public int $otherCategoryId = 0;

    #[Groups(['course_group_action:write'])]
    public int $baseGroupId = 0;

    #[Groups(['course_group_action:write'])]
    public int $numberOfGroups = 0;

    /**
     * @var int[]
     */
    #[Groups(['course_group_action:write'])]
    public array $classIds = [];

    #[Groups(['course_group_action:write'])]
    public bool $consistentLink = false;

    #[Groups(['course_group_action:write'])]
    public bool $visible = true;

    /**
     * @var array<int, array{name: string, categoryId?: int, maxStudent?: int}>
     */
    #[Groups(['course_group_action:write'])]
    public array $groups = [];

    #[Groups(['course_group_action:read'])]
    public bool $success = false;

    #[Groups(['course_group_action:read'])]
    public string $message = '';

    /**
     * @var int[]
     */
    #[Groups(['course_group_action:read'])]
    public array $affectedIds = [];

    public function getId(): string
    {
        return $this->id;
    }
}
