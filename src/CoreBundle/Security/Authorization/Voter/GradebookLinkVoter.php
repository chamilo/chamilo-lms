<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\User;
use Error;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants write access to a gradebook link through the gradebook category it belongs to, not
 * through the course carried by the request: the contextual teacher role only describes the
 * course named by cid.
 *
 * @extends Voter<'CREATE'|'EDIT'|'DELETE', GradebookLink>
 */
final class GradebookLinkVoter extends Voter
{
    public const string CREATE = 'CREATE';
    public const string EDIT = 'EDIT';
    public const string DELETE = 'DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::CREATE, self::EDIT, self::DELETE], true)
            && $subject instanceof GradebookLink;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$token->getUser() instanceof User) {
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

            $categoryAttribute = self::DELETE === $attribute
                ? GradebookCategoryVoter::DELETE
                : GradebookCategoryVoter::EDIT;

            if (!$this->accessDecisionManager->decide($token, [$categoryAttribute], $category)) {
                return false;
            }

            return self::CREATE !== $attribute
                || $this->accessDecisionManager->decide($token, [ResourceNodeVoter::VIEW], $subject->getResourceNode());
        } catch (Error) {
            // A link being created may not have its category, course or resource yet.
            return false;
        }
    }
}
