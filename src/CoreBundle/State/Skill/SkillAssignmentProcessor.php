<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Skill;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Skill\SkillAssignment;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const DATE_ATOM;

/**
 * @implements ProcessorInterface<SkillAssignment, SkillAssignment>
 */
final readonly class SkillAssignmentProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private SkillAssignmentAccessChecker $accessChecker,
        private SkillAssignmentLevelResolver $levelResolver,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SkillAssignment
    {
        if (!$data instanceof SkillAssignment) {
            throw new BadRequestHttpException('A valid skill assignment payload is required.');
        }

        if ('true' !== $this->settingsManager->getSetting('skill.allow_skills_tool', true)) {
            throw new AccessDeniedHttpException('The skills tool is disabled.');
        }

        $userId = (int) ($uriVariables['id'] ?? 0);
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            throw new NotFoundHttpException('The requested user was not found.');
        }

        $actor = $this->accessChecker->assertCanManage($user);

        $skill = $this->entityManager->getRepository(Skill::class)->find($data->skillId);
        if (!$skill instanceof Skill || Skill::STATUS_ENABLED !== $skill->getStatus()) {
            throw new NotFoundHttpException('The requested skill was not found.');
        }

        return match ($data->action) {
            'assign' => $this->assign($data, $actor, $user, $skill),
            'remove' => $this->remove($actor, $user, $skill),
            default => throw new BadRequestHttpException('Unsupported skill assignment action.'),
        };
    }

    private function assign(SkillAssignment $data, User $actor, User $user, Skill $skill): SkillAssignment
    {
        $argumentation = trim($data->argumentation);
        if (mb_strlen($argumentation) < 10) {
            throw new BadRequestHttpException('Argumentation must contain at least 10 characters.');
        }

        $repository = $this->entityManager->getRepository(SkillRelUser::class);
        $active = $repository->findOneBy([
            'user' => $user,
            'skill' => $skill,
            'status' => SkillRelUser::STATUS_ACQUIRED,
        ]);

        if ($active instanceof SkillRelUser) {
            throw new BadRequestHttpException('The user has already acquired this skill.');
        }

        $assignment = $repository->findOneBy(
            [
                'user' => $user,
                'skill' => $skill,
                'course' => null,
                'session' => null,
                'status' => SkillRelUser::STATUS_REMOVED,
            ],
            ['id' => 'DESC'],
        );

        if (!$assignment instanceof SkillRelUser) {
            $assignment = (new SkillRelUser())
                ->setUser($user)
                ->setSkill($skill)
            ;
        }

        $assignment
            ->setStatus(SkillRelUser::STATUS_ACQUIRED)
            ->setLastStatusUpdateUserId((int) $actor->getId())
            ->setArgumentation($argumentation)
            ->setArgumentationAuthorId((int) $actor->getId())
        ;

        if ('false' === $this->settingsManager->getSetting('skill.hide_skill_levels', true)) {
            $level = $this->levelResolver->resolve($skill, $data->acquiredLevelId);
            if ($data->acquiredLevelId > 0 && null === $level) {
                throw new BadRequestHttpException('The selected skill level is invalid.');
            }

            $assignment->setAcquiredLevel($level);
        }

        $this->entityManager->persist($assignment);
        $this->entityManager->flush();

        return $this->response($assignment);
    }

    private function remove(User $actor, User $user, Skill $skill): SkillAssignment
    {
        $assignment = $this->entityManager->getRepository(SkillRelUser::class)->findOneBy(
            [
                'user' => $user,
                'skill' => $skill,
                'course' => null,
                'session' => null,
                'status' => SkillRelUser::STATUS_ACQUIRED,
            ],
            ['id' => 'DESC'],
        );

        if (!$assignment instanceof SkillRelUser || !$assignment->isManualAssignment()) {
            throw new AccessDeniedHttpException('Only a manually assigned skill can be removed.');
        }

        $assignment
            ->setStatus(SkillRelUser::STATUS_REMOVED)
            ->setLastStatusUpdateUserId((int) $actor->getId())
        ;

        $this->entityManager->persist($assignment);
        $this->entityManager->flush();

        return $this->response($assignment);
    }

    private function response(SkillRelUser $assignment): SkillAssignment
    {
        $response = new SkillAssignment();
        $response->id = (int) $assignment->getUser()->getId();
        $response->userId = (int) $assignment->getUser()->getId();
        $response->skillId = (int) ($assignment->getSkill()?->getId() ?? 0);
        $response->success = true;
        $response->assignment = [
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

        return $response;
    }
}
