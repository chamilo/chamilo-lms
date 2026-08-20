<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseGroup;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupImportProcessor;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupImportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseGroupImport',
    operations: [
        new Get(
            uriTemplate: '/course-groups/import',
            openapi: new Operation(summary: 'Course group CSV import form data'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_group_import',
            provider: CourseGroupImportProvider::class,
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
            uriTemplate: '/course-groups/import',
            openapi: new Operation(
                summary: 'Import course group categories and groups from CSV',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => ['type' => 'string', 'format' => 'binary'],
                                    'deleteMissing' => ['type' => 'boolean'],
                                ],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            deserialize: false,
            name: 'post_course_group_import',
            processor: CourseGroupImportProcessor::class,
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
    normalizationContext: ['groups' => ['course_group_import:read']],
)]
final class CourseGroupImport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_group_import:read'])]
    public string $id = 'course_group_import';

    #[Groups(['course_group_import:read'])]
    public bool $canImport = false;

    #[Groups(['course_group_import:read'])]
    public bool $success = false;

    #[Groups(['course_group_import:read'])]
    public string $message = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_group_import:read'])]
    public array $result = [];

    public function getId(): string
    {
        return $this->id;
    }
}
