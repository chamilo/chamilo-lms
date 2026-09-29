<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\TrackEExerciseRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Authority for "may this user read this exercise attempt", and for the stricter "may they act
 * on someone else's".
 *
 * Port of the access rules of main/exercise/result.php and main/exercise/exercise_show.php
 * (1.11.x). TrackEAttemptVoter and TrackEAttemptQualifyVoter defer here for their own rows, so
 * the attempt, its answers and its grading are reachable under one rule. The collection
 * extensions express the same predicate as a WHERE through TrackEExerciseRepository.
 *
 * Two deliberate deviations from the legacy pages, both stricter:
 *  - the DRH term is relation-checked. exercise_show.php granted every api_is_drh() with no
 *    check at all, while the student boss term next to it did call userIsBossOfStudent().
 *  - the session admin term is scoped to the attempt's own session. api_is_session_admin() was
 *    global there, which api_protect_course_script() made safe; the API has no such gate.
 *
 * @extends Voter<'VIEW'|'MANAGE', TrackEExercise>
 */
class TrackEExerciseVoter extends Voter
{
    public const string VIEW = 'VIEW';

    /**
     * The $is_allowedToEdit of exercise_show.php: reaching a foreign attempt, and overriding the
     * exercise's own results_disabled setting. Deliberately excludes the learner, who would
     * otherwise see results the exercise hides from them.
     */
    public const string MANAGE = 'MANAGE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
        private readonly UserRepository $userRepository,
        private readonly TrackEExerciseRepository $trackEExerciseRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof TrackEExercise && \in_array($attribute, [self::VIEW, self::MANAGE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        \assert($subject instanceof TrackEExercise);

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // result.php: a learner always reaches their own attempt, but never manages it.
        if (self::VIEW === $attribute
            && (int) $subject->getUser()->getId() === (int) $user->getId()
        ) {
            return true;
        }

        return $this->canManage($subject, $user, $token);
    }

    private function canManage(TrackEExercise $trackExercise, User $user, TokenInterface $token): bool
    {
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $ownerId = (int) $trackExercise->getUser()->getId();
        $userId = (int) $user->getId();

        if ($this->accessDecisionManager->decide($token, ['ROLE_STUDENT_BOSS'])
            && $this->userRepository->isUserBossOfStudent($ownerId, $userId)
        ) {
            return true;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_HR'])
            && $this->userRepository->isUserFollowedByDrh($ownerId, $userId)
        ) {
            return true;
        }

        $course = $trackExercise->getCourse();
        $session = $trackExercise->getSession();

        if (null !== $session
            && $this->accessDecisionManager->decide($token, ['ROLE_SESSION_MANAGER'])
            && $session->hasUserAsSessionAdmin($user)
        ) {
            return true;
        }

        // IsAllowedToEditHelper::check() weighs ROLE_CURRENT_COURSE_TEACHER and
        // ROLE_CURRENT_COURSE_SESSION_TEACHER, which answer for the course resolved from the
        // request (cid), not for the $course passed in. Requiring a real relation with the
        // attempt's own course first stops ?cid=<a course I teach> from vouching for an attempt
        // that belongs to another one.
        if (!$this->trackEExerciseRepository->isTaughtBy((int) $trackExercise->getExeId(), $userId)) {
            return false;
        }

        // result.php: api_is_allowed_to_edit(null, true) || api_is_course_tutor(), with the
        // student view honoured — a teacher previewing as a student loses the foreign attempt.
        return $this->isAllowedToEditHelper->check(
            tutor: true,
            coach: true,
            course: $course,
            session: $session,
        );
    }
}
