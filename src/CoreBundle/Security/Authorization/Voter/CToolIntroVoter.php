<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Helpers\CourseFromRequestHelper;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides who may edit a course tool introduction.
 *
 * The contextual teacher roles describe the course of the request (cid/sid), not the course
 * the introduction belongs to. The introduction's own course (and session) must therefore
 * match the request before a role counts.
 *
 * @extends Voter<'EDIT', CToolIntro>
 */
final class CToolIntroVoter extends Voter
{
    public const string EDIT = 'EDIT';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly RequestStack $requestStack,
        private readonly CourseFromRequestHelper $courseFromRequest,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::EDIT === $attribute && $subject instanceof CToolIntro;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $courseTool = $subject->getCourseTool();

        if ($courseTool->getCourse()->getId() !== $this->courseFromRequest->getCourseId($request)) {
            return false;
        }

        $isCourseTeacher = $this->accessDecisionManager->decide(
            $token,
            [ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER]
        );

        $session = $courseTool->getSession();

        // A base course introduction is edited by the course teacher: a session coach only
        // edits the introduction of their own session.
        if (null === $session) {
            return $isCourseTeacher;
        }

        if ($session->getId() !== $this->courseFromRequest->getSessionId($request)) {
            return false;
        }

        return $isCourseTeacher || $this->accessDecisionManager->decide(
            $token,
            [ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER]
        );
    }
}
