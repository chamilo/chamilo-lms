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
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupFormProcessor;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupFormProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseGroupForm',
    operations: [
        new Get(
            uriTemplate: '/course-groups/form',
            openapi: new Operation(summary: 'New course group form data'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_group_create_form',
            provider: CourseGroupFormProvider::class,
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
            uriTemplate: '/course-groups/form/{groupId}',
            uriVariables: [
                'groupId' => new Link(schema: ['type' => 'integer'], property: 'groupId'),
            ],
            openapi: new Operation(summary: 'Existing course group form data'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_group_update_form',
            provider: CourseGroupFormProvider::class,
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
            uriTemplate: '/course-groups/form',
            openapi: new Operation(summary: 'Create a course group'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_create_form',
            processor: CourseGroupFormProcessor::class,
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
            uriTemplate: '/course-groups/form/{groupId}',
            uriVariables: [
                'groupId' => new Link(schema: ['type' => 'integer'], property: 'groupId'),
            ],
            openapi: new Operation(summary: 'Update a course group'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_update_form',
            processor: CourseGroupFormProcessor::class,
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
    normalizationContext: ['groups' => ['course_group_form:read']],
    denormalizationContext: ['groups' => ['course_group_form:write']],
)]
final class CourseGroupForm
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_group_form:read'])]
    public string $id = 'course_group_form';

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $groupId = 0;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public string $title = '';

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public string $description = '';

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $categoryId = 0;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $maxStudent = 0;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public bool $selfRegistrationAllowed = false;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public bool $selfUnregistrationAllowed = false;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $docState = 2;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $workState = 2;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $calendarState = 2;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $announcementsState = 2;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $forumState = 2;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $wikiState = 2;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $chatState = 2;

    #[Groups(['course_group_form:read', 'course_group_form:write'])]
    public int $documentAccess = 0;

    /**
     * @var array<int, array{id: int, label: string}>
     */
    #[Groups(['course_group_form:read'])]
    public array $categories = [];

    #[Groups(['course_group_form:read'])]
    public bool $allowCategories = false;

    #[Groups(['course_group_form:read'])]
    public bool $allowDocumentAccess = false;

    /**
     * @var array<int, array{id: int, label: string, members: int}>
     */
    #[Groups(['course_group_form:read'])]
    public array $baseGroups = [];

    /**
     * @var array<int, array{id: int, label: string, users: int}>
     */
    #[Groups(['course_group_form:read'])]
    public array $classes = [];

    #[Groups(['course_group_form:read'])]
    public int $nextGroupNumber = 1;

    #[Groups(['course_group_form:read'])]
    public bool $linkedToClass = false;

    #[Groups(['course_group_form:read'])]
    public string $linkedClassTitle = '';

    #[Groups(['course_group_form:read'])]
    public bool $canRemoveClassLink = false;

    #[Groups(['course_group_form:read'])]
    public bool $success = false;

    #[Groups(['course_group_form:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
