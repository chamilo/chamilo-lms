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

        $leaf = new SkillTreeNode();
        $leaf->id = $object->getId();
        $leaf->title = $object->getTitle();
        $leaf->status = $object->getStatus();

        if (($shortCode = $object->getShortCode())
            && 'false' === $this->settingsManager->getSetting('skill.show_full_skill_name_on_skill_wheel')
        ) {
            $leaf->shortCode = $shortCode;
        }

        $leaf->children = [];

        foreach ($object->getChildSkills() as $childSkill) {
            $leaf->children[] = $this->transform($childSkill, $to, $context);
        }

        return $leaf;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return $data instanceof Skill && SkillTreeNode::class === $to;
    }
}
