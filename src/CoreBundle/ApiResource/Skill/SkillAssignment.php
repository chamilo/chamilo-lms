<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Skill;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\Skill\SkillAssignmentProcessor;
use Chamilo\CoreBundle\State\Skill\SkillAssignmentProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SkillAssignment',
    operations: [
        new Get(
            uriTemplate: '/skill-assignment/{id}',
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_USER')",
            name: 'get_skill_assignment',
            provider: SkillAssignmentProvider::class,
        ),
        new Post(
            uriTemplate: '/skill-assignment/{id}',
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_USER')",
            read: false,
            name: 'post_skill_assignment',
            processor: SkillAssignmentProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['skill_assignment:read']],
    denormalizationContext: ['groups' => ['skill_assignment:write']],
)]
final class SkillAssignment
{
    #[ApiProperty(identifier: true)]
    public int $id = 0;

    #[Groups(['skill_assignment:read'])]
    public int $userId = 0;

    #[Groups(['skill_assignment:write'])]
    public string $action = 'assign';

    #[Groups(['skill_assignment:write'])]
    public int $skillId = 0;

    #[Groups(['skill_assignment:write'])]
    public int $acquiredLevelId = 0;

    #[Groups(['skill_assignment:write'])]
    public string $argumentation = '';

    #[Groups(['skill_assignment:read'])]
    public array $user = [];

    #[Groups(['skill_assignment:read'])]
    public array $skillOptions = [];

    #[Groups(['skill_assignment:read'])]
    public array $levelOptions = [];

    #[Groups(['skill_assignment:read'])]
    public ?array $selectedSkill = null;

    #[Groups(['skill_assignment:read'])]
    public ?array $assignment = null;

    #[Groups(['skill_assignment:read'])]
    public bool $showLevels = false;

    #[Groups(['skill_assignment:read'])]
    public bool $success = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
