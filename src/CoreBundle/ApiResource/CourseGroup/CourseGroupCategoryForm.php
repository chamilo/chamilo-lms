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
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupCategoryFormProcessor;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupCategoryFormProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseGroupCategoryForm',
    operations: [
        new Get(
            uriTemplate: '/course-groups/categories/form',
            openapi: new Operation(summary: 'New group category form data'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_group_category_create_form',
            provider: CourseGroupCategoryFormProvider::class,
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
            uriTemplate: '/course-groups/categories/form/{categoryId}',
            uriVariables: [
                'categoryId' => new Link(schema: ['type' => 'integer'], property: 'categoryId'),
            ],
            openapi: new Operation(summary: 'Existing group category form data'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_group_category_update_form',
            provider: CourseGroupCategoryFormProvider::class,
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
            uriTemplate: '/course-groups/categories/form',
            openapi: new Operation(summary: 'Create a group category'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_category_create_form',
            processor: CourseGroupCategoryFormProcessor::class,
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
            uriTemplate: '/course-groups/categories/form/{categoryId}',
            uriVariables: [
                'categoryId' => new Link(schema: ['type' => 'integer'], property: 'categoryId'),
            ],
            openapi: new Operation(summary: 'Update a group category'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_group_category_update_form',
            processor: CourseGroupCategoryFormProcessor::class,
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
    normalizationContext: ['groups' => ['course_group_category_form:read']],
    denormalizationContext: ['groups' => ['course_group_category_form:write']],
)]
final class CourseGroupCategoryForm
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_group_category_form:read'])]
    public string $id = 'course_group_category_form';

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $categoryId = 0;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public string $title = '';

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public string $description = '';

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $groupsPerUser = 1;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $maxStudent = 0;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public bool $selfRegistrationAllowed = false;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public bool $selfUnregistrationAllowed = false;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $docState = 2;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $workState = 2;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $calendarState = 2;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $announcementsState = 2;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $forumState = 2;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $wikiState = 2;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $chatState = 2;

    #[Groups(['course_group_category_form:read', 'course_group_category_form:write'])]
    public int $documentAccess = 0;

    #[Groups(['course_group_category_form:read'])]
    public bool $allowDocumentAccess = false;

    #[Groups(['course_group_category_form:read'])]
    public bool $allowCategories = true;

    #[Groups(['course_group_category_form:read'])]
    public bool $success = false;

    #[Groups(['course_group_category_form:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
