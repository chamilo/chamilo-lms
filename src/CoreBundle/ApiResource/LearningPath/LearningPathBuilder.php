<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\LearningPath\LearningPathBuilderProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_paths/{lpId}/builder',
            requirements: ['lpId' => '\d+'],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_learning_path_builder',
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
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
                'includeLaunchUrls' => new QueryParameter(
                    schema: ['type' => 'boolean'],
                    description: 'Include resolved launch URLs for course resources',
                ),
                'catalogOnly' => new QueryParameter(
                    schema: ['type' => 'boolean'],
                    description: 'Return the course resource catalog without builder-side setup',
                ),
            ],
            provider: LearningPathBuilderProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_builder:read']],
)]
final class LearningPathBuilder
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_builder:read'])]
    public int $lpId = 0;

    #[Groups(['learning_path_builder:read'])]
    public string $title = '';

    #[Groups(['learning_path_builder:read'])]
    public int $lpType = 1;

    #[Groups(['learning_path_builder:read'])]
    public bool $canManageStructure = false;

    #[Groups(['learning_path_builder:read'])]
    public bool $titleAsHtml = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['learning_path_builder:read'])]
    public array $items = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['learning_path_builder:read'])]
    public array $resources = [];

    #[Groups(['learning_path_builder:read'])]
    public int $documentsRootNodeId = 0;

    #[Groups(['learning_path_builder:read'])]
    public int $defaultDocumentParentNodeId = 0;

    #[Groups(['learning_path_builder:read'])]
    public string $courseLanguage = '';

    #[Groups(['learning_path_builder:read'])]
    public bool $searchEnabled = false;

    #[Groups(['learning_path_builder:read'])]
    public bool $aiQuickTestEnabled = false;

    /**
     * @var array<int, array{label: string, value: string}>
     */
    #[Groups(['learning_path_builder:read'])]
    public array $aiQuickTestProviders = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['learning_path_builder:read'])]
    public array $certificate = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['learning_path_builder:read'])]
    public array $bulkAuthorPrice = [];

    public function getLpId(): int
    {
        return $this->lpId;
    }
}
