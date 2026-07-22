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
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressThematicDeleteProcessor;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressThematicProcessor;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressThematicProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseProgressThematic',
    operations: [
        new Get(
            uriTemplate: '/course-progress/thematic/form',
            openapi: new Operation(
                summary: 'Course progress thematic form data',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'id', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_progress_thematic_form',
            provider: CourseProgressThematicProvider::class,
        ),
        new Post(
            uriTemplate: '/course-progress/thematic',
            openapi: new Operation(
                summary: 'Create a course progress thematic',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_course_progress_thematic',
            processor: CourseProgressThematicProcessor::class,
        ),
        new Patch(
            uriTemplate: '/course-progress/thematic/{iid}',
            requirements: ['iid' => '\d+'],
            openapi: new Operation(
                summary: 'Update a course progress thematic',
                parameters: [
                    new Parameter(name: 'iid', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'put_course_progress_thematic',
            processor: CourseProgressThematicProcessor::class,
        ),
        new Delete(
            uriTemplate: '/course-progress/thematic/{iid}',
            requirements: ['iid' => '\d+'],
            openapi: new Operation(
                summary: 'Delete a course progress thematic from the current context',
                parameters: [
                    new Parameter(name: 'iid', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'delete_course_progress_thematic',
            provider: CourseProgressThematicProvider::class,
            processor: CourseProgressThematicDeleteProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_progress_thematic:read']],
    denormalizationContext: ['groups' => ['course_progress_thematic:write']],
)]
final class CourseProgressThematic
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_progress_thematic:read', 'course_progress_thematic:write'])]
    public ?int $iid = null;

    #[Groups(['course_progress_thematic:read', 'course_progress_thematic:write'])]
    public string $title = '';

    #[Groups(['course_progress_thematic:read', 'course_progress_thematic:write'])]
    public string $content = '';

    #[Groups(['course_progress_thematic:read', 'course_progress_thematic:write'])]
    public string $language = '';

    #[Groups(['course_progress_thematic:read', 'course_progress_thematic:write'])]
    public string $csrfToken = '';

    #[Groups(['course_progress_thematic:read'])]
    public bool $canEdit = false;

    #[Groups(['course_progress_thematic:read'])]
    public bool $isNew = true;

    /**
     * @var array<int, array<string, string>>
     */
    #[Groups(['course_progress_thematic:read'])]
    public array $languages = [];

    /**
     * @var array<string, bool>
     */
    #[Groups(['course_progress_thematic:read'])]
    public array $settings = [];

    public function getIid(): ?int
    {
        return $this->iid;
    }
}
