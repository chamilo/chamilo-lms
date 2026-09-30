<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Skill;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Skill\SkillAssignment;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<SkillAssignment>
 */
final readonly class SkillAssignmentProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private SettingsManager $settingsManager,
        private SkillAssignmentAccessChecker $accessChecker,
        private SkillAssignmentLevelResolver $levelResolver,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SkillAssignment
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if ('true' !== $this->settingsManager->getSetting('skill.allow_skills_tool', true)) {
            throw new AccessDeniedHttpException('The skills tool is disabled.');
        }

        $userId = (int) ($uriVariables['id'] ?? 0);
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            throw new NotFoundHttpException('The requested user was not found.');
        }

        $this->accessChecker->assertCanManage($user);

        $result = new SkillAssignment();
        $result->id = $userId;
        $result->userId = $userId;
        $result->user = [
            'id' => (int) $user->getId(),
            'fullName' => trim($user->getFirstname().' '.$user->getLastname()),
            'username' => $user->getUsername(),
        ];
        $result->skillOptions = $this->getSkillOptions();
        $result->showLevels = 'false' === $this->settingsManager->getSetting('skill.hide_skill_levels', true);

        $skillId = $request->query->getInt('skillId');
        if ($skillId <= 0) {
            return $result;
        }

        $skill = $this->entityManager->getRepository(Skill::class)->find($skillId);
        if (!$skill instanceof Skill || Skill::STATUS_ENABLED !== $skill->getStatus()) {
            throw new NotFoundHttpException('The requested skill was not found.');
        }

        $result->selectedSkill = [
            'id' => (int) $skill->getId(),
            'title' => $skill->getTitle(),
        ];
        $result->levelOptions = $result->showLevels ? $this->levelResolver->getOptions($skill) : [];

        $repository = $this->entityManager->getRepository(SkillRelUser::class);
        $activeManual = $repository->findOneBy(
            [
                'user' => $user,
                'skill' => $skill,
                'course' => null,
                'session' => null,
                'status' => SkillRelUser::STATUS_ACQUIRED,
            ],
            ['id' => 'DESC'],
        );
        $active = $activeManual instanceof SkillRelUser ? $activeManual : $repository->findOneBy(
            [
                'user' => $user,
                'skill' => $skill,
                'status' => SkillRelUser::STATUS_ACQUIRED,
            ],
            ['id' => 'DESC'],
        );
        $removedManual = $repository->findOneBy(
            [
                'user' => $user,
                'skill' => $skill,
                'course' => null,
                'session' => null,
                'status' => SkillRelUser::STATUS_REMOVED,
            ],
            ['id' => 'DESC'],
        );

        $assignment = $active instanceof SkillRelUser ? $active : $removedManual;
        if ($assignment instanceof SkillRelUser) {
            $result->assignment = $this->normalizeAssignment($assignment);
        }

        return $result;
    }

    private function getSkillOptions(): array
    {
        $skills = $this->entityManager->getRepository(Skill::class)->findBy(
            ['status' => Skill::STATUS_ENABLED],
            ['title' => 'ASC'],
        );

        $options = [];
        foreach ($skills as $skill) {
            if ($skill instanceof Skill && null !== $skill->getId()) {
                $options[] = [
                    'label' => $skill->getTitle(),
                    'value' => (int) $skill->getId(),
                ];
            }
        }

        return $options;
    }

    private function normalizeAssignment(SkillRelUser $assignment): array
    {
        return [
            'id' => (int) $assignment->getId(),
            'status' => $assignment->getStatus(),
            'acquired' => $assignment->isAcquired(),
            'manual' => $assignment->isManualAssignment(),
            'canRemove' => $assignment->isAcquired() && $assignment->isManualAssignment(),
            'argumentation' => $assignment->getArgumentation(),
            'acquiredLevelId' => (int) ($assignment->getAcquiredLevel()?->getId() ?? 0),
            'sourceName' => $assignment->getSourceName(),
            'acquiredAt' => $assignment->getAcquiredSkillAt()->format(DATE_ATOM),
        ];
    }
}
