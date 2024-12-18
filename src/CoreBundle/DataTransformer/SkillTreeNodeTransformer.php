<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use Chamilo\CoreBundle\ApiResource\SkillTreeNode;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Settings\SettingsManager;

readonly class SkillTreeNodeTransformer implements DataTransformerInterface
{
    public function __construct(
        private SettingsManager $settingsManager,
    ) {}

    public function transform($object, string $to, array $context = [])
    {
        \assert($object instanceof Skill);

        $skillNode = new SkillTreeNode();
        $skillNode->id = $object->getId();
        $skillNode->title = $object->getTitle();
        $skillNode->status = $object->getStatus();
        $skillNode->isSearched = $object->getProfiles()->count() > 0;
        $skillNode->hasGradebook = $object->getGradeBookCategories()->count() > 0;

        if (($shortCode = $object->getShortCode())
            && 'false' === $this->settingsManager->getSetting('skill.show_full_skill_name_on_skill_wheel')
        ) {
            $skillNode->shortCode = $shortCode;
        }

        $skillNode->children = $object->getChildSkills()
            ->map(fn(Skill $childSkill) => $this->transform($childSkill, $to, $context))
            ->toArray()
        ;

        return $skillNode;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return $data instanceof Skill && SkillTreeNode::class === $to;
    }
}
