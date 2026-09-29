<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseGroup;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupMembersProcessor;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupMembersProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseGroupMembers',
    operations: [
        new Get(
            uriTemplate: '/course-groups/{groupId}/members',
            uriVariables: [
                'groupId' => new Link(schema: ['type' => 'integer'], property: 'groupId'),
            ],
            openapi: new Operation(summary: 'Course group member selection data'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_group_members',
            provider: CourseGroupMembersProvider::class,
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
            uriTemplate: '/course-groups/{groupId}/members',
            uriVariables: [
                'groupId' => new Link(schema: ['type' => 'integer'], property: 'groupId'),
            ],
            openapi: new Operation(summary: 'Replace course group members'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_members',
            processor: CourseGroupMembersProcessor::class,
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
        new Get(
            uriTemplate: '/course-groups/{groupId}/tutors',
            uriVariables: [
                'groupId' => new Link(schema: ['type' => 'integer'], property: 'groupId'),
            ],
            openapi: new Operation(summary: 'Course group tutor selection data'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_group_tutors',
            provider: CourseGroupMembersProvider::class,
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
            uriTemplate: '/course-groups/{groupId}/tutors',
            uriVariables: [
                'groupId' => new Link(schema: ['type' => 'integer'], property: 'groupId'),
            ],
            openapi: new Operation(summary: 'Replace course group tutors'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_tutors',
            processor: CourseGroupMembersProcessor::class,
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
    normalizationContext: ['groups' => ['course_group_members:read']],
    denormalizationContext: ['groups' => ['course_group_members:write']],
)]
final class CourseGroupMembers
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_group_members:read'])]
    public string $id = 'course_group_members';

    #[Groups(['course_group_members:read'])]
    public int $groupId = 0;

    #[Groups(['course_group_members:read'])]
    public string $groupTitle = '';

    #[Groups(['course_group_members:read'])]
    public string $mode = 'members';

    /**
     * @var array<int, array{id: int, name: string}>
     */
    #[Groups(['course_group_members:read'])]
    public array $options = [];

    /**
     * @var int[]
     */
    #[Groups(['course_group_members:read', 'course_group_members:write'])]
    public array $selectedIds = [];

    #[Groups(['course_group_members:read'])]
    public int $maxStudent = 0;

    #[Groups(['course_group_members:read'])]
    public bool $linkedToClass = false;

    #[Groups(['course_group_members:read'])]
    public string $linkedClassTitle = '';

    #[Groups(['course_group_members:read'])]
    public bool $success = false;

    #[Groups(['course_group_members:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
