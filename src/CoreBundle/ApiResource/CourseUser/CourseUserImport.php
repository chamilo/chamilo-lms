<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseUser;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\State\CourseUser\CourseUserImportProcessor;
use Chamilo\CoreBundle\State\CourseUser\CourseUserImportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseUserImport',
    operations: [
        new Get(
            uriTemplate: '/course-users/import',
            openapi: new Operation(
                summary: 'Course user CSV import form data',
                parameters: [
                    new Parameter(name: 'type', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_user_import',
            provider: CourseUserImportProvider::class,
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
            uriTemplate: '/course-users/import',
            openapi: new Operation(
                summary: 'Import course users from CSV',
                parameters: [
                    new Parameter(name: 'type', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => ['type' => 'string', 'format' => 'binary'],
                                    'replace' => ['type' => 'boolean'],
                                ],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            deserialize: false,
            name: 'post_course_user_import',
            processor: CourseUserImportProcessor::class,
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
    normalizationContext: ['groups' => ['course_user_import:read']],
)]
final class CourseUserImport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_user_import:read'])]
    public string $id = 'course_user_import';

    #[Groups(['course_user_import:read'])]
    public bool $canImport = false;

    #[Groups(['course_user_import:read'])]
    public string $sampleByUsername = "username\njdoe";

    #[Groups(['course_user_import:read'])]
    public string $sampleById = "id\n23";

    #[Groups(['course_user_import:read'])]
    public bool $success = false;

    #[Groups(['course_user_import:read'])]
    public string $message = '';

    #[Groups(['course_user_import:read'])]
    public int $importedCount = 0;

    /**
     * @var int[]
     */
    #[Groups(['course_user_import:read'])]
    public array $importedUserIds = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_user_import:read'])]
    public array $invalidRows = [];

    /**
     * @var array<int, array{id: int, message: string}>
     */
    #[Groups(['course_user_import:read'])]
    public array $failed = [];

    public function getId(): string
    {
        return $this->id;
    }
}
