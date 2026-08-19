<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseSettings;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsConfigurationProcessor;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsConfigurationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseSettingsConfiguration',
    operations: [
        new Get(
            uriTemplate: '/course-settings',
            openapi: new Operation(
                summary: 'Course settings configuration for the current course context',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_settings_configuration',
            provider: CourseSettingsConfigurationProvider::class,
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
            uriTemplate: '/course-settings',
            read: false,
            openapi: new Operation(
                summary: 'Save course settings for the current course context',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_course_settings_configuration',
            processor: CourseSettingsConfigurationProcessor::class,
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
    normalizationContext: ['groups' => ['course_settings:read']],
    denormalizationContext: ['groups' => ['course_settings:write']],
)]
final class CourseSettingsConfiguration
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_settings:read'])]
    public string $id = 'course_settings_configuration';

    #[Groups(['course_settings:read'])]
    public int $courseId = 0;

    #[Groups(['course_settings:read'])]
    public ?int $sessionId = null;

    #[Groups(['course_settings:read'])]
    public int $resourceNodeId = 0;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_settings:read', 'course_settings:write'])]
    public array $values = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_settings:read'])]
    public array $sections = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_settings:read'])]
    public array $permissions = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_settings:read'])]
    public array $media = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_settings:read'])]
    public array $integrations = [];

    #[Groups(['course_settings:read'])]
    public bool $success = false;

    #[Groups(['course_settings:read'])]
    public string $message = '';

    /**
     * @var array<string, string>
     */
    #[Groups(['course_settings:read'])]
    public array $errors = [];

    public function getId(): string
    {
        return $this->id;
    }
}
