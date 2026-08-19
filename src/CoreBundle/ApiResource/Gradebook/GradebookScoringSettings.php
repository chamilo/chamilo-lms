<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Gradebook\GradebookScoringSettingsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookScoringSettings',
    operations: [
        new Get(
            uriTemplate: '/gradebook/scoring-settings',
            openapi: new Operation(
                summary: 'Read Gradebook score display settings for the current category',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_scoring_settings',
            provider: GradebookScoringSettingsProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_scoring_settings:read']],
)]
final class GradebookScoringSettings
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_scoring_settings:read'])]
    public string $id = 'gradebook_scoring_settings';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_scoring_settings:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_scoring_settings:read'])]
    public ?array $category = null;

    #[Groups(['gradebook_scoring_settings:read'])]
    public bool $canManage = false;

    #[Groups(['gradebook_scoring_settings:read'])]
    public bool $customEnabled = false;

    #[Groups(['gradebook_scoring_settings:read'])]
    public bool $coloringEnabled = false;

    #[Groups(['gradebook_scoring_settings:read'])]
    public bool $upperLimitIncluded = false;

    #[Groups(['gradebook_scoring_settings:read'])]
    public int $colorSplitPercent = 50;

    /**
     * @var list<array{score: float, display: string}>
     */
    #[Groups(['gradebook_scoring_settings:read'])]
    public array $ranges = [];

    #[Groups(['gradebook_scoring_settings:read'])]
    public string $csrfToken = '';

    public function getId(): string
    {
        return $this->id;
    }
}
