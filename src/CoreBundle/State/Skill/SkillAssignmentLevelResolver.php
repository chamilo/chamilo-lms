<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Skill;

use Chamilo\CoreBundle\Entity\Level;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelSkill;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SkillAssignmentLevelResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array<int, array{label: string, value: int}>
     */
    public function getOptions(Skill $skill): array
    {
        $levels = $this->getLevels($skill);
        $options = [];

        foreach ($levels as $level) {
            if (null === $level->getId()) {
                continue;
            }

            $options[] = [
                'label' => $level->getTitle(),
                'value' => (int) $level->getId(),
            ];
        }

        return $options;
    }

    public function resolve(Skill $skill, int $levelId): ?Level
    {
        if ($levelId <= 0) {
            return null;
        }

        foreach ($this->getLevels($skill) as $level) {
            if ((int) $level->getId() === $levelId) {
                return $level;
            }
        }

        return null;
    }

    /**
     * @return Level[]
     */
    private function getLevels(Skill $skill): array
    {
        $profile = $skill->getLevelProfile();
        $current = $skill;
        $visited = [];

        while (null === $profile && null !== $current->getId()) {
            $currentId = (int) $current->getId();
            if (isset($visited[$currentId])) {
                break;
            }
            $visited[$currentId] = true;

            $relation = $this->entityManager->getRepository(SkillRelSkill::class)->findOneBy(['skill' => $current]);
            if (!$relation instanceof SkillRelSkill || null === $relation->getParent()) {
                break;
            }

            $current = $relation->getParent();
            $profile = $current->getLevelProfile();
        }

        if (null === $profile) {
            $firstLevel = $this->entityManager->getRepository(Level::class)->findOneBy([], ['id' => 'ASC']);
            $profile = $firstLevel instanceof Level ? $firstLevel->getProfile() : null;
        }

        if (null === $profile) {
            return [];
        }

        return $this->entityManager->getRepository(Level::class)->findBy(
            ['profile' => $profile],
            ['position' => 'ASC'],
        );
    }
}
