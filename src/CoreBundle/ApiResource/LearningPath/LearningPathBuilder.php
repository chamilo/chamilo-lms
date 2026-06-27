<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\LearningPath\LearningPathBuilderProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_paths/{lpId}/builder',
            requirements: ['lpId' => '\\d+'],
            name: 'get_learning_path_builder',
            provider: LearningPathBuilderProvider::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
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

    #[Groups(['learning_path_builder:read'])]
    public string $csrfToken = '';

    /** @var array<int, array<string, mixed>> */
    #[Groups(['learning_path_builder:read'])]
    public array $items = [];

    /** @var array<string, mixed> */
    #[Groups(['learning_path_builder:read'])]
    public array $resources = [];

    #[Groups(['learning_path_builder:read'])]
    public int $documentsRootNodeId = 0;

    #[Groups(['learning_path_builder:read'])]
    public bool $searchEnabled = false;

    /** @var array<string, mixed> */
    #[Groups(['learning_path_builder:read'])]
    public array $certificate = [];

    /** @var array<string, mixed> */
    #[Groups(['learning_path_builder:read'])]
    public array $bulkAuthorPrice = [];

    public function getLpId(): int
    {
        return $this->lpId;
    }
}
