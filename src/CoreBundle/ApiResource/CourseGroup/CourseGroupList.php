<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseGroup;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseGroupList',
    operations: [
        new Get(
            uriTemplate: '/course-groups/list',
            openapi: new Operation(
                summary: 'Course groups and categories in the current course context',
                parameters: [
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_group_list',
            provider: CourseGroupListProvider::class,
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
    normalizationContext: ['groups' => ['course_group_list:read']],
)]
final class CourseGroupList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_group_list:read'])]
    public string $id = 'course_group_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_group_list:read'])]
    public array $categories = [];

    #[Groups(['course_group_list:read'])]
    public int $totalGroups = 0;

    #[Groups(['course_group_list:read'])]
    public int $courseId = 0;

    #[Groups(['course_group_list:read'])]
    public ?int $sessionId = null;

    #[Groups(['course_group_list:read'])]
    public bool $allowCategories = false;

    #[Groups(['course_group_list:read'])]
    public bool $canManageCourse = false;

    #[Groups(['course_group_list:read'])]
    public bool $canCreateCategory = false;

    #[Groups(['course_group_list:read'])]
    public int $defaultCategoryId = 0;

    #[Groups(['course_group_list:read'])]
    public bool $showSubscriptionTabs = false;

    #[Groups(['course_group_list:read'])]
    public bool $showClasses = false;

    #[Groups(['course_group_list:read'])]
    public string $csvExportUrl = '';

    #[Groups(['course_group_list:read'])]
    public string $xlsxExportUrl = '';

    #[Groups(['course_group_list:read'])]
    public string $pdfExportUrl = '';

    public function getId(): string
    {
        return $this->id;
    }
}
