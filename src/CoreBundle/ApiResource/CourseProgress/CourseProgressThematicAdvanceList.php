<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseProgress;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressThematicAdvanceListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseProgressThematicAdvanceList',
    operations: [
        new Get(
            uriTemplate: '/course-progress/thematic/{thematicId}/advances',
            requirements: ['thematicId' => '\d+'],
            openapi: new Operation(
                summary: 'Course progress thematic advance list',
                parameters: [
                    new Parameter(name: 'thematicId', in: 'path', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_progress_thematic_advances',
            provider: CourseProgressThematicAdvanceListProvider::class,
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
    normalizationContext: ['groups' => ['course_progress_thematic_advance_list:read']],
)]
final class CourseProgressThematicAdvanceList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_progress_thematic_advance_list:read'])]
    public int $thematicId = 0;

    #[Groups(['course_progress_thematic_advance_list:read'])]
    public string $thematicTitle = '';

    #[Groups(['course_progress_thematic_advance_list:read'])]
    public string $thematicContent = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_progress_thematic_advance_list:read'])]
    public array $items = [];

    #[Groups(['course_progress_thematic_advance_list:read'])]
    public int $totalItems = 0;

    #[Groups(['course_progress_thematic_advance_list:read'])]
    public bool $canEdit = false;

    public function getThematicId(): int
    {
        return $this->thematicId;
    }
}
