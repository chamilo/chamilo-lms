<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Skill;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Skill\SkillAssignment;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelSkill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<SkillAssignment>
 */
final readonly class SkillAssignmentProvider implements ProviderInterface
{
    // The root of the skills tree, which the legacy page never offered.
    private const int ROOT_SKILL_ID = 1;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private SettingsManager $settingsManager,
        private SkillAssignmentAccessChecker $accessChecker,
        private SkillAssignmentLevelResolver $levelResolver,
        private TranslatorInterface $translator,
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
        $skillId = $request->query->getInt('skillId');
        $result->subskillAutoload = 'true' === $this->settingsManager->getSetting('skill.manual_assignment_subskill_autoload', true);
        if (!$result->subskillAutoload) {
            $result->skillOptions = $this->getSkillOptions($skillId);
        }
        $result->showLevels = 'false' === $this->settingsManager->getSetting('skill.hide_skill_levels', true);

        if ($skillId <= 0) {
            if ($result->subskillAutoload) {
                $result->skillLevels = $this->getSkillLevels(null);
            }

            return $result;
        }

        $skill = $this->entityManager->getRepository(Skill::class)->find($skillId);
        if (!$skill instanceof Skill || Skill::STATUS_ENABLED !== $skill->getStatus()) {
            throw new NotFoundHttpException('The requested skill was not found.');
        }

        $result->selectedSkill = [
            'id' => (int) $skill->getId(),
            'title' => $skill->getTitle(),
            'acquirable' => $skill->isAcquirable(),
        ];
        if ($result->subskillAutoload) {
            $result->skillLevels = $this->getSkillLevels($skill);
        }
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

    private function getSkillOptions(int $selectedSkillId = 0): array
    {
        $repository = $this->entityManager->getRepository(Skill::class);
        $skills = $repository->findBy(
            [
                'status' => Skill::STATUS_ENABLED,
                'acquirable' => true,
            ],
            ['title' => 'ASC'],
        );

        $byId = [];
        foreach ($skills as $skill) {
            if ($skill instanceof Skill && null !== $skill->getId()) {
                $byId[(int) $skill->getId()] = [
                    'label' => $skill->getTitle(),
                    'value' => (int) $skill->getId(),
                ];
            }
        }

        if ($selectedSkillId > 0 && !isset($byId[$selectedSkillId])) {
            $selected = $repository->find($selectedSkillId);
            if ($selected instanceof Skill && Skill::STATUS_ENABLED === $selected->getStatus()) {
                $byId[$selectedSkillId] = [
                    'label' => $selected->getTitle(),
                    'value' => (int) $selected->getId(),
                ];
            }
        }

        uasort(
            $byId,
            static fn (array $left, array $right): int => strcasecmp($left['label'], $right['label'])
        );

        return array_values($byId);
    }

    /**
     * Port of the cascading selects of main/skills/assign.php: the first select lists the skills of
     * the first level, and each selected skill adds a select with its own children, so the assigned
     * skill can be picked at any depth. Structural skills that cannot be acquired are listed too,
     * since they lead to their children.
     *
     * @return array<int, array{label: string, name: string, value: ?int, options: array<int, array{label: string, value: int}>}>
     */
    private function getSkillLevels(?Skill $selected): array
    {
        $path = [];
        $current = $selected;
        while ($current instanceof Skill
            && self::ROOT_SKILL_ID !== $current->getId()
            && !isset($path[(int) $current->getId()])
        ) {
            $path[(int) $current->getId()] = $current;
            $relation = $this->entityManager->getRepository(SkillRelSkill::class)->findOneBy(['skill' => $current]);
            $current = $relation instanceof SkillRelSkill ? $relation->getParent() : null;
        }
        $path = array_reverse(array_values($path));

        $levelNames = json_decode((string) $this->settingsManager->getSetting('skill.skill_levels_names', true), true);
        $levelNames = \is_array($levelNames) && \is_array($levelNames['levels'] ?? null) ? $levelNames['levels'] : [];

        $levels = [];
        $parentId = self::ROOT_SKILL_ID;
        foreach ([...$path, null] as $index => $skill) {
            $options = $this->getChildOptions($parentId);
            if (null === $skill && [] === $options) {
                break;
            }

            if ($skill instanceof Skill && !\in_array((int) $skill->getId(), array_column($options, 'value'), true)) {
                array_unshift($options, ['label' => $skill->getTitle(), 'value' => (int) $skill->getId()]);
            }

            $customLevelName = $levelNames[$index + 1] ?? null;
            $levels[] = [
                'label' => null !== $customLevelName
                    ? (string) $customLevelName
                    : $this->translator->trans(0 === $index ? 'Skill' : 'Sub-skill'),
                'name' => 0 === $index ? 'skillId' : 'subSkillId'.$index,
                'value' => $skill?->getId(),
                'options' => $options,
            ];

            if (null !== $skill) {
                $parentId = (int) $skill->getId();
            }
        }

        return $levels;
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function getChildOptions(int $parentId): array
    {
        $children = $this->entityManager->createQueryBuilder()
            ->select('s.id AS value', 's.title AS label')
            ->from(SkillRelSkill::class, 'r')
            ->innerJoin('r.skill', 's')
            ->where('IDENTITY(r.parent) = :parent')
            ->andWhere('s.status = :status')
            ->setParameter('parent', $parentId)
            ->setParameter('status', Skill::STATUS_ENABLED)
            ->orderBy('s.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map(
            static fn (array $child): array => ['label' => (string) $child['label'], 'value' => (int) $child['value']],
            $children,
        );
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
