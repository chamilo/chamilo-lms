<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseDescription;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseDescription\CourseDescriptionDeleteProcessor;
use Chamilo\CoreBundle\State\CourseDescription\CourseDescriptionItemProcessor;
use Chamilo\CoreBundle\State\CourseDescription\CourseDescriptionItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseDescriptionItem',
    operations: [
        new Get(
            uriTemplate: '/course-description/form',
            openapi: new Operation(
                summary: 'Course description edition form data',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'descriptionType', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'get_course_description_form',
            provider: CourseDescriptionItemProvider::class,
        ),
        new Post(
            uriTemplate: '/course-description',
            openapi: new Operation(
                summary: 'Create a course description',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'post_course_description',
            processor: CourseDescriptionItemProcessor::class,
        ),
        new Put(
            uriTemplate: '/course-description/{iid}',
            requirements: ['iid' => '\\d+'],
            openapi: new Operation(
                summary: 'Update a course description',
                parameters: [
                    new Parameter(name: 'iid', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'put_course_description',
            processor: CourseDescriptionItemProcessor::class,
        ),
        new Delete(
            uriTemplate: '/course-description/{iid}',
            requirements: ['iid' => '\\d+'],
            openapi: new Operation(
                summary: 'Delete a course description',
                parameters: [
                    new Parameter(name: 'iid', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'delete_course_description',
            provider: CourseDescriptionItemProvider::class,
            processor: CourseDescriptionDeleteProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_description_item:read']],
    denormalizationContext: ['groups' => ['course_description_item:write']],
)]
final class CourseDescriptionItem
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_description_item:read', 'course_description_item:write'])]
    public ?int $iid = null;

    #[Groups(['course_description_item:read', 'course_description_item:write'])]
    public int $descriptionType = 1;

    #[Groups(['course_description_item:read', 'course_description_item:write'])]
    public string $title = '';

    #[Groups(['course_description_item:read', 'course_description_item:write'])]
    public string $content = '';

    #[Groups(['course_description_item:read', 'course_description_item:write'])]
    public int $progress = 0;

    #[Groups(['course_description_item:read', 'course_description_item:write'])]
    public string $language = '';

    #[Groups(['course_description_item:read', 'course_description_item:write'])]
    public bool $enableSearch = true;

    #[Groups(['course_description_item:read', 'course_description_item:write'])]
    public string $csrfToken = '';

    #[Groups(['course_description_item:read'])]
    public bool $canEdit = false;

    #[Groups(['course_description_item:read'])]
    public bool $isNew = true;

    #[Groups(['course_description_item:read'])]
    public string $defaultTitle = '';

    #[Groups(['course_description_item:read'])]
    public string $help = '';

    #[Groups(['course_description_item:read'])]
    public string $information = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_description_item:read'])]
    public array $types = [];

    /**
     * @var array<int, array<string, string>>
     */
    #[Groups(['course_description_item:read'])]
    public array $languages = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_description_item:read'])]
    public array $settings = [];

    public function getIid(): ?int
    {
        return $this->iid;
    }
}
