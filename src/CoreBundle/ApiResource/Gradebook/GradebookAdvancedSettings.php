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
use Chamilo\CoreBundle\State\Gradebook\GradebookAdvancedSettingsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookAdvancedSettings',
    operations: [
        new Get(
            uriTemplate: '/gradebook/advanced-settings',
            openapi: new Operation(
                summary: 'Read Gradebook advanced category options in the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'parentCategoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'skillQuery', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_advanced_settings',
            provider: GradebookAdvancedSettingsProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_advanced_settings:read']],
)]
final class GradebookAdvancedSettings
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_advanced_settings:read'])]
    public string $id = 'gradebook_advanced_settings';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_advanced_settings:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_advanced_settings:read'])]
    public ?array $category = null;

    #[Groups(['gradebook_advanced_settings:read'])]
    public bool $canManage = false;

    #[Groups(['gradebook_advanced_settings:read'])]
    public bool $canManageSkills = false;

    #[Groups(['gradebook_advanced_settings:read'])]
    public bool $gradeModelEnabled = false;

    #[Groups(['gradebook_advanced_settings:read'])]
    public bool $canChangeGradeModel = false;

    #[Groups(['gradebook_advanced_settings:read'])]
    public bool $gradeModelFrozen = false;

    #[Groups(['gradebook_advanced_settings:read'])]
    public ?int $gradeModelId = null;

    #[Groups(['gradebook_advanced_settings:read'])]
    public ?int $defaultGradeModelId = null;

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_advanced_settings:read'])]
    public array $gradeModels = [];

    /**
     * @var list<int>
     */
    #[Groups(['gradebook_advanced_settings:read'])]
    public array $skillIds = [];

    /**
     * @var list<array{id: int, title: string}>
     */
    #[Groups(['gradebook_advanced_settings:read'])]
    public array $skills = [];

    #[Groups(['gradebook_advanced_settings:read'])]
    public bool $skillToolEnabled = false;

    #[Groups(['gradebook_advanced_settings:read'])]
    public bool $allowSubcategorySkillsSetting = false;

    #[Groups(['gradebook_advanced_settings:read'])]
    public bool $parentAllowsSkillsBySubcategory = false;

    public function getId(): string
    {
        return $this->id;
    }
}
