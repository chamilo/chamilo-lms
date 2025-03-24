<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Entity\Skill;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Skill',
    operations: [],
)]
class SkillTreeNode
{
    #[Groups(['skill:tree:read'])]
    #[ApiProperty(identifier: true)]
    public int $id;

    #[Groups(['skill:tree:read'])]
    public string $title;

    #[Groups(['skill:tree:read'])]
    public ?string $shortCode = null;

    #[Groups(['skill:tree:read'])]
    public int $status = Skill::STATUS_DISABLED;

    /**
     * @var array<int, SkillTreeNode>
     */
    #[Groups(['skill:tree:read'])]
    public array $children = [];

    #[Groups(['skill:tree:read'])]
    public bool $hasGradebook = false;

    #[Groups(['skill:tree:read'])]
    public bool $isSearched = false;

    #[Groups(['skill:tree:read'])]
    public bool $isAchievedByUser = false;
}
