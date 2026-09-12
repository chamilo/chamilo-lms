<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\SkillTreeNode;
use Chamilo\CoreBundle\DataTransformer\SkillTreeNodeTransformer;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Repository\SkillRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;

/**
 * @implements ProviderInterface<Skill>
 */
readonly class SkillTreeStateProvider implements ProviderInterface
{
    private SkillTreeNodeTransformer $transformer;

    public function __construct(
        private SkillRepository $skillRepo,
        private SettingsManager $settingsManager,
    ) {
        $this->transformer = new SkillTreeNodeTransformer(
            $this->settingsManager,
        );
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<int, SkillTreeNode>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $topLevelSkills = $this->skillRepo->findTopLevelSkills();

        $topLevelCount = \count($topLevelSkills);

        if ($topLevelCount > 1) {
            return array_map(
                fn (Skill $skill): SkillTreeNode => $this->transformer->transform($skill),
                $topLevelSkills,
            );
        }

        if (1 === $topLevelCount) {
            $children = $topLevelSkills[0]->getChildSkills();

            if ($children->isEmpty()) {
                return [$this->transformer->transform($topLevelSkills[0])];
            }

            return $children
                ->map(fn (Skill $skill): SkillTreeNode => $this->transformer->transform($skill))
                ->toArray()
            ;
        }

        $root = $this->skillRepo->findOneBy([], ['id' => 'ASC']);

        if (!$root) {
            return [];
        }

        return $root->getChildSkills()
            ->map(fn (Skill $skill): SkillTreeNode => $this->transformer->transform($skill))
            ->toArray()
        ;
    }
}
