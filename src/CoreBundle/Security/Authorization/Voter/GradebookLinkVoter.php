<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Error;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants read and write access to a gradebook link through the gradebook category it belongs to, not
 * through the course carried by the request: the contextual teacher role only describes the
 * course named by cid.
 *
 * @extends Voter<'VIEW'|'CREATE'|'EDIT'|'DELETE', GradebookLink>
 */
final class GradebookLinkVoter extends Voter
{
    public const string VIEW = 'VIEW';
    public const string CREATE = 'CREATE';
    public const string EDIT = 'EDIT';
    public const string DELETE = 'DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly GradebookLinkResourceResolver $resourceResolver,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE], true)
            && $subject instanceof GradebookLink;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        try {
            $category = $subject->getCategory();

            // The link and its category must sit in the same course.
            if ($subject->getCourse()?->getId() !== $category->getCourse()->getId()) {
                return false;
            }

            if (self::VIEW === $attribute) {
                return $this->isMemberOfLinkContext($user, $subject);
            }

            $categoryAttribute = self::DELETE === $attribute
                ? GradebookCategoryVoter::DELETE
                : GradebookCategoryVoter::EDIT;

            if (!$this->accessDecisionManager->decide($token, [$categoryAttribute], $category)) {
                return false;
            }

            // The link's own course decides: a gradebook may later gather items of several courses.
            if (!$this->canEditInLinkCourse($user, $subject)) {
                return false;
            }

            if (self::CREATE !== $attribute) {
                return true;
            }

            // The linked resource must belong to the link's own course (and to the category's session
            // only when the link sits in the category's course, as a link has no session of its own).
            $this->resourceResolver->requireResourceByNodeId(
                $subject->getType(),
                (int) $subject->getResourceNode()->getId(),
                $subject->getCourse(),
                $this->getLinkSession($subject),
            );

            return true;
        } catch (AccessDeniedHttpException|BadRequestHttpException|Error) {
            // A link being created may not have its category, course or resource yet, and its
            // resource may not be available in the link's course.
            return false;
        }
    }

    /**
     * Write access on the course the link belongs to: its teachers or, when the link sits in the
     * category's own course, the coaches of the category's session.
     */
    private function canEditInLinkCourse(User $user, GradebookLink $link): bool
    {
        $course = $link->getCourse();

        if ($course->hasUserAsTeacher($user)) {
            return true;
        }

        $session = $this->getLinkSession($link);

        return null !== $session
            && ($session->hasUserAsGeneralCoach($user) || $session->hasCourseCoachInCourse($user, $course));
    }

    private function getLinkSession(GradebookLink $link): ?Session
    {
        $category = $link->getCategory();

        return $link->getCourse()->getId() === $category->getCourse()->getId() ? $category->getSession() : null;
    }

    /**
     * A link is readable by the teachers and the members of its gradebook category's course, or of
     * the session when the category belongs to one. Reading is deliberately tied to the gradebook,
     * not to the link's own course: once a teacher has put an item in a gradebook, every learner of
     * that gradebook may read its score, so two learners never see different gradebooks. Keep in
     * sync with GradebookLinkExtension.
     */
    private function isMemberOfLinkContext(User $user, GradebookLink $link): bool
    {
        $course = $link->getCategory()->getCourse();

        if ($course->hasUserAsTeacher($user)) {
            return true;
        }

        $session = $link->getCategory()->getSession();
        if (null === $session) {
            return $course->hasSubscriptionByUser($user);
        }

        return $session->hasUserInCourse($user, $course) || $session->hasUserAsGeneralCoach($user);
    }
}
