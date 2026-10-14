<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CBlogRating;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Authorizes a blog rating through its blog.
 *
 * Whoever can view the blog can read ratings and rate its posts, a rating is changed or deleted
 * by its author, and a blog editor may also change or delete any rating of that blog.
 *
 * @extends Voter<'VIEW'|'CREATE'|'EDIT'|'DELETE', CBlogRating>
 */
final class CBlogRatingVoter extends Voter
{
    public const string VIEW = 'VIEW';
    public const string CREATE = 'CREATE';
    public const string EDIT = 'EDIT';
    public const string DELETE = 'DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE], true)
            && $subject instanceof CBlogRating;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $blogNode = $subject->getBlog()?->getResourceNode();

        if (null === $blogNode || !$this->accessDecisionManager->decide($token, [ResourceNodeVoter::VIEW], $blogNode)) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::CREATE => $this->pointsAtItsOwnBlog($subject),
            self::EDIT, self::DELETE => $this->isAuthor($subject, $token)
                || $this->accessDecisionManager->decide($token, [$attribute], $blogNode),
            default => false,
        };
    }

    /**
     * A rating names both a blog and a post: the post must belong to that blog.
     */
    private function pointsAtItsOwnBlog(CBlogRating $rating): bool
    {
        $post = $rating->getPost();

        return null !== $post && $post->getBlog() === $rating->getBlog();
    }

    private function isAuthor(CBlogRating $rating, TokenInterface $token): bool
    {
        $user = $token->getUser();
        $author = $rating->getUser();

        return $user instanceof User && null !== $author && $author->getId() === $user->getId();
    }
}
