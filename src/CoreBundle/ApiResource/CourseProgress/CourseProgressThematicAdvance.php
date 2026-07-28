<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseProgress;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressThematicAdvanceDeleteProcessor;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressThematicAdvanceProcessor;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressThematicAdvanceProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseProgressThematicAdvance',
    operations: [
        new Get(
            uriTemplate: '/course-progress/thematic-advance/form',
            openapi: new Operation(
                summary: 'Course progress thematic advance form data',
                parameters: [
                    new Parameter(name: 'thematicId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'id', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_progress_thematic_advance_form',
            provider: CourseProgressThematicAdvanceProvider::class,
        ),
        new Post(
            uriTemplate: '/course-progress/thematic-advance',
            openapi: new Operation(
                summary: 'Create a course progress thematic advance',
                parameters: [
                    new Parameter(name: 'thematicId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_course_progress_thematic_advance',
            processor: CourseProgressThematicAdvanceProcessor::class,
        ),
        new Patch(
            uriTemplate: '/course-progress/thematic-advance/{iid}',
            requirements: ['iid' => '\d+'],
            openapi: new Operation(
                summary: 'Update a course progress thematic advance',
                parameters: [
                    new Parameter(name: 'thematicId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'iid', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'put_course_progress_thematic_advance',
            processor: CourseProgressThematicAdvanceProcessor::class,
        ),
        new Delete(
            uriTemplate: '/course-progress/thematic-advance/{iid}',
            requirements: ['iid' => '\d+'],
            openapi: new Operation(
                summary: 'Delete a course progress thematic advance',
                parameters: [
                    new Parameter(name: 'thematicId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'iid', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'delete_course_progress_thematic_advance',
            provider: CourseProgressThematicAdvanceProvider::class,
            processor: CourseProgressThematicAdvanceDeleteProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_progress_thematic_advance:read']],
    denormalizationContext: ['groups' => ['course_progress_thematic_advance:write']],
)]
final class CourseProgressThematicAdvance
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_progress_thematic_advance:read', 'course_progress_thematic_advance:write'])]
    public ?int $iid = null;

    #[Groups(['course_progress_thematic_advance:read', 'course_progress_thematic_advance:write'])]
    public int $thematicId = 0;

    #[Groups(['course_progress_thematic_advance:read'])]
    public string $thematicTitle = '';

    #[Groups(['course_progress_thematic_advance:read', 'course_progress_thematic_advance:write'])]
    public string $dateSource = 'custom';

    #[Groups(['course_progress_thematic_advance:read', 'course_progress_thematic_advance:write'])]
    public ?string $startDate = null;

    #[Groups(['course_progress_thematic_advance:read', 'course_progress_thematic_advance:write'])]
    public ?int $attendanceId = null;

    #[Groups(['course_progress_thematic_advance:read', 'course_progress_thematic_advance:write'])]
    public ?int $attendanceCalendarId = null;

    #[Groups(['course_progress_thematic_advance:read', 'course_progress_thematic_advance:write'])]
    public int $duration = 1;

    #[Groups(['course_progress_thematic_advance:read', 'course_progress_thematic_advance:write'])]
    public string $content = '';

    #[Groups(['course_progress_thematic_advance:read', 'course_progress_thematic_advance:write'])]
    public string $csrfToken = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_progress_thematic_advance:read'])]
    public array $attendances = [];

    #[Groups(['course_progress_thematic_advance:read'])]
    public bool $canEdit = false;

    #[Groups(['course_progress_thematic_advance:read'])]
    public bool $isNew = true;

    public function getIid(): ?int
    {
        return $this->iid;
    }
}
