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
use Doctrine\Common\Collections\Collection;

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
            $this->settingsManager
        );
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|Collection
    {
        /** @var Skill $root */
        $root = $this->skillRepo->findOneBy([], ['id' => 'ASC']);

        if (!$root) {
            return [];
        }

        return $root->getChildSkills()
            ->map(fn (Skill $childSkill) => $this->transformer->transform($childSkill))
        ;
    }
}
