<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelGradebook;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;

final readonly class GradebookSkillAwarder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
    ) {}

    public function award(
        GradebookCategory $category,
        User $user,
        Course $course,
        ?Session $session,
    ): int {
        if (!$this->isSkillToolEnabled()) {
            return 0;
        }

        $affected = $this->awardCategory($category, $user, $course, $session);
        if ($affected > 0) {
            $this->entityManager->flush();
        }

        return $affected;
    }

    private function awardCategory(
        GradebookCategory $category,
        User $user,
        Course $course,
        ?Session $session,
    ): int {
        $affected = 0;

        foreach ($category->getSkills() as $relation) {
            if (!$relation instanceof SkillRelGradebook) {
                continue;
            }

            $skill = $relation->getSkill();
            if (!$skill instanceof Skill || null === $skill->getId()) {
                continue;
            }

            $criteria = [
                'user' => $user,
                'skill' => $skill,
                'course' => $course,
                'session' => $session,
            ];
            $existing = $this->entityManager->getRepository(SkillRelUser::class)->findOneBy($criteria);
            if ($existing instanceof SkillRelUser) {
                continue;
            }

            $skillRelUser = (new SkillRelUser())
                ->setUser($user)
                ->setSkill($skill)
                ->setCourse($course)
                ->setAcquiredSkillAt(new DateTime('now', new DateTimeZone('UTC')))
                ->setValidationStatus(1)
                ->setArgumentation('')
                ->setArgumentationAuthorId((int) $user->getId())
            ;
            if ($session instanceof Session) {
                $skillRelUser->setSession($session);
            }

            $this->entityManager->persist($skillRelUser);
            $affected++;
        }

        foreach ($category->getSubCategories() as $subCategory) {
            if ($subCategory instanceof GradebookCategory) {
                $affected += $this->awardCategory($subCategory, $user, $course, $session);
            }
        }

        return $affected;
    }

    private function isSkillToolEnabled(): bool
    {
        $value = $this->settingsManager->getSetting('skill.allow_skills_tool', true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
