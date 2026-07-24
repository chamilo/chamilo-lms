<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseProgress;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressThematicPlanProcessor;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressThematicPlanProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseProgressThematicPlan',
    operations: [
        new Get(
            uriTemplate: '/course-progress/thematic/{thematicId}/plans',
            requirements: ['thematicId' => '\d+'],
            openapi: new Operation(
                summary: 'Course progress thematic plan data',
                parameters: [
                    new Parameter(name: 'thematicId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_progress_thematic_plans',
            provider: CourseProgressThematicPlanProvider::class,
        ),
        new Patch(
            uriTemplate: '/course-progress/thematic/{thematicId}/plans',
            requirements: ['thematicId' => '\d+'],
            openapi: new Operation(
                summary: 'Update a course progress thematic plan',
                parameters: [
                    new Parameter(name: 'thematicId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'put_course_progress_thematic_plans',
            processor: CourseProgressThematicPlanProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_progress_thematic_plan:read']],
    denormalizationContext: ['groups' => ['course_progress_thematic_plan:write']],
)]
final class CourseProgressThematicPlan
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_progress_thematic_plan:read', 'course_progress_thematic_plan:write'])]
    public int $thematicId = 0;

    #[Groups(['course_progress_thematic_plan:read'])]
    public string $thematicTitle = '';

    #[Groups(['course_progress_thematic_plan:read'])]
    public string $thematicContent = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_progress_thematic_plan:read', 'course_progress_thematic_plan:write'])]
    public array $items = [];

    #[Groups(['course_progress_thematic_plan:read', 'course_progress_thematic_plan:write'])]
    public string $csrfToken = '';

    #[Groups(['course_progress_thematic_plan:write'])]
    public bool $addNewItem = false;

    #[Groups(['course_progress_thematic_plan:read'])]
    public bool $canEdit = false;

    public function getThematicId(): int
    {
        return $this->thematicId;
    }
}
